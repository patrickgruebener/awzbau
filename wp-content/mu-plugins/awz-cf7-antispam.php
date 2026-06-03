<?php
/**
 * Plugin Name: AWZ Anti-Spam
 * Description: Server-side spam heuristics for Contact Form 7 and WordPress comments.
 * Author: AWZ / Codex
 * Version: 1.2.0
 */

defined( 'ABSPATH' ) || exit;

const AWZ_ANTISPAM_CF7_MESSAGE = 'Ihre Nachricht enthält Links, HTML oder nicht erlaubte Zeichen. Bitte entfernen Sie diese Inhalte und senden Sie das Formular erneut.';

if ( ! defined( 'AWZ_ANTISPAM_DISABLE_COMMENTS' ) ) {
	define( 'AWZ_ANTISPAM_DISABLE_COMMENTS', true );
}

function awz_antispam_blocklist() {
	return array(
		'.ru',
		'inbox.ru',
		'piterholod.ru',
		'vpn-1.ru',
		'kwork.ru',
		'praymdirect.site',
		'budgetthailandtravel.com',
	);
}

function awz_antispam_value_to_text( $value ) {
	if ( is_array( $value ) ) {
		$value = implode( ' ', array_map( 'awz_antispam_value_to_text', $value ) );
	} elseif ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
		$value = (string) $value;
	} else {
		return '';
	}

	return trim( $value );
}

function awz_antispam_normalize_text( $value ) {
	$value = awz_antispam_value_to_text( $value );

	return trim( wp_strip_all_tags( $value ) );
}

function awz_antispam_has_blocklist_match( $text, &$reason = '' ) {
	$text = awz_antispam_value_to_text( $text );
	if ( '' === $text ) {
		return false;
	}

	$blocklist = apply_filters( 'awz_antispam_blocklist', awz_antispam_blocklist() );
	$text_lc   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );

	foreach ( $blocklist as $needle ) {
		$needle = trim( (string) $needle );
		if ( '' === $needle ) {
			continue;
		}

		$needle_lc = function_exists( 'mb_strtolower' ) ? mb_strtolower( $needle, 'UTF-8' ) : strtolower( $needle );
		if ( false !== strpos( $text_lc, $needle_lc ) ) {
			$reason = sprintf( 'Blocklist match: %s', $needle );
			return true;
		}
	}

	return false;
}

function awz_antispam_text_is_spam( $text, &$reason = '' ) {
	$text = awz_antispam_normalize_text( $text );
	if ( '' === $text ) {
		return false;
	}

	if ( awz_antispam_has_blocklist_match( $text, $reason ) ) {
		return true;
	}

	$link_count   = (int) preg_match_all( '/(?:https?:\/\/|www\.|href=)/i', $text, $m );
	$has_cyrillic = (bool) preg_match( '/\p{Cyrillic}/u', $text );

	if ( $link_count >= 2 ) {
		$reason = sprintf( 'Too many links: %d', $link_count );
		return true;
	}

	if ( $has_cyrillic && $link_count >= 1 ) {
		$reason = 'Cyrillic text + link detected.';
		return true;
	}

	return false;
}

function awz_antispam_cf7_field_is_exempt( $tag ) {
	$type = isset( $tag->type ) ? (string) $tag->type : '';
	$name = isset( $tag->name ) ? (string) $tag->name : '';

	if ( false !== stripos( $type, 'email' ) ) {
		return true;
	}

	return (bool) preg_match( '/(^|[-_])(e-?mail|mail)([-_]|$)/i', $name );
}

function awz_antispam_cf7_text_is_spam( $value, &$reason = '' ) {
	$raw_text = awz_antispam_value_to_text( $value );
	if ( '' === $raw_text ) {
		return false;
	}

	if ( awz_antispam_has_blocklist_match( $raw_text, $reason ) ) {
		return true;
	}

	if ( preg_match( '/<\s*\/?\s*[a-z][^>]*>/i', $raw_text ) ) {
		$reason = 'HTML detected.';
		return true;
	}

	if ( preg_match( '/(?:https?:\/\/|www\.|href\s*=)/i', $raw_text ) ) {
		$reason = 'Link detected.';
		return true;
	}

	if ( preg_match( '/\p{Cyrillic}|\p{Arabic}|\p{Han}|\p{Greek}/u', $raw_text ) ) {
		$reason = 'Foreign script detected.';
		return true;
	}

	return false;
}

function awz_antispam_cf7_validate_text_field( $result, $tag ) {
	if ( awz_antispam_cf7_field_is_exempt( $tag ) ) {
		return $result;
	}

	$name = isset( $tag->name ) ? (string) $tag->name : '';
	if ( '' === $name || ! isset( $_POST[ $name ] ) ) {
		return $result;
	}

	$value  = wp_unslash( $_POST[ $name ] );
	$reason = '';
	if ( awz_antispam_cf7_text_is_spam( $value, $reason ) ) {
		if ( method_exists( $result, 'invalidate' ) ) {
			$result->invalidate( $tag, AWZ_ANTISPAM_CF7_MESSAGE );
		}
	}

	return $result;
}

add_filter( 'wpcf7_validate_text', 'awz_antispam_cf7_validate_text_field', 20, 2 );
add_filter( 'wpcf7_validate_text*', 'awz_antispam_cf7_validate_text_field', 20, 2 );
add_filter( 'wpcf7_validate_textarea', 'awz_antispam_cf7_validate_text_field', 20, 2 );
add_filter( 'wpcf7_validate_textarea*', 'awz_antispam_cf7_validate_text_field', 20, 2 );

/**
 * Mark obvious link-spam as spam before CF7 sends mail.
 *
 * Rules:
 * - Blocklist hits anywhere in posted data
 * - >= 2 links (http(s):// or www.) in posted data
 * - Cyrillic characters AND >= 1 link
 */
add_filter(
	'wpcf7_spam',
	static function ( $spam, $submission ) {
		if ( $spam ) {
			return true;
		}

		if ( ! $submission || ! is_object( $submission ) || ! method_exists( $submission, 'get_posted_data' ) ) {
			return $spam;
		}

		$text_parts = array();
		foreach ( (array) $submission->get_posted_data() as $key => $value ) {
			$key = (string) $key;

			// Ignore internal/technical fields.
			if ( '' === $key || 'g-recaptcha-response' === $key || 0 === strpos( $key, '_' ) ) {
				continue;
			}

			$value = awz_antispam_normalize_text( $value );
			if ( '' === $value ) {
				continue;
			}

			$text_parts[] = $value;
		}

		$text = implode( "\n", $text_parts );
		if ( '' === $text ) {
			return $spam;
		}

		$reason = '';
		if ( awz_antispam_text_is_spam( $text, $reason ) ) {
			if ( method_exists( $submission, 'add_spam_log' ) ) {
				$submission->add_spam_log(
					array(
						'agent'  => 'awz-cf7-antispam',
						'reason' => $reason,
					)
				);
			}

			return true;
		}

		return $spam;
	},
	20,
	2
);

if ( AWZ_ANTISPAM_DISABLE_COMMENTS ) {
	add_filter( 'comments_open', '__return_false', 20, 2 );
	add_filter( 'pings_open', '__return_false', 20, 2 );
	add_filter( 'pre_option_default_comment_status', static function () { return 'closed'; } );
	add_filter( 'pre_option_default_ping_status', static function () { return 'closed'; } );
}

add_filter(
	'pre_comment_approved',
	static function ( $approved, $commentdata ) {
		$text = implode(
			"\n",
			array(
				isset( $commentdata['comment_author'] ) ? $commentdata['comment_author'] : '',
				isset( $commentdata['comment_author_email'] ) ? $commentdata['comment_author_email'] : '',
				isset( $commentdata['comment_author_url'] ) ? $commentdata['comment_author_url'] : '',
				isset( $commentdata['comment_content'] ) ? $commentdata['comment_content'] : '',
			)
		);

		$reason = '';
		if ( awz_antispam_text_is_spam( $text, $reason ) ) {
			return 'spam';
		}

		return $approved;
	},
	20,
	2
);
