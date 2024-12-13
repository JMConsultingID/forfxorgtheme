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
 * Custom WooCommerce Checkout Flow Implementation
 * Add this to your child theme's functions.php
 */

/**
 * Remove unnecessary fields and sections
 */
function custom_remove_checkout_fields($fields) {
    // Remove shipping fields
    unset($fields['shipping']);
    
    // Remove order comments
    unset($fields['order']['order_comments']);
    
    // Optionally remove specific billing fields
    // unset($fields['billing']['billing_company']);
    // unset($fields['billing']['billing_address_2']);
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'custom_remove_checkout_fields');

/**
 * Remove shipping methods display
 */
function custom_remove_shipping_methods() {
    remove_action('woocommerce_checkout_shipping', 'woocommerce_checkout_shipping', 10);
}
add_action('init', 'custom_remove_shipping_methods');

/**
 * Customize checkout template parts
 */
function custom_reorder_checkout() {
    // Remove default order review
    remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    
    // Add simplified order review
    add_action('woocommerce_checkout_before_customer_details', 'custom_order_review', 10);
}
add_action('init', 'custom_reorder_checkout');

/**
 * Display simplified order review
 */
function custom_order_review() {
    echo '<div class="custom-order-review">';
    echo '<h3>' . __('Order Review', 'woocommerce') . '</h3>';
    
    // Display cart items
    echo '<table class="shop_table">';
    echo '<thead><tr>';
    echo '<th>' . __('Product', 'woocommerce') . '</th>';
    echo '<th>' . __('Total', 'woocommerce') . '</th>';
    echo '</tr></thead><tbody>';
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $_product = $cart_item['data'];
        echo '<tr>';
        echo '<td>' . $_product->get_name() . ' × ' . $cart_item['quantity'] . '</td>';
        echo '<td>' . WC()->cart->get_product_subtotal($_product, $cart_item['quantity']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '<tfoot>';
    echo '<tr>';
    echo '<th>' . __('Total', 'woocommerce') . '</th>';
    echo '<td>' . WC()->cart->get_total() . '</td>';
    echo '</tr>';
    echo '</tfoot>';
    echo '</table>';
    echo '</div>';
}

/**
 * Modify checkout button text
 */
function custom_checkout_button_text($button_text) {
    return __('Proceed to Payment', 'woocommerce');
}
add_filter('woocommerce_order_button_text', 'custom_checkout_button_text');

/**
 * Handle order creation before payment
 */
function custom_create_order_before_payment($order_id) {
    $order = wc_get_order($order_id);
    
    if ($order) {
        // Set order status to pending payment
        $order->set_status('pending');
        $order->save();
        
        // Store order ID in session
        WC()->session->set('order_awaiting_payment', $order_id);
        
        // Get payment URL
        $payment_url = $order->get_checkout_payment_url();
        
        // Redirect to payment page
        wp_redirect($payment_url);
        exit;
    }
}
add_action('woocommerce_checkout_order_processed', 'custom_create_order_before_payment', 10, 1);

/**
 * Add custom styling
 */
function custom_checkout_styles() {
    if (is_checkout()) {
        ?>
        <style>
            /* Hide unnecessary elements */
            .woocommerce-shipping-fields,
            .woocommerce-additional-fields {
                display: none !important;
            }
            
            /* Style order review */
            .custom-order-review {
                margin-bottom: 30px;
                padding: 20px;
                background: #f8f8f8;
                border-radius: 4px;
            }
            
            .custom-order-review table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .custom-order-review th,
            .custom-order-review td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            
            /* Style form layout */
            .woocommerce-billing-fields {
                margin-top: 20px;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .custom-order-review {
                    padding: 15px;
                }
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'custom_checkout_styles');

/**
 * Customize payment page
 */
function customize_payment_page($order_id) {
    if (!is_wc_endpoint_url('order-pay')) {
        return;
    }
    
    // Add custom content or modifications to payment page
    echo '<div class="payment-page-notice">';
    echo '<p>' . __('Please review your order and select your payment method below.', 'woocommerce') . '</p>';
    echo '</div>';
}
add_action('woocommerce_before_pay_action', 'customize_payment_page');

/**
 * Optional: Add payment page styles
 */
function custom_payment_page_styles() {
    if (is_wc_endpoint_url('order-pay')) {
        ?>
        <style>
            .payment-page-notice {
                margin-bottom: 30px;
                padding: 15px;
                background: #f8f8f8;
                border-left: 4px solid #2196F3;
            }
            
            .woocommerce-order-pay .shop_table {
                margin-bottom: 30px;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'custom_payment_page_styles');