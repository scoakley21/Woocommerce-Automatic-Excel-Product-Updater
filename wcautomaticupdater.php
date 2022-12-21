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

// Create a new menu item under WooCommerce settings
add_filter( 'woocommerce_settings_tabs_array', 'wc_excel_import_add_settings_tab', 50 );
function wc_excel_import_add_settings_tab( $settings_tabs ) {
    $settings_tabs['excel_import'] = __( 'Excel Import', 'woocommerce' );
    return $settings_tabs;
}

// Display the plugin settings page
add_action( 'woocommerce_settings_tabs_excel_import', 'wc_excel_import_settings_page' );
function wc_excel_import_settings_page() {
    woocommerce_admin_fields( wc_excel_import_settings() );
}

// Save the plugin settings
add_action( 'woocommerce_update_options_excel_import', 'wc_excel_import_update_settings' );
function wc_excel_import_update_settings() {
    woocommerce_update_options( wc_excel_import_settings() );
}

// Define the plugin settings fields
function wc_excel_import_settings() {
    $settings = array(
        'section_title' => array(
            'name'     => __( 'Excel Import', 'woocommerce' ),
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'wc_excel_import_section_title'
        ),
        'url' => array(
            'name' => __( 'Excel File URL', 'woocommerce' ),
            'type' => 'text',
            'desc' => __( 'Enter the URL of the Excel file to import', 'woocommerce' ),
            'id'   => 'wc_excel_import_url'
        ),
        'column_mapping' => array(
            'name'    => __( 'Column Mapping', 'woocommerce' ),
            'type'    => 'select',
            'options' => array(
                'sku'         => __( 'SKU', 'woocommerce' ),
                'name'        => __( 'Name', 'woocommerce' ),
                'description' => __( 'Description', 'woocommerce' ),
                'price'       => __( 'Price', 'woocommerce' ),
                'stock'       => __( 'Stock', 'woocommerce' ),
                'image'       => __( 'Image', 'woocommerce' ),
                'none'        => __( 'Do not import', 'woocommerce' )
            ),
            'desc' => __( 'Select the Excel column that corresponds to each product field', 'woocommerce' ),
            'id'   => 'wc_excel_import_column_mapping'
        ),
        'section_end' => array(
             'type' => 'sectionend',
             'id' => 'wc_excel_import_section_end'
        )
    );
    return $settings;
}

// Handle form submission
add_action( 'admin_init', 'wc_excel_import_form_handler' );
function wc_excel_import_form_handler() {
    if ( isset( $_POST['wc_excel_import_url'] ) && isset( $_POST['wc_excel_import_column_mapping'] ) ) {
        update_option( 'wc_excel_import_url', sanitize_text_field( $_POST['wc_excel_import_url'] ) );
        update_option( 'wc_excel_import_column_mapping', sanitize_text_field( $_POST['wc_excel_import_column_mapping'] ) );
    }
}

// Import the Excel file and update products
function wc_excel_import_and_update() {
    // Get the user-defined URL and column mapping from the database
    $url = get_option( 'wc_excel_import_url' );
    $column_mapping = get_option( 'wc_excel_import_column_mapping' );
    
    // Use PHPExcel to read the Excel file and convert it to an array
    $excel = PHPExcel_IOFactory::load( $url );
    $data = $excel->getActiveSheet()->toArray();
    
    // Iterate through the array and update products with matching SKUs
    foreach ( $data as $row ) {
        $sku = $row[ array_search( 'sku', $column_mapping ) ];
        $product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
        if ( ! $product ) {
            continue;
        }
        
        $update_data = array();
        foreach ( $column_mapping as $key => $value ) {
            if ( $value == 'sku' ) {
                continue;
            }
            if ( $value == 'name' ) {
                $update_data['name'] = $row[ $key ];
            }
            if ( $value == 'description' ) {
                $update_data['description'] = $row[ $key ];
            }
            if ( $value == 'price' ) {
                $update_data['price'] = $row[ $key ];
            }
            if ( $value == 'stock' ) {
                $update_data['stock_quantity'] = $row[ $key ];
            }
            if ( $value == 'image' ) {
                $update_data['image_id'] = wc_download_image_from_url( $row[ $key ] );
            }
        }
        $product->set_props( $update_data );
        $product->save();
    }
}

// Register a custom cron schedule
add_filter( 'cron_schedules', 'wc_excel_import_cron_schedule' );
function wc_excel_import_cron_schedule( $schedules ) {
    $schedules['hourly'] = array(
        'interval' => 3600, // seconds
        'display'  => __( 'Once Hourly' ),
    );
    return $schedules;
}

// Schedule the cron job to run hourly
if ( ! wp_next_scheduled( 'wc_excel_import_cron_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'wc_excel_import_cron_hook' );
}

// Hook the cron job function to the scheduled event
add_action( 'wc_excel_import_cron_hook', 'wc_excel_import_cron_function' );
function wc_excel_import_cron_function() {
    wc_excel_import_and_update();
}


