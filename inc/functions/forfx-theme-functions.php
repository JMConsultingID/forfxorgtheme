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
 * Uses WooCommerce native session handling
 */

function custom_billing_form_shortcode() {
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
        <form id="custom-billing-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('process_billing_form', 'billing_form_nonce'); ?>
            <input type="hidden" name="action" value="process_custom_billing">
            <input type="hidden" name="return_url" value="<?php echo esc_url(wp_get_referer()); ?>">

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
    return ob_get_clean();
}
add_shortcode('custom_billing_form', 'custom_billing_form_shortcode');

// Handle form submission through admin-post.php
function handle_billing_form_submission() {
    // Ensure WooCommerce is loaded
    if (!function_exists('WC')) {
        wp_die('WooCommerce is required to process this form.');
        return;
    }

    // Get return URL
    $return_url = isset($_POST['return_url']) ? esc_url_raw($_POST['return_url']) : wc_get_checkout_url();

    // Initialize WooCommerce if not already done
    if (!did_action('woocommerce_init')) {
        WC()->frontend_includes();
        WC()->initialize_session();
        WC()->initialize_cart();
    }

    // Verify nonce
    if (!isset($_POST['billing_form_nonce']) || !wp_verify_nonce($_POST['billing_form_nonce'], 'process_billing_form')) {
        wc_add_notice('Security check failed.', 'error');
        wp_safe_redirect($return_url);
        exit;
    }

    // Now we can safely check the cart
    if (WC()->cart->is_empty()) {
        wc_add_notice('Your cart is empty. Cannot proceed with checkout.', 'error');
        wp_safe_redirect($return_url);
        exit;
    }

    // Get checkout fields
    $checkout = WC()->checkout;
    $billing_fields = $checkout->get_checkout_fields('billing');

    // Validate required fields
    foreach ($billing_fields as $key => $field) {
        if (isset($field['required']) && $field['required'] && empty($_POST[$key])) {
            wc_add_notice(sprintf('%s is required', $field['label']), 'error');
        }
    }

    // If validation errors exist, return
    if (wc_notice_count('error') > 0) {
        wp_safe_redirect($return_url);
        exit;
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

        // Set billing data
        $billing_address = array();
        foreach ($billing_fields as $key => $field) {
            if (!empty($_POST[$key])) {
                $clean_key = str_replace('billing_', '', $key);
                $billing_address[$clean_key] = wc_clean($_POST[$key]);
            }
        }
        
        // Set addresses
        $order->set_address($billing_address, 'billing');
        $order->set_address($billing_address, 'shipping');

        // Calculate totals
        $order->calculate_totals();
        
        // Set order status
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
        wp_safe_redirect($return_url);
        exit;
    }
}
add_action('admin_post_process_custom_billing', 'handle_billing_form_submission');
add_action('admin_post_nopriv_process_custom_billing', 'handle_billing_form_submission');