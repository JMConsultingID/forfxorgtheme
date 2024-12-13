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
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

    <div class="custom-checkout-container">
        <!-- Cart Review Section -->
        <div class="cart-review-section">
            <h3><?php esc_html_e('Order Summary', 'woocommerce'); ?></h3>
            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php do_action('custom_checkout_order_review'); ?>
            </div>
        </div>

        <!-- Billing Section -->
        <div class="billing-section">
            <h3><?php esc_html_e('Billing Details', 'woocommerce'); ?></h3>
            <div class="woocommerce-billing-fields">
                <?php foreach ($checkout->get_checkout_fields('billing') as $key => $field) : ?>
                    <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hidden Payment Section -->
        <div id="payment" class="woocommerce-checkout-payment" style="display: none;">
            <?php woocommerce_checkout_payment(); ?>
        </div>

        <!-- Next Step Button -->
        <div class="form-row place-order">
            <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
            <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php esc_attr_e('Next Step', 'woocommerce'); ?>" data-value="<?php esc_attr_e('Next Step', 'woocommerce'); ?>">
                <?php esc_html_e('Next Step', 'woocommerce'); ?>
            </button>
        </div>
    </div>

</form>

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
</style>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>