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
 * Custom Checkout Flow with simplified WooCommerce integration
 */

function handle_billing_form_submission() {
    // Get return URL
    $return_url = isset($_POST['return_url']) ? esc_url_raw($_POST['return_url']) : home_url();

    // Verify nonce
    if (!isset($_POST['billing_form_nonce']) || !wp_verify_nonce($_POST['billing_form_nonce'], 'process_billing_form')) {
        wp_redirect(add_query_arg('checkout_error', 'security_check', $return_url));
        exit;
    }

    // Basic validation
    $required_fields = array('billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_redirect(add_query_arg('checkout_error', 'required_fields', $return_url));
            exit;
        }
    }

    try {
        // Create order
        $order = new WC_Order();
        
        // Set customer data
        $order->set_customer_id(get_current_user_id());
        $order->set_created_via('custom_checkout');
        $order->set_payment_method('');

        // Set addresses
        $billing_address = array(
            'first_name' => isset($_POST['billing_first_name']) ? sanitize_text_field($_POST['billing_first_name']) : '',
            'last_name'  => isset($_POST['billing_last_name']) ? sanitize_text_field($_POST['billing_last_name']) : '',
            'company'    => isset($_POST['billing_company']) ? sanitize_text_field($_POST['billing_company']) : '',
            'email'      => isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '',
            'phone'      => isset($_POST['billing_phone']) ? sanitize_text_field($_POST['billing_phone']) : '',
            'address_1'  => isset($_POST['billing_address_1']) ? sanitize_text_field($_POST['billing_address_1']) : '',
            'address_2'  => isset($_POST['billing_address_2']) ? sanitize_text_field($_POST['billing_address_2']) : '',
            'city'       => isset($_POST['billing_city']) ? sanitize_text_field($_POST['billing_city']) : '',
            'state'      => isset($_POST['billing_state']) ? sanitize_text_field($_POST['billing_state']) : '',
            'postcode'   => isset($_POST['billing_postcode']) ? sanitize_text_field($_POST['billing_postcode']) : '',
            'country'    => isset($_POST['billing_country']) ? sanitize_text_field($_POST['billing_country']) : ''
        );

        $order->set_address($billing_address, 'billing');
        $order->set_address($billing_address, 'shipping');

        // Get cart items manually
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if ($product) {
                $order->add_product(
                    $product,
                    $cart_item['quantity'],
                    array(
                        'total' => $cart_item['line_total'],
                        'subtotal' => $cart_item['line_subtotal'],
                        'variation' => $cart_item['variation']
                    )
                );
            }
        }

        // Calculate totals
        $order->calculate_totals();
        
        // Set pending status
        $order->set_status('pending');
        
        // Save the order
        $order->save();
        
        // Empty the cart
        WC()->cart->empty_cart();
        
        // Set order ID in session
        WC()->session->set('order_awaiting_payment', $order->get_id());

        // Redirect to payment page
        wp_redirect($order->get_checkout_payment_url());
        exit;

    } catch (Exception $e) {
        wp_redirect(add_query_arg('checkout_error', urlencode($e->getMessage()), $return_url));
        exit;
    }
}

// Add the actions for both logged in and non-logged in users
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