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
require_once get_stylesheet_directory() . '/inc/functions/forfx-theme-functions.php';

function forfx_theme_scripts_styles()
{
    wp_enqueue_style('forfx-theme-style', get_stylesheet_directory_uri() . '/style.css', [], FORFX_THEME_VERSION);
    wp_enqueue_style('forfx-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/forfx-theme.css', [], FORFX_THEME_VERSION);
    wp_enqueue_script('forfx-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/forfx-theme.js', [], FORFX_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'forfx_theme_scripts_styles', 20);

function custom_billing_form_shortcode() {
    // If user already have an order in process, redirect to payment page
    if (!empty(WC()->session) && !empty(WC()->session->get('order_awaiting_payment'))) {
        $order_id = WC()->session->get('order_awaiting_payment');
        wp_redirect(wc_get_endpoint_url('order-pay', $order_id, wc_get_checkout_url()));
        exit;
    }

    // Get WooCommerce billing fields
    $checkout = WC()->checkout;
    $billing_fields = $checkout->get_checkout_fields('billing');

    ob_start();
    ?>
    <form id="custom-billing-form" method="post">
        <?php wp_nonce_field('process_billing_form', 'billing_form_nonce'); ?>

        <?php
        // Loop through billing fields
        foreach ($billing_fields as $key => $field) {
            if (isset($field['enabled']) && !$field['enabled']) {
                continue;
            }

            $field_value = WC()->checkout->get_value($key);
            woocommerce_form_field($key, $field, $field_value);
        }
        ?>

        <div class="form-row place-order">
            <button type="submit" class="button alt" name="process_billing" id="place_order">
                <?php esc_html_e('Proceed to Payment', 'woocommerce'); ?>
            </button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_billing_form', 'custom_billing_form_shortcode');

// Process form submission
function process_billing_form() {
    // Check if our form is submitted
    if (!isset($_POST['process_billing'])) {
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

    // Process will continue in next implementation
}
add_action('template_redirect', 'process_billing_form');