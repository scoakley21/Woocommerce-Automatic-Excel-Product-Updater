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
    
    // check if the form has been submitted
    if ( isset( $_POST['wc_csv_import_form_submitted'] ) ) {
        // process form data
        check_admin_referer( 'wc_csv_import_settings_form' );
        $options = array(
            'csv_url' => sanitize_text_field( $_POST['wc_csv_import_csv_url'] ),
            'import_interval' => sanitize_text_field( $_POST['wc_csv_import_import_interval'] ),
            'column_mapping' => array()
        );
        foreach ( $_POST['wc_csv_import_column_mapping'] as $csv_column => $product_field ) {
            $options['column_mapping'][ $csv_column ] = sanitize_text_field( $product_field );
        }
        update_option( 'wc_csv_import_settings', $options );
        wp_clear_scheduled_hook( 'wc_csv_import_import_event' );
        wp_schedule_event( time(), $options['import_interval'], 'wc_csv_import_import_event' );
        $message = __( 'Settings saved.', 'wc-csv-import' );
    }
    
    // get the current plugin settings
    $options = get_option( 'wc_csv_import_settings' );
    $csv_url = ( isset( $options['csv_url'] ) ) ? $options['csv_url'] : '';
    $import_interval = ( isset( $options['import_interval'] ) ) ? $options['import_interval'] : '';
    $column_mapping = ( isset( $options['column_mapping'] ) ) ? $options['column_mapping'] :
