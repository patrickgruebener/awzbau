<?php
/**
 * Plugin Name: AWZ STEC Performance Overrides
 * Description: Performance guards for STEC calendar rendering without plugin core patches.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AWZ_STEC_EVENTS_CACHE_TTL' ) ) {
	define( 'AWZ_STEC_EVENTS_CACHE_TTL', 300 );
}

if ( ! defined( 'AWZ_STEC_EVENTS_CACHE_VERSION_OPTION' ) ) {
	define( 'AWZ_STEC_EVENTS_CACHE_VERSION_OPTION', 'awz_stec_events_cache_version' );
}

if ( ! function_exists( 'awz_stec_perf_is_frontend_request' ) ) {
	function awz_stec_perf_is_frontend_request() {
		return ! is_admin() || wp_doing_ajax();
	}
}

if ( ! function_exists( 'awz_stec_perf_is_weiterbildung_context' ) ) {
	function awz_stec_perf_is_weiterbildung_context() {
		static $is_weiterbildung = null;

		if ( null !== $is_weiterbildung ) {
			return $is_weiterbildung;
		}

		if ( ! awz_stec_perf_is_frontend_request() ) {
			$is_weiterbildung = false;
			return $is_weiterbildung;
		}

		if ( function_exists( 'is_page' ) && is_page( 'weiterbildung' ) ) {
			$is_weiterbildung = true;
			return $is_weiterbildung;
		}

		global $post;
		$is_weiterbildung = ( $post instanceof WP_Post && 'weiterbildung' === $post->post_name );

		return $is_weiterbildung;
	}
}

if ( ! function_exists( 'awz_stec_perf_today' ) ) {
	function awz_stec_perf_today() {
		$now = new DateTimeImmutable( 'now', wp_timezone() );
		return $now->setTime( 0, 0 )->format( 'Y-m-d\TH:i' );
	}
}

if ( ! function_exists( 'awz_stec_perf_filter_shortcode_atts' ) ) {
	function awz_stec_perf_filter_shortcode_atts( $atts ) {
		if ( ! is_array( $atts ) || ! awz_stec_perf_is_weiterbildung_context() ) {
			return $atts;
		}

		// No date filters here: end_date filtering is handled server-side in
		// awz_stec_perf_rest_post_dispatch to correctly show long-running and
		// in-progress courses (e.g. Meisterlehrgang running Jan–Dec 2027).
		$atts['misc__events_per_request'] = 20;
		$atts['misc__events_prefetch']    = false;

		return $atts;
	}
}
add_filter( 'stec_shortcode_atts', 'awz_stec_perf_filter_shortcode_atts', 100 );

if ( ! function_exists( 'awz_stec_perf_normalize_array' ) ) {
	function awz_stec_perf_normalize_array( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = awz_stec_perf_normalize_array( $item );
		}

		if ( array_values( $value ) !== $value ) {
			ksort( $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'awz_stec_perf_is_events_collection_request' ) ) {
	function awz_stec_perf_is_events_collection_request( $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return false;
		}

		if ( 'GET' !== strtoupper( (string) $request->get_method() ) ) {
			return false;
		}

		$route = untrailingslashit( (string) $request->get_route() );
		return (bool) preg_match( '#^/stec/v\d+/events$#i', $route );
	}
}

if ( ! function_exists( 'awz_stec_perf_get_cache_version' ) ) {
	function awz_stec_perf_get_cache_version() {
		$version = (int) get_option( AWZ_STEC_EVENTS_CACHE_VERSION_OPTION, 1 );
		return ( $version > 0 ) ? $version : 1;
	}
}

if ( ! function_exists( 'awz_stec_perf_get_cache_key' ) ) {
	function awz_stec_perf_get_cache_key( WP_REST_Request $request ) {
		$params = awz_stec_perf_normalize_array( $request->get_query_params() );
		$route  = untrailingslashit( (string) $request->get_route() );
		$lang   = (string) $request->get_param( 'lang' );

		if ( '' === $lang ) {
			$lang = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		}

		$is_logged_in = is_user_logged_in() ? 1 : 0;
		$user_id      = $is_logged_in ? (int) get_current_user_id() : 0;

		$payload = array(
			'route'         => $route,
			'params'        => $params,
			'lang'          => $lang,
			'is_logged_in'  => $is_logged_in,
			'user_id'       => $user_id,
			'cache_version' => awz_stec_perf_get_cache_version(),
		);

		return 'awz_stec_events_' . md5( wp_json_encode( $payload ) );
	}
}

if ( ! function_exists( 'awz_stec_perf_rest_pre_dispatch' ) ) {
	function awz_stec_perf_rest_pre_dispatch( $result, $server, $request ) {
		if ( null !== $result || ! awz_stec_perf_is_events_collection_request( $request ) ) {
			return $result;
		}

		$cached = get_transient( awz_stec_perf_get_cache_key( $request ) );
		if ( ! is_array( $cached ) || ! isset( $cached['data'] ) ) {
			return $result;
		}

		$status  = isset( $cached['status'] ) ? (int) $cached['status'] : 200;
		$headers = isset( $cached['headers'] ) && is_array( $cached['headers'] ) ? $cached['headers'] : array();
		$data    = $cached['data'];

		$response = new WP_REST_Response( $data, $status, $headers );
		$response->header( 'X-AWZ-STEC-Cache', 'HIT' );

		return $response;
	}
}
add_filter( 'rest_pre_dispatch', 'awz_stec_perf_rest_pre_dispatch', 10, 3 );

if ( ! function_exists( 'awz_stec_perf_filter_events_by_end_date' ) ) {
	function awz_stec_perf_filter_events_by_end_date( array $data ) {
		$today = awz_stec_perf_today();

		return array_values(
			array_filter(
				$data,
				static function ( $event ) use ( $today ) {
					if ( ! is_array( $event ) || ! isset( $event['end_date'] ) ) {
						return true;
					}
					return $event['end_date'] >= $today;
				}
			)
		);
	}
}

if ( ! function_exists( 'awz_stec_perf_rest_post_dispatch' ) ) {
	function awz_stec_perf_rest_post_dispatch( $result, $server, $request ) {
		if ( ! awz_stec_perf_is_events_collection_request( $request ) || is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $result );
		if ( ! $response instanceof WP_REST_Response ) {
			return $result;
		}

		if ( 200 !== (int) $response->get_status() ) {
			return $response;
		}

		// Filter out past events (end_date < today) before caching.
		// This replaces the old filter__min_date shortcode param which incorrectly
		// excluded in-progress courses by their start_date.
		$data = $response->get_data();
		if ( is_array( $data ) ) {
			$response->set_data( awz_stec_perf_filter_events_by_end_date( $data ) );
		}

		$cache_payload = array(
			'status'  => (int) $response->get_status(),
			'headers' => $response->get_headers(),
			'data'    => $response->get_data(),
		);

		set_transient( awz_stec_perf_get_cache_key( $request ), $cache_payload, AWZ_STEC_EVENTS_CACHE_TTL );
		$response->header( 'X-AWZ-STEC-Cache', 'MISS' );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'awz_stec_perf_rest_post_dispatch', 10, 3 );

if ( ! function_exists( 'awz_stec_perf_bump_cache_version' ) ) {
	function awz_stec_perf_bump_cache_version() {
		static $did_bump = false;

		if ( $did_bump ) {
			return;
		}

		$current_version = awz_stec_perf_get_cache_version();
		update_option( AWZ_STEC_EVENTS_CACHE_VERSION_OPTION, $current_version + 1, false );

		$did_bump = true;
	}
}

if ( ! function_exists( 'awz_stec_perf_invalidate_on_save' ) ) {
	function awz_stec_perf_invalidate_on_save( $post_id, $post, $update ) {
		unset( $update );

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( $post instanceof WP_Post && 'stec_event' === $post->post_type ) {
			awz_stec_perf_bump_cache_version();
		}
	}
}
add_action( 'save_post_stec_event', 'awz_stec_perf_invalidate_on_save', 10, 3 );

if ( ! function_exists( 'awz_stec_perf_invalidate_on_deleted' ) ) {
	function awz_stec_perf_invalidate_on_deleted( $post_id, $post = null ) {
		$post_type = ( $post instanceof WP_Post ) ? $post->post_type : get_post_type( $post_id );
		if ( 'stec_event' !== $post_type ) {
			return;
		}

		awz_stec_perf_bump_cache_version();
	}
}
add_action( 'deleted_post', 'awz_stec_perf_invalidate_on_deleted', 10, 2 );

if ( ! function_exists( 'awz_stec_perf_invalidate_on_trashed' ) ) {
	function awz_stec_perf_invalidate_on_trashed( $post_id ) {
		if ( 'stec_event' !== get_post_type( $post_id ) ) {
			return;
		}

		awz_stec_perf_bump_cache_version();
	}
}
add_action( 'trashed_post', 'awz_stec_perf_invalidate_on_trashed', 10, 1 );

if ( ! function_exists( 'awz_stec_perf_invalidate_on_terms' ) ) {
	function awz_stec_perf_invalidate_on_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		unset( $terms, $tt_ids, $append, $old_tt_ids );

		$stec_taxonomies = array( 'stec_cal', 'stec_cat', 'stec_org', 'stec_loc', 'stec_gst' );
		if ( ! in_array( $taxonomy, $stec_taxonomies, true ) ) {
			return;
		}

		if ( 'stec_event' !== get_post_type( $object_id ) ) {
			return;
		}

		awz_stec_perf_bump_cache_version();
	}
}
add_action( 'set_object_terms', 'awz_stec_perf_invalidate_on_terms', 10, 6 );
