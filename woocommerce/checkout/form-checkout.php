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

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

// Jika checkout nonaktif, tampilkan pesan
if (!$checkout) {
    return;
}

// Alur Multi-Step
?>
<div id="multi-step-checkout">
    <div id="step-1" class="checkout-step active">
        <h2><?php esc_html_e('Billing Details', 'woocommerce'); ?></h2>
        <?php wc_get_template('checkout/form-billing.php'); ?>
    </div>

    <div id="step-2" class="checkout-step">
        <h2><?php esc_html_e('Payment', 'woocommerce'); ?></h2>
        <div class="order-review">
            <?php wc_get_template('checkout/review-order.php'); ?>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tombol "Next" di Step 1
        $('#next-to-payment').on('click', function() {
            const step1 = $('#step-1');
            const step2 = $('#step-2');

            const data = {
                action: 'create_order',
                nonce: '<?php echo wp_create_nonce('create_order_nonce'); ?>'
            };

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
                if (response.success) {
                    step1.removeClass('active').hide();
                    step2.addClass('active').show();
                    window.location.href = response.data.redirect; // Redirect ke Payment Page
                } else {
                    alert(response.data.message || 'Something went wrong.');
                }
            });
        });
    });
</script>

<style>
    /* Styling Multi-Step */
    #multi-step-checkout {
        display: flex;
        flex-direction: column;
    }
    .checkout-step {
        display: none;
    }
    .checkout-step.active {
        display: block;
    }
</style>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
