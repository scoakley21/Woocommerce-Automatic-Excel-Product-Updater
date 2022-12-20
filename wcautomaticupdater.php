<?php
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

// create update function
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
