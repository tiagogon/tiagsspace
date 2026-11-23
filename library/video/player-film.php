<?php
/**
 * Video Player Module: the film, on the Edge player (Tiago, 22 Nov 2026).
 *
 * What it is for
 *   An art film player: the picture alone. The cursor plays and pauses; the progress is the
 *   film's own soundtrack rising from the bottom edge, there only while the pointer is on that
 *   edge; the time and the icons (captions, AirPlay, fullscreen) show while paused or while the
 *   mouse moves. Decided on the design page, library/players/test.html. The module
 *   library/js/media/film.js mounts it.
 *
 * Playback (unchanged from the device-tested path; read "CAPTIONS ON HLS FILMS" in
 * .claude/rules/instructions AI.md before touching it)
 *   - An .m3u8 attachment plays HLS: hls.js wherever MediaSource exists, native elsewhere;
 *     an iPhone film with in-manifest subtitles plays in Apple's own player (no layer at all),
 *     because only native playback paints those renditions there.
 *   - In-manifest renditions and sidecar <track>s are mutually exclusive (both would load and
 *     every cue would render twice): sidecar tracks only when the bundle has no renditions.
 *   - Any other video plays the progressive MP4 ladder (tiagsspace_video_mp4_sources()).
 *   - No data-plyr-config: Plyr reads that attribute last and it would bring its controls back.
 *
 * Contract
 *   tiagsspace_render_film( $attachment_id, $args ) -> markup ('' without a source)
 *   $args: player_options (ACF film_player_options: muted / autoplay / loop), caption_tracks
 *          (the theme's caption rows), caption_color (hex, '' = white), caption_contrast
 *          ('box' | 'shadow'), title, poster_url.
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tiagsspace_render_film' ) ) {
	/**
	 * @param int   $attachment_id The film's video attachment (.m3u8 or a video file).
	 * @param array $args          See the file's docblock.
	 * @return string
	 */
	function tiagsspace_render_film( $attachment_id, $args ) {
		$hls     = tiagsspace_video_is_hls( $attachment_id );
		$sources = array();
		if ( $hls ) {
			$playlist = wp_get_attachment_url( $attachment_id );
			if ( $playlist ) {
				$sources[] = array( 'src' => $playlist, 'type' => 'application/vnd.apple.mpegurl' );
			}
		} else {
			$ladder  = tiagsspace_video_mp4_sources( $attachment_id );
			$sources = $ladder['sources'];
		}
		if ( ! $sources ) {
			return '';
		}

		$manifest_subs = $hls && (bool) get_post_meta( $attachment_id, '_tiagsspace_hls_subs', true );
		$tracks        = $manifest_subs ? array() : tiagsspace_video_caption_tracks( $attachment_id, $args );
		$has_captions  = $manifest_subs || ! empty( $tracks );
		$options       = isset( $args['player_options'] ) && is_array( $args['player_options'] ) ? $args['player_options'] : array();
		$ambient       = in_array( 'loop', $options, true ) || in_array( 'autoplay', $options, true );
		$poster        = tiagsspace_video_poster( $args );
		$title         = isset( $args['title'] ) ? (string) $args['title'] : '';
		$peaks         = function_exists( 'tiagsspace_audio_peaks_get' ) ? tiagsspace_audio_peaks_get( $attachment_id ) : null;
		$has_sound     = $peaks && ! empty( $peaks['rms'] );

		$attrs = array( 'title' => $title );
		if ( $hls ) {
			$attrs['hls'] = '1';
		}
		if ( $manifest_subs ) {
			$attrs['manifest-subs'] = '1';
		}
		if ( $ambient ) {
			$attrs['ambient'] = '1';
		}
		$class = 'media--video media--film is-paused at-start' . ( $has_sound ? ' has-sound' : '' )
			. ( isset( $args['caption_contrast'] ) && 'shadow' === $args['caption_contrast'] ? ' cc-shadow' : '' );
		$html  = tiagsspace_media_open( 'film', $attrs, $class );
		if ( ! empty( $args['caption_color'] ) ) {
			// The film's caption colour (ACF): on the outer element, read by the caption text.
			$html = preg_replace( '/>$/', ' style="--cc-color: ' . esc_attr( $args['caption_color'] ) . '">', $html, 1 );
		}
		$html .= '<div class="media__frame">';
		if ( $poster ) {
			$html .= '<img class="media__poster" src="' . $poster . '" alt="" decoding="async">';
		}

		// The <video>: the device-tested attributes; poster too, for Apple's own player.
		$html .= '<video class="media__video film-player' . ( $hls ? ' film-player--hls' : '' ) . '"'
			. ( $hls ? ' data-hls-src="' . esc_url( $sources[0]['src'] ) . '"' : '' )
			. ( $manifest_subs ? ' data-manifest-subs="1"' : '' )
			. ( $poster ? ' poster="' . $poster . '"' : '' )
			. ' preload="none" webkit-playsinline playsinline disablepictureinpicture controls'
			. ( in_array( 'muted', $options, true ) ? ' muted' : '' )
			. ( in_array( 'loop', $options, true ) ? ' loop' : '' )
			. ( in_array( 'autoplay', $options, true ) ? ' data-autoplay="1"' : '' )
			. '>';
		foreach ( $sources as $source ) {
			$html .= '<source src="' . esc_url( $source['src'] ) . '" type="' . esc_attr( $source['type'] ) . '">';
		}
		$default_index = 0;
		foreach ( $tracks as $index => $track ) {
			if ( ! empty( $track['default'] ) ) {
				$default_index = $index;
				break;
			}
		}
		foreach ( $tracks as $index => $track ) {
			// The values come pre-escaped from tiagsspace_video_caption_tracks().
			$html .= '<track kind="' . $track['kind'] . '" src="' . $track['src'] . '" srclang="' . $track['srclang'] . '" label="' . $track['label'] . '"' . ( $index === $default_index ? ' default' : '' ) . '>';
		}
		$html .= '</video>';

		// The Edge layer: printed at rest, so the first paint is final; film.js brings it to life.
		$html .= '<div class="film__layer">'
			. '<button type="button" class="film__hit" aria-label="' . esc_attr( trim( 'Play ' . $title ) ) . '"></button>'
			. '<span class="media__playmark media__playmark--large" aria-hidden="true">' . tiagsspace_player_icon( 'play-large', '' ) . '</span>'
			. '<div class="film__chrome">'
			. '<span class="film__time" aria-live="off">' . ( $has_sound ? esc_html( tiagsspace_audio_clock( $peaks['duration'] ) ) : '' ) . '</span>'
			. ( $has_captions
				? '<span class="cc-wrap"><button type="button" class="media-btn film__cc" aria-label="Captions: Off" aria-haspopup="true" aria-expanded="false">' . tiagsspace_player_icon( 'captions-off' ) . '</button>'
					. '<span class="cc-menu as-box" role="menu" aria-label="Captions"><span class="cc-list" role="none"></span></span></span>'
				: '' )
			. '<button type="button" class="media-btn film__airplay" aria-label="AirPlay" hidden>' . tiagsspace_player_icon( 'airplay' ) . '</button>'
			. '<button type="button" class="media-btn film__fs" aria-label="Fullscreen">' . tiagsspace_player_icon( 'enter-fullscreen' ) . '</button>'
			. '</div>'
			. '<div class="film__cc-text" aria-live="off"></div>'
			. '<div class="film__rail" role="slider" tabindex="-1" aria-label="Seek" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">'
			. '<span class="film__line"></span>'
			. '<svg class="film__snd" aria-hidden="true" preserveAspectRatio="none"><path class="film__snd-hollow"/><path class="film__snd-filled"/></svg>'
			. '<span class="film__pointer"></span>'
			. '</div>'
			. '</div>';
		if ( $has_sound ) {
			$html .= '<script type="application/json" class="media__peaks">' . wp_json_encode( array(
				'duration' => (float) $peaks['duration'],
				'rms'      => array_map( function ( $v ) { return round( $v, 3 ); }, tiagsspace_film_peaks_resample( $peaks['rms'], 1000 ) ),
			) ) . '</script>';
		}
		$html .= '</div>' . tiagsspace_media_close();
		return $html;
	}
}

/**
 * Max per bucket (a short loud hit is never lost).
 *
 * @param float[] $values
 * @param int     $n
 * @return float[]
 */
function tiagsspace_film_peaks_resample( $values, $n ) {
	$out = array_fill( 0, $n, 0 );
	$len = count( $values );
	foreach ( $values as $i => $v ) {
		$k = min( $n - 1, (int) floor( $i / $len * $n ) );
		if ( $v > $out[ $k ] ) {
			$out[ $k ] = $v;
		}
	}
	return $out;
}
