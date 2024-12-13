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
require_once get_stylesheet_directory() . '/inc/functions/forfx-theme-functions.php';

function forfx_theme_scripts_styles()
{
    wp_enqueue_style('forfx-theme-style', get_stylesheet_directory_uri() . '/style.css', [], FORFX_THEME_VERSION);
    wp_enqueue_style('forfx-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/forfx-theme.css', [], FORFX_THEME_VERSION);
    wp_enqueue_script('forfx-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/forfx-theme.js', [], FORFX_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'forfx_theme_scripts_styles', 20);

add_action( 'template_redirect', 'custom_checkout_flow' );

function custom_checkout_flow() {
    // Check if the form is submitted on the checkout page
    if ( isset( $_POST['create_order'] ) && isset( $_POST['create_order_nonce'] ) && wp_verify_nonce( $_POST['create_order_nonce'], 'create_order_action' ) ) {
        // Ensure the cart is not empty
        if ( WC()->cart->is_empty() ) {
            wc_add_notice( __( 'Your cart is empty. Please add items to your cart and try again.', 'woocommerce' ), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit;
        }

        // Get billing data from the form
        $billing_data = [
            'billing_first_name' => sanitize_text_field( $_POST['billing_first_name'] ),
            'billing_last_name'  => sanitize_text_field( $_POST['billing_last_name'] ),
            'billing_email'      => sanitize_email( $_POST['billing_email'] ),
            'billing_phone'      => sanitize_text_field( $_POST['billing_phone'] ),
            'billing_address_1'  => sanitize_text_field( $_POST['billing_address_1'] ),
            'billing_city'       => sanitize_text_field( $_POST['billing_city'] ),
            'billing_postcode'   => sanitize_text_field( $_POST['billing_postcode'] ),
            'billing_country'    => sanitize_text_field( $_POST['billing_country'] ),
        ];

        // Create a new order
        try {
            $order = wc_create_order();

            // Add products from the cart to the order
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $order->add_product( $cart_item['data'], $cart_item['quantity'] );
            }

            // Set billing information
            $order->set_address( $billing_data, 'billing' );

            // Set order status to pending payment
            $order->set_status( 'pending', __( 'Awaiting payment.', 'woocommerce' ) );

            // Calculate totals and save the order
            $order->calculate_totals();
            $order->save();

            // Clear the cart
            WC()->cart->empty_cart();

            // Redirect to the Payment Order page
            wp_redirect( $order->get_checkout_payment_url() );
            exit;

        } catch ( Exception $e ) {
            wc_add_notice( __( 'Failed to create order: ' . $e->getMessage(), 'woocommerce' ), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit;
        }
    }
}
