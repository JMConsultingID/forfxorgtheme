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

// Remove default WooCommerce actions
remove_action('woocommerce_checkout_order_processed', 'wc_checkout_process_redirect');
remove_action('woocommerce_checkout_process', 'wc_checkout_process_payment');

/**
 * Initialize custom checkout
 */
function init_custom_checkout() {
    // Remove terms validation
    add_filter('woocommerce_checkout_show_terms', '__return_false');
    add_filter('woocommerce_checkout_validate_terms', '__return_false');
    
    // Remove unnecessary fields
    add_filter('woocommerce_checkout_fields', function($fields) {
        unset($fields['shipping']);
        unset($fields['order']['order_comments']);
        return $fields;
    });

    // Change checkout button text
    add_filter('woocommerce_order_button_text', function() {
        return 'Next Step';
    });
}
add_action('init', 'init_custom_checkout');

/**
 * Set default payment method
 */
function set_default_payment_method() {
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if (!empty($available_gateways)) {
        $first_gateway = reset($available_gateways);
        WC()->session->set('chosen_payment_method', $first_gateway->id);
    }
}
add_action('woocommerce_before_checkout_form', 'set_default_payment_method', 10);

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
            <tr class="order-total">
                <th><?php esc_html_e('Total', 'woocommerce'); ?></th>
                <td><?php wc_cart_totals_order_total_html(); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php
}

/**
 * Process the checkout
 */
function custom_process_checkout($order_id) {
    if (!$order_id) {
        error_log('No order ID provided');
        return;
    }

    try {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Get available payment gateways
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (empty($available_gateways)) {
            throw new Exception('No payment gateways available');
        }

        // Get the first gateway
        $gateway = reset($available_gateways);
        
        // Set payment method
        $order->set_payment_method($gateway);
        $order->set_payment_method_title($gateway->get_title());
        
        // Set order status and other details
        $order->set_status('pending');
        $order->set_created_via('custom_checkout');
        
        // Save the order
        $order->save();

        // Store order ID in session
        WC()->session->set('order_awaiting_payment', $order_id);

        // Clear cart
        WC()->cart->empty_cart();

        // Get payment URL with special handling for BACS
        if ($gateway->id === 'bacs') {
            $pay_url = $order->get_checkout_order_received_url();
        } else {
            $pay_url = $order->get_checkout_payment_url(true);
        }

        error_log('Redirecting to: ' . $pay_url);
        
        // Perform redirect
        wp_safe_redirect($pay_url);
        exit;

    } catch (Exception $e) {
        error_log('Checkout error: ' . $e->getMessage());
        wc_add_notice($e->getMessage(), 'error');
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
}
add_action('woocommerce_checkout_order_processed', 'custom_process_checkout', 10);

/**
 * Add payment method to posted data
 */
function add_payment_method_to_posted_data($data) {
    if (empty($data['payment_method'])) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!empty($available_gateways)) {
            $first_gateway = reset($available_gateways);
            $data['payment_method'] = $first_gateway->id;
        }
    }
    return $data;
}
add_filter('woocommerce_checkout_posted_data', 'add_payment_method_to_posted_data', 10, 1);

/**
 * Debug logging
 */
function debug_checkout_process($posted_data, $errors) {
    error_log('=== Start Checkout Debug ===');
    error_log('Posted Data: ' . print_r($posted_data, true));
    
    if (!empty($errors->get_error_messages())) {
        error_log('Checkout Errors: ' . print_r($errors->get_error_messages(), true));
    }
    
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    error_log('Available Payment Methods: ' . print_r(array_keys($available_gateways), true));
    
    if (isset($posted_data['payment_method'])) {
        error_log('Selected Payment Method: ' . $posted_data['payment_method']);
    }
    
    error_log('=== End Checkout Debug ===');
}
add_action('woocommerce_after_checkout_validation', 'debug_checkout_process', 10, 2);

/**
 * Add custom checkout styles
 */
function add_custom_checkout_styles() {
    if (is_checkout()) {
        ?>
        <style>
            .custom-checkout-container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }

            .cart-review-section,
            .billing-section {
                background: #fff;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .cart-review-section h3,
            .billing-section h3 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .place-order {
                margin-top: 20px;
                text-align: right;
            }

            #place_order {
                background-color: #2196F3;
                color: white;
                padding: 15px 30px;
                font-size: 16px;
                border-radius: 4px;
                border: none;
            }

            #place_order:hover {
                background-color: #1976D2;
            }

            /* Hide payment methods radio but keep the input for form submission */
            .woocommerce-checkout-payment ul.payment_methods {
                display: none !important;
            }

            /* Hide unnecessary elements */
            .woocommerce-shipping-fields,
            .woocommerce-additional-fields,
            .woocommerce-terms-and-conditions-wrapper,
            .woocommerce-privacy-policy-text {
                display: none !important;
            }

            /* Custom order review table styling */
            .custom-order-review {
                width: 100%;
                margin-bottom: 20px;
            }

            .custom-order-review th,
            .custom-order-review td {
                padding: 12px;
                border-bottom: 1px solid #eee;
            }

            .custom-order-review tfoot tr:last-child {
                border-top: 2px solid #eee;
                font-weight: bold;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'add_custom_checkout_styles', 999);

/**
 * Ensure cart is not empty
 */
function ensure_cart_not_empty() {
    if (is_checkout() && WC()->cart->is_empty()) {
        wp_redirect(wc_get_cart_url());
        exit;
    }
}
add_action('template_redirect', 'ensure_cart_not_empty');