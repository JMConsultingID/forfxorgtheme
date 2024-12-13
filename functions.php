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
 * Set default payment method
 */
function set_default_payment_method($available_gateways) {
    if (!is_checkout()) {
        return $available_gateways;
    }

    // Get the first available payment method
    if (!empty($available_gateways)) {
        $first_gateway = reset($available_gateways);
        WC()->session->set('chosen_payment_method', $first_gateway->id);
    }

    return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'set_default_payment_method', 100);

/**
 * Add default payment method to checkout data
 */
function add_default_payment_method_to_checkout_data($data) {
    if (empty($data['payment_method'])) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!empty($available_gateways)) {
            $first_gateway = reset($available_gateways);
            $data['payment_method'] = $first_gateway->id;
        }
    }
    return $data;
}
add_filter('woocommerce_checkout_posted_data', 'add_default_payment_method_to_checkout_data');

/**
 * Handle order creation and redirect
 */
function handle_checkout_order_creation($order_id) {
    if (!$order_id) {
        return;
    }

    // Get the order
    $order = wc_get_order($order_id);
    
    if ($order) {
        // Set order status to pending
        $order->set_status('pending');
        $order->save();

        // Store order ID in session
        WC()->session->set('order_awaiting_payment', $order_id);

        // Get the payment URL
        $payment_url = $order->get_checkout_payment_url();

        // Redirect to payment page
        if (!empty($payment_url)) {
            wp_redirect($payment_url);
            exit;
        }
    }
}
add_action('woocommerce_checkout_order_processed', 'handle_checkout_order_creation', 10);

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

/**
 * Modify checkout button text
 */
function modify_checkout_button_text($button_text) {
    return __('Next Step', 'woocommerce');
}
add_filter('woocommerce_order_button_text', 'modify_checkout_button_text');

/**
 * Add custom styles to head
 */
function add_custom_checkout_styles() {
    if (is_checkout()) {
        ?>
        <style>
            /* Custom checkout styles */
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
                text-align: right;
                margin-top: 20px;
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
            .woocommerce-additional-fields {
                display: none !important;
            }
            
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
add_action('wp_head', 'add_custom_checkout_styles');

/**
 * Add custom body class to checkout
 */
function add_checkout_body_class($classes) {
    if (is_checkout()) {
        $classes[] = 'custom-checkout-page';
    }
    return $classes;
}
add_filter('body_class', 'add_checkout_body_class');

/**
 * Ensure WooCommerce cart is not empty
 */
function ensure_cart_not_empty() {
    if (is_checkout() && WC()->cart->is_empty()) {
        wp_redirect(wc_get_cart_url());
        exit;
    }
}
add_action('template_redirect', 'ensure_cart_not_empty');

/**
 * Debug checkout errors (uncomment when needed)
 */
/*
function debug_checkout_errors($data, $errors) {
    if (!empty($errors->get_error_messages())) {
        foreach ($errors->get_error_messages() as $message) {
            error_log('Checkout Error: ' . $message);
        }
    }
}
add_action('woocommerce_after_checkout_validation', 'debug_checkout_errors', 10, 2);
*/

/**
 * Remove duplicate terms and conditions
 */
function remove_duplicate_terms_and_conditions() {
    // Remove the default terms and conditions
    remove_action('woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20);
    remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);
}
add_action('init', 'remove_duplicate_terms_and_conditions');

/**
 * Remove privacy policy text from various locations
 */
function remove_privacy_policy_text() {
    remove_action('woocommerce_checkout_before_terms_and_conditions', 'wc_checkout_privacy_policy_text', 10);
    remove_action('woocommerce_checkout_after_terms_and_conditions', 'wc_checkout_privacy_policy_text', 10);
}
add_action('init', 'remove_privacy_policy_text');

/**
 * Add custom CSS to hide duplicate elements
 */
function add_custom_checkout_styles() {
    if (is_checkout()) {
        ?>
        <style>
            /* Hide all instances of terms and privacy policy except the last one */
            .woocommerce-terms-and-conditions-wrapper:not(:last-of-type),
            .woocommerce-privacy-policy-text:not(:last-of-type),
            form.checkout > .woocommerce-terms-and-conditions-wrapper {
                display: none !important;
            }
            
            /* Hide duplicate buttons */
            .form-row.place-order:not(:last-of-type),
            #place_order:not(:last-of-type) {
                display: none !important;
            }

            /* Custom styling for the remaining terms section */
            .terms-section {
                background: #f8f8f8;
                padding: 20px;
                border-radius: 4px;
                margin-bottom: 20px;
            }

            .woocommerce-terms-and-conditions-wrapper {
                margin-bottom: 0;
            }

            .form-row.validate-required {
                margin: 0;
            }

            /* Style the checkbox and label */
            .woocommerce-form__label-for-checkbox {
                display: inline-flex !important;
                align-items: flex-start !important;
                margin: 0 !important;
            }

            .woocommerce-form__input-checkbox {
                margin: 5px 8px 0 0 !important;
            }

            /* Style the Next Step button */
            #place_order {
                float: right;
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
        </style>
        <?php
    }
}
add_action('wp_head', 'add_custom_checkout_styles', 999);

/**
 * Ensure only one terms and conditions section is rendered
 */
function modify_terms_display($html) {
    static $terms_displayed = false;
    
    if ($terms_displayed) {
        return '';
    }
    
    $terms_displayed = true;
    return $html;
}
add_filter('woocommerce_checkout_terms_and_conditions_html', 'modify_terms_display');