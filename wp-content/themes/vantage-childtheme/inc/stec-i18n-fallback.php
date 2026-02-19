<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'awz_stec_i18n_should_run' ) ) {
	/**
	 * Scope STEC translation fallback to relevant frontend requests.
	 *
	 * @param string $domain Translation domain.
	 * @param string $handle Script handle.
	 * @return bool
	 */
	function awz_stec_i18n_should_run( $domain, $handle ) {
		if ( 'stec' !== $domain ) {
			return false;
		}

		if ( function_exists( 'awz_is_stec_handle' ) ) {
			if ( ! awz_is_stec_handle( $handle ) ) {
				return false;
			}
		} elseif ( ! is_string( $handle ) || ( 'stec' !== $handle && 0 !== strpos( $handle, 'stec-' ) ) ) {
			return false;
		}

		if ( function_exists( 'awz_is_frontend_non_admin_request' ) ) {
			if ( ! awz_is_frontend_non_admin_request() ) {
				return false;
			}
		} elseif ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( function_exists( 'awz_is_stec_frontend_context' ) && ! awz_is_stec_frontend_context() ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'awz_stec_i18n_get_plural_forms' ) ) {
	/**
	 * Resolve plural forms header from loaded translations.
	 *
	 * @param object $translations Loaded translation object.
	 * @return string
	 */
	function awz_stec_i18n_get_plural_forms( $translations ) {
		if ( is_object( $translations ) ) {
			$headers = $translations->headers;
			if ( is_array( $headers ) ) {
				if ( ! empty( $headers['plural-forms'] ) ) {
					return (string) $headers['plural-forms'];
				}

				if ( ! empty( $headers['Plural-Forms'] ) ) {
					return (string) $headers['Plural-Forms'];
				}
			}
		}

		return 'nplurals=2; plural=n != 1;';
	}
}

if ( ! function_exists( 'awz_stec_i18n_normalize_entry_key' ) ) {
	/**
	 * Build JED key from translation entry.
	 *
	 * @param object $entry Translation entry.
	 * @return string
	 */
	function awz_stec_i18n_normalize_entry_key( $entry ) {
		$singular = isset( $entry->singular ) ? (string) $entry->singular : '';

		if ( '' === $singular ) {
			return '';
		}

		if ( empty( $entry->context ) ) {
			return $singular;
		}

		return (string) $entry->context . "\x04" . $singular;
	}
}

if ( ! function_exists( 'awz_stec_pre_load_script_translations' ) ) {
	/**
	 * Fallback JS translations for STEC when JSON files are missing.
	 *
	 * @param string|false|null $translations Existing script translation JSON.
	 * @param string            $file         Candidate translation file.
	 * @param string            $handle       Script handle.
	 * @param string            $domain       Text domain.
	 * @return string|false|null
	 */
	function awz_stec_pre_load_script_translations( $translations, $file, $handle, $domain ) {
		if ( null !== $translations || ! awz_stec_i18n_should_run( $domain, $handle ) ) {
			return $translations;
		}

		if ( ! empty( $file ) && is_readable( $file ) ) {
			// Native JSON exists, let WordPress load it.
			return $translations;
		}

		$locale = determine_locale();

		if ( ! $locale ) {
			$locale = get_locale();
		}

		$mo_path = WP_PLUGIN_DIR . '/stachethemes_event_calendar/languages/stec-' . $locale . '.mo';
		if ( is_readable( $mo_path ) ) {
			load_textdomain( 'stec', $mo_path );
		}

		global $l10n;

		if ( empty( $l10n['stec'] ) || ! is_object( $l10n['stec'] ) ) {
			return $translations;
		}

		$entries = $l10n['stec']->entries;
		if ( ! is_array( $entries ) ) {
			$entries = (array) $entries;
		}
		if ( empty( $entries ) ) {
			return $translations;
		}

		$messages = array(
			'' => array(
				'domain'       => 'messages',
				'lang'         => $locale,
				'plural-forms' => awz_stec_i18n_get_plural_forms( $l10n['stec'] ),
			),
		);

		foreach ( $entries as $entry ) {
			if ( ! is_object( $entry ) ) {
				continue;
			}

			$key = awz_stec_i18n_normalize_entry_key( $entry );
			if ( '' === $key || empty( $entry->translations ) || ! is_array( $entry->translations ) ) {
				continue;
			}

			$translated = array();
			foreach ( $entry->translations as $translation ) {
				$translation = (string) $translation;
				if ( '' !== $translation ) {
					$translated[] = $translation;
				}
			}

			if ( ! empty( $translated ) ) {
				$messages[ $key ] = array_values( $translated );
			}
		}

		if ( count( $messages ) <= 1 ) {
			return $translations;
		}

		$payload = array(
			'translation-revision-date' => '',
			'generator'                 => 'AWZ STEC JS i18n fallback',
			'domain'                    => 'messages',
			'locale_data'               => array(
				'messages' => $messages,
			),
		);

		return wp_json_encode( $payload );
	}
}

add_filter( 'pre_load_script_translations', 'awz_stec_pre_load_script_translations', 10, 4 );
