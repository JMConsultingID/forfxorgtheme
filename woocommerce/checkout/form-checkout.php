<?php
defined( 'ABSPATH' ) || exit;

// Trigger WooCommerce hooks
do_action( 'woocommerce_before_checkout_form', $checkout );

// Check if there are products in the cart
if ( WC()->cart->is_empty() ) {
    wc_print_notices();
    echo '<p>' . esc_html__( 'Your cart is currently empty.', 'woocommerce' ) . '</p>';
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <?php
    // Billing fields
    do_action( 'woocommerce_checkout_billing' );

    // Include the nonce for security
    wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );
    ?>
    <button type="submit" class="button alt" name="woocommerce_checkout_place_order" value="Next">Next</button>
</form>

<?php
do_action( 'woocommerce_after_checkout_form', $checkout );
?>
