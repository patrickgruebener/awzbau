<?php
/**
 * Enqueue the parent theme stylesheet.
 */
function vantage_child_enqueue_parent_style() {
    wp_enqueue_style( 'vantage-parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'vantage_child_enqueue_parent_style', 8 );

/**
 * STEC v5: Open events on their single page instead of inline.
 */
add_filter('stec_shortcode_atts', function ($atts) {
    $atts['calendar__open_events_in'] = '_self';
    return $atts;
});

/**
 * STEC v5: Keep /lehrgang/ as URL slug for events (was default in v3).
 */
add_filter('stec_default_settings', function ($settings) {
    $settings['pages']['events_page_slug'] = 'lehrgang';
    return $settings;
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

/**
 * Override STEC v5 calendar accent colors with site primary green.
 * Uses CSS custom properties instead of v3 selector overrides.
 */
add_action('wp_head', function () {
    ?>
    <style>
        .stec {
            --stec-top-menu-bg-active-primary: #639582;
            --stec-top-menu-bg-active-secondary: #527a6b;
        }
    </style>
    <?php
}, 999);
