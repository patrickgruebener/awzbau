/**
 * AWZ Checkout Cart Controls
 * Enables quantity update and item removal directly from the checkout review table.
 */
(function ( $ ) {
	'use strict';

	/**
	 * Update cart item quantity via AJAX, then trigger WooCommerce checkout refresh.
	 *
	 * @param {string} cartItemKey
	 * @param {number} quantity
	 */
	function updateQty( cartItemKey, quantity ) {
		if ( quantity < 1 ) {
			removeItem( cartItemKey );
			return;
		}

		$.ajax( {
			type: 'POST',
			url: awzCheckout.ajax_url,
			data: {
				action:        'awz_update_checkout_qty',
				cart_item_key: cartItemKey,
				quantity:      quantity,
				security:      awzCheckout.nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					$( 'body' ).trigger( 'update_checkout' );
				}
			},
		} );
	}

	/**
	 * Remove item from cart via AJAX.
	 * If cart becomes empty, redirect to the event page (or shop).
	 *
	 * @param {string} cartItemKey
	 */
	function removeItem( cartItemKey ) {
		$.ajax( {
			type: 'POST',
			url: awzCheckout.ajax_url,
			data: {
				action:        'awz_remove_checkout_item',
				cart_item_key: cartItemKey,
				security:      awzCheckout.nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					if ( response.data && response.data.redirect ) {
						window.location.href = response.data.redirect;
					} else {
						$( 'body' ).trigger( 'update_checkout' );
					}
				}
			},
		} );
	}

	// Quantity minus button.
	$( document ).on( 'click', '.awz-qty-minus', function () {
		var key     = $( this ).data( 'cart-item-key' );
		var valueEl = $( this ).siblings( '.awz-qty-value' );
		var current = parseInt( valueEl.text(), 10 ) || 1;
		var next    = current - 1;

		if ( next < 1 ) {
			removeItem( key );
			return;
		}

		valueEl.text( next );
		updateQty( key, next );
	} );

	// Quantity plus button.
	$( document ).on( 'click', '.awz-qty-plus', function () {
		var key     = $( this ).data( 'cart-item-key' );
		var valueEl = $( this ).siblings( '.awz-qty-value' );
		var current = parseInt( valueEl.text(), 10 ) || 1;
		var next    = current + 1;

		valueEl.text( next );
		updateQty( key, next );
	} );

	// Remove item button.
	$( document ).on( 'click', '.awz-remove-item', function ( e ) {
		e.preventDefault();
		removeItem( $( this ).data( 'cart-item-key' ) );
	} );

	// Re-render qty values after WooCommerce refreshes the order review table.
	$( document ).on( 'updated_checkout', function () {
		// Values are re-rendered from PHP after each update_checkout, nothing extra needed.
	} );

} )( jQuery );
