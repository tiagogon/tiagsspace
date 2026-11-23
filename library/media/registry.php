<?php
/**
 * Media players: the list of kinds, and the opening tag every renderer prints.
 *
 * What it is for
 *   A player is a *kind* (library/media/README.md). This file is the one list of kinds and the
 *   JS module that drives each, and the one place that writes a player's outer element, so every
 *   player on a page is found by the loader the same way.
 *
 * Contract
 *   tiagsspace_media_kinds()                       kind => [ 'module' => file in library/js/media/ ]
 *   tiagsspace_media_open( $kind, $attrs, $class ) '<div class="media …" data-media-kind data-media-module …>'
 *                                                  plus, on the page's first player, the icon
 *                                                  sprite and the loader (once each).
 *   tiagsspace_media_close()                       '</div>'
 *   $attrs: extra data-* attributes, name => value (escaped here); names without "data-".
 *
 * Do not
 *   - print a player's outer element by hand: always through tiagsspace_media_open();
 *   - add a kind anywhere else (one row here, then its renderer, module, skin and icons).
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every player kind and the module that mounts it.
 *
 * @return array<string, array{module: string}>
 */
function tiagsspace_media_kinds() {
	return array(
		'gallery-video' => array( 'module' => 'gallery-video' ),
		'gallery-audio' => array( 'module' => 'audio-envelope' ),
		'content-audio' => array( 'module' => 'audio-envelope' ),
		'film'          => array( 'module' => 'film' ),
	);
}

/**
 * The opening tag of one player.
 *
 * @param string $kind  A key of tiagsspace_media_kinds().
 * @param array  $attrs Extra data attributes, name (without "data-") => value.
 * @param string $class Extra classes.
 * @return string Markup; '' for an unknown kind.
 */
function tiagsspace_media_open( $kind, $attrs = array(), $class = '' ) {
	$kinds = tiagsspace_media_kinds();
	if ( ! isset( $kinds[ $kind ] ) ) {
		return '';
	}
	$html = '';
	if ( tiagsspace_once( 'media-loader' ) ) {
		$html .= tiagsspace_player_sprite_once() . tiagsspace_media_loader_markup();
	}
	$data = array(
		'media-kind'   => $kind,
		'media-module' => $kinds[ $kind ]['module'],
	) + $attrs;
	$html .= '<div class="' . esc_attr( trim( 'media ' . $class ) ) . '"';
	foreach ( $data as $name => $value ) {
		$html .= ' data-' . sanitize_key( $name ) . '="' . esc_attr( (string) $value ) . '"';
	}
	return $html . '>';
}

/**
 * The closing tag of one player.
 *
 * @return string
 */
function tiagsspace_media_close() {
	return '</div>';
}
