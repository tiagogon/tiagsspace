<?php
/**
 * Audio peaks: a file's loudness shape, computed once on the server, for the players to draw.
 *
 * What it is for
 *   Mixes and gallery audio draw their envelope, films their sound edge, from these numbers
 *   (library/js/media/envelope.js). They are computed with ffmpeg once per file, in the
 *   background (WP-Cron, a minute after upload), never during a page request, and stored in
 *   the attachment's meta. Decided by Tiago, 22 Nov 2026 (the plan: "Envelope data is computed
 *   on the server, on upload, with ffmpeg"; "the stored data reduces to the RMS lists").
 *
 * The numbers (the same as bin/audio-peaks.py, the reference, for these three lists)
 *   1. ffmpeg decodes the sound to 16-bit PCM at 8 kHz, once as mono, once as stereo.
 *   2. The samples are cut into 2,000 equal slices; per slice the RMS of the mono mix (`rms`),
 *      of the left (`rmsL`) and of the right channel (`rmsR`). A mono file gives L = R.
 *   3. The three lists are divided by the loudest slice of the three, so the loudest is 1 and
 *      left and right stay comparable. Rounded to 3 decimals.
 *   Stored in meta `_tiagsspace_audio_peaks` as JSON:
 *     {"v":2,"slices":2000,"duration":2799.1,"rms":[…],"rmsL":[…],"rmsR":[…]}
 *   A file without sound stores {"v":2,"none":true}: the film draws its flat line.
 *   dB, contrast and the resampling to the player's width happen in the browser (or in the
 *   renderer for the first paint); they are not stored.
 *
 * Which files
 *   Every audio file, every video file that is not an encode of another video (Videopack's
 *   resolutions are children of their master and carry the same sound), and every HLS film
 *   (.m3u8 attachment, read through its playlist).
 *
 * Contract
 *   tiagsspace_audio_peaks_get( $id )             array|null  the stored data
 *   tiagsspace_audio_peaks_generate( $id, $force ) true|WP_Error  compute and store now
 *   tiagsspace_audio_peaks_wanted( $id )           bool
 *   wp tiagsspace audio-peaks [<id>…] [--all] [--force]   (existing files; run it detached on
 *   production: nohup + a log, a long run outlives an SSH session)
 *   Log: wp-content/uploads/audio-peaks.log
 *
 * Portable: no ACF, no theme rules; ffmpeg's path can be changed with the filter
 * `tiagsspace_audio_ffmpeg_path` (default: the first of /usr/bin, /usr/local/bin, /opt/homebrew/bin).
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TIAGSSPACE_PEAKS_META   = '_tiagsspace_audio_peaks';
const TIAGSSPACE_PEAKS_SLICES = 2000;
const TIAGSSPACE_PEAKS_RATE   = 8000;

/**
 * Full path of an ffmpeg tool, or '' when it is not installed.
 *
 * @param string $tool 'ffmpeg' or 'ffprobe'.
 * @return string
 */
function tiagsspace_audio_tool( $tool ) {
	$found = '';
	foreach ( array( '/usr/bin/', '/usr/local/bin/', '/opt/homebrew/bin/' ) as $dir ) {
		if ( is_executable( $dir . $tool ) ) {
			$found = $dir . $tool;
			break;
		}
	}
	return (string) apply_filters( 'tiagsspace_audio_ffmpeg_path', $found, $tool );
}

/**
 * Append a timestamped line to uploads/audio-peaks.log.
 *
 * @param string $line
 */
function tiagsspace_audio_peaks_log( $line ) {
	$uploads = wp_get_upload_dir();
	@file_put_contents( $uploads['basedir'] . '/audio-peaks.log', '[' . gmdate( 'Y-m-d H:i:s' ) . "] $line\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore
}

/**
 * Should this attachment have peaks?
 *
 * @param int $id Attachment ID.
 * @return bool
 */
function tiagsspace_audio_peaks_wanted( $id ) {
	$mime = (string) get_post_mime_type( $id );
	if ( 0 === strpos( $mime, 'audio/' ) || 'application/vnd.apple.mpegurl' === $mime ) {
		return true;
	}
	if ( 0 !== strpos( $mime, 'video/' ) ) {
		return false;
	}
	$parent = (int) wp_get_post_parent_id( $id );
	return ! ( $parent && 'attachment' === get_post_type( $parent ) );   // an encode of another video
}

/**
 * The stored peaks.
 *
 * @param int $id Attachment ID.
 * @return array|null
 */
function tiagsspace_audio_peaks_get( $id ) {
	$raw = get_post_meta( $id, TIAGSSPACE_PEAKS_META, true );
	if ( ! $raw ) {
		return null;
	}
	$data = json_decode( (string) $raw, true );
	return is_array( $data ) ? $data : null;
}

/**
 * Run a command (an array of arguments, never a shell string).
 *
 * @param string[] $cmd
 * @return array{0: int, 1: string, 2: string} exit code, stdout, stderr
 */
function tiagsspace_audio_run( array $cmd ) {
	$proc = proc_open( $cmd, array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	if ( ! is_resource( $proc ) ) {
		return array( -1, '', 'could not start ' . $cmd[0] );
	}
	$out = stream_get_contents( $pipes[1] );
	$err = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	return array( proc_close( $proc ), (string) $out, (string) $err );
}

/**
 * ffmpeg's input options for a file: an HLS playlist may list fMP4 segments (.m4s), which the
 * HLS reader refuses unless told otherwise.
 *
 * @param string $file
 * @return string[]
 */
function tiagsspace_audio_input_args( $file ) {
	$args = array();
	if ( preg_match( '/\.m3u8$/i', $file ) ) {
		$args = array( '-allowed_extensions', 'ALL', '-protocol_whitelist', 'file,crypto,data' );
	}
	return array_merge( $args, array( '-i', $file ) );
}

/**
 * RMS per slice of a raw s16le file, per channel, raw (not normalised). Read in chunks, so an
 * hour of sound never sits in memory. Slices as bin/audio-peaks.py cuts them.
 *
 * @param string $pcm      Path of the raw PCM file.
 * @param int    $channels 1 or 2.
 * @param int    $slices
 * @return array<int, float[]> One list per channel.
 */
function tiagsspace_audio_rms_slices( $pcm, $channels, $slices ) {
	$frames = intdiv( (int) filesize( $pcm ), 2 * $channels );
	$sum    = array_fill( 0, $channels, array_fill( 0, $slices, 0.0 ) );
	$len    = array_fill( 0, $slices, 0 );
	$fh     = fopen( $pcm, 'rb' );

	if ( $frames < $slices ) {
		// A very short file: read it whole and cut it exactly as the script does (empty slices = 0).
		$all = $frames ? array_values( unpack( 's*', fread( $fh, $frames * 2 * $channels ) ) ) : array();
		fclose( $fh );
		$out = array_fill( 0, $channels, array_fill( 0, $slices, 0.0 ) );
		for ( $i = 0; $i < $slices; $i++ ) {
			$a = intdiv( $frames * $i, $slices );
			$b = min( max( intdiv( $frames * ( $i + 1 ), $slices ), $a + 1 ), $frames );
			for ( $c = 0; $c < $channels; $c++ ) {
				$s = 0.0;
				for ( $f = $a; $f < $b; $f++ ) {
					$x  = $all[ $f * $channels + $c ];
					$s += $x * $x;
				}
				$out[ $c ][ $i ] = $b > $a ? sqrt( $s / ( $b - $a ) ) : 0.0;
			}
		}
		return $out;
	}

	// Slices are contiguous here: slice i holds frames [frames*i/slices, frames*(i+1)/slices).
	$i    = 0;
	$next = intdiv( $frames, $slices );   // first frame of slice 1
	$f    = 0;
	$acc  = array_fill( 0, $channels, 0.0 );
	while ( ! feof( $fh ) && $f < $frames ) {
		$chunk = fread( $fh, 65536 * 2 * $channels );
		if ( '' === $chunk || false === $chunk ) {
			break;
		}
		$v = unpack( 's*', $chunk );
		$n = count( $v );
		$k = 1;   // unpack() counts from 1
		while ( $k <= $n && $f < $frames ) {
			if ( $f >= $next ) {
				for ( $c = 0; $c < $channels; $c++ ) {
					$sum[ $c ][ $i ] = $acc[ $c ];
					$acc[ $c ]       = 0.0;
				}
				$len[ $i ] = $next - intdiv( $frames * $i, $slices );
				$i++;
				$next = intdiv( $frames * ( $i + 1 ), $slices );
			}
			if ( 1 === $channels ) {
				$x        = $v[ $k ];
				$acc[0]  += $x * $x;
				$k++;
			} else {
				$x        = $v[ $k ];
				$y        = $v[ $k + 1 ];
				$acc[0]  += $x * $x;
				$acc[1]  += $y * $y;
				$k       += 2;
			}
			$f++;
		}
	}
	fclose( $fh );
	for ( $c = 0; $c < $channels; $c++ ) {
		$sum[ $c ][ $i ] = $acc[ $c ];
	}
	$len[ $i ] = $frames - intdiv( $frames * $i, $slices );

	$out = array();
	for ( $c = 0; $c < $channels; $c++ ) {
		$out[ $c ] = array();
		for ( $j = 0; $j < $slices; $j++ ) {
			$out[ $c ][ $j ] = $len[ $j ] ? sqrt( $sum[ $c ][ $j ] / $len[ $j ] ) : 0.0;
		}
	}
	return $out;
}

/**
 * Compute the peaks of a file.
 *
 * @param string $file   Local path (an audio or video file, or an .m3u8 playlist).
 * @param int    $slices
 * @return array|WP_Error The data to store.
 */
function tiagsspace_audio_peaks_compute( $file, $slices = TIAGSSPACE_PEAKS_SLICES ) {
	if ( pack( 'S', 1 ) !== "\x01\x00" ) {
		return new WP_Error( 'peaks_endian', 'big-endian machine: s16le cannot be read with unpack(s)' );
	}
	$ffmpeg  = tiagsspace_audio_tool( 'ffmpeg' );
	$ffprobe = tiagsspace_audio_tool( 'ffprobe' );
	if ( ! $ffmpeg || ! $ffprobe ) {
		return new WP_Error( 'peaks_no_ffmpeg', 'ffmpeg / ffprobe not found' );
	}
	if ( ! is_readable( $file ) ) {
		return new WP_Error( 'peaks_no_file', 'file not readable: ' . $file );
	}

	// Does it have sound at all?
	list( $code, $streams ) = tiagsspace_audio_run( array( $ffprobe, '-v', 'error', '-select_streams', 'a', '-show_entries', 'stream=index', '-of', 'csv=p=0', $file ) );
	if ( 0 === $code && '' === trim( $streams ) ) {
		return array( 'v' => 2, 'none' => true );
	}
	list( $code, $duration, $err ) = tiagsspace_audio_run( array( $ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=nw=1:nk=1', $file ) );
	if ( 0 !== $code ) {
		return new WP_Error( 'peaks_probe', 'ffprobe failed: ' . trim( $err ) );
	}

	$lists = array();
	foreach ( array( 1, 2 ) as $channels ) {
		$pcm = wp_tempnam( 'peaks' );
		list( $code, , $err ) = tiagsspace_audio_run( array_merge(
			array( $ffmpeg, '-v', 'error', '-y' ),
			tiagsspace_audio_input_args( $file ),
			array( '-vn', '-ac', (string) $channels, '-ar', (string) TIAGSSPACE_PEAKS_RATE, '-f', 's16le', $pcm )
		) );
		if ( 0 !== $code ) {
			@unlink( $pcm ); // phpcs:ignore
			return new WP_Error( 'peaks_decode', 'ffmpeg failed: ' . trim( $err ) );
		}
		$lists[ $channels ] = tiagsspace_audio_rms_slices( $pcm, $channels, $slices );
		@unlink( $pcm ); // phpcs:ignore
	}

	$mono  = $lists[1][0];
	$left  = $lists[2][0];
	$right = $lists[2][1];
	$top   = max( max( $mono ), max( $left ), max( $right ) ) ?: 1;
	$norm  = function ( $values ) use ( $top ) {
		return array_map( function ( $v ) use ( $top ) { return round( $v / $top, 3 ); }, $values );
	};
	return array(
		'v'        => 2,
		'slices'   => $slices,
		'duration' => round( (float) $duration, 2 ),
		'rms'      => $norm( $mono ),
		'rmsL'     => $norm( $left ),
		'rmsR'     => $norm( $right ),
	);
}

/**
 * Compute and store the peaks of one attachment.
 *
 * @param int  $id    Attachment ID.
 * @param bool $force Recompute even when stored.
 * @return true|WP_Error
 */
function tiagsspace_audio_peaks_generate( $id, $force = false ) {
	$id = (int) $id;
	if ( ! tiagsspace_audio_peaks_wanted( $id ) ) {
		return new WP_Error( 'peaks_unwanted', "$id: not an audio file, a master video or an HLS film" );
	}
	if ( ! $force && tiagsspace_audio_peaks_get( $id ) ) {
		return true;
	}
	$lock = 'tiagsspace_peaks_lock_' . $id;
	if ( get_transient( $lock ) ) {
		return new WP_Error( 'peaks_locked', "$id: already being computed" );
	}
	set_transient( $lock, 1, 30 * MINUTE_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 0 ); // phpcs:ignore
	}

	$file    = get_attached_file( $id );
	$started = microtime( true );
	$data    = tiagsspace_audio_peaks_compute( (string) $file );
	delete_transient( $lock );

	if ( is_wp_error( $data ) ) {
		tiagsspace_audio_peaks_log( "$id failed: " . $data->get_error_message() );
		return $data;
	}
	update_post_meta( $id, TIAGSSPACE_PEAKS_META, wp_slash( wp_json_encode( $data ) ) );
	tiagsspace_audio_peaks_log( sprintf( '%d ok%s in %.1fs: %s', $id, ! empty( $data['none'] ) ? ' (no sound)' : '', microtime( true ) - $started, basename( (string) $file ) ) );
	return true;
}

// ----- On upload: a minute later, in the background (the HLS import has re-pointed the file by then) -----
add_action( 'add_attachment', function ( $id ) {
	if ( tiagsspace_audio_peaks_wanted( $id ) || preg_match( '/\.zip$/i', (string) get_attached_file( $id ) ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'tiagsspace_audio_peaks_job', array( (int) $id ) );
	}
}, 20 );
add_action( 'tiagsspace_audio_peaks_job', function ( $id ) {
	if ( tiagsspace_audio_peaks_wanted( $id ) ) {
		tiagsspace_audio_peaks_generate( $id, true );
	}
} );

// ----- WP-CLI: existing files -----
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Compute the players' audio peaks.
	 *
	 * ## OPTIONS
	 * [<id>...]
	 * : Attachment IDs.
	 * [--all]
	 * : Every audio file, master video and HLS film.
	 * [--force]
	 * : Recompute files that already have peaks.
	 */
	WP_CLI::add_command( 'tiagsspace audio-peaks', function ( $args, $assoc ) {
		$ids = array_map( 'intval', $args );
		if ( ! empty( $assoc['all'] ) ) {
			global $wpdb;
			$ids = array_map( 'intval', $wpdb->get_col(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'
				 AND ( post_mime_type LIKE 'audio/%' OR post_mime_type LIKE 'video/%' OR post_mime_type = 'application/vnd.apple.mpegurl' )
				 ORDER BY ID"
			) );
			$ids = array_values( array_filter( $ids, 'tiagsspace_audio_peaks_wanted' ) );
		}
		if ( ! $ids ) {
			WP_CLI::error( 'Give attachment IDs or --all.' );
		}
		$force = ! empty( $assoc['force'] );
		$done  = 0;
		$fail  = 0;
		foreach ( $ids as $n => $id ) {
			$t   = microtime( true );
			$res = tiagsspace_audio_peaks_generate( $id, $force );
			if ( is_wp_error( $res ) ) {
				$fail++;
				WP_CLI::warning( sprintf( '[%d/%d] %d: %s', $n + 1, count( $ids ), $id, $res->get_error_message() ) );
			} else {
				$done++;
				WP_CLI::log( sprintf( '[%d/%d] %d ok (%.1fs)', $n + 1, count( $ids ), $id, microtime( true ) - $t ) );
			}
		}
		WP_CLI::success( "$done ok, $fail failed." );
	} );
}
