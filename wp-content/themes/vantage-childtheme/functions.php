<?php
/**
 * Enqueue the parent theme stylesheet.
 */
function vantage_child_enqueue_parent_style() {
    wp_enqueue_style( 'vantage-parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'vantage_child_enqueue_parent_style', 8 );

/**
 * Load child theme STEC helper modules.
 */
$awz_stec_i18n_fallback = get_stylesheet_directory() . '/inc/stec-i18n-fallback.php';
if ( file_exists( $awz_stec_i18n_fallback ) ) {
	require_once $awz_stec_i18n_fallback;
}

$awz_stec_booking_repair = get_stylesheet_directory() . '/inc/stec-booking-repair.php';
if ( file_exists( $awz_stec_booking_repair ) ) {
	require_once $awz_stec_booking_repair;
}

/**
 * STEC v5: Open events on their single page instead of inline.
 */
add_filter('stec_shortcode_atts', function ($atts) {
    $atts['calendar__open_events_in'] = 'single';
    $atts['calendar__links_target'] = '_self';
    return $atts;
});

/**
 * STEC v5: Keep /lehrgang/ as URL slug for events (was default in v3).
 */
add_filter('stec_default_settings', function ($settings) {
    $settings['pages']['events_page_slug'] = 'lehrgang';
    return $settings;
});

/**
 * STEC ticket CTA label on event pages.
 */
add_filter(
	'gettext',
	function ( $translation, $text, $domain ) {
		if ( 'stec' !== $domain ) {
			return $translation;
		}

		if ( 'Add to cart' === $text ) {
			return 'Buchen';
		}

		return $translation;
	},
	20,
	3
);

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
 * Load legacy-aligned STEC styles.
 */
function awz_stec_enqueue_legacy_styles() {
	$style_path = get_stylesheet_directory() . '/assets/css/stec-single-legacy.css';

	if ( ! file_exists( $style_path ) ) {
		return;
	}

	wp_enqueue_style(
		'awz-stec-single-legacy',
		get_stylesheet_directory_uri() . '/assets/css/stec-single-legacy.css',
		array( 'vantage-parent-style' ),
		filemtime( $style_path )
	);
}
add_action( 'wp_enqueue_scripts', 'awz_stec_enqueue_legacy_styles', 20 );

/**
 * Load STEC single-page tab overrides.
 */
function awz_stec_enqueue_single_tab_overrides() {
	if ( ! is_singular( 'stec_event' ) ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/stec-single-tabs.js';

	if ( ! file_exists( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'awz-stec-single-tabs',
		get_stylesheet_directory_uri() . '/assets/js/stec-single-tabs.js',
		array(),
		filemtime( $script_path ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'awz_stec_enqueue_single_tab_overrides', 25 );
