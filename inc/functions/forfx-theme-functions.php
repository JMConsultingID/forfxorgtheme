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
 * Custom Checkout Flow
 * 
 * Features:
 * - Custom billing form
 * - Cart validation
 * - Order creation from cart items
 * - Payment redirect
 */

function custom_billing_form_shortcode() {
    // Initialize output buffer
    ob_start();

    // Check if cart is empty
    if (WC()->cart->is_empty()) {
        wc_print_notices();
        wc_add_notice('Your cart is empty. Please add some products before checkout.', 'error');
        echo '<p>' . wp_kses_post(sprintf(
            'Return to <a href="%s">shop page</a>.', 
            esc_url(wc_get_page_permalink('shop'))
        )) . '</p>';
        return ob_get_clean();
    }

    // If user already has an order in process, redirect to payment page
    if (!empty(WC()->session) && !empty(WC()->session->get('order_awaiting_payment'))) {
        $order_id = WC()->session->get('order_awaiting_payment');
        wp_safe_redirect(wc_get_endpoint_url('order-pay', $order_id, wc_get_checkout_url()));
        exit;
    }

    // Get WooCommerce billing fields
    $checkout = WC()->checkout;
    $billing_fields = $checkout->get_checkout_fields('billing');

    // Print any notices
    wc_print_notices();
    ?>
    <div class="custom-checkout-container">
        <!-- Cart Review Section -->
        <div class="cart-review-section">
            <h3>Cart Review</h3>
            <table class="cart-review-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
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
                            <td><?php echo wp_kses_post($product->get_price_html()); ?></td>
                            <td><?php echo wp_kses_post(WC()->cart->get_product_subtotal($product, $quantity)); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Subtotal</th>
                        <td><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></td>
                    </tr>
                    <tr>
                        <th colspan="3">Total</th>
                        <td><?php echo wp_kses_post(WC()->cart->get_total()); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Billing Form Section -->
        <form id="custom-billing-form" method="post">
            <?php wp_nonce_field('process_billing_form', 'billing_form_nonce'); ?>

            <div class="billing-fields">
                <h3>Billing Details</h3>
                <?php
                foreach ($billing_fields as $key => $field) {
                    if (isset($field['enabled']) && !$field['enabled']) {
                        continue;
                    }

                    $field_value = WC()->checkout->get_value($key);
                    woocommerce_form_field($key, $field, $field_value);
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .cart-review-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .cart-review-table th,
        .cart-review-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .billing-fields {
            margin-top: 30px;
        }
        .woocommerce-error,
        .woocommerce-message,
        .woocommerce-info {
            margin-bottom: 20px;
        }
    </style>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('custom_billing_form', 'custom_billing_form_shortcode');

function process_billing_form() {
    // Check if our form is submitted
    if (!isset($_POST['process_billing'])) {
        return;
    }

    // Check if cart is empty
    if (WC()->cart->is_empty()) {
        wc_add_notice('Your cart is empty. Cannot proceed with checkout.', 'error');
        return;
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['billing_form_nonce'], 'process_billing_form')) {
        wc_add_notice('Security check failed.', 'error');
        return;
    }

    // Validate billing fields
    $checkout = WC()->checkout;
    $billing_fields = $checkout->get_checkout_fields('billing');
    
    foreach ($billing_fields as $key => $field) {
        if (isset($field['required']) && $field['required'] && empty($_POST[$key])) {
            wc_add_notice(sprintf('%s is required', $field['label']), 'error');
        }
    }

    // If validation errors exist, return
    if (wc_notice_count('error') > 0) {
        return;
    }

    try {
        // Create new order
        $order = wc_create_order();

        // Set created via
        $order->set_created_via('checkout');

        // Set customer id
        $customer_id = get_current_user_id();
        $order->set_customer_id($customer_id);

        // Add cart items to order
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            
            $order->add_product(
                $product,
                $quantity,
                array(
                    'subtotal' => $cart_item['line_subtotal'],
                    'total' => $cart_item['line_total']
                )
            );
        }

        // Set billing data properly
        $billing_address = array();
        foreach ($billing_fields as $key => $field) {
            if (!empty($_POST[$key])) {
                $clean_key = str_replace('billing_', '', $key);
                $billing_address[$clean_key] = wc_clean($_POST[$key]);
            }
        }
        
        // Set billing address
        $order->set_address($billing_address, 'billing');
        
        // Copy billing to shipping
        $order->set_address($billing_address, 'shipping');

        // Calculate and set totals
        $order->calculate_totals();
        
        // Set order status to pending
        $order->set_status('pending');
        
        // Save order
        $order->save();

        // Empty cart
        WC()->cart->empty_cart();

        // Store order ID in session
        WC()->session->set('order_awaiting_payment', $order->get_id());

        // Redirect to payment page
        wp_safe_redirect($order->get_checkout_payment_url());
        exit;

    } catch (Exception $e) {
        wc_add_notice($e->getMessage(), 'error');
        return;
    }
}
add_action('template_redirect', 'process_billing_form');

// Make sure WooCommerce is loaded before processing
function check_woocommerce_loaded() {
    if (!function_exists('WC')) {
        return 'WooCommerce must be installed and activated to use this feature.';
    }
    return '';
}
add_action('init', 'check_woocommerce_loaded');