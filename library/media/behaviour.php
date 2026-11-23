<?php
/**
 * Gallery videos: how each one behaves (the presets), read from ACF and the theme's rules.
 *
 * What it is for
 *   One answer per gallery video: Ambient, Player or Custom, with the four flags the player needs.
 *     Ambient  nothing to see or click: muted, loops, plays by itself while in view.
 *     Player   play / pause only (the play / pause cursor; the play mark on touch). Loop and mute
 *              are options; a muted video that loops plays by itself in view, and stays paused
 *              once the visitor pauses it. A video with sound never starts by itself.
 *     Custom   the old ticks (controls, mute, loop, autoplay) as stored.
 *   Decided by Tiago, 22 Nov 2026 (the plan: "Gallery video behaviour = presets plus Custom").
 *
 * Where the answer comes from, first that says something wins
 *   1. the video attachment: the select `video_behaviour`, else its old tick set (when its
 *      "Define player options" box is ticked);
 *   2. the post: the select `post_video_behaviour`, else its old tick set;
 *   3. the post type: Log and the Log branch "blwww" are Ambient, everything else is Player.
 *   The old ticks are READ, never migrated: "no controls + muted + loop + autoplay" reads as
 *   Ambient, "controls + no autoplay" reads as Player (with its mute and loop), anything else as
 *   Custom. Saving the select in the editor stores the choice; nothing else is written.
 *
 * Contract
 *   tiagsspace_gallery_video_behaviour( $attachment_id, $post_id )
 *     -> [ 'behaviour' => 'ambient'|'player'|'custom', 'controls' => bool, 'muted' => bool,
 *          'loop' => bool, 'autoplay' => bool ]   autoplay is only ever true for a muted video.
 *   tiagsspace_gallery_video_legacy_choice( $gate, $ticks ) -> 'default'|'ambient'|'player'|'custom'
 *     (also used by the ACF filter that shows the old state in the new select).
 *
 * Do not read these fields anywhere else.
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What an old tick set means as a preset.
 *
 * @param mixed $gate  The "Define player options" box.
 * @param array $ticks [ controls, muted, loop, autoplay ] as stored (truthy / falsy).
 * @return string
 */
function tiagsspace_gallery_video_legacy_choice( $gate, $ticks ) {
	if ( ! $gate ) {
		return 'default';
	}
	$c = ! empty( $ticks['controls'] );
	$m = ! empty( $ticks['muted'] );
	$l = ! empty( $ticks['loop'] );
	$a = ! empty( $ticks['autoplay'] );
	if ( ! $c && $m && $l && $a ) {
		return 'ambient';
	}
	if ( $c && ! $a ) {
		return 'player';
	}
	return 'custom';
}

/**
 * The choice and ticks stored at one level.
 *
 * @param string $level 'attachment' or 'post'.
 * @param int    $id    The attachment or post ID.
 * @return array{choice: string, ticks: array}
 */
function tiagsspace_gallery_video_level( $level, $id ) {
	$p      = 'post' === $level ? 'post_' : '';
	$gate   = 'post' === $level ? 'alternative_video_player_options_on_post' : 'alternative_video_player_options';
	// get_field() (not get_post_meta) so a post preview reads its revision (instructions AI.md, ACF).
	$read   = function ( $name ) use ( $id ) {
		return function_exists( 'get_field' ) ? get_field( $name, $id, false ) : get_post_meta( $id, $name, true );
	};
	$ticks  = array(
		'controls' => $read( $p . 'video_controls' ),
		'muted'    => $read( $p . 'video_mute' ),
		'loop'     => $read( $p . 'video_loop' ),
		'autoplay' => $read( $p . 'video_autoplay' ),
	);
	$choice = (string) $read( $p . 'video_behaviour' );
	if ( ! in_array( $choice, array( 'default', 'ambient', 'player', 'custom' ), true ) ) {
		$choice = tiagsspace_gallery_video_legacy_choice( $read( $gate ), $ticks );
	}
	return array( 'choice' => $choice, 'ticks' => $ticks );
}

/**
 * How one gallery video behaves.
 *
 * @param int $attachment_id
 * @param int $post_id The post whose gallery shows it.
 * @return array
 */
function tiagsspace_gallery_video_behaviour( $attachment_id, $post_id ) {
	$level = tiagsspace_gallery_video_level( 'attachment', $attachment_id );
	if ( 'default' === $level['choice'] ) {
		$level = tiagsspace_gallery_video_level( 'post', $post_id );
	}
	if ( 'default' === $level['choice'] ) {
		$ambient = 'log' === get_post_type( $post_id ) || has_term( 'blwww', 'log-branch', $post_id );
		$level   = array( 'choice' => $ambient ? 'ambient' : 'player', 'ticks' => array() );
	}

	$t = $level['ticks'];
	switch ( $level['choice'] ) {
		case 'ambient':
			$flags = array( 'controls' => false, 'muted' => true, 'loop' => true, 'autoplay' => true );
			break;
		case 'custom':
			$flags = array(
				'controls' => ! empty( $t['controls'] ),
				'muted'    => ! empty( $t['muted'] ),
				'loop'     => ! empty( $t['loop'] ),
				'autoplay' => ! empty( $t['autoplay'] ),
			);
			break;
		default: // player
			$flags = array( 'controls' => true, 'muted' => ! empty( $t['muted'] ), 'loop' => ! empty( $t['loop'] ) );
			$flags['autoplay'] = $flags['muted'] && $flags['loop'];
	}
	// A video with sound never starts by itself (browsers refuse it, and it would be rude).
	$flags['autoplay'] = $flags['autoplay'] && $flags['muted'];
	// A video nobody can start must at least play by itself.
	if ( ! $flags['controls'] && ! $flags['autoplay'] ) {
		$flags['muted']    = true;
		$flags['autoplay'] = true;
	}
	return array( 'behaviour' => $level['choice'] ) + $flags;
}
