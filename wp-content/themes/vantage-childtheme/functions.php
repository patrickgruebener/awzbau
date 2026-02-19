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
    // Umbrella events span months; allow ticket purchase while event is "in progress".
    $settings['booking']['allow_inprogress'] = 1;
    return $settings;
});

/**
 * STEC ticket CTA label and error messages on event pages.
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

		// Replace "Ticketverkauf" with "Lehrgangsbuchung" in all STEC messages.
		if ( false !== strpos( $translation, 'Ticketverkauf' ) ) {
			return str_replace( 'Ticketverkauf', 'Lehrgangsbuchung', $translation );
		}

		return $translation;
	},
	20,
	3
);

/**
 * German fallback labels for WooCommerce checkout/thankyou pages.
 */
function awz_wc_booking_gettext_fallback_de( $translation, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translation;
	}

	static $map = array(
		'Thank you. Your order has been received.' => 'Vielen Dank. Deine Bestellung ist eingegangen.',
		'Order details'                            => 'Bestelldetails',
		'Order received'                           => 'Bestellung eingegangen',
		'Billing address'                          => 'Rechnungsadresse',
		'Shipping address'                         => 'Lieferadresse',
		'Billing details'                          => 'Rechnungsdetails',
		'Your order'                               => 'Deine Bestellung',
		'Place order'                              => 'Zahlungspflichtig buchen',
		'Proceed to checkout'                      => 'Zur Kasse',
		'Product'                                  => 'Produkt',
		'Total'                                    => 'Gesamt',
		'Subtotal:'                                => 'Zwischensumme:',
		'Payment method:'                          => 'Zahlungsart:',
		'Payment method'                           => 'Zahlungsart',
		'ORDER NUMBER:'                            => 'BESTELLNUMMER:',
		'DATE:'                                    => 'DATUM:',
		'EMAIL:'                                   => 'E-MAIL:',
		'TOTAL:'                                   => 'GESAMT:',
		'PAYMENT METHOD:'                          => 'ZAHLUNGSART:',
	);

	if ( isset( $map[ $text ] ) ) {
		return $map[ $text ];
	}

	return $translation;
}
add_filter( 'gettext', 'awz_wc_booking_gettext_fallback_de', 20, 3 );

/**
 * Disable STEC QR-code generation globally.
 */
add_filter( 'stec_qrcode_disabled', '__return_true', 99 );

/**
 * Remove STEC QR rendering from order details and emails.
 */
function awz_disable_stec_order_qr_output() {
	if ( ! class_exists( 'Stachethemes\\Stec\\Booking' ) ) {
		return;
	}

	$booking_class = 'Stachethemes\\Stec\\Booking';

	remove_action( 'woocommerce_order_details_after_order_table', array( $booking_class, 'filter_add_order_qrcode' ), 10 );
	remove_action( 'woocommerce_email_after_order_table', array( $booking_class, 'filter_add_order_qrcode' ), 10 );
	remove_filter( 'woocommerce_display_item_meta', array( $booking_class, 'filter_add_order_item_qrcode' ), 10 );
}
add_action( 'plugins_loaded', 'awz_disable_stec_order_qr_output', 20 );

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
 * Skip cart: redirect to checkout on STEC event pages (PHP fallback for non-AJAX).
 */
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
	if ( is_singular( 'stec_event' ) ) {
		return wc_get_checkout_url();
	}
	return $url;
} );

/**
 * Skip cart: JS redirect after AJAX add-to-cart on event pages.
 * Activates ONLY after "Buchen" button is clicked.
 */
add_action( 'wp_footer', function () {
	if ( ! is_singular( 'stec_event' ) ) {
		return;
	}
	$checkout_url = wc_get_checkout_url();
	?>
	<script>
	(function() {
		var redirectTriggered = false;
		var watchingForRedirect = false;
		var initialCartCount = null;

		function getCartCount() {
			var cartCountEl = document.querySelector( '.cart-contents-count, .woocommerce-mini-cart__quantity, .cart-count' );
			if ( cartCountEl ) {
				return parseInt( cartCountEl.textContent ) || 0;
			}
			return null;
		}

		function triggerRedirect() {
			if ( ! redirectTriggered ) {
				redirectTriggered = true;
				console.log( 'AWZ: Redirecting to checkout...' );
				setTimeout( function() {
					window.location.href = '<?php echo esc_js( $checkout_url ); ?>';
				}, 300 );
			}
		}

		function startWatching() {
			if ( watchingForRedirect ) {
				return;
			}
			watchingForRedirect = true;
			initialCartCount = getCartCount();
			console.log( 'AWZ: Started watching. Initial cart count:', initialCartCount );

			// Method 1: Watch for STEC toast/notification.
			var observer = new MutationObserver( function( mutations ) {
				if ( ! watchingForRedirect || redirectTriggered ) {
					return;
				}
				mutations.forEach( function( mutation ) {
					mutation.addedNodes.forEach( function( node ) {
						if ( node.nodeType === 1 ) {
							var isToast = node.className && (
								node.className.indexOf( 'stec-toast' ) !== -1 ||
								node.className.indexOf( 'stec-notification' ) !== -1 ||
								node.className.indexOf( 'woocommerce-message' ) !== -1 ||
								( node.textContent && node.textContent.indexOf( 'Warenkorb' ) !== -1 )
							);
							if ( isToast ) {
								console.log( 'AWZ: Toast detected' );
								triggerRedirect();
							}
						}
					} );
				} );
			} );

			observer.observe( document.body, {
				childList: true,
				subtree: true
			} );

			// Method 2: Poll cart count.
			var pollCount = 0;
			var pollInterval = setInterval( function() {
				if ( redirectTriggered ) {
					clearInterval( pollInterval );
					return;
				}

				pollCount++;
				var currentCount = getCartCount();
				if ( initialCartCount !== null && currentCount !== null && currentCount > initialCartCount ) {
					console.log( 'AWZ: Cart count increased from', initialCartCount, 'to', currentCount );
					clearInterval( pollInterval );
					triggerRedirect();
				}

				// Stop after 20 polls (10 seconds).
				if ( pollCount >= 20 ) {
					clearInterval( pollInterval );
					console.log( 'AWZ: Stopped watching (timeout)' );
				}
			}, 500 );
		}

		// Listen for clicks on "Buchen" buttons.
		document.addEventListener( 'click', function( e ) {
			var target = e.target;
			// Check if clicked element or parent is a "Buchen" button.
			var buchenButton = target.closest( 'button, a, .stec-button' );
			if ( buchenButton && buchenButton.textContent && buchenButton.textContent.indexOf( 'Buchen' ) !== -1 ) {
				console.log( 'AWZ: "Buchen" button clicked' );
				startWatching();
			}
		}, true );
	})();
	</script>
	<?php
}, 20 );

/**
 * Override STEC "Add to cart" → "Buchen" in JS translations (React components).
 */
add_filter( 'pre_load_script_translations', function ( $translations, $file, $handle, $domain ) {
	if ( 'stec' !== $domain ) {
		return $translations;
	}

	// Load from file if not yet provided.
	if ( null === $translations && ! empty( $file ) && is_readable( $file ) ) {
		$translations = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	if ( ! $translations ) {
		return $translations;
	}

	$data = json_decode( $translations, true );
	if ( ! isset( $data['locale_data']['messages'] ) ) {
		return $translations;
	}

	// Two JED keys for the "Add to cart" button in STEC v5.
	$data['locale_data']['messages']['Add to cart']                                           = array( 'Buchen' );
	$data['locale_data']['messages']["Event preview add to cart button text\x04Add to cart"] = array( 'Buchen' );

	// Replace "Ticketverkauf" with "Lehrgangsbuchung" in all message strings.
	foreach ( $data['locale_data']['messages'] as $key => $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $i => $str ) {
				if ( is_string( $str ) && false !== strpos( $str, 'Ticketverkauf' ) ) {
					$data['locale_data']['messages'][ $key ][ $i ] = str_replace( 'Ticketverkauf', 'Lehrgangsbuchung', $str );
				}
			}
		}
	}

	return wp_json_encode( $data );
}, 99, 4 );

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
