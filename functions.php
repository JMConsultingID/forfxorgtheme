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

// Add WooCommerce support
function theme_add_woocommerce_support() {
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'theme_add_woocommerce_support');

/**
 * Custom Functions for Multi-Step Checkout
 */

// Step 1: Restrict cart to single product
function restrict_cart_to_single_product($valid, $product_id, $quantity) {
    if (!is_admin() && WC()->cart->get_cart_contents_count() > 0) {
        WC()->cart->empty_cart();
    }
    return $valid;
}
add_filter('woocommerce_add_to_cart_validation', 'restrict_cart_to_single_product', 10, 3);

// Step 2: Modify Checkout Page
function modify_checkout_fields($fields) {
    // Remove unnecessary fields
    unset($fields['order']['order_comments']);
    unset($fields['shipping']);
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'modify_checkout_fields');

// Hide order review on checkout
function remove_order_review_from_checkout() {
    remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
}
add_action('init', 'remove_order_review_from_checkout');

// Replace Place Order button with Next
function replace_place_order_button_text($button_text) {
    return 'Next';
}
add_filter('woocommerce_order_button_text', 'replace_place_order_button_text');

// Create pending order and redirect to payment
function create_pending_order_and_redirect($order_id) {
    $order = wc_get_order($order_id);
    
    // Set order status to pending payment
    $order->update_status('pending');
    
    // Generate payment URL
    $payment_url = $order->get_checkout_payment_url();
    
    // Redirect to payment page
    wp_redirect($payment_url);
    exit;
}
add_action('woocommerce_checkout_order_processed', 'create_pending_order_and_redirect');

// Step 3: Customize Payment Page
function customize_pay_page($order_id) {
    // Get order object
    $order = wc_get_order($order_id);
    
    // Display order details
    ?>
    <div class="order-details-wrapper">
        <h2>Order Details</h2>
        <table class="order-details">
            <tr>
                <th>Product</th>
                <td><?php echo $order->get_items()[0]['name']; ?></td>
            </tr>
            <tr>
                <th>Total</th>
                <td><?php echo $order->get_formatted_order_total(); ?></td>
            </tr>
        </table>
    </div>
    <?php
}
add_action('woocommerce_before_pay_action', 'customize_pay_page');

/**
 * Add custom templates
 */
function get_custom_template($template) {
    if (is_checkout()) {
        $template = get_stylesheet_directory() . '/woocommerce/checkout/form-checkout.php';
    }
    return $template;
}
add_filter('template_include', 'get_custom_template');