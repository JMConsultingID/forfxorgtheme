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

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}

?>
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

    <div class="custom-checkout-container">
        <!-- Cart Review Section -->
        <div class="cart-review-section">
            <h3><?php esc_html_e('Order Summary', 'woocommerce'); ?></h3>
            <?php do_action('woocommerce_checkout_before_order_review'); ?>
            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php do_action('custom_checkout_order_review'); ?>
            </div>
            <?php do_action('woocommerce_checkout_after_order_review'); ?>
        </div>

        <!-- Billing Section -->
        <div class="billing-section">
            <h3><?php esc_html_e('Billing Details', 'woocommerce'); ?></h3>
            <?php do_action('woocommerce_checkout_billing'); ?>
        </div>

        <!-- Next Step Button -->
        <div class="form-row place-order">
            <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php esc_attr_e('Next Step', 'woocommerce'); ?>" data-value="<?php esc_attr_e('Next Step', 'woocommerce'); ?>">
                <?php esc_html_e('Next Step', 'woocommerce'); ?>
            </button>
        </div>
    </div>

</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>

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

/* Hide unnecessary elements */
.woocommerce-shipping-fields,
.woocommerce-additional-fields,
.woocommerce-checkout-payment {
    display: none !important;
}
</style>
