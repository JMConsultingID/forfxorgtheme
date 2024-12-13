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

// Restriksi satu produk di cart
add_filter('woocommerce_add_to_cart_validation', 'limit_cart_to_one_product', 10, 3);
function limit_cart_to_one_product($passed, $product_id, $quantity) {
    if (WC()->cart->get_cart_contents_count() > 0) {
        wc_add_notice(__('You can only have one product in the cart.', 'woocommerce'), 'error');
        return false;
    }
    return $passed;
}

// Force quantity to 1
add_action('woocommerce_before_calculate_totals', 'force_quantity_one');
function force_quantity_one($cart) {
    foreach ($cart->get_cart() as $cart_item) {
        $cart_item['quantity'] = 1;
    }
}

// Handle order creation via AJAX
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
