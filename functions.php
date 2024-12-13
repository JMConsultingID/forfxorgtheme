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

<?php
/**
 * Custom WooCommerce Checkout Modifications
 * Add to your child theme's functions.php
 */

/**
 * Remove unnecessary checkout fields
 */
function modify_checkout_fields($fields) {
    // Remove shipping fields completely
    unset($fields['shipping']);
    
    // Optionally remove specific billing fields
    // unset($fields['billing']['billing_company']);
    // unset($fields['billing']['billing_address_2']);
    
    // Modify existing fields
    $fields['billing']['billing_email']['priority'] = 1;
    $fields['billing']['billing_phone']['priority'] = 2;
    $fields['billing']['billing_first_name']['priority'] = 3;
    $fields['billing']['billing_last_name']['priority'] = 4;
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'modify_checkout_fields');

/**
 * Modify the checkout process flow
 */
function modify_checkout_process() {
    // Set shipping same as billing
    add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
    
    // Auto fill shipping fields with billing
    add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
}
add_action('woocommerce_checkout_init', 'modify_checkout_process');

/**
 * Add custom field validation
 */
function custom_checkout_field_validation() {
    // Example: Additional phone validation
    if (isset($_POST['billing_phone'])) {
        $phone = sanitize_text_field($_POST['billing_phone']);
        if (strlen($phone) < 10) {
            wc_add_notice('Phone number should be at least 10 digits', 'error');
        }
    }
}
add_action('woocommerce_checkout_process', 'custom_checkout_field_validation');

/**
 * Modify checkout layout
 */
function modify_checkout_layout() {
    // Remove order notes
    remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
    
    // Add order review in custom position
    add_action('woocommerce_checkout_before_customer_details', 'woocommerce_order_review', 10);
}
add_action('init', 'modify_checkout_layout');

/**
 * Add custom CSS for checkout page
 */
function add_custom_checkout_css() {
    if (is_checkout()) {
        ?>
        <style>
            /* Customize checkout form layout */
            .woocommerce-checkout .woocommerce {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            /* Make billing form full width */
            .woocommerce-checkout .col2-set {
                width: 100%;
                float: none;
            }

            /* Style the order review table */
            #order_review {
                margin-bottom: 30px;
                background: #f8f8f8;
                padding: 20px;
                border-radius: 5px;
            }

            /* Style form fields */
            .woocommerce form .form-row input.input-text {
                height: 40px;
                padding: 0 15px;
            }

            /* Payment section styling */
            #payment {
                background: #ffffff !important;
                border-radius: 5px;
                margin-top: 20px;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'add_custom_checkout_css');

/**
 * Add custom content before checkout form
 */
function add_content_before_checkout_form() {
    ?>
    <div class="checkout-header">
        <h2>Complete Your Order</h2>
        <div class="checkout-steps">
            <span class="step active">Order Review</span>
            <span class="step">Billing Details</span>
            <span class="step">Payment</span>
        </div>
    </div>
    <?php
}
add_action('woocommerce_before_checkout_form', 'add_content_before_checkout_form', 5);

/**
 * Modify the order review table
 */
function customize_order_review($order_id) {
    // Only run on checkout page
    if (!is_checkout()) {
        return;
    }
    ?>
    <div class="custom-order-review-header">
        <h3>Order Summary</h3>
    </div>
    <?php
}
add_action('woocommerce_before_order_notes', 'customize_order_review');

/**
 * Add custom fields after billing form
 */
function add_custom_checkout_fields($checkout) {
    // Example: Add custom notes field
    woocommerce_form_field('custom_notes', array(
        'type'          => 'textarea',
        'class'         => array('custom-notes-field'),
        'label'         => 'Additional Notes',
        'placeholder'   => 'Add any special instructions here',
        'required'      => false,
    ), $checkout->get_value('custom_notes'));
}
add_action('woocommerce_after_checkout_billing_form', 'add_custom_checkout_fields');

/**
 * Save custom field values
 */
function save_custom_checkout_fields($order_id) {
    if (!empty($_POST['custom_notes'])) {
        $order = wc_get_order($order_id);
        $order->update_meta_data('custom_notes', sanitize_textarea_field($_POST['custom_notes']));
        $order->save();
    }
}
add_action('woocommerce_checkout_update_order_meta', 'save_custom_checkout_fields');