<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'awz_stec_booking_repair_normalize_ids' ) ) {
	/**
	 * Normalize mixed ID input into unique, ordered integer IDs.
	 *
	 * @param mixed $raw Raw ID container.
	 * @return array<int>
	 */
	function awz_stec_booking_repair_normalize_ids( $raw ) {
		if ( is_string( $raw ) ) {
			$maybe_unserialized = maybe_unserialize( $raw );
			if ( $maybe_unserialized !== $raw ) {
				$raw = $maybe_unserialized;
			} else {
				$decoded = json_decode( $raw, true );
				if ( is_array( $decoded ) ) {
					$raw = $decoded;
				} else {
					$raw = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
				}
			}
		}

		if ( is_object( $raw ) ) {
			$raw = get_object_vars( $raw );
		}

		if ( ! is_array( $raw ) ) {
			$raw = array( $raw );
		}

		$normalized = array();
		$seen       = array();

		foreach ( $raw as $value ) {
			$id = absint( $value );
			if ( $id <= 0 || isset( $seen[ $id ] ) ) {
				continue;
			}

			$seen[ $id ]   = true;
			$normalized[] = $id;
		}

		return $normalized;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_get_events_by_uid' ) ) {
	/**
	 * Collect STEC events by UID for a given status.
	 *
	 * @param string $status Event post status.
	 * @return array<string,int>
	 */
	function awz_stec_booking_repair_get_events_by_uid( $status ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT p.ID AS event_id, pm.meta_value AS uid
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE p.post_type = %s
			AND p.post_status = %s
			AND pm.meta_value <> ''",
			'uid',
			'stec_event',
			$status
		);

		$rows = $wpdb->get_results( $query, ARRAY_A );
		if ( empty( $rows ) ) {
			return array();
		}

		$map = array();
		foreach ( $rows as $row ) {
			$uid = isset( $row['uid'] ) ? (string) $row['uid'] : '';
			$id  = isset( $row['event_id'] ) ? absint( $row['event_id'] ) : 0;
			if ( '' === $uid || $id <= 0 ) {
				continue;
			}

			$map[ $uid ] = $id;
		}

		return $map;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_get_source_ids' ) ) {
	/**
	 * Read legacy source products for a legacy event.
	 *
	 * @param int $legacy_event_id Legacy event ID.
	 * @return array{source_key:string,ids:array<int>}
	 */
	function awz_stec_booking_repair_get_source_ids( $legacy_event_id ) {
		$bookable_products = awz_stec_booking_repair_normalize_ids( get_post_meta( $legacy_event_id, 'bookable_products', true ) );
		if ( ! empty( $bookable_products ) ) {
			return array(
				'source_key' => 'bookable_products',
				'ids'        => $bookable_products,
			);
		}

		$products = awz_stec_booking_repair_normalize_ids( get_post_meta( $legacy_event_id, 'products', true ) );

		return array(
			'source_key' => 'products',
			'ids'        => $products,
		);
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_split_valid_product_ids' ) ) {
	/**
	 * Split source IDs into valid and invalid WooCommerce products.
	 *
	 * @param array<int> $source_ids Source IDs.
	 * @return array{0:array<int>,1:array<int>}
	 */
	function awz_stec_booking_repair_split_valid_product_ids( $source_ids ) {
		$valid   = array();
		$invalid = array();

		foreach ( $source_ids as $product_id ) {
			$post_type   = get_post_type( $product_id );
			$post_status = get_post_status( $product_id );

			if ( 'product' === $post_type && 'publish' === $post_status ) {
				$valid[] = $product_id;
			} else {
				$invalid[] = $product_id;
			}
		}

		return array( $valid, $invalid );
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_get_target_state' ) ) {
	/**
	 * Read target booking/ticket meta state from publish event.
	 *
	 * @param int $event_id Publish event ID.
	 * @return array{products:array<int>,tickets:array<int>,primary_ticket:int}
	 */
	function awz_stec_booking_repair_get_target_state( $event_id ) {
		return array(
			'products'      => awz_stec_booking_repair_normalize_ids( get_post_meta( $event_id, 'products', false ) ),
			'tickets'       => awz_stec_booking_repair_normalize_ids( get_post_meta( $event_id, 'tickets', false ) ),
			'primary_ticket' => absint( get_post_meta( $event_id, 'primary_ticket', true ) ),
		);
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_is_synced' ) ) {
	/**
	 * Compare current target state against expected source values.
	 *
	 * @param array $target_state Current target state.
	 * @param array $expected_ids Expected product IDs.
	 * @return bool
	 */
	function awz_stec_booking_repair_is_synced( $target_state, $expected_ids ) {
		$expected_primary = empty( $expected_ids ) ? 0 : (int) $expected_ids[0];
		$target_primary   = isset( $target_state['primary_ticket'] ) ? (int) $target_state['primary_ticket'] : 0;

		return $target_state['products'] === $expected_ids
			&& $target_state['tickets'] === $expected_ids
			&& $target_primary === $expected_primary;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_write_target_state' ) ) {
	/**
	 * Persist cleaned products/tickets meta into publish event.
	 *
	 * @param int        $event_id      Publish event ID.
	 * @param array<int> $product_ids   Valid product IDs.
	 * @return void
	 */
	function awz_stec_booking_repair_write_target_state( $event_id, $product_ids ) {
		delete_post_meta( $event_id, 'products' );
		delete_post_meta( $event_id, 'tickets' );
		delete_post_meta( $event_id, 'primary_ticket' );

		foreach ( $product_ids as $product_id ) {
			add_post_meta( $event_id, 'products', $product_id );
			add_post_meta( $event_id, 'tickets', $product_id );
		}

		if ( ! empty( $product_ids ) ) {
			awz_stec_booking_repair_set_products_ticket_flag( $product_ids );
			update_post_meta( $event_id, 'primary_ticket', (int) $product_ids[0] );
		}
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_get_effective_product_type' ) ) {
	/**
	 * Resolve an effective Woo product type for ticket defaults.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	function awz_stec_booking_repair_get_effective_product_type( $product_id ) {
		$type = '';

		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product && is_callable( array( $product, 'get_type' ) ) ) {
				$type = (string) $product->get_type();
			}
		}

		// STEC ticket products can use custom product types; treat unknowns like simple.
		if ( ! in_array( $type, array( 'simple', 'variable', 'variation' ), true ) ) {
			$type = 'simple';
		}

		return $type;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_normalize_stop_before_hours' ) ) {
	/**
	 * Normalize stop-before value to STEC hours unit.
	 *
	 * Legacy imports can carry seconds (e.g. 86400), while STEC expects hours.
	 *
	 * @param mixed $raw_value Raw stop-before value.
	 * @return int
	 */
	function awz_stec_booking_repair_normalize_stop_before_hours( $raw_value ) {
		if ( '' === (string) $raw_value || ! is_numeric( (string) $raw_value ) ) {
			return 0;
		}

		$value = (int) round( (float) $raw_value );
		if ( $value < 0 ) {
			return 0;
		}

		// Convert legacy second-based values to hours (e.g. 86400 => 24).
		if ( $value >= HOUR_IN_SECONDS && 0 === ( $value % HOUR_IN_SECONDS ) ) {
			return (int) ( $value / HOUR_IN_SECONDS );
		}

		return $value;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_ensure_ticket_meta_defaults' ) ) {
	/**
	 * Ensure ticket products have required STEC booking meta defaults.
	 *
	 * Missing defaults can crash STEC v5 ticket rendering in frontend JS.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	function awz_stec_booking_repair_ensure_ticket_meta_defaults( $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		$product_type = awz_stec_booking_repair_get_effective_product_type( $product_id );
		$defaults     = array(
			'stec_allow_inprogress'      => 0,
			'stec_manage_stock'          => 0,
			'stec_stop_before'           => 0,
			'stec_manage_per_order_limit' => 0,
			'stec_per_order_limit'       => -1,
		);

		if ( 'variable' === $product_type ) {
			$defaults['stec_limit_per_occurrence_combined'] = -1;
		} else {
			$defaults['stec_limit_per_occurrence'] = -1;
		}

		foreach ( $defaults as $meta_key => $meta_value ) {
			$has_meta = metadata_exists( 'post', $product_id, $meta_key );
			$current  = $has_meta ? get_post_meta( $product_id, $meta_key, true ) : '';

			if ( 'stec_stop_before' === $meta_key ) {
				$normalized = awz_stec_booking_repair_normalize_stop_before_hours( $current );

				if ( ! $has_meta || (string) $normalized !== (string) $current ) {
					update_post_meta( $product_id, $meta_key, $normalized );
				}

				continue;
			}

			if ( $has_meta ) {
				if ( '' !== (string) $current && is_numeric( (string) $current ) ) {
					continue;
				}
			}

			update_post_meta( $product_id, $meta_key, $meta_value );
		}

		if ( 'variable' !== $product_type || ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! is_callable( array( $product, 'get_children' ) ) ) {
			return;
		}

		foreach ( (array) $product->get_children() as $variation_id ) {
			$variation_id = absint( $variation_id );
			if ( $variation_id <= 0 ) {
				continue;
			}

			$variation_limit = get_post_meta( $variation_id, 'stec_limit_per_occurrence', true );
			if ( '' === (string) $variation_limit || ! is_numeric( (string) $variation_limit ) ) {
				update_post_meta( $variation_id, 'stec_limit_per_occurrence', -1 );
			}

			$variation_exclusive = get_post_meta( $variation_id, 'stec_exclusive', true );
			if ( '' === (string) $variation_exclusive || ! is_numeric( (string) $variation_exclusive ) ) {
				update_post_meta( $variation_id, 'stec_exclusive', 0 );
			}
		}
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_prepare_add_to_cart_ticket_meta' ) ) {
	/**
	 * Ensure ticket meta defaults before STEC cart validation runs.
	 *
	 * Prevents DateInterval errors when legacy products miss stec_stop_before.
	 *
	 * @param array<string,mixed> $cart_data Cart payload.
	 * @return void
	 */
	function awz_stec_booking_repair_prepare_add_to_cart_ticket_meta( $cart_data ) {
		if ( ! function_exists( 'wc_get_product' ) || ! is_array( $cart_data ) ) {
			return;
		}

		$product_id = isset( $cart_data['id'] ) ? absint( $cart_data['id'] ) : 0;
		if ( $product_id <= 0 ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$parent_id = is_callable( array( $product, 'get_parent_id' ) ) ? absint( $product->get_parent_id() ) : 0;
		if ( $parent_id <= 0 ) {
			$parent_id = $product_id;
		}

		$is_ticket = ( '1' === (string) get_post_meta( $product_id, 'stec_ticket', true ) )
			|| ( '1' === (string) get_post_meta( $parent_id, 'stec_ticket', true ) );

		if ( ! $is_ticket ) {
			return;
		}

		awz_stec_booking_repair_ensure_ticket_meta_defaults( $parent_id );

		if ( $product_id !== $parent_id ) {
			awz_stec_booking_repair_ensure_ticket_meta_defaults( $product_id );
		}
	}
}
add_action( 'stec_before_add_to_cart', 'awz_stec_booking_repair_prepare_add_to_cart_ticket_meta', 1, 1 );

if ( ! function_exists( 'awz_stec_booking_repair_set_products_ticket_flag' ) ) {
	/**
	 * Ensure products are flagged as STEC tickets.
	 *
	 * @param array<int> $product_ids Product IDs.
	 * @return int Number of changed products.
	 */
	function awz_stec_booking_repair_set_products_ticket_flag( $product_ids ) {
		$updated = 0;

		foreach ( $product_ids as $product_id ) {
			$product_id = absint( $product_id );
			if ( $product_id <= 0 ) {
				continue;
			}

			$current = (string) get_post_meta( $product_id, 'stec_ticket', true );
			if ( '1' !== $current ) {
				update_post_meta( $product_id, 'stec_ticket', 1 );
				$updated++;
			}

			awz_stec_booking_repair_ensure_ticket_meta_defaults( $product_id );
		}

		return $updated;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_meta_data_find' ) ) {
	/**
	 * Find a key/value entry in REST meta_data payload.
	 *
	 * @param array<int,mixed> $meta_data Meta data array.
	 * @param string           $meta_key  Meta key.
	 * @return array{found:bool,value:string}
	 */
	function awz_stec_booking_repair_meta_data_find( $meta_data, $meta_key ) {
		foreach ( $meta_data as $item ) {
			$key   = '';
			$value = '';

			if ( is_object( $item ) ) {
				$key   = isset( $item->key ) ? (string) $item->key : '';
				$value = isset( $item->value ) ? (string) $item->value : '';
			} elseif ( is_array( $item ) ) {
				$key   = isset( $item['key'] ) ? (string) $item['key'] : '';
				$value = isset( $item['value'] ) ? (string) $item['value'] : '';
			}

			if ( $meta_key === $key ) {
				return array(
					'found' => true,
					'value' => $value,
				);
			}
		}

		return array(
			'found' => false,
			'value' => '',
		);
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_meta_data_append_default' ) ) {
	/**
	 * Append default key/value to REST meta_data only when missing.
	 *
	 * @param array<int,mixed> $meta_data Meta data array.
	 * @param string           $meta_key  Meta key.
	 * @param int              $value     Default value.
	 * @return array<int,mixed>
	 */
	function awz_stec_booking_repair_meta_data_append_default( $meta_data, $meta_key, $value ) {
		$existing = awz_stec_booking_repair_meta_data_find( $meta_data, $meta_key );
		if ( $existing['found'] ) {
			return $meta_data;
		}

		$meta_data[] = array(
			'id'    => 0,
			'key'   => $meta_key,
			'value' => (string) $value,
		);

		return $meta_data;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_meta_data_set_value' ) ) {
	/**
	 * Set an existing meta_data value by key, append if missing.
	 *
	 * @param array<int,mixed> $meta_data Meta data array.
	 * @param string           $meta_key  Meta key.
	 * @param int|string       $value     Meta value.
	 * @return array<int,mixed>
	 */
	function awz_stec_booking_repair_meta_data_set_value( $meta_data, $meta_key, $value ) {
		$updated = false;

		foreach ( $meta_data as $index => $item ) {
			$key = '';

			if ( is_object( $item ) ) {
				$key = isset( $item->key ) ? (string) $item->key : '';
				if ( $meta_key === $key ) {
					$meta_data[ $index ]->value = (string) $value;
					$updated                    = true;
					break;
				}
			} elseif ( is_array( $item ) ) {
				$key = isset( $item['key'] ) ? (string) $item['key'] : '';
				if ( $meta_key === $key ) {
					$meta_data[ $index ]['value'] = (string) $value;
					$updated                      = true;
					break;
				}
			}
		}

		if ( ! $updated ) {
			$meta_data[] = array(
				'id'    => 0,
				'key'   => $meta_key,
				'value' => (string) $value,
			);
		}

		return $meta_data;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_fix_ticket_payload_meta' ) ) {
	/**
	 * Guard STEC REST ticket payload against missing limit meta defaults.
	 *
	 * @param array<string,mixed> $data Product payload data.
	 * @return array<string,mixed>
	 */
	function awz_stec_booking_repair_fix_ticket_payload_meta( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$meta_data = isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ? $data['meta_data'] : array();
		$ticket    = awz_stec_booking_repair_meta_data_find( $meta_data, 'stec_ticket' );

		if ( ! $ticket['found'] || '1' !== (string) $ticket['value'] ) {
			return $data;
		}

		$type = isset( $data['type'] ) ? (string) $data['type'] : '';

		$stop_before = awz_stec_booking_repair_meta_data_find( $meta_data, 'stec_stop_before' );
		if ( $stop_before['found'] ) {
			$normalized_stop_before = awz_stec_booking_repair_normalize_stop_before_hours( $stop_before['value'] );
			if ( (string) $normalized_stop_before !== (string) $stop_before['value'] ) {
				$meta_data = awz_stec_booking_repair_meta_data_set_value( $meta_data, 'stec_stop_before', $normalized_stop_before );
			}
		}

		if ( 'variable' === $type ) {
			$meta_data = awz_stec_booking_repair_meta_data_append_default( $meta_data, 'stec_limit_per_occurrence_combined', -1 );
		} else {
			$meta_data = awz_stec_booking_repair_meta_data_append_default( $meta_data, 'stec_limit_per_occurrence', -1 );
		}

		$meta_data = awz_stec_booking_repair_meta_data_append_default( $meta_data, 'stec_manage_per_order_limit', 0 );
		$meta_data = awz_stec_booking_repair_meta_data_append_default( $meta_data, 'stec_per_order_limit', -1 );

		// Force allow_inprogress=1 so umbrella events (start date in the past) don't
		// block ticket sales for future course occurrences.
		$meta_data = awz_stec_booking_repair_meta_data_set_value( $meta_data, 'stec_allow_inprogress', 1 );

		$data['meta_data'] = $meta_data;
		return $data;
	}
}
add_filter( 'stec_get_product_data', 'awz_stec_booking_repair_fix_ticket_payload_meta', 10, 1 );

if ( ! function_exists( 'awz_stec_ticket_flag_repair_run' ) ) {
	/**
	 * Mark virtual WooCommerce products as STEC tickets.
	 *
	 * @param bool $apply Whether to write changes.
	 * @return array<string,mixed>
	 */
	function awz_stec_ticket_flag_repair_run( $apply = false ) {
		$args = array(
			'post_type'              => 'product',
			'post_status'            => array( 'publish', 'private' ),
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => '_virtual',
					'value' => 'yes',
				),
			),
		);

		$product_ids = get_posts( $args );
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		$report = array(
			'apply'                => (bool) $apply,
			'virtual_products'     => count( $product_ids ),
			'already_ticket'       => 0,
			'needs_ticket_flag'    => 0,
			'updated_products'     => 0,
			'rows'                 => array(),
		);

		foreach ( $product_ids as $product_id ) {
			$title       = get_the_title( $product_id );
			$ticket_flag = (string) get_post_meta( $product_id, 'stec_ticket', true );
			$is_ticket   = ( '1' === $ticket_flag );

			$row = array(
				'product_id'   => (int) $product_id,
				'title'        => $title ? $title : '(ohne Titel)',
				'ticket_state' => $is_ticket ? 'already_ticket' : 'needs_flag',
			);

			if ( $is_ticket ) {
				$report['already_ticket']++;
			} else {
				$report['needs_ticket_flag']++;
			}

			$report['rows'][] = $row;
		}

		if ( $apply && ! empty( $product_ids ) ) {
			$to_update = array_values(
				array_map(
					'absint',
					$product_ids
				)
			);

			$report['updated_products'] = awz_stec_booking_repair_set_products_ticket_flag( $to_update );
		}

		return $report;
	}
}

if ( ! function_exists( 'awz_stec_title_link_normalize' ) ) {
	/**
	 * Normalize title for matching.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	function awz_stec_title_link_normalize( $title ) {
		$title = html_entity_decode( (string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$title = remove_accents( $title );
		$title = mb_strtolower( $title );
		$title = str_replace( array( '–', '—', '−' ), '-', $title );
		$title = str_replace( '-', ' ', $title );
		$title = preg_replace( '/\b\d{1,2}\.\d{1,2}\.\d{4}\b/u', ' ', $title );
		$title = str_replace( array( '*', '&', '/', ',', ':', ';', '(', ')' ), ' ', $title );
		$title = preg_replace( '/\s+/u', ' ', $title );
		return trim( (string) $title );
	}
}

if ( ! function_exists( 'awz_stec_title_link_normalize_strict' ) ) {
	/**
	 * Normalize title but keep date fragments for deduping.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	function awz_stec_title_link_normalize_strict( $title ) {
		$title = html_entity_decode( (string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$title = remove_accents( $title );
		$title = mb_strtolower( $title );
		$title = str_replace( array( '–', '—', '−' ), '-', $title );
		$title = str_replace( '-', ' ', $title );
		$title = str_replace( array( '*', '&', '/', ',', ':', ';', '(', ')' ), ' ', $title );
		$title = preg_replace( '/\s+/u', ' ', $title );
		return trim( (string) $title );
	}
}

if ( ! function_exists( 'awz_stec_title_link_extract_code' ) ) {
	/**
	 * Extract code token like S03, K07, L01 from title.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	function awz_stec_title_link_extract_code( $title ) {
		if ( preg_match( '/\b([A-Za-z])\s*0*([0-9]{1,2})\b/u', $title, $matches ) ) {
			return strtoupper( $matches[1] ) . sprintf( '%02d', (int) $matches[2] );
		}

		return '';
	}
}

if ( ! function_exists( 'awz_stec_title_link_contains' ) ) {
	/**
	 * Word-aware contains helper for normalized strings.
	 *
	 * @param string $haystack Normalized haystack.
	 * @param string $needle Normalized needle.
	 * @return bool
	 */
	function awz_stec_title_link_contains( $haystack, $needle ) {
		$needle = trim( (string) $needle );
		if ( '' === $needle ) {
			return false;
		}

		return false !== strpos( ' ' . $haystack . ' ', ' ' . $needle . ' ' );
	}
}

if ( ! function_exists( 'awz_stec_title_link_get_ticket_product_pool' ) ) {
	/**
	 * Get published ticket products prepared for matching.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function awz_stec_title_link_get_ticket_product_pool() {
		$product_ids = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => array( 'publish' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! is_array( $product_ids ) ) {
			return array();
		}

		$pool = array();

		foreach ( $product_ids as $product_id ) {
			if ( '1' !== (string) get_post_meta( $product_id, 'stec_ticket', true ) ) {
				continue;
			}

			$title = get_the_title( $product_id );
			if ( ! $title ) {
				continue;
			}

			$norm = awz_stec_title_link_normalize( $title );

			$pool[] = array(
				'id'         => (int) $product_id,
				'title'      => (string) $title,
				'norm'       => $norm,
				'norm_strict' => awz_stec_title_link_normalize_strict( $title ),
				'code'       => awz_stec_title_link_extract_code( $title ),
				'has_member' => awz_stec_title_link_contains( $norm, 'fur mitglieder' ),
				'is_master'  => awz_stec_title_link_contains( $norm, 'meister' ),
				'has_teil_3' => awz_stec_title_link_has_teil_3( $norm ),
				'has_teil_4' => awz_stec_title_link_has_teil_4( $norm ),
			);
		}

		return $pool;
	}
}

if ( ! function_exists( 'awz_stec_title_link_dedupe_product_ids' ) ) {
	/**
	 * Deduplicate product IDs by normalized title, keep latest ID.
	 *
	 * @param array<int>                    $product_ids Product IDs.
	 * @param array<int,array<string,mixed>> $pool Product pool.
	 * @return array<int>
	 */
	function awz_stec_title_link_dedupe_product_ids( $product_ids, $pool ) {
		$by_id = array();
		foreach ( $pool as $item ) {
			$by_id[ $item['id'] ] = $item;
		}

		$deduped = array();
		foreach ( $product_ids as $product_id ) {
			if ( ! isset( $by_id[ $product_id ] ) ) {
				continue;
			}

			$key = $by_id[ $product_id ]['norm_strict'];
			if ( ! isset( $deduped[ $key ] ) || $product_id > $deduped[ $key ] ) {
				$deduped[ $key ] = $product_id;
			}
		}

		$values = array_values( $deduped );
		sort( $values, SORT_NUMERIC );
		return $values;
	}
}

if ( ! function_exists( 'awz_stec_title_link_has_teil_3' ) ) {
	/**
	 * Detect "Teil III" marker.
	 *
	 * @param string $event_norm Normalized event title.
	 * @return bool
	 */
	function awz_stec_title_link_has_teil_3( $event_norm ) {
		if ( 1 === preg_match( '/\bteil(?:e)?\s*(iii|3)\b/u', $event_norm ) ) {
			return true;
		}

		return 1 === preg_match( '/\b(iii|3)\s*(\+|und|&)\s*(iv|4)\b/u', $event_norm );
	}
}

if ( ! function_exists( 'awz_stec_title_link_has_teil_4' ) ) {
	/**
	 * Detect "Teil IV" marker.
	 *
	 * @param string $event_norm Normalized event title.
	 * @return bool
	 */
	function awz_stec_title_link_has_teil_4( $event_norm ) {
		if ( 1 === preg_match( '/\bteil(?:e)?\s*(iv|4)\b/u', $event_norm ) ) {
			return true;
		}

		return 1 === preg_match( '/\b(iii|3)\s*(\+|und|&)\s*(iv|4)\b/u', $event_norm );
	}
}

if ( ! function_exists( 'awz_stec_title_link_has_teil_i_ii' ) ) {
	/**
	 * Detect "Teil I & II" marker.
	 *
	 * @param string $event_norm Normalized event title.
	 * @return bool
	 */
	function awz_stec_title_link_has_teil_i_ii( $event_norm ) {
		return 1 === preg_match( '/\bteil(?:e)?\s*i\b.*\bii\b/u', $event_norm );
	}
}

if ( ! function_exists( 'awz_stec_title_link_special_match' ) ) {
	/**
	 * Match special course families that need multi-product assignment.
	 *
	 * @param string                         $event_norm Normalized event title.
	 * @param array<int,array<string,mixed>> $pool Product pool.
	 * @return array{strategy:string,ids:array<int>}
	 */
	function awz_stec_title_link_special_match( $event_norm, $pool ) {
		$is_teil_3 = awz_stec_title_link_has_teil_3( $event_norm );
		$is_teil_4 = awz_stec_title_link_has_teil_4( $event_norm );

		$ids = array();

		// Teil III + IV combinations (including M 01 phrasing) -> all Meister Teil III+IV products.
		if ( $is_teil_3 && $is_teil_4 ) {
			foreach ( $pool as $item ) {
				if ( $item['is_master'] && $item['has_teil_3'] && $item['has_teil_4'] && ! $item['has_member'] ) {
					$ids[] = $item['id'];
				}
			}

			return array(
				'strategy' => 'special_teil3_teil4_combo',
				'ids'      => $ids,
			);
		}

		// AEVO / Teil IV -> all Meister Teil IV products.
		if ( $is_teil_4 && ( awz_stec_title_link_contains( $event_norm, 'aevo' ) || awz_stec_title_link_contains( $event_norm, 'ausbildereignungsprufung' ) ) ) {
			foreach ( $pool as $item ) {
				if ( $item['is_master'] && $item['has_teil_4'] && ! $item['has_teil_3'] && ! $item['has_member'] ) {
					$ids[] = $item['id'];
				}
			}

			return array(
				'strategy' => 'special_teil4_aevo',
				'ids'      => $ids,
			);
		}

		// Teil III (kaufm. Betriebsführung) -> all Meister Teil III products.
		if ( $is_teil_3 && ! $is_teil_4 && ( awz_stec_title_link_contains( $event_norm, 'betriebsfuhrung' ) || awz_stec_title_link_contains( $event_norm, 'betriebsfuehrung' ) ) ) {
			foreach ( $pool as $item ) {
				if ( $item['is_master'] && $item['has_teil_3'] && ! $item['has_teil_4'] && ! $item['has_member'] ) {
					$ids[] = $item['id'];
				}
			}

			return array(
				'strategy' => 'special_teil3_hwo',
				'ids'      => $ids,
			);
		}

		// Straßenbauer Teil I + II.
		if ( awz_stec_title_link_contains( $event_norm, 'strassenbauer' ) && awz_stec_title_link_has_teil_i_ii( $event_norm ) ) {
			foreach ( $pool as $item ) {
				if ( awz_stec_title_link_contains( $item['norm'], 'strassenbauer' ) && awz_stec_title_link_has_teil_i_ii( $item['norm'] ) ) {
					$ids[] = $item['id'];
				}
			}

			return array(
				'strategy' => 'special_strassenbauer_i_ii',
				'ids'      => $ids,
			);
		}

		return array(
			'strategy' => 'none',
			'ids'      => array(),
		);
	}
}

if ( ! function_exists( 'awz_stec_title_link_generic_match' ) ) {
	/**
	 * Generic score-based title matching.
	 *
	 * @param string                         $event_title Event title.
	 * @param string                         $event_norm Normalized event title.
	 * @param array<int,array<string,mixed>> $pool Product pool.
	 * @return array{strategy:string,ids:array<int>,top_score:int}
	 */
	function awz_stec_title_link_generic_match( $event_title, $event_norm, $pool ) {
		$event_code       = awz_stec_title_link_extract_code( $event_title );
		$event_has_member = awz_stec_title_link_contains( $event_norm, 'fur mitglieder' );

		$scored = array();

		foreach ( $pool as $item ) {
			$score = 0;

			if ( $event_norm === $item['norm'] ) {
				$score += 1000;
			}

			if ( '' !== $event_code && $event_code === $item['code'] ) {
				$score += 420;
			} elseif ( '' !== $event_code && '' !== $item['code'] && $event_code !== $item['code'] ) {
				$score -= 320;
			}

			if ( ! $event_has_member && $item['has_member'] ) {
				$score -= 260;
			}

			if ( awz_stec_title_link_contains( $item['norm'], $event_norm ) ) {
				$score += 90;
			}

			if ( awz_stec_title_link_contains( $event_norm, $item['norm'] ) ) {
				$score += 80;
			}

			similar_text( $event_norm, $item['norm'], $percent );
			$score += (int) round( $percent * 2.0 );

			if ( $score >= 450 ) {
				$scored[] = array(
					'id'    => $item['id'],
					'score' => $score,
				);
			}
		}

		if ( empty( $scored ) ) {
			return array(
				'strategy'  => 'generic_none',
				'ids'       => array(),
				'top_score' => 0,
			);
		}

		usort(
			$scored,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		$top_score = (int) $scored[0]['score'];
		$ids       = array();

		foreach ( $scored as $row ) {
			if ( $row['score'] >= ( $top_score - 35 ) ) {
				$ids[] = (int) $row['id'];
			}
		}

		return array(
			'strategy'  => 'generic_score',
			'ids'       => $ids,
			'top_score' => $top_score,
		);
	}
}

if ( ! function_exists( 'awz_stec_title_link_pick_products_for_event' ) ) {
	/**
	 * Determine ticket products for event by title.
	 *
	 * @param \WP_Post                        $event Event post object.
	 * @param array<int,array<string,mixed>> $pool Product pool.
	 * @return array{strategy:string,ids:array<int>,top_score:int}
	 */
	function awz_stec_title_link_pick_products_for_event( $event, $pool ) {
		$event_title = (string) $event->post_title;
		$event_norm  = awz_stec_title_link_normalize( $event_title );
		$event_code  = awz_stec_title_link_extract_code( $event_title );

		$generic = awz_stec_title_link_generic_match( $event_title, $event_norm, $pool );
		if ( ! empty( $event_code ) && ! empty( $generic['ids'] ) ) {
			$generic['ids'] = awz_stec_title_link_dedupe_product_ids( $generic['ids'], $pool );
			return $generic;
		}

		$special = awz_stec_title_link_special_match( $event_norm, $pool );
		if ( ! empty( $special['ids'] ) ) {
			$special['ids'] = awz_stec_title_link_dedupe_product_ids( $special['ids'], $pool );
			return array(
				'strategy'  => $special['strategy'],
				'ids'       => $special['ids'],
				'top_score' => 999,
			);
		}

		$generic['ids'] = awz_stec_title_link_dedupe_product_ids( $generic['ids'], $pool );
		return $generic;
	}
}

if ( ! function_exists( 'awz_stec_title_link_repair_run' ) ) {
	/**
	 * Assign ticket products to publish events based on title matching.
	 *
	 * @param bool $apply Whether to write.
	 * @param bool $force Whether to overwrite already synced links.
	 * @return array<string,mixed>
	 */
	function awz_stec_title_link_repair_run( $apply = false, $force = false ) {
		$events = get_posts(
			array(
				'post_type'              => 'stec_event',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! is_array( $events ) ) {
			$events = array();
		}

		$pool = awz_stec_title_link_get_ticket_product_pool();

		$report = array(
			'apply'                  => (bool) $apply,
			'force'                  => (bool) $force,
			'total_events'           => count( $events ),
			'total_ticket_products'  => count( $pool ),
			'matched_events'         => 0,
			'would_apply_events'     => 0,
			'applied_events'         => 0,
			'skipped_no_match'       => 0,
			'skipped_already_synced' => 0,
			'rows'                   => array(),
		);

		foreach ( $events as $event ) {
			$pick = awz_stec_title_link_pick_products_for_event( $event, $pool );
			$ids  = awz_stec_booking_repair_normalize_ids( $pick['ids'] );

			$row = array(
				'event_id'       => (int) $event->ID,
				'event_title'    => (string) $event->post_title,
				'strategy'       => (string) $pick['strategy'],
				'top_score'      => (int) $pick['top_score'],
				'match_count'    => count( $ids ),
				'product_ids'    => implode( ',', $ids ),
				'status'         => '',
			);

			if ( empty( $ids ) ) {
				$report['skipped_no_match']++;
				$row['status'] = 'no_match';
				$report['rows'][] = $row;
				continue;
			}

			$report['matched_events']++;

			$target_state = awz_stec_booking_repair_get_target_state( $event->ID );
			$is_synced    = awz_stec_booking_repair_is_synced( $target_state, $ids );

			if ( $is_synced && ! $force ) {
				$report['skipped_already_synced']++;
				$row['status'] = 'already_synced';
				$report['rows'][] = $row;
				continue;
			}

			if ( $apply ) {
				awz_stec_booking_repair_write_target_state( $event->ID, $ids );
				$report['applied_events']++;
				$row['status'] = $is_synced ? 'reapplied_force' : 'applied';
			} else {
				$report['would_apply_events']++;
				$row['status'] = $is_synced ? 'would_reapply_force' : 'would_apply';
			}

			$report['rows'][] = $row;
		}

		return $report;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_run' ) ) {
	/**
	 * Execute booking repair as dry-run or apply mode.
	 *
	 * @param bool $apply Whether to persist changes.
	 * @param bool $force Whether to apply even when already synced.
	 * @return array<string,mixed>
	 */
	function awz_stec_booking_repair_run( $apply = false, $force = false ) {
		$publish_events = awz_stec_booking_repair_get_events_by_uid( 'publish' );
		$legacy_events  = awz_stec_booking_repair_get_events_by_uid( 'stec_legacy' );

		$report = array(
			'apply'                    => (bool) $apply,
			'force'                    => (bool) $force,
			'total_publish_events'     => count( $publish_events ),
			'pairs_found'              => 0,
			'pairs_missing'            => 0,
			'repairable_events'        => 0,
			'would_apply_events'       => 0,
			'applied_events'           => 0,
			'skipped_no_source'        => 0,
			'skipped_invalid_only'     => 0,
			'skipped_already_synced'   => 0,
			'invalid_products_total'   => 0,
			'rows'                     => array(),
		);

		foreach ( $publish_events as $uid => $publish_event_id ) {
			$row = array(
				'uid'             => $uid,
				'publish_event'   => (int) $publish_event_id,
				'legacy_event'    => 0,
				'source_key'      => '',
				'source_count'    => 0,
				'valid_count'     => 0,
				'invalid_count'   => 0,
				'status'          => '',
			);

			if ( ! isset( $legacy_events[ $uid ] ) ) {
				$report['pairs_missing']++;
				$row['status'] = 'missing_legacy_pair';
				$report['rows'][] = $row;
				continue;
			}

			$legacy_event_id      = (int) $legacy_events[ $uid ];
			$report['pairs_found']++;
			$row['legacy_event'] = $legacy_event_id;

			$source             = awz_stec_booking_repair_get_source_ids( $legacy_event_id );
			$source_ids         = $source['ids'];
			$row['source_key']  = $source['source_key'];
			$row['source_count'] = count( $source_ids );

			if ( empty( $source_ids ) ) {
				$report['skipped_no_source']++;
				$row['status'] = 'no_source_products';
				$report['rows'][] = $row;
				continue;
			}

			list( $valid_ids, $invalid_ids ) = awz_stec_booking_repair_split_valid_product_ids( $source_ids );

			$row['valid_count']   = count( $valid_ids );
			$row['invalid_count'] = count( $invalid_ids );
			$report['invalid_products_total'] += count( $invalid_ids );

			if ( empty( $valid_ids ) ) {
				$report['skipped_invalid_only']++;
				$row['status'] = 'source_products_invalid';
				$report['rows'][] = $row;
				continue;
			}

			$report['repairable_events']++;
			$target_state = awz_stec_booking_repair_get_target_state( $publish_event_id );
			$is_synced    = awz_stec_booking_repair_is_synced( $target_state, $valid_ids );

			if ( $is_synced && ! $force ) {
				$report['skipped_already_synced']++;
				$row['status'] = 'already_synced';
				$report['rows'][] = $row;
				continue;
			}

			if ( $apply ) {
				awz_stec_booking_repair_write_target_state( $publish_event_id, $valid_ids );
				$report['applied_events']++;
				$row['status'] = $is_synced ? 'reapplied_force' : 'applied';
			} else {
				$report['would_apply_events']++;
				$row['status'] = $is_synced ? 'would_reapply_force' : 'would_apply';
			}

			$report['rows'][] = $row;
		}

		return $report;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_status_label' ) ) {
	/**
	 * Map internal status code to readable label.
	 *
	 * @param string $status Status code.
	 * @return string
	 */
	function awz_stec_booking_repair_status_label( $status ) {
		$map = array(
			'missing_legacy_pair'  => 'Legacy-Paar fehlt',
			'no_source_products'   => 'Keine Quell-Produkte',
			'source_products_invalid' => 'Nur ungültige Quell-Produkte',
			'already_synced'       => 'Bereits synchron',
			'would_apply'          => 'Wird repariert',
			'would_reapply_force'  => 'Wird mit Force neu geschrieben',
			'applied'              => 'Repariert',
			'reapplied_force'      => 'Mit Force neu geschrieben',
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_render_report' ) ) {
	/**
	 * Render admin report block.
	 *
	 * @param array<string,mixed> $report Report data.
	 * @return void
	 */
	function awz_stec_booking_repair_render_report( $report ) {
		if ( empty( $report ) ) {
			return;
		}
		?>
		<h2>Repair Report</h2>
		<table class="widefat striped" style="max-width: 900px; margin-bottom: 18px;">
			<tbody>
			<tr><th>Modus</th><td><?php echo esc_html( $report['apply'] ? 'Apply' : 'Dry Run' ); ?></td></tr>
			<tr><th>Force</th><td><?php echo esc_html( $report['force'] ? 'Ja' : 'Nein' ); ?></td></tr>
			<tr><th>Publish-Events mit UID</th><td><?php echo esc_html( (string) $report['total_publish_events'] ); ?></td></tr>
			<tr><th>Gefundene UID-Paare</th><td><?php echo esc_html( (string) $report['pairs_found'] ); ?></td></tr>
			<tr><th>Fehlende Legacy-Paare</th><td><?php echo esc_html( (string) $report['pairs_missing'] ); ?></td></tr>
			<tr><th>Reparierbar</th><td><?php echo esc_html( (string) $report['repairable_events'] ); ?></td></tr>
			<tr><th>Wird repariert (Dry Run)</th><td><?php echo esc_html( (string) $report['would_apply_events'] ); ?></td></tr>
			<tr><th>Repariert (Apply)</th><td><?php echo esc_html( (string) $report['applied_events'] ); ?></td></tr>
			<tr><th>Übersprungen: keine Quelle</th><td><?php echo esc_html( (string) $report['skipped_no_source'] ); ?></td></tr>
			<tr><th>Übersprungen: Quelle ungültig</th><td><?php echo esc_html( (string) $report['skipped_invalid_only'] ); ?></td></tr>
			<tr><th>Übersprungen: bereits synchron</th><td><?php echo esc_html( (string) $report['skipped_already_synced'] ); ?></td></tr>
			<tr><th>Ungültige Produkt-IDs gesamt</th><td><?php echo esc_html( (string) $report['invalid_products_total'] ); ?></td></tr>
			</tbody>
		</table>

		<h3>Details (erste 120 Zeilen)</h3>
		<table class="widefat striped">
			<thead>
			<tr>
				<th>UID</th>
				<th>Publish</th>
				<th>Legacy</th>
				<th>Quelle</th>
				<th>Quelle #</th>
				<th>Valid #</th>
				<th>Invalid #</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( array_slice( $report['rows'], 0, 120 ) as $row ) : ?>
				<tr>
					<td><code><?php echo esc_html( (string) $row['uid'] ); ?></code></td>
					<td><?php echo esc_html( (string) $row['publish_event'] ); ?></td>
					<td><?php echo esc_html( (string) $row['legacy_event'] ); ?></td>
					<td><?php echo esc_html( (string) $row['source_key'] ); ?></td>
					<td><?php echo esc_html( (string) $row['source_count'] ); ?></td>
					<td><?php echo esc_html( (string) $row['valid_count'] ); ?></td>
					<td><?php echo esc_html( (string) $row['invalid_count'] ); ?></td>
					<td><?php echo esc_html( awz_stec_booking_repair_status_label( (string) $row['status'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

if ( ! function_exists( 'awz_stec_ticket_flag_repair_render_report' ) ) {
	/**
	 * Render report for product ticket flag repair.
	 *
	 * @param array<string,mixed> $report Report data.
	 * @return void
	 */
	function awz_stec_ticket_flag_repair_render_report( $report ) {
		if ( empty( $report ) ) {
			return;
		}
		?>
		<h2>Ticket-Produkt-Flag Report</h2>
		<table class="widefat striped" style="max-width: 900px; margin-bottom: 18px;">
			<tbody>
			<tr><th>Modus</th><td><?php echo esc_html( $report['apply'] ? 'Apply' : 'Dry Run' ); ?></td></tr>
			<tr><th>Virtuelle Produkte gesamt</th><td><?php echo esc_html( (string) $report['virtual_products'] ); ?></td></tr>
			<tr><th>Bereits Ticket</th><td><?php echo esc_html( (string) $report['already_ticket'] ); ?></td></tr>
			<tr><th>Benötigen Ticket-Flag</th><td><?php echo esc_html( (string) $report['needs_ticket_flag'] ); ?></td></tr>
			<tr><th>Aktualisiert (Apply)</th><td><?php echo esc_html( (string) $report['updated_products'] ); ?></td></tr>
			</tbody>
		</table>

		<h3>Details (erste 120 Zeilen)</h3>
		<table class="widefat striped">
			<thead>
			<tr>
				<th>Produkt-ID</th>
				<th>Titel</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( array_slice( $report['rows'], 0, 120 ) as $row ) : ?>
				<tr>
					<td><?php echo esc_html( (string) $row['product_id'] ); ?></td>
					<td><?php echo esc_html( (string) $row['title'] ); ?></td>
					<td><?php echo esc_html( 'already_ticket' === $row['ticket_state'] ? 'Bereits Ticket' : 'Ticket-Flag fehlt' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

if ( ! function_exists( 'awz_stec_title_link_status_label' ) ) {
	/**
	 * Human labels for title-link statuses.
	 *
	 * @param string $status Status code.
	 * @return string
	 */
	function awz_stec_title_link_status_label( $status ) {
		$map = array(
			'no_match'            => 'Kein Match',
			'already_synced'      => 'Bereits synchron',
			'would_apply'         => 'Wird zugeordnet',
			'would_reapply_force' => 'Wird mit Force neu geschrieben',
			'applied'             => 'Zuordnung geschrieben',
			'reapplied_force'     => 'Mit Force neu geschrieben',
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}
}

if ( ! function_exists( 'awz_stec_title_link_render_report' ) ) {
	/**
	 * Render report for title-based event-product assignment.
	 *
	 * @param array<string,mixed> $report Report data.
	 * @return void
	 */
	function awz_stec_title_link_render_report( $report ) {
		if ( empty( $report ) ) {
			return;
		}
		?>
		<h2>Event-Ticket-Zuordnung per Titel: Report</h2>
		<table class="widefat striped" style="max-width: 980px; margin-bottom: 18px;">
			<tbody>
			<tr><th>Modus</th><td><?php echo esc_html( $report['apply'] ? 'Apply' : 'Dry Run' ); ?></td></tr>
			<tr><th>Force</th><td><?php echo esc_html( $report['force'] ? 'Ja' : 'Nein' ); ?></td></tr>
			<tr><th>Events gesamt</th><td><?php echo esc_html( (string) $report['total_events'] ); ?></td></tr>
			<tr><th>Ticket-Produkte im Pool</th><td><?php echo esc_html( (string) $report['total_ticket_products'] ); ?></td></tr>
			<tr><th>Events mit Match</th><td><?php echo esc_html( (string) $report['matched_events'] ); ?></td></tr>
			<tr><th>Wird zugeordnet (Dry Run)</th><td><?php echo esc_html( (string) $report['would_apply_events'] ); ?></td></tr>
			<tr><th>Zugeordnet (Apply)</th><td><?php echo esc_html( (string) $report['applied_events'] ); ?></td></tr>
			<tr><th>Übersprungen: kein Match</th><td><?php echo esc_html( (string) $report['skipped_no_match'] ); ?></td></tr>
			<tr><th>Übersprungen: bereits synchron</th><td><?php echo esc_html( (string) $report['skipped_already_synced'] ); ?></td></tr>
			</tbody>
		</table>

		<h3>Details (erste 120 Zeilen)</h3>
		<table class="widefat striped">
			<thead>
			<tr>
				<th>Event-ID</th>
				<th>Event</th>
				<th>Strategie</th>
				<th>Top-Score</th>
				<th>Treffer</th>
				<th>Produkt-IDs</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( array_slice( $report['rows'], 0, 120 ) as $row ) : ?>
				<tr>
					<td><?php echo esc_html( (string) $row['event_id'] ); ?></td>
					<td><?php echo esc_html( (string) $row['event_title'] ); ?></td>
					<td><code><?php echo esc_html( (string) $row['strategy'] ); ?></code></td>
					<td><?php echo esc_html( (string) $row['top_score'] ); ?></td>
					<td><?php echo esc_html( (string) $row['match_count'] ); ?></td>
					<td><code><?php echo esc_html( (string) $row['product_ids'] ); ?></code></td>
					<td><?php echo esc_html( awz_stec_title_link_status_label( (string) $row['status'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

if ( ! function_exists( 'awz_stec_booking_repair_render_admin_page' ) ) {
	/**
	 * Render admin repair page.
	 *
	 * @return void
	 */
	function awz_stec_booking_repair_render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'vantage-child' ) );
		}

		$report               = array();
		$ticket_flag_report   = array();
		$title_link_report    = array();
		$notice               = '';

		if ( isset( $_POST['awz_stec_repair_action'] ) ) {
			check_admin_referer( 'awz_stec_repair_action', 'awz_stec_repair_nonce' );

			$action = sanitize_key( wp_unslash( $_POST['awz_stec_repair_action'] ) );
			$force  = ! empty( $_POST['awz_stec_repair_force'] );

			if ( in_array( $action, array( 'dry_run', 'apply' ), true ) ) {
				$apply  = ( 'apply' === $action );
				$report = awz_stec_booking_repair_run( $apply, $force );
				$notice = $apply ? 'Apply wurde ausgeführt. Bitte Ergebnis prüfen.' : 'Dry Run wurde ausgeführt.';
			} elseif ( in_array( $action, array( 'ticket_flags_dry_run', 'ticket_flags_apply' ), true ) ) {
				$apply_flag         = ( 'ticket_flags_apply' === $action );
				$ticket_flag_report = awz_stec_ticket_flag_repair_run( $apply_flag );
				$notice             = $apply_flag ? 'Ticket-Produkt-Flags wurden aktualisiert.' : 'Ticket-Produkt-Flags Dry Run wurde ausgeführt.';
			} elseif ( in_array( $action, array( 'title_link_dry_run', 'title_link_apply' ), true ) ) {
				$apply_link        = ( 'title_link_apply' === $action );
				$title_link_report = awz_stec_title_link_repair_run( $apply_link, $force );
				$notice            = $apply_link ? 'Titelbasierte Event-Ticket-Zuordnung wurde ausgeführt.' : 'Titelbasierter Dry Run wurde ausgeführt.';
			}
		}
		?>
		<div class="wrap">
			<h1>AWZ STEC Repair</h1>
			<p>
				Dieses Werkzeug synchronisiert fehlende Buchungs-Meta von <code>stec_legacy</code> nach <code>publish</code>.
				<br>
				<strong>Wichtig:</strong> Vor <em>Apply</em> ein vollständiges Datenbank-Backup erstellen.
			</p>

			<?php if ( '' !== $notice ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'awz_stec_repair_action', 'awz_stec_repair_nonce' ); ?>
				<p>
					<label>
						<input type="checkbox" name="awz_stec_repair_force" value="1">
						Force (auch bereits synchronisierte Events neu schreiben)
					</label>
				</p>
				<p>
					<button type="submit" class="button button-secondary" name="awz_stec_repair_action" value="dry_run">Dry Run</button>
					<button type="submit" class="button button-primary" name="awz_stec_repair_action" value="apply">Apply</button>
				</p>
			</form>

			<?php awz_stec_booking_repair_render_report( $report ); ?>

			<hr style="margin: 32px 0;">
			<h2>Ticket-Produkt-Flags reparieren</h2>
			<p>
				Dies markiert virtuelle WooCommerce-Produkte als STEC-Tickets
				(<code>stec_ticket=1</code>), damit sie in „Tickets hinzufügen“ auswählbar sind.
			</p>
			<form method="post">
				<?php wp_nonce_field( 'awz_stec_repair_action', 'awz_stec_repair_nonce' ); ?>
				<p>
					<button type="submit" class="button button-secondary" name="awz_stec_repair_action" value="ticket_flags_dry_run">Ticket-Flags Dry Run</button>
					<button type="submit" class="button button-primary" name="awz_stec_repair_action" value="ticket_flags_apply">Ticket-Flags Apply</button>
				</p>
			</form>

			<?php awz_stec_ticket_flag_repair_render_report( $ticket_flag_report ); ?>

			<hr style="margin: 32px 0;">
			<h2>Event-Tickets per Titel zuordnen</h2>
			<p>
				Dieser Lauf verknüpft STEC-Events mit passenden Ticket-Produkten auf Basis von Titeln
				(inkl. Spezialregeln für Teil III/IV-Kurse). Bei Mehrfachtreffern werden Duplikate
				mit identischem Titel auf die neueste Produkt-ID reduziert.
			</p>
			<form method="post">
				<?php wp_nonce_field( 'awz_stec_repair_action', 'awz_stec_repair_nonce' ); ?>
				<p>
					<label>
						<input type="checkbox" name="awz_stec_repair_force" value="1">
						Force (auch bereits synchronisierte Event-Zuordnungen neu schreiben)
					</label>
				</p>
				<p>
					<button type="submit" class="button button-secondary" name="awz_stec_repair_action" value="title_link_dry_run">Titel-Matching Dry Run</button>
					<button type="submit" class="button button-primary" name="awz_stec_repair_action" value="title_link_apply">Titel-Matching Apply</button>
				</p>
			</form>

			<?php awz_stec_title_link_render_report( $title_link_report ); ?>
		</div>
		<?php
	}
}

add_action(
	'admin_menu',
	function () {
		add_management_page(
			'AWZ STEC Repair',
			'AWZ STEC Repair',
			'manage_options',
			'awz-stec-repair',
			'awz_stec_booking_repair_render_admin_page'
		);
	}
);
