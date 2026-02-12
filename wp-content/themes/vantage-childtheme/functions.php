<?php
/**
 * Enqueue the parent theme stylesheet.
 */
function vantage_child_enqueue_parent_style() {
    wp_enqueue_style( 'vantage-parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'vantage_child_enqueue_parent_style', 8 );

add_action('stec_after_html', function() {
    ?>
    <script type="text/javascript">
        (function ($) {
            $.stecExtend(function (m) {
                m.eventsHandler.eventToggle = function (e) {
                    window.open(e.data('permalink'), '_self');
                    return false;
                };
            });
        })(window.jQuery);
    </script>
    <?php
});

function vantage_child_alter_page_setting_defaults( $defaults, $type, $id ) {
	$defaults['layout'] = 'no-sidebar';

	return $defaults;
}
add_filter( 'siteorigin_page_settings_defaults', 'vantage_child_alter_page_setting_defaults', 15, 3 );

/**
 * Produktfoto im Checkout entfernen.
 */
add_filter( 'vantage_import_google_fonts', '__return_false' );

/**
 * Produktfoto im Checkout entfernen.
 */
add_filter( 'woocommerce_cart_item_thumbnail', '__return_false' );

/**
 * Links auf Produktseiten im Checkout entfernen.
 */
function sv_remove_cart_product_link( $product_link, $cart_item, $cart_item_key ) {
    $product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
    return $product->get_title();
}
add_filter( 'woocommerce_cart_item_name', 'sv_remove_cart_product_link', 10, 3 );

/**
 *Links auf Produktseiten auf der Bestellbestätigung-Seite entfernen
  */
add_filter( 'woocommerce_order_item_permalink', '__return_false' );