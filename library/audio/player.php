<?php
/**
 * Audio player: the envelope (the `mix` cursor's drawing, made long), for gallery tiles and mixes.
 *
 * What it is for
 *   Every audio player on the site (library/media/README.md): a gallery tile (`context: gallery`)
 *   or a mix in a post's text (`context: content`). Decided on the design page,
 *   library/players/test.html (Tiago, 22 Nov 2026):
 *     - no title, no sound control; a click on the envelope plays or pauses, and only the
 *       playhead's edge can be dragged (scrub cursor near it, one seek on release);
 *     - tile: the envelope fills the cell, no time; on touch the plain play mark while paused;
 *     - mix: the envelope at full width, then one row: the play lens (touch only), AirPlay (where
 *       Safari has a device), the time ("00:47:12", "12:03 / 47:12" once started);
 *     - dB, Stereo L/R (left above the spine, right below), contrast 0.8, "Finer" detail.
 *   The module library/js/media/audio-envelope.js mounts it; this file prints the resting look,
 *   envelope included (computed here from the stored peaks), so the first paint is final.
 *
 * Contract
 *   render_audio_player( $args ) -> markup, '' without a source.
 *   $args: attachment_id (for the peaks, library/audio/peaks.php), src, mime, title,
 *          context ('gallery' | 'content'), album and artwork (Media Session on the lock screen,
 *          mixes only).
 *
 * Portable: no ACF. Uses the theme's media glue (tiagsspace_media_open(), the icon sprite).
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The envelope's numbers, the same maths as library/js/media/envelope.js (dB of the RMS, floor
 * -48 dB; contrast over both channels together; max per bucket). $cap is the ceiling's
 * percentile: 0.98 for mixes, 1 for gallery audio (no flat tops; Tiago, 24 Nov).
 *
 * @param array $peaks    Stored peaks (rmsL, rmsR).
 * @param int   $n        Points.
 * @param float $contrast
 * @param float $cap
 * @return array{0: float[], 1: float[]} Top (left) and bottom (right), 0..1.
 */
function tiagsspace_audio_envelope_values( $peaks, $n, $contrast = 0.8, $cap = 0.98 ) {
	$db    = function ( $v ) { return max( 0, ( 20 * log10( max( $v, 1e-6 ) ) + 48 ) / 48 ); };
	$left  = array_map( $db, $peaks['rmsL'] );
	$right = array_map( $db, $peaks['rmsR'] );
	$all   = array_merge( $left, $right );
	sort( $all );
	$lo    = $all[ (int) floor( count( $all ) * 0.1 ) ] * $contrast;
	$hi    = $all[ min( count( $all ) - 1, (int) floor( count( $all ) * $cap ) ) ] ?: 1;
	$scale = function ( $v ) use ( $lo, $hi ) { return min( 1, max( 0, ( $v - $lo ) / ( ( $hi - $lo ) ?: 1 ) ) ); };
	$out   = array();
	foreach ( array( $left, $right ) as $list ) {
		$res = array_fill( 0, $n, 0 );
		$len = count( $list );
		foreach ( $list as $i => $v ) {
			$k = min( $n - 1, (int) floor( $i / $len * $n ) );
			$v = $scale( $v );
			if ( $v > $res[ $k ] ) {
				$res[ $k ] = $v;
			}
		}
		$out[] = $res;
	}
	return $out;
}

/**
 * The envelope's SVG path for a w x h box.
 *
 * @return string
 */
function tiagsspace_audio_envelope_path( $top, $bottom, $w, $h ) {
	$n   = count( $top );
	$mid = $h / 2;
	$amp = $h / 2 - 2;
	$x   = function ( $i ) use ( $n, $w ) { return 1 + $i / max( 1, $n - 1 ) * ( $w - 2 ); };
	$d   = '';
	for ( $i = 0; $i < $n; $i++ ) {
		$d .= ( $i ? ' L' : 'M' ) . round( $x( $i ), 1 ) . ' ' . round( $mid - max( 0.04, $top[ $i ] ) * $amp, 1 );
	}
	for ( $i = $n - 1; $i >= 0; $i-- ) {
		$d .= ' L' . round( $x( $i ), 1 ) . ' ' . round( $mid + max( 0.04, $bottom[ $i ] ) * $amp, 1 );
	}
	return $d . ' Z';
}

/**
 * The peaks the browser needs, resampled (max per bucket), as compact JSON.
 *
 * @return string
 */
function tiagsspace_audio_peaks_payload( $peaks, $n ) {
	$pack = function ( $list ) use ( $n ) {
		$res = array_fill( 0, $n, 0 );
		$len = count( $list );
		foreach ( $list as $i => $v ) {
			$k = min( $n - 1, (int) floor( $i / $len * $n ) );
			if ( $v > $res[ $k ] ) {
				$res[ $k ] = $v;
			}
		}
		return array_map( function ( $v ) { return round( $v, 3 ); }, $res );
	};
	return wp_json_encode( array(
		'duration' => (float) $peaks['duration'],
		'rmsL'     => $pack( $peaks['rmsL'] ),
		'rmsR'     => $pack( $peaks['rmsR'] ),
	) );
}

if ( ! function_exists( 'render_audio_player' ) ) {
	/**
	 * @param array $args See the file's docblock.
	 * @return string
	 */
	function render_audio_player( $args ) {
		$a = wp_parse_args( $args, array( 'attachment_id' => 0, 'src' => '', 'mime' => 'audio/mpeg', 'title' => '', 'context' => 'gallery', 'album' => '', 'artwork' => '' ) );
		if ( ! $a['src'] ) {
			return '';
		}
		$tile  = 'gallery' === $a['context'];
		$peaks = $a['attachment_id'] ? tiagsspace_audio_peaks_get( (int) $a['attachment_id'] ) : null;
		$peaks = ( $peaks && ! empty( $peaks['rmsL'] ) ) ? $peaks : null;
		$title = trim( (string) $a['title'] );

		// The resting envelope, drawn for a nominal box and stretched to the real one
		// (non-scaling strokes keep the 1.5px line); the module redraws it at the real size.
		list( $w, $h, $n ) = $tile ? array( 300, 400, 100 ) : array( 1200, 120, 600 );
		$path = '';
		if ( $peaks ) {
			list( $top, $bottom ) = tiagsspace_audio_envelope_values( $peaks, $n, 0.8, $tile ? 1.0 : 0.98 );
			$path = tiagsspace_audio_envelope_path( $top, $bottom, $w, $h );
		}
		$grad = 'tg-env-' . wp_unique_id();
		$dur  = $peaks ? (float) $peaks['duration'] : 0;

		$html  = tiagsspace_media_open(
			$tile ? 'gallery-audio' : 'content-audio',
			array_filter( array( 'title' => $title, 'album' => $a['album'], 'artwork' => $a['artwork'] ) ),
			'media--audio ' . ( $tile ? 'media--tile' : 'media--mix' )
		);
		$html .= '<div class="media__frame">';
		$html .= '<div class="env__wave" role="slider" tabindex="0" aria-label="' . esc_attr( trim( 'Play ' . $title ) ) . '" aria-valuemin="0" aria-valuemax="' . esc_attr( round( $dur ) ) . '" aria-valuenow="0">';
		$html .= '<svg class="env__shape" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" aria-hidden="true">'
			. '<defs><linearGradient id="' . esc_attr( $grad ) . '" x1="0" y1="1" x2="0" y2="0"><stop offset="0" style="stop-color:var(--tg-paper-bottom)"/><stop offset="1" style="stop-color:var(--tg-paper-top)"/></linearGradient></defs>'
			. '<path class="env__hollow" fill="url(#' . esc_attr( $grad ) . ')" d="' . esc_attr( $path ) . '"/>'
			. '<path class="env__filled" d="' . esc_attr( $path ) . '"/>'
			. '</svg>';
		$html .= '<span class="env__pointer"></span>';
		if ( $tile ) {
			$html .= '<span class="media__playmark" aria-hidden="true">' . tiagsspace_player_icon( 'play', '' ) . '</span>';
		}
		$html .= '</div>';
		if ( ! $tile ) {
			$html .= '<div class="env__foot">'
				. '<button type="button" class="media-btn env__lens" aria-label="' . esc_attr( trim( 'Play ' . $title ) ) . '">' . tiagsspace_player_icon( 'play-large', 'env__lens-icon' ) . '</button>'
				. '<button type="button" class="media-btn env__airplay" aria-label="AirPlay" hidden>' . tiagsspace_player_icon( 'airplay' ) . '</button>'
				. '<span class="env__spacer"></span>'
				. '<span class="env__time">' . esc_html( $dur ? tiagsspace_audio_clock( $dur ) : '' ) . '</span>'
				. '</div>';
		}
		$html .= '<audio class="media__audio" preload="none" controls><source src="' . esc_url( $a['src'] ) . '" type="' . esc_attr( $a['mime'] ) . '"></audio>';
		if ( $peaks ) {
			$html .= '<script type="application/json" class="media__peaks">' . tiagsspace_audio_peaks_payload( $peaks, $tile ? 500 : 1000 ) . '</script>';
		}
		$html .= '</div>' . tiagsspace_media_close();
		return $html;
	}
}

/**
 * "04:07", "01:02:03": the players' time.
 *
 * @param float $s Seconds.
 * @return string
 */
function tiagsspace_audio_clock( $s ) {
	$s = max( 0, (int) floor( $s ) );
	$h = intdiv( $s, 3600 );
	$m = intdiv( $s % 3600, 60 );
	return ( $h ? sprintf( '%02d:', $h ) : '' ) . sprintf( '%02d:%02d', $m, $s % 60 );
}
