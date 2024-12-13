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

// Add this to your child theme's functions.php

/**
 * Customize checkout process
 */
function customize_checkout_process() {
    // Remove default order review
    remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    
    // Add custom order review
    add_action('custom_checkout_order_review', 'custom_order_review', 10);
}
add_action('init', 'customize_checkout_process');

/**
 * Custom order review display
 */
function custom_order_review() {
    ?>
    <table class="shop_table custom-order-review">
        <thead>
            <tr>
                <th class="product-name"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                <th class="product-total"><?php esc_html_e('Total', 'woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product = $cart_item['data'];
                ?>
                <tr>
                    <td>
                        <?php echo $_product->get_name(); ?>
                        <strong class="product-quantity">× <?php echo $cart_item['quantity']; ?></strong>
                    </td>
                    <td><?php echo WC()->cart->get_product_subtotal($_product, $cart_item['quantity']); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="cart-subtotal">
                <th><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
                <td><?php wc_cart_totals_subtotal_html(); ?></td>
            </tr>
            <tr class="order-total">
                <th><?php esc_html_e('Total', 'woocommerce'); ?></th>
                <td><?php wc_cart_totals_order_total_html(); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php
}

/**
 * Handle order creation and redirect
 */
function handle_checkout_order_creation($order_id, $posted_data) {
    $order = wc_get_order($order_id);
    
    if ($order) {
        // Set order status
        $order->set_status('pending');
        $order->save();

        // Store order ID in session
        WC()->session->set('order_awaiting_payment', $order_id);

        // Redirect to payment page
        wp_redirect($order->get_checkout_payment_url());
        exit;
    }
}
add_action('woocommerce_checkout_order_processed', 'handle_checkout_order_creation', 10, 2);

/**
 * Remove unnecessary checkout fields
 */
function remove_unnecessary_checkout_fields($fields) {
    // Remove shipping fields
    unset($fields['shipping']);
    
    // Remove order comments
    unset($fields['order']['order_comments']);
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'remove_unnecessary_checkout_fields');