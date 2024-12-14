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


// Handle order creation programmatically on form submission.
add_action('woocommerce_checkout_process', function () {
    // Ensure the necessary fields are filled in before creating the order.
    if (empty($_POST['billing_first_name']) || empty($_POST['billing_email'])) {
        wc_add_notice(__('Please fill in all required fields.', 'your-textdomain'), 'error');
    }
});

// Set a default payment method and ensure all required data are set.
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    // Set the default payment method (adjust as needed, e.g., 'bacs', 'stripe').
    $default_payment_method = 'cod'; // Replace 'cod' with your payment method ID.

    // Set the payment method for the order.
    $order->set_payment_method($default_payment_method);

    // Set the payment method title (optional, but ensures clarity).
    $payment_gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($payment_gateways[$default_payment_method])) {
        $order->set_payment_method_title($payment_gateways[$default_payment_method]->get_title());
    }

    // Set the order status to pending payment.
    $order->update_status('pending', __('Order created and awaiting payment.', 'your-textdomain'));
}, 10, 2);

// Redirect to the order pay page after order creation.
add_action('woocommerce_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);

    if ($order && $order->get_status() === 'pending') {
        // Redirect the user to the order payment page.
        wp_redirect($order->get_checkout_payment_url());
        exit;
    }
});

// Add a fallback payment method to prevent errors during checkout process.
add_filter('woocommerce_available_payment_gateways', function ($available_gateways) {
    if (is_checkout() && !is_wc_endpoint_url('order-pay')) {
        // Forcefully set a default payment gateway if none is selected.
        if (empty($available_gateways)) {
            $default_payment_method = 'cod'; // Replace 'cod' with your preferred payment method.
            $available_gateways = [];
            $gateways = WC()->payment_gateways()->payment_gateways();

            if (isset($gateways[$default_payment_method])) {
                $available_gateways[$default_payment_method] = $gateways[$default_payment_method];
            }
        }
    }
    return $available_gateways;
});

