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
                        <p class="description" id="wc_excel_import_url-description">Enter the URL of the Excel file to import</p>
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


