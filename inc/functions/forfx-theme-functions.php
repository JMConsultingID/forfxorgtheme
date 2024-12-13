<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package ForfxTheme
 */

// Disable add to cart messages
function forfx_theme_setup_single_product_checkout_mode() {
        // Disable add to cart messages
        add_filter( 'wc_add_to_cart_message_html', '__return_false' );        
        // Empty the cart before adding a new product
        add_filter( 'woocommerce_add_cart_item_data', '_forfx_theme_additional_empty_cart' );        
        // Redirect to the checkout page after adding the product to the cart
        add_filter( 'woocommerce_add_to_cart_redirect', 'forfx_theme_additional_add_to_cart_redirect' );
        // Check if there are more than 1 product in cart at checkout
        add_action( 'woocommerce_before_checkout_form', 'forfx_theme_check_for_multiple_products' );
}
add_action( 'init', 'forfx_theme_setup_single_product_checkout_mode' );

// Function to empty the cart before adding a new product
function _forfx_theme_additional_empty_cart( $cart_item_data ) {
    WC()->cart->empty_cart(); // Clear cart before adding a new product
    return $cart_item_data; // Proceed with adding the new product
}

// Function to redirect to the checkout page after product is added
function forfx_theme_additional_add_to_cart_redirect() {
    return wc_get_checkout_url(); // Redirect to checkout
}

// Check for multiple products and display notice with refresh button
function forfx_theme_check_for_multiple_products() {
    if ( WC()->cart->get_cart_contents_count() > 1 ) {
        // Display notice
        wc_print_notice( __( 'Only 1 product can be checked out at a time. Please refresh the cart to keep only the last product.', 'hello-theme' ), 'error' );
        
        // Display refresh button
        echo '<form method="post">';
        echo '<button type="submit" name="refresh_cart" class="button">' . __( 'Refresh Cart', 'hello-theme' ) . '</button>';
        echo '</form>';
        
        // If refresh button is pressed, keep only the last added product
        if ( isset( $_POST['refresh_cart'] ) ) {
            forfx_theme_refresh_cart_keep_last_product();
        }
    }
}

// Function to refresh the cart and keep only the last product
function forfx_theme_refresh_cart_keep_last_product() {
    $cart_items = WC()->cart->get_cart();
    
    // Get the last added product key
    $last_product_key = array_key_last( $cart_items );
    
    // Clear the cart
    WC()->cart->empty_cart();
    
    // Add back the last product to the cart
    $last_product = $cart_items[$last_product_key];
    WC()->cart->add_to_cart( $last_product['product_id'], $last_product['quantity'], $last_product['variation_id'], $last_product['variation'], $last_product['cart_item_data'] );
    
    // Refresh the page
    wp_safe_redirect( wc_get_checkout_url() );
    exit;
}

// Disable order notes field
add_filter('woocommerce_enable_order_notes_field', '__return_false');

// Change order button text
add_filter( 'woocommerce_order_button_text', 'forfx_theme_wc_custom_order_button_text' );
function forfx_theme_wc_custom_order_button_text() {
    return __( 'PROCEED TO PAYMENT', 'woocommerce' );
}
add_filter( 'woocommerce_checkout_fields' , 'forfx_theme_modify_woocommerce_billing_fields' );

function forfx_theme_modify_woocommerce_billing_fields( $fields ) {
    $fields['billing']['billing_email']['priority'] = 5;
    return $fields;
}


add_action('woocommerce_checkout_process', 'forfx_theme_create_pending_order');
function forfx_theme_create_pending_order() {
    if (!empty($_POST['billing_first_name'])) {
        $order = wc_create_order();


        foreach (WC()->cart->get_cart() as $cart_item) {
            $order->add_product($cart_item['data'], $cart_item['quantity']);
        }

        $order->set_address($_POST, 'billing');


        $order->set_status('pending');
        $order->save();

        wp_redirect(site_url('/order-pay/') . '?order_id=' . $order->get_id());
        exit;
    }
}

add_action('init', 'forfx_theme_add_order_pay_endpoint');
function forfx_theme_add_order_pay_endpoint() {
    add_rewrite_rule('^order-pay/?', 'index.php?order_pay=1', 'top');
}

add_filter('query_vars', function ($vars) {
    $vars[] = 'order_pay';
    return $vars;
});

add_action('template_redirect', 'forfx_theme_load_order_payment_page');
function forfx_theme_load_order_payment_page() {
    if (get_query_var('order_pay')) {
        include get_stylesheet_directory() . '/woocommerce/checkout/form-payment.php';
        exit;
    }
}
