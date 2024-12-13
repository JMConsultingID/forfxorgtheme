<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

    <?php if ($checkout->get_checkout_fields()) : ?>

        <div class="custom-checkout-container">
            <!-- Cart Review Section -->
            <div class="cart-review-section">
                <h3><?php esc_html_e('Order Summary', 'woocommerce'); ?></h3>
                <?php do_action('woocommerce_checkout_before_order_review'); ?>
                <div id="order_review" class="woocommerce-checkout-review-order">
                    <?php do_action('custom_checkout_order_review'); ?>
                </div>
            </div>

            <!-- Billing Section -->
            <div class="billing-section">
                <h3><?php esc_html_e('Billing Details', 'woocommerce'); ?></h3>
                <div class="woocommerce-billing-fields">
                    <?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>
                    <?php foreach ($checkout->get_checkout_fields('billing') as $key => $field) : ?>
                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                    <?php endforeach; ?>
                    <?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="terms-section">
                <?php woocommerce_checkout_terms_and_conditions(); ?>
            </div>

            <!-- Hidden Payment Section -->
            <div id="payment" class="woocommerce-checkout-payment" style="display: none;">
                <?php woocommerce_checkout_payment(); ?>
            </div>

            <!-- Next Step Button -->
            <div class="form-row place-order">
                <noscript>
                    <?php
                    printf(esc_html__('Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce'), '<em>', '</em>');
                    ?>
                    <br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e('Update totals', 'woocommerce'); ?>"><?php esc_html_e('Update totals', 'woocommerce'); ?></button>
                </noscript>

                <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>

                <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php esc_attr_e('Next Step', 'woocommerce'); ?>" data-value="<?php esc_attr_e('Next Step', 'woocommerce'); ?>">
                    <?php esc_html_e('Next Step', 'woocommerce'); ?>
                </button>
            </div>
        </div>

    <?php endif; ?>

</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>

<style>
.custom-checkout-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.cart-review-section,
.billing-section,
.terms-section {
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

/* Remove duplicate privacy policy text */
.woocommerce-privacy-policy-text {
    display: none;
}
.terms-section .woocommerce-privacy-policy-text {
    display: block;
}

/* Style terms and conditions checkbox */
.terms-section {
    margin-top: 20px;
}
.terms-section .woocommerce-terms-and-conditions-checkbox-text {
    display: inline-block;
    margin-left: 5px;
}
</style>