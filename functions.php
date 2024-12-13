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

// Membatasi checkout ke satu produk dengan quantity 1
add_action('woocommerce_add_to_cart_validation', 'limit_cart_to_single_product', 10, 2);
function limit_cart_to_single_product($passed, $product_id) {
    WC()->cart->empty_cart();
    return $passed;
}

// Handle AJAX untuk membuat order
add_action('wp_ajax_create_order', 'create_pending_order');
add_action('wp_ajax_nopriv_create_order', 'create_pending_order');

function create_pending_order() {
    check_ajax_referer('create_order_nonce', 'nonce');

    if (!WC()->cart->get_cart_contents_count()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }

    $order = wc_create_order();
    foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
        $product = $values['data'];
        $order->add_product($product, $values['quantity']);
    }

    $order->set_status('pending');
    $order->save();

    wp_send_json_success(['redirect' => $order->get_checkout_payment_url()]);
}
