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
// require_once get_stylesheet_directory() . '/inc/functions/forfx-theme-functions-checkout.php';

function forfx_theme_scripts_styles()
{
    wp_enqueue_style('forfx-theme-style', get_stylesheet_directory_uri() . '/style.css', [], FORFX_THEME_VERSION);
    wp_enqueue_style('forfx-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/forfx-theme.css', [], FORFX_THEME_VERSION);
    wp_enqueue_script('forfx-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/forfx-theme.js', [], FORFX_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'forfx_theme_scripts_styles', 20);

// Remove order review and payment sections from the checkout page.
add_action('wp', function () {
    if (is_checkout() && !is_order_received_page()) {
        // Remove the order review section.
        remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
        
        // Remove the payment section.
        remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    }
});

// Modify the "Place Order" button text (optional).
add_filter('woocommerce_order_button_text', function ($button_text) {
    if (is_checkout() && !is_order_received_page()) {
        $button_text = __('Place Order', 'your-textdomain');
    }
    return $button_text;
});

// Add custom styles to hide unnecessary elements if any are left.
add_action('wp_enqueue_scripts', function () {
    if (is_checkout() && !is_order_received_page()) {
        wp_add_inline_style('woocommerce-inline', '
            .woocommerce-checkout-review-order-table,
            .woocommerce-checkout-payment {
                display: none !important;
            }
        ');
    }
});
