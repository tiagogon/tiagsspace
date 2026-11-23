<?php
/**
 * Audio in post text: every <audio> becomes the mix player, when the page is shown.
 *
 * What it is for
 *   The 4K Lento mixes and a few Hyper posts have their sound written into the text: 35 mixes
 *   with a raw <audio class="plyr" controls><source …></audio>, one with WordPress's Audio block
 *   (<figure class="wp-block-audio"><audio src=…>). Decided by Tiago, 22 Nov 2026: swap them at
 *   render time, edit no post. This filter replaces each <audio> element with
 *   render_audio_player( context: content ): the envelope at full width, the row under it, the
 *   lock screen's title and cover (library/audio/player.php).
 *
 * Rules
 *   - Singular front-end views only. Feeds, REST, the editor and excerpts keep the stored text,
 *     so switching this filter off brings the old players back exactly.
 *   - Returns at once when the text has no "<audio" (a strpos before any parsing).
 *   - The file is found as an attachment by its path under uploads (the stored URLs are
 *     production's, also on the local copy), so the player uses the attachment's own URL and
 *     its stored peaks. A file that is not an attachment still plays, with a flat envelope.
 *   - SoundCloud links and iframes are third party and stay as they are.
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'tiagsspace_media_content_audio', 20 );

/**
 * The attachment ID of an uploads URL, by its path (any host), or 0.
 *
 * @param string $url
 * @return int
 */
function tiagsspace_media_attachment_by_url( $url ) {
	if ( ! preg_match( '~/wp-content/uploads/(.+)$~', (string) wp_parse_url( $url, PHP_URL_PATH ), $m ) ) {
		return 0;
	}
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
		rawurldecode( $m[1] )
	) );
}

/**
 * @param string $content
 * @return string
 */
function tiagsspace_media_content_audio( $content ) {
	if ( false === stripos( $content, '<audio' ) || ! is_singular() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_admin() || ! in_the_loop() ) {
		return $content;
	}
	$post_id = get_the_ID();
	$title   = get_the_title( $post_id );
	$cover   = get_the_post_thumbnail_url( $post_id, 'medium' );
	$album   = function_exists( 'tiagsspace_series_ui_name' ) ? tiagsspace_series_ui_name( get_post_type( $post_id ) ) : '';

	return preg_replace_callback( '~<audio\b([^>]*)>(.*?)</audio>~is', function ( $m ) use ( $title, $cover, $album ) {
		$src = '';
		if ( preg_match( '~\ssrc=["\']([^"\']+)["\']~i', $m[1], $s ) ) {
			$src = $s[1];
		} elseif ( preg_match( '~<source\b[^>]*\ssrc=["\']([^"\']+)["\']~i', $m[2], $s ) ) {
			$src = $s[1];
		}
		if ( ! $src ) {
			return $m[0];
		}
		$src  = html_entity_decode( $src );
		$id   = tiagsspace_media_attachment_by_url( $src );
		$mime = $id ? (string) get_post_mime_type( $id ) : '';
		if ( ! $mime ) {
			$type = wp_check_filetype( (string) wp_parse_url( $src, PHP_URL_PATH ) );
			$mime = $type['type'] ? $type['type'] : 'audio/mpeg';
		}
		$player = render_audio_player( array(
			'attachment_id' => $id,
			'src'           => $id ? wp_get_attachment_url( $id ) : $src,
			'mime'          => $mime,
			'title'         => $title,
			'context'       => 'content',
			'album'         => $album,
			'artwork'       => $cover ? $cover : '',
		) );
		return $player ? $player : $m[0];
	}, $content );
}
