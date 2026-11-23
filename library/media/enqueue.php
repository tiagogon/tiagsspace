<?php
/**
 * Media players: what reaches the page.
 *
 * What it is for
 *   1. On every page, at the top of <head> (inline, no request): two classes on <html>.
 *        tg-media  players' native elements stay hidden until their module has mounted, so the
 *                  browser's own controls never flash (without JavaScript the class is never set
 *                  and the native player shows: the no-JS fallback);
 *        tg-touch  a touch screen: the play mark shows on players at rest (the design page's test).
 *   2. On a page with a player, once: the loader (library/js/media/loader.js) as a module, with
 *      the version of the whole library/js/media/ folder, which every module import carries.
 *      Printed by tiagsspace_media_open() with the first player, so pages without one load nothing.
 *
 * Do not
 *   - enqueue player modules one by one (the loader imports them when needed);
 *   - give the loader an unversioned URL (the server caches static files for a year).
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	function () {
		echo "<script>(function(d){var h=d.documentElement;h.classList.add('tg-media');"
			. "if(matchMedia('(hover: none), (pointer: coarse)').matches||navigator.maxTouchPoints>1)h.classList.add('tg-touch');})(document);</script>\n";
	},
	1
);

// Videopack (video-embed-thumbnail-generator) only encodes now: its player (Video.js) is no longer
// printed anywhere, so its stylesheets stop loading on the front end. A post that still has a
// [KGVID] shortcode in its text keeps them.
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_singular() && false !== strpos( (string) get_post_field( 'post_content', get_queried_object_id() ), '[KGVID' ) ) {
			return;
		}
		foreach ( array( 'video-js', 'video-js-kg-skin', 'kgvid_video_styles' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
	},
	20
);

/**
 * One version for the whole module folder: changes when any media module changes.
 *
 * @return string Short hash.
 */
function tiagsspace_media_js_version() {
	static $version = null;
	if ( null === $version ) {
		$stamps = array();
		foreach ( (array) glob( get_template_directory() . '/library/js/media/*.js' ) as $file ) {
			$stamps[] = basename( $file ) . ':' . filemtime( $file );
		}
		$stamps[] = 'plyr:' . tiagsspace_asset_ver( '/library/js/plyr/plyr.js' );
		$version  = substr( md5( implode( '|', $stamps ) ), 0, 10 );
	}
	return $version;
}

/**
 * The loader and its config. Called once per page, by tiagsspace_media_open().
 *
 * @return string Markup.
 */
function tiagsspace_media_loader_markup() {
	$uri    = get_template_directory_uri();
	$config = array(
		'plyr' => $uri . '/library/js/plyr/plyr.js?ver=' . tiagsspace_asset_ver( '/library/js/plyr/plyr.js' ),
	);
	return '<script>window.tiagsspaceMediaConfig=' . wp_json_encode( $config ) . ';</script>'
		. '<script type="module" src="' . esc_url( $uri . '/library/js/media/loader.js?v=' . tiagsspace_media_js_version() ) . '"></script>';
}
