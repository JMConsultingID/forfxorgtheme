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
            // Ambil data billing
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

            // Buat order baru
            $order = wc_create_order();

            // Tambahkan data billing ke order
            foreach ($billing_data as $key => $value) {
                $order->set_address([$key => $value], 'billing');
            }

            // Tambahkan produk dari cart ke order
            foreach (WC()->cart->get_cart() as $cart_item) {
                $order->add_product($cart_item['data'], $cart_item['quantity']);
            }

            // Set status order menjadi pending payment
            $order->set_status('pending');
            $order->save();

            // Redirect ke halaman order pay
            wp_redirect($order->get_checkout_payment_url());
            exit;
        }
    }
}
