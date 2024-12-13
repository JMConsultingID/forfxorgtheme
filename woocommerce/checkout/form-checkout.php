<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */
/**
 * Remove terms and conditions completely
 */
function remove_terms_and_conditions() {
    // Remove terms and conditions checkbox requirement
    add_filter('woocommerce_checkout_show_terms', '__return_false');
    // Remove privacy policy text
    remove_action('woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20);
}
add_action('init', 'remove_terms_and_conditions');

/**
 * Debug checkout errors
 */
function debug_checkout_errors($data, $errors) {
    if (!empty($errors->get_error_messages())) {
        foreach ($errors->get_error_messages() as $message) {
            error_log('Checkout Error: ' . $message);
        }
    }
}
add_action('woocommerce_after_checkout_validation', 'debug_checkout_errors', 10, 2);

/**
 * Set default payment method if none selected
 */
function ensure_payment_method($posted_data) {
    if (empty($posted_data['payment_method'])) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!empty($available_gateways)) {
            $first_gateway = reset($available_gateways);
            $posted_data['payment_method'] = $first_gateway->id;
        }
    }
    return $posted_data;
}
add_filter('woocommerce_checkout_posted_data', 'ensure_payment_method', 10, 1);

/**
 * Modify order processing for our custom flow
 */
function modify_order_processing($order_id) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Set created via
    $order->set_created_via('custom_checkout');
    
    // Set status to pending payment
    $order->set_status('pending');
    
    // Save the order
    $order->save();

    // Store in session
    WC()->session->set('order_awaiting_payment', $order_id);
}
add_action('woocommerce_checkout_order_processed', 'modify_order_processing', 10, 1);

/**
 * Fix payment URL redirect
 */
function custom_get_checkout_payment_url($order_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        $payment_url = $order->get_checkout_payment_url();
        wp_redirect($payment_url);
        exit;
    }
}
add_action('woocommerce_checkout_order_processed', 'custom_get_checkout_payment_url', 20, 1);