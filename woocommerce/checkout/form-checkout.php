<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

?>
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <?php do_action( 'woocommerce_checkout_billing' ); ?>
    <button type="submit" class="button alt" name="woocommerce_checkout_place_order" value="Next">Next</button>
</form>
<?php
do_action( 'woocommerce_after_checkout_form', $checkout );
?>
