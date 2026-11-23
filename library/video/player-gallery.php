<?php
/**
 * Video Player Module: gallery video renderer.
 *
 * What it is for
 *   A video in a gallery, drawn in the players' language (library/media/README.md): the poster as
 *   a srcset <img>, the native <video> over it, and nothing else on the picture. The module
 *   library/js/media/gallery-video.js mounts it. Portable: no ACF; the caller passes the
 *   behaviour (library/media/behaviour.php in this theme).
 *
 * Contract
 *   tiagsspace_render_gallery_video( $attachment_id, $args ) -> markup (fills its parent: the
 *   gallery item's ratio box), '' when the file has no playable source.
 *   $args:
 *     behaviour   [ 'behaviour', 'controls', 'muted', 'loop', 'autoplay' ] (required)
 *     poster_id   the attachment's own featured image (Videopack's thumbnail), 0 for none
 *     sizes       the `sizes` attribute for the poster (the gallery item's)
 *     img_attrs   loading / decoding / fetchpriority for the poster, as the gallery's images
 *     title       for the accessible name ("Play <title>")
 *
 * Markup rules
 *   - Class `gallery-player`, never `film-player`: the film-only scripts (fullscreen on play,
 *     adaptive quality, iPhone captions) must never pick a gallery video up.
 *   - `playsinline disablepictureinpicture preload="none"`: nothing downloads before the loader
 *     mounts it (600px ahead); `controls` only for a video the visitor can play, so without
 *     JavaScript it still works natively (html.tg-media hides it until mounted otherwise).
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tiagsspace_render_gallery_video' ) ) {
	/**
	 * @param int   $attachment_id Video attachment ID.
	 * @param array $args          See the file's docblock.
	 * @return string
	 */
	function tiagsspace_render_gallery_video( $attachment_id, $args ) {
		$ladder = tiagsspace_video_mp4_sources( $attachment_id );
		if ( empty( $ladder['sources'] ) ) {
			// Formats the ladder does not list (webm…): the file itself, with its own type.
			$url  = wp_get_attachment_url( $attachment_id );
			$mime = (string) get_post_mime_type( $attachment_id );
			if ( ! $url || 0 !== strpos( $mime, 'video/' ) ) {
				return '';
			}
			$ladder['sources'] = array( array( 'src' => $url, 'type' => $mime, 'size' => 0 ) );
		}
		$b     = wp_parse_args( isset( $args['behaviour'] ) ? $args['behaviour'] : array(), array( 'behaviour' => 'player', 'controls' => true, 'muted' => false, 'loop' => false, 'autoplay' => false ) );
		$title = isset( $args['title'] ) ? (string) $args['title'] : '';

		$html = tiagsspace_media_open(
			'gallery-video',
			array(
				'behaviour' => $b['behaviour'],
				'controls'  => $b['controls'] ? '1' : '0',
				'autoplay'  => $b['autoplay'] ? '1' : '0',
			),
			'media--video'
		);
		$html .= '<div class="media__frame">';

		if ( ! empty( $args['poster_id'] ) ) {
			$html .= tiagsspace_render_picture_from_attachment(
				(int) $args['poster_id'],
				array(),
				isset( $args['sizes'] ) ? $args['sizes'] : '100vw',
				'media__poster',
				'',
				isset( $args['img_attrs'] ) ? (array) $args['img_attrs'] : array( 'loading' => 'lazy', 'decoding' => 'async' )
			);
		}

		$html .= '<video class="media__video gallery-player" playsinline webkit-playsinline disablepictureinpicture preload="none"'
			. ( $b['controls'] ? ' controls' : '' )
			. ( $b['muted'] ? ' muted' : '' )
			. ( $b['loop'] ? ' loop' : '' )
			. '>';
		foreach ( $ladder['sources'] as $source ) {
			$html .= '<source src="' . esc_url( $source['src'] ) . '" type="' . esc_attr( $source['type'] ) . '">';
		}
		$html .= '</video>';

		if ( $b['controls'] ) {
			// The whole picture plays and pauses; on touch a play mark shows while it is not playing.
			$html .= '<button type="button" class="media__hit" aria-label="' . esc_attr( trim( sprintf( __( 'Play %s', 'tiagsspace' ), $title ) ) ) . '"></button>';
			$html .= '<span class="media__playmark media__playmark--large" aria-hidden="true">' . tiagsspace_player_icon( 'play-large', '' ) . '</span>';
		}

		$html .= '</div>' . tiagsspace_media_close();
		return $html;
	}
}
