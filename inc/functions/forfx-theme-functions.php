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


function handle_billing_form_submission() {
    // Load WooCommerce core
    include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
    include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
    include_once WC_ABSPATH . 'includes/wc-template-hooks.php';

    // Get return URL early
    $return_url = isset($_POST['return_url']) ? esc_url_raw($_POST['return_url']) : home_url();

    // Initialize WooCommerce session and cart if needed
    if (!WC()->is_loaded()) {
        WC()->frontend_includes();
        WC()->cart = new WC_Cart();
        WC()->session = new WC_Session_Handler();
        WC()->session->init();
    }

    // Verify nonce first
    if (!isset($_POST['billing_form_nonce']) || !wp_verify_nonce($_POST['billing_form_nonce'], 'process_billing_form')) {
        wp_safe_redirect(add_query_arg('wc_error', 'security_check', $return_url));
        exit;
    }

    try {
        // Create new order directly without cart check
        $order = wc_create_order();

        // Set created via
        $order->set_created_via('checkout');

        // Set customer id
        $customer_id = get_current_user_id();
        $order->set_customer_id($customer_id);

        // Get cart items from session if available
        if (WC()->cart && !WC()->cart->is_empty()) {
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
        } else {
            throw new Exception('Cart is empty or not available.');
        }

        // Set billing data
        $billing_fields = array(
            'first_name', 'last_name', 'company', 'address_1',
            'address_2', 'city', 'state', 'postcode', 'country',
            'email', 'phone'
        );

        $billing_address = array();
        foreach ($billing_fields as $field) {
            $post_key = 'billing_' . $field;
            if (!empty($_POST[$post_key])) {
                $billing_address[$field] = wc_clean($_POST[$post_key]);
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

        // Try to empty cart if available
        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        // Redirect to payment page
        wp_safe_redirect($order->get_checkout_payment_url());
        exit;

    } catch (Exception $e) {
        wp_safe_redirect(add_query_arg('wc_error', urlencode($e->getMessage()), $return_url));
        exit;
    }
}
add_action('admin_post_process_custom_billing', 'handle_billing_form_submission');
add_action('admin_post_nopriv_process_custom_billing', 'handle_billing_form_submission');

// Handle error messages on return
function handle_custom_checkout_messages() {
    if (isset($_GET['wc_error'])) {
        $error = sanitize_text_field(urldecode($_GET['wc_error']));
        if ($error === 'security_check') {
            wc_add_notice('Security check failed.', 'error');
        } else {
            wc_add_notice($error, 'error');
        }
    }
}
add_action('wp_loaded', 'handle_custom_checkout_messages');