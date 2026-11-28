<?php
/**
 * Move sets from one series (post type) to another, keeping their id, slug, gallery,
 * terms and fields. The old address (/<old series>/<slug>/) is redirected by
 * library/redirects.php: a 404 under one series finds the same slug in another.
 *
 *   wp tiagsspace move-set 38838 --to=campo --dry-run
 *   wp tiagsspace move-set 38838 --to=campo
 *
 * The post type is changed by a direct UPDATE, not wp_update_post(), so no save hook
 * runs: nothing is re-posted to Mastodon, Instagram, Tumblr or the queue, and the
 * modified date stays. Yoast's records of the posts are rebuilt under the new type.
 *
 * Each moved post gets a `_tiagsspace_moved_from` row with the type it left, so the
 * redirect finds it even when another series has a set with the same slug.
 *
 * @package tiagsspace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Moves posts to the post type `$to`.
 *
 * @param array  $posts id => expected slug (the slug must match, or nothing moves),
 *                      or a plain list of ids (no slug check).
 * @param string $to    Target post type, registered.
 * @param array  $args  'from' (string, optional: every post must be of this type),
 *                      'dry_run' (bool), 'log' (callable, one line per call).
 * @return array        'moved' => count, or 'stopped' => true.
 */
function tiagsspace_move_sets( $posts, $to, $args = array() ) {
	global $wpdb;
	$dry   = ! empty( $args['dry_run'] );
	$from  = isset( $args['from'] ) ? (string) $args['from'] : '';
	$log   = isset( $args['log'] ) && is_callable( $args['log'] ) ? $args['log'] : function ( $line ) { echo $line, "\n"; };
	$label = $dry ? '[dry run] ' : '';

	// A plain list of ids: no slug to check.
	if ( array_keys( $posts ) === range( 0, count( $posts ) - 1 ) ) {
		$posts = array_fill_keys( array_map( 'intval', $posts ), '' );
	}

	$log( "{$label}Moving " . count( $posts ) . ' set(s)' . ( $from ? " {$from}" : '' ) . " → {$to}" );
	if ( ! post_type_exists( $to ) ) {
		$log( "STOP: post type {$to} is not registered. Nothing moved." );
		return array( 'stopped' => true );
	}

	$move = $problems = $left = array();
	foreach ( $posts as $id => $slug ) {
		$id = (int) $id;
		$p  = get_post( $id );
		if ( ! $p || in_array( $p->post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
			$problems[] = "{$id}: no such set";
			continue;
		}
		if ( $slug !== '' && $p->post_name !== $slug ) {
			$problems[] = "{$id}: expected slug {$slug}, found {$p->post_type}/{$p->post_name}";
			continue;
		}
		if ( $p->post_type === $to ) {
			continue; // Already moved.
		}
		if ( $from && $p->post_type !== $from ) {
			$problems[] = "{$id}: is a {$p->post_type}, not a {$from}";
			continue;
		}
		$clash = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s AND post_status <> 'trash'", $to, $p->post_name ) );
		if ( $clash ) {
			$problems[] = "{$id}: slug {$p->post_name} is already used in {$to} by {$clash}";
			continue;
		}
		$move[]      = $id;
		$left[ $id ] = $p->post_type;
		$log( sprintf( '  %-6d %-8s %s/%s', $id, $p->post_status, $p->post_type, $p->post_name ) );
	}
	foreach ( $problems as $pr ) {
		$log( "  PROBLEM {$pr}" );
	}
	if ( $problems ) {
		$log( 'STOP: nothing moved.' );
		return array( 'stopped' => true );
	}
	$log( '  to move: ' . count( $move ) . ' (already moved: ' . ( count( $posts ) - count( $move ) ) . ')' );
	if ( $dry || ! $move ) {
		$log( $dry ? '[dry run] nothing written.' : 'Nothing to do.' );
		return array( 'moved' => 0 );
	}

	$in = implode( ',', array_map( 'intval', $move ) );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE ID IN ({$in})", $to ) );
	foreach ( $move as $id ) {
		// The series it left, for the redirect of its old address (library/redirects.php).
		add_post_meta( $id, '_tiagsspace_moved_from', $left[ $id ] );
		clean_post_cache( $id );
	}
	// Yoast keeps each post's permalink: drop the records, rebuild them under the new type.
	$table = $wpdb->prefix . 'yoast_indexable';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		$ix = $wpdb->get_col( "SELECT id FROM {$table} WHERE object_type = 'post' AND object_id IN ({$in})" );
		if ( $ix ) {
			$ixin = implode( ',', array_map( 'intval', $ix ) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}yoast_indexable_hierarchy WHERE indexable_id IN ({$ixin}) OR ancestor_id IN ({$ixin})" );
			$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ixin})" );
		}
		if ( function_exists( 'YoastSEO' ) ) {
			try {
				foreach ( $move as $id ) {
					YoastSEO()->meta->for_post( $id );
				}
				YoastSEO()->meta->for_post_type_archive( $to );
			} catch ( \Throwable $e ) {
				$log( '  yoast rebuild stopped: ' . $e->getMessage() . ' (the rest is rebuilt on first visit)' );
			}
		}
	}
	update_option( 'tiagsspace_archive_stats_salt', time(), false );
	wp_cache_flush();
	$log( 'Moved: ' . count( $move ) . '. Flush the page cache (W3TC) so the old address stops serving the old page.' );
	return array( 'moved' => count( $move ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Move sets to another series, keeping id and slug.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more post ids.
	 *
	 * --to=<post_type>
	 * : The series to move them to (dusk, hyper, strings, cidade, campo…).
	 *
	 * [--dry-run]
	 * : Only report what would change.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tiagsspace move-set 38838 --to=campo --dry-run
	 */
	WP_CLI::add_command( 'tiagsspace move-set', function ( $args, $assoc ) {
		$result = tiagsspace_move_sets( array_map( 'intval', $args ), (string) $assoc['to'], array(
			'dry_run' => ! empty( $assoc['dry-run'] ),
			'log'     => function ( $line ) { WP_CLI::log( $line ); },
		) );
		if ( ! empty( $result['stopped'] ) ) {
			WP_CLI::halt( 1 );
		}
	} );
}
