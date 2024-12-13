<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined('ABSPATH') || exit;

?>
<div class="woocommerce-billing-fields">
    <h3><?php esc_html_e('Billing details', 'woocommerce'); ?></h3>
    <?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

    <div class="woocommerce-billing-fields__field-wrapper">
        <?php
        foreach ($checkout->get_checkout_fields('billing') as $key => $field) {
            woocommerce_form_field($key, $field, $checkout->get_value($key));
        }
        ?>
    </div>

    <?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
</div>

<!-- Tombol Next -->
<button id="next-to-payment" type="button" class="button alt">
    <?php esc_html_e('Next', 'woocommerce'); ?>
</button>

<script>
    jQuery(document).ready(function($) {
        $('#next-to-payment').on('click', function() {
            const data = {
                action: 'create_order',
                nonce: '<?php echo wp_create_nonce('create_order_nonce'); ?>'
            };

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data.message || 'Something went wrong.');
                }
            });
        });
    });
</script>

