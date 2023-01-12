<?php
/**
 * Plugin Name: WooCommerce Automatic CSV Product Updater
 * Description: Automatically updates products at a given interval by importing a CSV file from a user-defined location and allows column mapping within the admin panel.
 * Version: 1.0
 * Author: Steve Coakley 
 * License: GPL2
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Step 1: Read the CSV file from wp-content/uploads/product-list.csv
add_action( 'admin_menu', 'wc_csv_import_menu' );
function wc_csv_import_menu() {
    add_submenu_page( 'woocommerce', 'CSV Import', 'CSV Import', 'manage_options', 'csv-import', 'wc_csv_import_page' );
}

function wc_csv_import_page() {
    // Read the CSV file
    $file = ABSPATH . 'wp-content/uploads/product-list.csv';
    $data = array_map( 'str_getcsv', file( $file ) );
    // Save the data and column headings to the session
    $_SESSION['wc_csv_import_data'] = $data;
    $_SESSION['wc_csv_import_column_headings'] = array_shift( $data );
    // Display the column mapping form
    wc_csv_import_column_mapping_form();
}

// Step 2: Display the form to map the columns to product fields
function wc_csv_import_column_mapping_form() {
    // Get the data and column headings from the session
    $data = $_SESSION['wc_csv_import_data'];
    $column_headings = $_SESSION['wc_csv_import_column_headings'];
    // Check if the form has been submitted
    if ( isset( $_POST['wc_csv_import_column_mapping'] ) && isset( $_POST['wc_csv_import_next'] ) ) {
        // Save the column mapping to the session
        $_SESSION['wc_csv_import_column_mapping'] = $_POST['wc_csv_import_column_mapping'];
        // Display the update interval form
        wc_csv_import_update_interval_form();
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
                        <th scope="row"><label for="wc_csv_import_column_<?php echo $column_heading; ?>"><?php echo $column_heading; ?></label></th>
                        <td>
                            <select name="wc_csv_import_column_mapping[<?php echo $column_heading; ?>]">
                                <option value="">--Select a field--</option>
<option value="name">Product Name</option>
<option value="price">Price</option>
<option value="stock">Stock</option>
<option value="cost">Cost</option>
<option value="id">ID</option>
<option value="sku">SKU</option>
</select>
</td>
</tr>
<?php
             }
             ?>
</tbody>
</table>
<p class="submit"><input type="submit" name="wc_csv_import_next" id="wc_csv_import_next" class="button button-primary" value="Next"></p>
</form>
<?php
}

// Step 3: Display the form to set the update interval
function wc_csv_import_update_interval_form() {
// Check if the form has been submitted
if ( isset( $_POST['wc_csv_import_update_interval'] ) && isset( $_POST['wc_csv_import_save'] ) ) {
// Save the update interval to the session
$SESSION['wc_csv_import_update_interval'] = $_POST['wc_csv_import_update_interval'];
// Schedule the update event using the update interval
wp_schedule_event( time(), $_SESSION['wc_csv_import_update_interval'], 'wc_csv_import_update_event' );
// Display a success notice and the column mapping form
echo '<div class="notice notice-success is-dismissible"><p>Update interval set successfully</p></div>';
return;
}
// Display the form to set the update interval
?>
<form method="post">
<table class="form-table">
<tbody>
<tr>
<th scope="row"><label for="wc_csv_import_update_interval">Update Interval</label></th>
<td>
<select name="wc_csv_import_update_interval" id="wc_csv_import_update_interval">
<option value="hourly">Hourly</option>
<option value="twicedaily">Twice Daily</option>
<option value="daily">Daily</option>
<option value="fifteen_minutes">Every 15 minutes</option>
</select>
<p class="description" id="wc_csv_import_update_interval-description">Select the interval at which the products should be updated</p>
</td>
</tr>
</tbody>
</table>
<p class="submit"><input type="submit" name="wc_csv_import_save" id="wc_csv_import_save" class="button button-primary" value="Save"></p>
    </form>
<?php
}

// Step 4: Update the products using the data from the CSV file and the column mapping
add_action( 'wc_csv_import_update_event', 'wc_csv_import_update_products' );
function wc_csv_import_update_products() {
// Get the data, column headings, and column mapping from the session
$data = $_SESSION['wc_csv_import_data'];
$column_headings = $_SESSION['wc_csv_import_column_headings'];
$column_mapping = $_SESSION['wc_csv_import_column_mapping'];
// Loop through the data and update the products
foreach ( $data as $row ) {
$product_data = array();
// Loop through the columns and map the data to the product fields
foreach ( $column_headings as $index => $column_heading ) {
$product_data[ $column_mapping[ $column_heading ] ] = $row[ $index ];
}
// Update the product using the WooCommerce API
wc_update_product( $product_data );
}
}
