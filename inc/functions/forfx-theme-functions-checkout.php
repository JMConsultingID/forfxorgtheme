<?php
/**
 * Complete Custom Checkout Implementation
 * Features:
 * - Custom billing form
 * - Cart validation
 * - Order creation
 * - Payment redirection
 */

// Display the custom checkout form
function custom_billing_form_shortcode() {
    ob_start();

    // Print any notices
    wc_print_notices();

    // Check if cart is empty
    if (WC()->cart->is_empty()) {
        echo '<p>' . __('Your cart is empty. Please add some products before checkout.', 'woocommerce') . '</p>';
        return ob_get_clean();
    }

    ?>
    <div class="custom-checkout-container">
        <!-- Cart Review Section -->
        <div class="cart-review-section">
            <h3><?php esc_html_e('Cart Review', 'woocommerce'); ?></h3>
            <table class="shop_table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Product', 'woocommerce'); ?></th>
                        <th><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                        <th><?php esc_html_e('Price', 'woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $product = $cart_item['data'];
                        $quantity = $cart_item['quantity'];
                        ?>
                        <tr>
                            <td><?php echo wp_kses_post($product->get_name()); ?></td>
                            <td><?php echo esc_html($quantity); ?></td>
                            <td><?php echo wp_kses_post(WC()->cart->get_product_subtotal($product, $quantity)); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2"><?php esc_html_e('Total', 'woocommerce'); ?></th>
                        <td><?php echo wp_kses_post(WC()->cart->get_total()); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Billing Form Section -->
        <form id="custom-billing-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="process_custom_billing">
            <input type="hidden" name="return_url" value="<?php echo esc_url(get_permalink()); ?>">
            <?php wp_nonce_field('process_billing_form', 'billing_form_nonce'); ?>

            <div class="billing-fields">
                <h3><?php esc_html_e('Billing Details', 'woocommerce'); ?></h3>
                <?php
                $fields = array(
                    'billing_first_name' => array(
                        'label' => __('First Name', 'woocommerce'),
                        'required' => true,
                        'type' => 'text'
                    ),
                    'billing_last_name' => array(
                        'label' => __('Last Name', 'woocommerce'),
                        'required' => true,
                        'type' => 'text'
                    ),
                    'billing_email' => array(
                        'label' => __('Email', 'woocommerce'),
                        'required' => true,
                        'type' => 'email'
                    ),
                    'billing_phone' => array(
                        'label' => __('Phone', 'woocommerce'),
                        'required' => true,
                        'type' => 'tel'
                    ),
                    'billing_address_1' => array(
                        'label' => __('Address', 'woocommerce'),
                        'required' => true,
                        'type' => 'text'
                    ),
                    'billing_city' => array(
                        'label' => __('City', 'woocommerce'),
                        'required' => true,
                        'type' => 'text'
                    ),
                    'billing_state' => array(
                        'label' => __('State', 'woocommerce'),
                        'required' => true,
                        'type' => 'text'
                    ),
                    'billing_postcode' => array(
                        'label' => __('Postcode', 'woocommerce'),
                        'required' => true,
                        'type' => 'text'
                    ),
                    'billing_country' => array(
                        'label' => __('Country', 'woocommerce'),
                        'required' => true,
                        'type' => 'country'
                    )
                );

                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, '');
                }
                ?>
            </div>

            <div class="form-row place-order">
                <button type="submit" class="button alt" name="process_billing" id="place_order">
                    <?php esc_html_e('Proceed to Payment', 'woocommerce'); ?>
                </button>
            </div>
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
        .form-row {
            margin-bottom: 15px;
        }
        .required {
            color: red;
        }
        .button.alt {
            background-color: #333;
            color: #fff;
            padding: 15px 30px;
            border: none;
            cursor: pointer;
        }
        .button.alt:hover {
            background-color: #555;
        }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode('custom_billing_form', 'custom_billing_form_shortcode');

// Process the form submission
function handle_billing_form_submission() {
    // Get return URL
    $return_url = isset($_POST['return_url']) ? esc_url_raw($_POST['return_url']) : home_url();

    // Verify nonce
    if (!isset($_POST['billing_form_nonce']) || !wp_verify_nonce($_POST['billing_form_nonce'], 'process_billing_form')) {
        wp_safe_redirect(add_query_arg('checkout_error', 'security_check', $return_url));
        exit;
    }

    // Validate required fields
    $required_fields = array(
        'billing_first_name', 'billing_last_name', 'billing_email', 
        'billing_phone', 'billing_address_1', 'billing_city', 
        'billing_state', 'billing_postcode', 'billing_country'
    );

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_safe_redirect(add_query_arg('checkout_error', 'required_fields', $return_url));
            exit;
        }
    }

    try {
        // Create new order
        $order = wc_create_order();

        // Set addresses
        $billing_address = array(
            'first_name' => sanitize_text_field($_POST['billing_first_name']),
            'last_name'  => sanitize_text_field($_POST['billing_last_name']),
            'email'      => sanitize_email($_POST['billing_email']),
            'phone'      => sanitize_text_field($_POST['billing_phone']),
            'address_1'  => sanitize_text_field($_POST['billing_address_1']),
            'city'       => sanitize_text_field($_POST['billing_city']),
            'state'      => sanitize_text_field($_POST['billing_state']),
            'postcode'   => sanitize_text_field($_POST['billing_postcode']),
            'country'    => sanitize_text_field($_POST['billing_country'])
        );

        // Set the order billing address
        $order->set_address($billing_address, 'billing');
        // Set shipping address same as billing
        $order->set_address($billing_address, 'shipping');

        // Add cart items to order
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            
            $order->add_product(
                $product,
                $quantity,
                array(
                    'total' => $cart_item['line_total'],
                    'subtotal' => $cart_item['line_subtotal']
                )
            );
        }

        // Set order metadata
        $order->set_created_via('custom_checkout');
        $order->set_customer_id(get_current_user_id());
        $order->set_customer_ip_address(WC_Geolocation::get_ip_address());
        $order->set_customer_user_agent(wc_get_user_agent());

        // Calculate totals
        $order->calculate_totals();
        
        // Set order status
        $order->set_status('pending');
        
        // Save order
        $order->save();

        // Empty cart
        WC()->cart->empty_cart();

        // Set order ID in session
        WC()->session->set('order_awaiting_payment', $order->get_id());

        // Redirect to payment page
        wp_safe_redirect($order->get_checkout_payment_url());
        exit;

    } catch (Exception $e) {
        wc_add_notice($e->getMessage(), 'error');
        wp_safe_redirect($return_url);
        exit;
    }
}
add_action('admin_post_process_custom_billing', 'handle_billing_form_submission');
add_action('admin_post_nopriv_process_custom_billing', 'handle_billing_form_submission');

// Handle error messages
function handle_checkout_error_messages() {
    if (isset($_GET['checkout_error'])) {
        $error = sanitize_text_field($_GET['checkout_error']);
        
        switch ($error) {
            case 'security_check':
                wc_add_notice(__('Security check failed. Please try again.', 'woocommerce'), 'error');
                break;
            case 'required_fields':
                wc_add_notice(__('Please fill in all required fields.', 'woocommerce'), 'error');
                break;
            default:
                wc_add_notice($error, 'error');
                break;
        }
    }
}
add_action('wp_loaded', 'handle_checkout_error_messages');

// Add custom CSS to style notices
function add_custom_checkout_styles() {
    ?>
    <style>
        .woocommerce-error,
        .woocommerce-message,
        .woocommerce-info {
            padding: 1em 2em 1em 3.5em;
            margin: 0 0 2em;
            position: relative;
            background-color: #f8f8f8;
            color: #515151;
            border-top: 3px solid #ff0000;
            list-style: none outside;
            width: auto;
            word-wrap: break-word;
        }
        .woocommerce-message {
            border-top-color: #8fae1b;
        }
        .woocommerce-info {
            border-top-color: #1e85be;
        }
    </style>
    <?php
}
add_action('wp_head', 'add_custom_checkout_styles');