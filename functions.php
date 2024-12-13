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
 * Remove terms and conditions completely
 */
function remove_terms_and_conditions() {
    // Remove terms and conditions checkbox requirement
    add_filter('woocommerce_checkout_show_terms', '__return_false');
    // Remove privacy policy text
    remove_action('woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20);
}
add_action('init', 'remove_terms_and_conditions');

/**
 * Debug checkout errors
 */
function debug_checkout_errors($data, $errors) {
    if (!empty($errors->get_error_messages())) {
        foreach ($errors->get_error_messages() as $message) {
            error_log('Checkout Error: ' . $message);
        }
    }
}
add_action('woocommerce_after_checkout_validation', 'debug_checkout_errors', 10, 2);

/**
 * Set default payment method if none selected
 */
function ensure_payment_method($posted_data) {
    if (empty($posted_data['payment_method'])) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!empty($available_gateways)) {
            $first_gateway = reset($available_gateways);
            $posted_data['payment_method'] = $first_gateway->id;
        }
    }
    return $posted_data;
}
add_filter('woocommerce_checkout_posted_data', 'ensure_payment_method', 10, 1);

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
 * Modify order button text
 */
function modify_checkout_button_text($button_text) {
    return __('Next Step', 'woocommerce');
}
add_filter('woocommerce_order_button_text', 'modify_checkout_button_text');

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
 * Modify order processing for our custom flow
 */
function modify_order_processing($order_id) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Set created via
    $order->set_created_via('custom_checkout');
    
    // Set status to pending payment
    $order->set_status('pending');
    
    // Save the order
    $order->save();

    // Store in session
    WC()->session->set('order_awaiting_payment', $order_id);
}
add_action('woocommerce_checkout_order_processed', 'modify_order_processing', 10, 1);

/**
 * Handle redirect after order creation
 */
function custom_checkout_redirect($order_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        wp_redirect($order->get_checkout_payment_url());
        exit;
    }
}
add_action('woocommerce_checkout_order_processed', 'custom_checkout_redirect', 20, 1);

/**
 * Ensure cart is not empty on checkout
 */
function ensure_cart_not_empty() {
    if (is_checkout() && WC()->cart->is_empty()) {
        wp_redirect(wc_get_cart_url());
        exit;
    }
}
add_action('template_redirect', 'ensure_cart_not_empty');

/**
 * Add custom styles to checkout
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
            .woocommerce-additional-fields {
                display: none !important;
            }

            /* Hide all terms and privacy policy related elements */
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