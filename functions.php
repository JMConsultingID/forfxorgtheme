<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/forfx-elementor-theme/
 *
 * @package ForfxTheme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('FORFX_THEME_VERSION', '2.1.9');

/**
 * Load forfx theme scripts & styles.
 *
 * @return void
 */
// require_once get_stylesheet_directory() . '/inc/functions/forfx-theme-functions.php';

function forfx_theme_scripts_styles()
{
    wp_enqueue_style('forfx-theme-style', get_stylesheet_directory_uri() . '/style.css', [], FORFX_THEME_VERSION);
    wp_enqueue_style('forfx-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/forfx-theme.css', [], FORFX_THEME_VERSION);
    wp_enqueue_script('forfx-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/forfx-theme.js', [], FORFX_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'forfx_theme_scripts_styles', 20);

// Membatasi Cart ke Satu Produk dengan Kuantitas 1
add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $quantity, $variation_id, $variation) {
    WC()->cart->empty_cart(); // Kosongkan keranjang sebelum menambahkan produk baru
}, 10, 5);

add_filter('woocommerce_add_cart_item_quantity', function ($quantity, $cart_item_key) {
    return 1; // Pastikan hanya 1 kuantitas
}, 10, 2);

// Menampilkan Form Billing Saja di Halaman Checkout
add_filter('woocommerce_checkout_fields', function ($fields) {
    unset($fields['shipping']); // Hapus field pengiriman
    return $fields;
});

add_action('woocommerce_before_checkout_form', function () {
    remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10); // Hapus daftar produk
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20); // Hapus metode pembayaran
}, 1);

// Mengubah Tombol "Place Order" menjadi "Next"
add_filter('woocommerce_order_button_text', function () {
    return 'Next'; // Ubah label tombol
});

// Membuat Pesanan saat Tombol "Next" Ditekan
add_action('template_redirect', function () {
    if (isset($_POST['woocommerce_checkout_place_order']) && is_checkout()) {
        // Buat pesanan dengan status Pending Payment
        $order = wc_create_order();
        $cart_items = WC()->cart->get_cart();

        // Tambahkan item dari keranjang ke pesanan
        foreach ($cart_items as $cart_item) {
            $order->add_product(
                wc_get_product($cart_item['product_id']),
                $cart_item['quantity']
            );
        }

        // Tetapkan billing details
        $billing_data = WC()->customer->get_billing();
        foreach ($billing_data as $key => $value) {
            $order->set_billing($key, $value);
        }

        // Simpan pesanan
        $order->calculate_totals();
        $order->update_status('pending', 'Order created via custom multi-step checkout flow.');

        // Kosongkan keranjang dan arahkan ke halaman pembayaran
        WC()->cart->empty_cart();
        wp_redirect($order->get_checkout_payment_url());
        exit;
    }
});

// Menampilkan Halaman Payment Order (Default WooCommerce)
add_filter('woocommerce_order_button_text', function () {
    return __('Pay Now', 'woocommerce'); // Tombol pembayaran pada halaman order-pay
});

// Penyesuaian URL Checkout untuk Parameter add-to-cart
add_action('template_redirect', function () {
    if (isset($_GET['add-to-cart']) && is_checkout()) {
        $product_id = absint($_GET['add-to-cart']);
        if ($product_id) {
            WC()->cart->add_to_cart($product_id);
            wp_redirect(wc_get_checkout_url()); // Arahkan ke halaman checkout
            exit;
        }
    }
});

// Membersihkan Data Checkout Setelah Pesanan Dibuat
add_action('woocommerce_checkout_order_processed', function ($order_id, $posted_data, $order) {
    WC()->cart->empty_cart(); // Kosongkan keranjang setelah pesanan dibuat
}, 10, 3);
