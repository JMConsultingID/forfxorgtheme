<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/forfx-elementor-theme/
 *
 * @package ForfxTheme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('FORFX_THEME_VERSION', '2.1.9');

/**
 * Load forfx theme scripts & styles.
 *
 * @return void
 */
// require_once get_stylesheet_directory() . '/inc/functions/forfx-theme-functions.php';

function forfx_theme_scripts_styles()
{
    wp_enqueue_style('forfx-theme-style', get_stylesheet_directory_uri() . '/style.css', [], FORFX_THEME_VERSION);
    wp_enqueue_style('forfx-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/forfx-theme.css', [], FORFX_THEME_VERSION);
    wp_enqueue_script('forfx-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/forfx-theme.js', [], FORFX_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'forfx_theme_scripts_styles', 20);


/**
 * Functions.php - Hello Elementor Child Theme
 * Custom WooCommerce Multi-step Checkout Implementation
 */

// Pastikan hanya 1 produk dalam cart
add_filter('woocommerce_add_to_cart_validation', 'force_single_product_cart', 10, 2);
function force_single_product_cart($valid, $product_id) {
    if (!WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }
    return $valid;
}

// Hapus fields yang tidak diperlukan di checkout
add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
function custom_override_checkout_fields($fields) {
    // Hapus fields yang tidak diperlukan
    unset($fields['order']['order_comments']);
    unset($fields['shipping']);
    
    return $fields;
}

// Custom template untuk halaman checkout
add_filter('woocommerce_locate_template', 'custom_woocommerce_locate_template', 10, 3);
function custom_woocommerce_locate_template($template, $template_name, $template_path) {
    if ('checkout/form-checkout.php' === $template_name) {
        $template = get_stylesheet_directory() . '/woocommerce/checkout/form-checkout.php';
    }
    return $template;
}

// Modifikasi tombol place order
add_filter('woocommerce_order_button_text', 'custom_order_button_text');
function custom_order_button_text() {
    return 'Next Step';
}

// Buat order ketika next step ditekan
add_action('woocommerce_checkout_order_processed', 'redirect_to_payment_page', 10, 3);
function redirect_to_payment_page($order_id, $posted_data, $order) {
    $order = wc_get_order($order_id);
    $order->update_status('pending');
    
    // Redirect ke halaman pembayaran
    wp_redirect($order->get_checkout_payment_url());
    exit;
}

// Custom template untuk halaman pembayaran
add_filter('woocommerce_locate_template', 'custom_payment_template', 10, 3);
function custom_payment_template($template, $template_name, $template_path) {
    if ('checkout/payment.php' === $template_name) {
        $template = get_stylesheet_directory() . '/woocommerce/checkout/payment.php';
    }
    return $template;
}

// Hapus tambahan produk di checkout
add_filter('woocommerce_cart_needs_payment', '__return_false');

// Nonaktifkan pembaruan checkout
add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');