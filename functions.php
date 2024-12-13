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
require_once get_stylesheet_directory() . '/inc/functions/forfx-theme-functions.php';

function forfx_theme_scripts_styles()
{
    wp_enqueue_style('forfx-theme-style', get_stylesheet_directory_uri() . '/style.css', [], FORFX_THEME_VERSION);
    wp_enqueue_style('forfx-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/forfx-theme.css', [], FORFX_THEME_VERSION);
    wp_enqueue_script('forfx-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/forfx-theme.js', [], FORFX_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'forfx_theme_scripts_styles', 20);

function create_custom_order_without_product() {
    // Create a new order
    $order = wc_create_order();

    // Add a custom product/item to the order
    $item = new WC_Order_Item_Product();
    $item->set_name('Test Custom Item Name'); // Custom item name
    $item->set_quantity(1);
    $item->set_total(100); // Set the item price (e.g., $100)
    $order->add_item($item);

    // Set billing information (hardcoded for testing purposes)
    $order->set_billing_first_name('John');
    $order->set_billing_last_name('Doe');
    $order->set_billing_email('john.doe@example.com');
    $order->set_billing_address_1('123 Example Street');
    $order->set_billing_city('City');
    $order->set_billing_postcode('12345');
    $order->set_billing_country('US');

    // Set order status to pending payment
    $order->set_status('pending', 'Awaiting payment.');

    // Save the order
    $order->calculate_totals();
    $order->save();

    return $order->get_id();
}

function custom_order_shortcode_handler() {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
        $order_id = create_custom_order_without_product();

        if ($order_id) {
            // Redirect to the Order Pay page
            $order = wc_get_order($order_id);
            $redirect_url = $order->get_checkout_payment_url();

            // Use PHP header redirection
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            return '<p>Failed to create the order. Please try again.</p>';
        }
    }

    // Display the form
    $html = '<form method="POST">';
    $html .= '<button type="submit" name="create_order">Create Order</button>';
    $html .= '</form>';

    return $html;
}

// Register the shortcode
add_shortcode('create_custom_order', 'custom_order_shortcode_handler');
