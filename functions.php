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

// Remove "Your Order" section and payment section properly.
add_action('wp', function () {
    if (is_checkout() && !is_order_received_page()) {
        // Remove the "Your Order" section.
        remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);

        // Remove the payment section.
        remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    }
});

// Ensure the "Place Order" button is visible.
add_action('woocommerce_checkout_before_customer_details', function () {
    if (is_checkout() && !is_order_received_page()) {
        echo '<style>
            .woocommerce-checkout-review-order-table, 
            .woocommerce-checkout-payment, 
            #order_review_heading { display: none !important; }
            button#place_order { display: block !important; }
        </style>';
    }
});

// Add a custom "Place Order" button if necessary.
add_action('woocommerce_checkout_after_customer_details', function () {
    if (is_checkout() && !is_order_received_page()) {
        echo '<button type="submit" class="button alt" id="place_order" style="margin-top: 20px;">' . __('Place Order', 'your-textdomain') . '</button>';
    }
});
