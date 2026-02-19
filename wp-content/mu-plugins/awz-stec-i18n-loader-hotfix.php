<?php
/**
 * Plugin Name: AWZ STEC i18n Loader Hotfix
 * Description: Bypasses jpI18nLoader fetches for STEC language chunks on STEC frontend pages.
 * Version: 1.0.0
 *
 * Rollback:
 * - Rename or remove this file from wp-content/mu-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'awz_is_stec_frontend_context_for_i18n_hotfix' ) ) {
	/**
	 * Run only where STEC content is rendered.
	 *
	 * @return bool
	 */
	function awz_is_stec_frontend_context_for_i18n_hotfix() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( is_singular( 'stec_event' ) ) {
			return true;
		}

		if ( is_page( 'weiterbildung' ) ) {
			return true;
		}

		global $post;

		return (
			$post instanceof WP_Post
			&& (
				has_shortcode( (string) $post->post_content, 'stec' )
				|| has_shortcode( (string) $post->post_content, 'stachethemes_ec' )
			)
		);
	}
}

if ( ! function_exists( 'awz_patch_stec_i18n_loader' ) ) {
	/**
	 * Patch the globally loaded Jetpack i18n loader on STEC pages.
	 */
	function awz_patch_stec_i18n_loader() {
		if ( ! awz_is_stec_frontend_context_for_i18n_hotfix() ) {
			return;
		}

		if ( ! wp_script_is( 'wp-jp-i18n-loader', 'registered' ) ) {
			return;
		}

		$inline_js = <<<'JS'
(function () {
  if (!window.wp || !window.wp.jpI18nLoader || typeof window.wp.jpI18nLoader.downloadI18n !== 'function') {
    return;
  }

  var loader = window.wp.jpI18nLoader;
  if (loader.__awzStecHotfixApplied) {
    return;
  }

  var original = loader.downloadI18n.bind(loader);

  loader.downloadI18n = async function (assetPath, domain, location) {
    if (domain === 'stec') {
      // Skip STEC JSON fetches to avoid slow 404 chains on live.
      return;
    }

    return original(assetPath, domain, location);
  };

  loader.__awzStecHotfixApplied = true;
})();
JS;

		wp_add_inline_script( 'wp-jp-i18n-loader', $inline_js, 'after' );
	}
}
add_action( 'wp_enqueue_scripts', 'awz_patch_stec_i18n_loader', 99 );
