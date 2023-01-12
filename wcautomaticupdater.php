<?php
/**
 * Plugin Name: WooCommerce Automatic Excel Product Updater
 * Description: Automatically updates products at a given interval by importing an excel file from an external site and allows column mapping within the admin panel.
 * Version: 1.0
 * Author: Steve Coakley 
 * License: GPL2
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Step 1: Display the form to input the Excel file URL and import the file
add_action( 'admin_menu', 'wc_excel_import_menu' );
function wc_excel_import_menu() {
    add_submenu_page( 'woocommerce', 'Excel Import', 'Excel Import', 'manage_options', 'excel-import', 'wc_excel_import_page' );
}

function wc_excel_import_page() {
    // Check if the form has been submitted
    if ( isset( $_POST['wc_excel_import_url'] ) && isset( $_POST['wc_excel_import_import'] ) ) {
        // Get the user-defined URL and import the Excel file
        $url = sanitize_text_field( $_POST['wc_excel_import_url'] );
        $excel = PHPExcel_IOFactory::load( $url );
        $data = $excel->getActiveSheet()->toArray();
        // Save the data and column headings to the session
        $_SESSION['wc_excel_import_data'] = $data;
        $_SESSION['wc_excel_import_column_headings'] = array_shift( $data );
        // Display a success notice and the column mapping form
        echo '<div class="notice notice-success is-dismissible"><p>Excel file imported successfully</p></div>';
        wc_excel_import_column_mapping_form();
        return;
    }
    // Display the form to input the Excel file URL
    ?>
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="wc_excel_import_url">Excel File URL</label></th>
                    <td>
                        <input name="wc_excel_import_url" type="text" id="wc_excel_import_url" class="regular-text">
                        <p class="description" id="wc_excel_import_url-description">Enter the URL of the Excel file to import</p><?php
/**
 * Plugin Name: WooCommerce Automatic CSV Import
 * Description: Automatically updates products at a given interval by importing a CSV from an external site and allows column mapping within the admin panel.
 * Version: 1.0
 * Author: Steve Coakley 
 * License: GPL2
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register a new menu item in the WooCommerce settings menu for the plugin.
 */
function wc_csv_import_add_menu_item() {
    add_submenu_page(
        'woocommerce',
        'CSV Import',
        'CSV Import',
        'manage_options',
        'wc-csv-import',
        'wc_csv_import_settings_page'
    );
}
add_action( 'admin_menu', 'wc_csv_import_add_menu_item' );

/**
 * Display the plugin's settings page.
 */
function wc_csv_import_settings_page() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
 // display the form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'wc_csv_import_settings_form' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wc_csv_import_csv_url">CSV URL</label></th>
                    <td><input type="text" id="wc_csv_import_csv_url" name="wc_csv_import_csv_url" value="" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">&nbsp;</th>
                    <td><input type="submit" name="wc_csv_import_import_csv" value="Import CSV" class="button-primary" /></td>
                </tr>
            </table>
            <input type="hidden" name="wc_csv_import_form_submitted" value="1" />
        </form>
    </div>
    <?php
}
    
    // check if the form has been submitted
   if ( isset( $_POST['wc_csv_import_form_submitted'] ) ) {
    // process form data
    check_admin_referer( 'wc_csv_import_settings_form' );
    $csv_url = sanitize_text_field( $_POST['wc_csv_import_csv_url'] );
    // fetch the CSV file
    $response = wp_remote_get( $csv_url );
    if ( ! is_wp_error( $response ) ) {
        // parse the CSV data into an array
        $csv_data = str_getcsv( wp_remote_retrieve_body( $response ) );
        // process the data
        // ...
    } else {
        // display an error message
        add_settings_error( 'wc_csv_import_messages', 'wc_csv_import_message', 'Error: Unable to fetch CSV file', 'error' );
    }
}
// Column Mapping Form
function wc_csv_import_column_mapping_page() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // get the list of product fields
    $product_fields = get_product_fields();
    
    // display the form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'wc_csv_import_column_mapping_form' ); ?>
            <table class="form-table">
                <?php
                foreach ( $csv_data[0] as $column ) {
                    ?>
                    <tr>
                        <th scope="row"><label for="wc_csv_import_column_mapping_<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $column ); ?></label></th>
                        <td>
                            <select id="wc_csv_import_column_mapping_<?php echo esc_attr( $column ); ?>" name="wc_csv_import_column_mapping[<?php echo esc_attr( $column ); ?>]" class="regular-text">
                                <option value="">-- Select Field --</option>
                                <?php
                                foreach ( $product_fields as $field_key => $field_label ) {
                                    ?>
                                    <option value="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $field_label ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <th scope="row">&nbsp;</th>
                    <td><input type="submit" name="wc_csv_import_next_step" value="Next" class="button-primary" /></td>
                </tr>
            </table>
            <input type="hidden" name="wc_csv_import_column_mapping_submitted" value="1" />
        </form>
    </div>
    <?php
}

// Check column mapping ans process
if ( isset( $_POST['wc_csv_import_column_mapping_submitted'] ) ) {
    // process form data
    check_admin_referer( 'wc_csv_import_column_mapping_form' );
    $column_mapping = sanitize_text_field( $_POST['wc_csv_import_column_mapping'] );
    // store the column mapping in a transient
    set_transient( 'wc_csv_import_column_mapping', $column_mapping, DAY_IN_SECONDS );
    // redirect to the time interval setting page
    wp_safe_redirect( admin_url( 'admin.php?page=wc-csv-import&tab=time_interval' ) );
    exit;
}

// Import time interval
function wc_csv_import_time_interval_page() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // display the form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'wc_csv_import_time_interval_form' ); ?>
<table class="form-table">
<tr>
<th scope="row"><label for="wc_csv_import_time_interval">Time Interval</label></th>
<td>
<select id="wc_csv_import_time_interval" name="wc_csv_import_time_interval" class="regular-text">
<option value="">-- Select Interval --</option>
<option value="hourly">Hourly</option>
<option value="twicedaily">Twice Daily</option>
<option value="daily">Daily</option>
</select>
</td>
</tr>
<tr>
<th scope="row"> </th>
<td><input type="submit" name="wc_csv_import_save_settings" value="Save Settings" class="button-primary" /></td>
</tr>
</table>
<input type="hidden" name="wc_csv_import_time_interval_submitted" value="1" />
</form>
</div>
<?php
}

// Process form data
if ( isset( $_POST['wc_csv_import_time_interval_submitted'] ) ) {
    // process form data
    check_admin_referer( 'wc_csv_import_time_interval_form' );
    $time_interval = sanitize_text_field( $_POST['wc_csv_import_time_interval'] );
    // save the time interval setting
    update_option( 'wc_csv_import_time_interval', $time_interval );
    // redirect to the plugin settings page
    wp_safe_redirect( admin_url( 'admin.php?page=wc-csv-import&settings-updated=true' ) );
    exit;
}

// schedule the recurring event
$time_interval = get_option( 'wc_csv_import_time_interval', 'daily' );
if ( ! wp_next_scheduled( 'wc_csv_import_event' ) ) {
    wp_schedule_event( time(), $time_interval, 'wc_csv_import_event' );
}

if ( ! function_exists( 'wc_csv_import_update_products' ) ) {
    /**
     * Update the products with matching SKUs using data from the CSV file.
     */

function wc_csv_import_update_products() {
    // get the CSV import URL and column mapping
    $csv_url = get_option( 'wc_csv_import_csv_url' );
    $column_mapping = get_transient( 'wc_csv_import_column_mapping' );
    if ( ! $csv_url || ! $column_mapping ) {
        return;
    }
    // fetch the CSV file
    $response = wp_remote_get( $csv_url );
    if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
        return;
    }
    $csv_data = wp_remote_retrieve_body( $response );
    // parse the CSV data
    $csv_lines = explode( "\n", $csv_data );
    $csv_header = str_getcsv( array_shift( $csv_lines ) );
    foreach ( $csv_lines as $csv_line ) {
        $csv_line = str_getcsv( $csv_line );
        $product_data = array_combine( $csv_header, $csv_line );
        // update the product with matching SKU
        $sku = $product_data[ $column_mapping['SKU'] ];
        $product_id = wc_get_product_id_by_sku( $sku );
        if ( ! $product_id ) {
            continue;
        }
        $product_update = array(
            'ID' => $product_id,
        );
        foreach ( $column_mapping as $csv_column => $product_field ) {
            $product_update[ $product_field ] = $product_data[ $csv_column ];
        }
        wp_update_post( $product_update );
    }
  }
}
// update products
if ( isset( $_POST['wc_csv_import_time_interval_submitted'] ) ) {
    // process form data
    check_admin_referer( 'wc_csv_import_time_interval_form' );
    $time_interval = sanitize_text_field( $_POST['wc_csv_import_time_interval'] );
    // update the products with matching SKUs immediately
    wc_csv_import_update_products();
    // save the time interval setting
    update_option( 'wc_csv_import_time_interval', $time_interval );
    // schedule the recurring event
    if ( ! wp_next_scheduled( 'wc_csv_import_event' ) ) {
        wp_schedule_event( time(), $time_interval, 'wc_csv_import_event' );
    }
    // redirect to the plugin settings page
    wp_safe_redirect( admin_url( 'admin.php?page=wc-csv-import&settings-updated=true' ) );
    exit;
}

/**
 * Handle the scheduled product import and update.
 */
function wc_csv_import_scheduled_import() {
    // import the CSV file and update the products
    wc_csv_import_import_csv();
    wc_csv_import_update_products();
}
add_action( 'wc_csv_import_event', 'wc_csv_import_scheduled_import' );
?>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="wc_excel_import_import" id="wc_excel_import_import" class="button button-primary" value="Import"></p>
    </form>
    <?php
}

// Step 2: Display the form to map the columns to product fields
function wc_excel_import_column_mapping_form() {
    // Get the data and column headings from the session
    $data = $_SESSION['wc_excel_import_data'];
    $column_headings = $_SESSION['wc_excel_import_column_headings'];
    // Check if the form has been submitted
    if ( isset( $_POST['wc_excel_import_column_mapping'] ) && isset( $_POST['wc_excel_import_next'] ) ) {
        // Save the column mapping to the session
        $_SESSION['wc_excel_import_column_mapping'] = $_POST['wc_excel_import_column_mapping'];
        // Display the update interval form
        wc_excel_import_update_interval_form();
        return;
    }
    // Display the form to map the columns
    ?>
    <form method="post">
        <table class="form-table">
            <tbody>
                <?php
                foreach ( $column_headings as $column_heading ) {
                    ?>
                    <tr>
                        <th scope="row"><label for="wc_excel_import_column_mapping_<?php echo esc_attr( $column_heading ); ?>"><?php echo esc_html( $column_heading ); ?></label></th>
                        <td>
                            <select name="wc_excel_import_column_mapping[<?php echo esc_attr( $column_heading ); ?>]" id="wc_excel_import_column_mapping_<?php echo esc_attr( $column_heading ); ?>">
                                <option value="">Do not import</option>
                                <option value="sku">SKU</option>
                                <option value="name">Name</option>
                                <option value="description">Description</option>
                                <option value="price">Price</option>
                                <option value="stock">Stock</option>
                                <option value="image">Image</option>
                            </select>
                            <p class="description" id="wc_excel_import_column_mapping_<?php echo esc_attr( $column_heading ); ?>-description">Map the column to a product field</p>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="wc_excel_import_next" id="wc_excel_import_next" class="button button-primary" value="Next"></p>
    </form>
    <?php
}

// Step 3: Display the form to select the update interval
function wc_excel_import_update_interval_form() {
    // Check if the form has been submitted
    if ( isset( $_POST['wc_excel_import_update_interval'] ) && isset( $_POST['wc_excel_import_save_settings'] ) ) {
        // Get the update interval and column mapping from the form submission
        $update_interval = sanitize_text_field( $_POST['wc_excel_import_update_interval'] );
        $column_mapping = $_POST['wc_excel_import_column_mapping'];
        // Save the update interval and column mapping to the database
        update_option( 'wc_excel_import_update_interval', $update_interval );
        update_option( 'wc_excel_import_column_mapping', $column_mapping );
        // Update the products and schedule the cron job
        wc_excel_import_and_update();
        wc_excel_import_schedule_cron( $update_interval );
        // Display a success notice
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved and products updated</p></div>';
        // Display the current time and date
        echo '<p>Last updated: ' . date( 'Y-m-d H:i:s' ) . '</p>';
        return;
    }
    // Display the form to select the update interval
    ?>
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="wc_excel_import_update_interval">Update Interval</label></th>
                    <td>
                        <select name="wc_excel_import_update_interval" id="wc_excel_import_update_interval">
                            <option value="hourly">Hourly</option>
                            <option value="twicedaily">Twice Daily</option>
                            <option value="daily">Daily</option>
                        </select>
                        <p class="description" id="wc_excel_import_update_interval-description">Select the interval to update the products</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit"><input type="submit" name="wc_excel_import_save_settings" id="wc_excel_import_save_settings" class="button button-primary" value="Save Settings"></p>
    </form>
    <?php
}

// Step 5: Update the products and schedule the cron job
// Update the products with the Excel data and column mapping
function wc_excel_import_and_update() {
    // Get the data, column headings, and column mapping from the session
    $data = $_SESSION['wc_excel_import_data'];
    $column_headings = $_SESSION['wc_excel_import_column_headings'];
    $column_mapping = $_SESSION['wc_excel_import_column_mapping'];
    // Loop through the data and update the products
    foreach ( $data as $row ) {
        $product_data = array();
        // Map the columns to the product fields
        foreach ( $column_headings as $index => $column_heading ) {
            $field = $column_mapping[$column_heading];
            if ( !empty( $field ) ) {
                $product_data[$field] = $row[$index];
            }
        }
        // Update the product with the SKU
        $sku = $product_data['sku'];
        $product_id = wc_get_product_id_by_sku( $sku );
        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            $product->set_props( $product_data );
            $product->save();
        }
    }
}

// Schedule the cron job to update the products at the specified interval
function wc_excel_import_schedule_cron( $interval ) {
    wp_clear_scheduled_hook( 'wc_excel_import_event' );
    wp_schedule_event( time(), $interval, 'wc_excel_import_event' );
}

// Hook the cron job to the event
add_action( 'wc_excel_import_event', 'wc_excel_import_and_update' );


