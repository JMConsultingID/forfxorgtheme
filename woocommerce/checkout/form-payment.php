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
$order_id = $_GET['order_id'] ?? 0;
$order = wc_get_order($order_id);

if ($order) {
    echo '<h2>Order Review</h2>';
    wc_get_template('checkout/review-order.php', array('order' => $order));
    
    echo '<h2>Payment</h2>';
    wc_get_template('checkout/payment.php', array('order' => $order));
} else {
    echo '<p>Invalid order. Please try again.</p>';
}
?>
