<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package ForfxTheme
 */
/**
 * Custom Checkout Flow with proper WooCommerce initialization
 */

function handle_billing_form_submission() {
    // Define WooCommerce path
    if (!defined('WC_ABSPATH')) {
        define('WC_ABSPATH', dirname(WC_PLUGIN_FILE) . '/');
    }

    // Load WooCommerce if not loaded
    require_once WC_ABSPATH . 'includes/class-woocommerce.php';
    require_once WC_ABSPATH . 'includes/wc-cart-functions.php';
    require_once WC_ABSPATH . 'includes/class-wc-cart.php';
    require_once WC_ABSPATH . 'includes/class-wc-session-handler.php';

    // Ensure WC() is available
    if (!function_exists('WC')) {
        return;
    }

    // Get return URL
    $return_url = isset($_POST['return_url']) ? esc_url_raw($_POST['return_url']) : home_url();

    // Initialize WooCommerce
    WC()->frontend_includes();
    
    // Initialize session
    if (!WC()->session) {
        WC()->session = new WC_Session_Handler();
        WC()->session->init();
    }

    // Initialize cart
    if (!WC()->cart) {
        WC()->initialize_cart();
    }

    // Verify nonce
    if (!isset($_POST['billing_form_nonce']) || !wp_verify_nonce($_POST['billing_form_nonce'], 'process_billing_form')) {
        wp_redirect(add_query_arg('checkout_error', 'security_check', $return_url));
        exit;
    }

    try {
        // Get cart data from session
        $cart_items = WC()->session->get('cart');
        if (empty($cart_items)) {
            throw new Exception('Cart is empty');
        }

        // Create order
        $order = wc_create_order();
        
        // Set customer data
        $order->set_customer_id(get_current_user_id());
        $order->set_created_via('custom_checkout');
        
        // Add products from session cart
        foreach ($cart_items as $cart_item_key => $cart_item) {
            if (!isset($cart_item['product_id'])) {
                continue;
            }

            $product = wc_get_product($cart_item['product_id']);
            if (!$product) {
                continue;
            }

            $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
            
            $order->add_product(
                $product,
                $quantity
            );
        }

        // Set billing address
        $billing_fields = [
            'first_name', 'last_name', 'company', 'email', 'phone',
            'address_1', 'address_2', 'city', 'state', 'postcode', 'country'
        ];

        $billing_address = [];
        foreach ($billing_fields as $field) {
            $post_key = 'billing_' . $field;
            if (!empty($_POST[$post_key])) {
                $billing_address[$field] = sanitize_text_field($_POST[$post_key]);
            }
        }

        // Set addresses
        if (!empty($billing_address)) {
            $order->set_address($billing_address, 'billing');
            $order->set_address($billing_address, 'shipping');
        }

        // Calculate totals
        $order->calculate_totals();
        
        // Set status to pending
        $order->set_status('pending');
        
        // Save the order
        $order->save();

        // Store order ID in session
        WC()->session->set('order_awaiting_payment', $order->get_id());

        // Clear cart
        WC()->cart->empty_cart();

        // Redirect to payment page
        wp_redirect($order->get_checkout_payment_url());
        exit;

    } catch (Exception $e) {
        error_log('Custom checkout error: ' . $e->getMessage());
        wp_redirect(add_query_arg('checkout_error', urlencode($e->getMessage()), $return_url));
        exit;
    }
}

add_action('admin_post_process_custom_billing', 'handle_billing_form_submission');
add_action('admin_post_nopriv_process_custom_billing', 'handle_billing_form_submission');

// Handle error messages
function handle_checkout_error_messages() {
    if (isset($_GET['checkout_error'])) {
        $error = sanitize_text_field(urldecode($_GET['checkout_error']));
        
        switch ($error) {
            case 'security_check':
                wc_add_notice('Security check failed. Please try again.', 'error');
                break;
            case 'required_fields':
                wc_add_notice('Please fill in all required fields.', 'error');
                break;
            default:
                wc_add_notice($error, 'error');
                break;
        }
    }
}
add_action('wp_loaded', 'handle_checkout_error_messages');

function custom_billing_form_shortcode() {
    // If cart is empty, show notice and return
    if (WC()->cart->is_empty()) {
        wc_print_notices();
        return '<p>' . __('Your cart is empty. Please add some products before checkout.', 'woocommerce') . '</p>';
    }

    ob_start();
    
    // Print notices
    wc_print_notices();

    ?>
    <div class="custom-checkout-container">
        <form id="custom-billing-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="process_custom_billing">
            <input type="hidden" name="return_url" value="<?php echo esc_url(get_permalink()); ?>">
            <?php wp_nonce_field('process_billing_form', 'billing_form_nonce'); ?>

            <!-- Cart Review -->
            <h3><?php _e('Cart Review', 'woocommerce'); ?></h3>
            <table class="shop_table">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'woocommerce'); ?></th>
                        <th><?php _e('Quantity', 'woocommerce'); ?></th>
                        <th><?php _e('Price', 'woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $product = $cart_item['data'];
                        echo '<tr>';
                        echo '<td>' . $product->get_name() . '</td>';
                        echo '<td>' . $cart_item['quantity'] . '</td>';
                        echo '<td>' . WC()->cart->get_product_subtotal($product, $cart_item['quantity']) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2"><?php _e('Total', 'woocommerce'); ?></th>
                        <td><?php echo WC()->cart->get_total(); ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Billing Fields -->
            <h3><?php _e('Billing Details', 'woocommerce'); ?></h3>
            <div class="billing-fields">
                <?php
                $fields = array(
                    'billing_first_name' => array(
                        'label' => __('First Name', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_last_name' => array(
                        'label' => __('Last Name', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_email' => array(
                        'label' => __('Email', 'woocommerce'),
                        'required' => true,
                        'type' => 'email'
                    ),
                    'billing_phone' => array(
                        'label' => __('Phone', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_address_1' => array(
                        'label' => __('Address', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_city' => array(
                        'label' => __('City', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_state' => array(
                        'label' => __('State', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_postcode' => array(
                        'label' => __('Postcode', 'woocommerce'),
                        'required' => true
                    ),
                    'billing_country' => array(
                        'label' => __('Country', 'woocommerce'),
                        'required' => true
                    )
                );

                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, WC()->checkout->get_value($key));
                }
                ?>
            </div>

            <button type="submit" class="button alt"><?php _e('Proceed to Payment', 'woocommerce'); ?></button>
        </form>
    </div>
    
    <style>
        .custom-checkout-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .shop_table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .shop_table th,
        .shop_table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .billing-fields {
            margin-bottom: 30px;
        }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode('custom_billing_form', 'custom_billing_form_shortcode');