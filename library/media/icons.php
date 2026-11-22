<?php
/**
 * Player icons: the one sprite and the markup that draws an icon from it.
 *
 * What it is for
 *   Every player draws its icons (play, pause, captions, fullscreen, AirPlay…) from ONE inline
 *   sprite, library/images/player-icons/sprite.svg, built by bin/player-icons-build.py from the
 *   drawings in library/images/player-icons/src/ (drawn like the cursors).
 *
 * Contract
 *   tiagsspace_player_icon( $name, $class ) returns `<svg class="…"><use href="#tg-<name>"/></svg>`.
 *   The first call on a page also returns the sprite itself, once (tiagsspace_once), so an icon
 *   never points at a sprite that is not on the page, whichever template prints the first one.
 *
 * Why inline, never <img> or an external sprite file
 *   The icons' inside is the page's gradient (url(#tg-pagefill)), whose stops read the CSS
 *   variables --tg-paper-bottom / --tg-paper-top, and the line is currentColor. CSS variables do
 *   not reach into an external file, so only an inline sprite follows the page's light / dark
 *   scheme. (The captions icons are drawn in fixed colours: see the build script.)
 *
 * Do not
 *   - edit sprite.svg by hand (run the build script);
 *   - draw a player icon any other way (no icon fonts, no Plyr sprite: Plyr is the engine only).
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The sprite's markup, read once per request.
 *
 * @return string The inline <svg> holding every <symbol id="tg-…">, or '' if the file is missing.
 */
function tiagsspace_player_sprite_markup() {
	static $markup = null;
	if ( null === $markup ) {
		$file   = get_template_directory() . '/library/images/player-icons/sprite.svg';
		$markup = is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : '';
	}
	return $markup;
}

/**
 * One player icon, drawn from the inline sprite.
 *
 * @param string $name  Icon name, the file name in src/ without .svg (e.g. 'play', 'captions-on').
 * @param string $class Class on the <svg>; the player skins size icons by it ('ico' by default).
 * @return string Markup: the sprite on the page's first icon, then the icon.
 */
function tiagsspace_player_icon( $name, $class = 'ico' ) {
	$name   = sanitize_key( $name );
	$sprite = tiagsspace_once( 'player-icon-sprite' ) ? tiagsspace_player_sprite_markup() : '';
	return $sprite . sprintf(
		'<svg class="%1$s" aria-hidden="true" focusable="false"><use href="#tg-%2$s"></use></svg>',
		esc_attr( $class ),
		esc_attr( $name )
	);
}
