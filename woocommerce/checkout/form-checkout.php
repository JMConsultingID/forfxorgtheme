<?php
defined( 'ABSPATH' ) || exit;

// Trigger WooCommerce hooks
do_action( 'woocommerce_before_checkout_form', $checkout );

// Ensure the cart is not empty
if ( WC()->cart->is_empty() ) {
    wc_print_notices();
    echo '<p>' . esc_html__( 'Your cart is currently empty.', 'woocommerce' ) . '</p>';
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <?php
    // Display only billing fields
    do_action( 'woocommerce_checkout_billing' );

    // Include nonce for security
    wp_nonce_field( 'create_order_action', 'create_order_nonce' );
    ?>
    <button type="submit" class="button alt" name="create_order" value="Next"><?php esc_html_e( 'Next', 'woocommerce' ); ?></button>
</form>

<?php
do_action( 'woocommerce_after_checkout_form', $checkout );
?>
