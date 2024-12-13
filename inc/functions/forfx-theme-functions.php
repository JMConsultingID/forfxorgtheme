<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package ForfxTheme
 */

add_action( 'template_redirect', 'forfx_theme_custom_checkout_flow' );

function forfx_theme_custom_checkout_flow() {
    if (is_checkout() && empty($_GET['order-pay'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['billing_first_name'])) {
            $billing_data = [
                'billing_first_name' => sanitize_text_field($_POST['billing_first_name']),
                'billing_last_name'  => sanitize_text_field($_POST['billing_last_name']),
                'billing_email'      => sanitize_email($_POST['billing_email']),
                'billing_phone'      => sanitize_text_field($_POST['billing_phone']),
                'billing_address_1'  => sanitize_text_field($_POST['billing_address_1']),
                'billing_city'       => sanitize_text_field($_POST['billing_city']),
                'billing_postcode'   => sanitize_text_field($_POST['billing_postcode']),
                'billing_country'    => sanitize_text_field($_POST['billing_country']),
            ];


            $order = wc_create_order();
            foreach (WC()->cart->get_cart() as $cart_item) {
                $order->add_product($cart_item['data'], $cart_item['quantity']);
            }
            $order->set_address($billing_data, 'billing');
            $order->calculate_totals();
            $order->update_status('pending');
            $order->save();


            wp_redirect($order->get_checkout_payment_url());

            exit;
        }
    }
}
