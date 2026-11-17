<?php
/**
 * One-off (Tiago, 17 Nov 2026): Cidade sets split into numbered parts ("Delirium, I / II / III")
 * become one post each. Per group, the lowest part is kept:
 *   1. the images of every part move to it (post_parent), in the order the gallery shows them,
 *      numbered 1…n across the group (kept post first, then part II, III…);
 *   2. text the kept post lacks is appended; the parts' from/places/medium terms are added;
 *   3. then the kept post gets its new title and a slug made from it; its own old slug and every
 *      part's slug are stored as meta `_tiagsspace_old_slug`, which library/redirects.php answers
 *      with a 301 (see server/redirects/OLD-SLUGS.md);
 *   4. the emptied parts go to the trash (meta `_tiagsspace_merged_into`).
 *
 *   local:  /Applications/MAMP/bin/php/php8.2.0/bin/php bin/merge-numbered-sets.php --dry-run
 *           /Applications/MAMP/bin/php/php8.2.0/bin/php bin/merge-numbered-sets.php [--only=<kept id>]
 *           /Applications/MAMP/bin/php/php8.2.0/bin/php bin/merge-numbered-sets.php --reverse
 *   server: sg www-data -c "umask 002 && HTTP_HOST=tiags.space php bin/merge-numbered-sets.php --dry-run"
 *
 * Every change is logged to wp-content/merge-numbered-sets-log.json; --reverse restores from it.
 * Nothing may reach a social network: WP_IMPORTING, the plugins' publish hooks removed, outgoing
 * requests to social hosts refused, queue tables and scheduled jobs compared before/after.
 *
 * Delete this file once production is merged and checked.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}
$opt = array( 'dry-run' => false, 'reverse' => false, 'only' => 0 );
foreach ( array_slice( $argv, 1 ) as $a ) {
	if ( '--dry-run' === $a || '--reverse' === $a ) {
		$opt[ substr( $a, 2 ) ] = true;
	} elseif ( preg_match( '/^--only=(\d+)$/', $a, $m ) ) {
		$opt['only'] = (int) $m[1];
	} else {
		fwrite( STDERR, "Unknown option {$a}\n" );
		exit( 1 );
	}
}
define( 'WP_IMPORTING', true );
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
global $wpdb, $wp_filter;

/**
 * kept id => [ new title, kept post's current slug, [ part id => part slug, … in gallery order ] ].
 * Titles approved by Tiago on 17 Nov 2026 (the four short ones: Always, Together, BRC, Facial Recognition).
 */
const MERGE_GROUPS = array(
	404  => array( 'That Light', 'that-light-i', array( 414 => 'that-light-ii' ) ),
	473  => array( 'BRC', 'not-from-barcelona-i', array( 511 => 'not-from-barcelona-ii', 512 => 'not-from-barcelona-iii' ) ),
	562  => array( 'Delirium', 'a-case-of-flash-delirium-i', array( 628 => 'a-case-of-flash-delirium-ii', 631 => 'a-case-of-flash-delirium-iii' ) ),
	921  => array( 'Forget', 'now-we-forget-why-we-started-this-i', array( 944 => 'we-forget-why-we-started-this-ii', 926 => 'we-forget-why-we-started-this-iii' ) ),
	1195 => array( 'Podium', 'a-podium-i', array( 1196 => 'the-podium-ii' ) ),
	1263 => array( 'Wallpaper', 'wallpaper-i', array( 1264 => 'wallpaper-ii', 1447 => 'wallpaper-special-features' ) ),
	1399 => array( 'Ween', 'marcelle-and-ween-i', array( 1401 => 'marcelle-and-ween-ii', 1404 => 'marcelle-and-ween-iii' ) ),
	1464 => array( 'Ona', 'ona-i', array( 1467 => 'ona-ii', 1471 => 'ona-iii' ) ),
	1710 => array( 'Always', 'and-its-always-been-like-this-i', array( 1732 => 'and-its-always-been-like-this-ii' ) ),
	1973 => array( 'Such', 'people-are-such-i', array( 1984 => 'people-are-such-ii' ) ),
	2081 => array( 'Burgers', 'vegetarian-burgers-i', array( 2082 => 'vegetarian-burgers-ii' ) ),
	2210 => array( 'Soon', 'soon-in-london-ii', array( 2252 => 'soon-in-london-iii' ) ),
	2319 => array( 'Beirut', 'she-loves-beirut-i', array( 2320 => 'she-loves-beirut-ii' ) ),
	2477 => array( 'Together', 'boy-we-can-do-much-more-together-i', array( 2478 => 'boy-we-can-do-much-more-together-ii' ) ),
	2835 => array( 'Words', 'the-words-that-maketh-murder', array( 2878 => 'the-words-that-maketh-murder-ii', 2857 => 'the-words-that-maketh-murder-iii' ) ),
	2950 => array( 'Ne T’en Fais Pas', 'je-vais-bien-ne-ten-fais-pas-i', array( 2951 => 'je-vais-bien-ne-ten-fais-pas-ii' ) ),
	3117 => array( 'Slightest Idea', 'i-dont-have-the-slightest-idea-ii', array( 3136 => 'i-dont-have-the-slightest-idea-iii' ) ),
	3224 => array( 'Facial Recognition', 'facial-recognition-system', array( 3247 => 'facial-recognition-system-ii', 3263 => 'facial-recognition-system-iii' ) ),
	3492 => array( 'Again', 'there-she-goes-again', array( 3497 => 'shes-out-on-the-streets-again', 3520 => 'shes-down-on-her-knees-my-friend', 3531 => 'but-you-know-shell-never-ask-you-please-again' ) ),
	4186 => array( 'Hospital', 'its-just-another-hospital-i', array( 4218 => 'its-just-another-hospital-ii' ) ),
	4308 => array( 'Not In Love', 'im-not-in-love-i', array( 4264 => 'im-not-in-love-ii', 4281 => 'im-not-in-love-iii' ) ),
	4611 => array( 'Kohlfurter', 'kohlfurter-strase-i', array( 4612 => 'kohlfurter-strase-ii', 4613 => 'kohlfurter-strase-iii', 4652 => 'kohlfurter-strase-iv' ) ),
	4968 => array( 'Adalbertstraße', 'adalbertstrase-i', array( 4970 => 'adalbertstrase-ii' ) ),
	5023 => array( 'Rec', 'spul-mal-vor-und-druck-auf-rec-i', array( 5024 => 'spul-mal-vor-und-druck-auf-rec-ii', 5030 => 'spul-mal-vor-und-druck-auf-rec-iii' ) ),
	5178 => array( 'Witness', 'witness-for-the-prosecution-i', array( 5179 => 'witness-for-the-prosecution-ii', 5180 => 'witness-for-the-prosecution-iii' ) ),
	5393 => array( 'Remote Control', 'remote-control-i', array( 5394 => 'remote-control-ii' ) ),
	5232 => array( 'Another Camera', 'another-camera-another-one-i', array( 5234 => 'another-camera-another-one-ii' ) ),
	5565 => array( 'R-Gespräch', 'r-gesprach-da-zahlt-der-angerufene-i', array( 5642 => 'r-gesprach-da-zahlt-der-angerufene-ii' ) ),
	6293 => array( 'Just Kids', 'just-kids-i', array( 6243 => 'just-kids-ii', 6257 => 'just-kids-iii' ) ),
	6402 => array( 'Encosta', 'e-se-mais-pedras-rebolassem-na-encosta-1', array( 6466 => 'e-se-mais-pedras-rebolassem-na-encosta-2', 6496 => 'e-se-mais-pedras-rebolassem-na-encosta-3' ) ),
	8015 => array( 'Wax', 'my-time-is-a-piece-of-wax-1', array( 8016 => 'my-time-is-a-piece-of-wax-2', 8017 => 'my-time-is-a-piece-of-wax-3' ) ),
);
const MERGE_TAXES = array( 'from', 'places', 'medium' );
const MERGE_TYPE  = 'cidade';
$log_file = WP_CONTENT_DIR . '/merge-numbered-sets-log.json';

// ---------------------------------------------------------------- guards
$blocked = array();
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$blocked ) {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	if ( preg_match( '/(tumblr\.com|instagram\.com|facebook\.com|graph\.facebook|fbcdn|threads\.net|bsky\.(app|social)|bsky\.network|mastodon|mstdn|fediverse|activitypub|podping|twitter\.com|x\.com|pinterest)/i', $host ) ) {
		$blocked[] = $host;
		return new WP_Error( 'merge_blocked', 'Blocked during the merge run' );
	}
	return $pre;
}, 1, 3 );
$unhook = array(
	'transition_post_status' => array( 'maybe_auto_publish_on_status_transition', 'on_transition_post_status', 'schedule_post_activity', 'on_post_published' ),
	'wp_after_insert_post'   => array( 'triage', 'notify_podping' ),
	'acf/save_post'          => array( 'maybe_auto_publish_on_acf_save' ),
);
$removed = array();
foreach ( $unhook as $hook => $methods ) {
	if ( empty( $wp_filter[ $hook ] ) ) {
		continue;
	}
	foreach ( $wp_filter[ $hook ]->callbacks as $prio => $cbs ) {
		foreach ( $cbs as $cb ) {
			$f = $cb['function'];
			if ( is_array( $f ) && in_array( $f[1], $methods, true ) ) {
				remove_action( $hook, $f, $prio );
				$removed[] = ( is_object( $f[0] ) ? get_class( $f[0] ) : $f[0] ) . '::' . $f[1];
			}
		}
	}
}
// An untrashed part goes back to the status it had (core would make it a draft).
add_filter( 'wp_untrash_post_status', function ( $status, $id, $previous ) {
	return $previous ? $previous : $status;
}, 10, 3 );

$snapshot = function () use ( $wpdb ) {
	$s = array();
	foreach ( array( 'fediverse_queue', 'fediverse_queue_targets', 'instagram_queue', 'instagram_queue_targets', 'cef_instagram_posts' ) as $t ) {
		$table = $wpdb->prefix . $t;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$s[ $t ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}
	}
	$s['tumblr send markers'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_tumblr_auto_published_at','_tumblr_sent_owner')" );
	return $s;
};
$social_cron = function () {
	$jobs = array();
	foreach ( (array) _get_cron_array() as $ts => $hooks ) {
		foreach ( (array) $hooks as $h => $events ) {
			if ( preg_match( '/fediverse|instagram|tumblr|activitypub|podping/i', $h ) ) {
				foreach ( $events as $key => $e ) {
					$jobs[ "$ts|$h|$key" ] = array( $ts, $h, $e['args'] );
				}
			}
		}
	}
	return $jobs;
};

/** Attachments of a post in the order its gallery shows them (template-parts/entry-gallery.php), then any others. */
function merge_gallery_order( $post_id ) {
	$shown = get_posts( array(
		'numberposts' => -1,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
		'post_parent' => $post_id,
		'post_status' => null,
		'post_type'   => 'attachment',
		'fields'      => 'ids',
		'meta_query'  => array(
			'relation' => 'OR',
			array( 'key' => 'remove_from_default_gallery', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'remove_from_default_gallery', 'value' => '1', 'compare' => '!=' ),
		),
	) );
	$all = get_posts( array( 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'post_parent' => $post_id, 'post_status' => null, 'post_type' => 'attachment', 'fields' => 'ids' ) );
	return array_values( array_unique( array_map( 'intval', array_merge( $shown, $all ) ) ) );
}
function merge_terms( $id ) {
	$t = array();
	foreach ( MERGE_TAXES as $tax ) {
		$t[ $tax ] = array_map( 'intval', wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) ) );
	}
	return $t;
}

echo 'Unhooked for this run: ' . ( $removed ? implode( ', ', $removed ) : '(none found)' ) . "\n";
$before      = $snapshot();
$cron_before = $social_cron();

// ================================================================ reverse
if ( $opt['reverse'] ) {
	$log = is_file( $log_file ) ? json_decode( file_get_contents( $log_file ), true ) : null;
	if ( ! $log ) {
		echo "STOP: no log at {$log_file}\n";
		exit( 1 );
	}
	foreach ( $log as $kept => $g ) {
		if ( $opt['only'] && (int) $kept !== $opt['only'] ) {
			continue;
		}
		echo "  restore {$kept} \"{$g['kept']['title']}\"\n";
		if ( $opt['dry-run'] ) {
			continue;
		}
		foreach ( $g['parts'] as $p ) {
			if ( 'trash' === get_post_status( $p['id'] ) ) {
				wp_untrash_post( $p['id'] );
			}
			delete_post_meta( $p['id'], '_tiagsspace_merged_into' );
		}
		foreach ( $g['attachments'] as $a ) {
			$wpdb->update( $wpdb->posts, array( 'post_parent' => $a['parent'], 'menu_order' => $a['menu_order'] ), array( 'ID' => $a['id'] ) );
			clean_post_cache( $a['id'] );
		}
		wp_update_post( array( 'ID' => (int) $kept, 'post_title' => $g['kept']['title'], 'post_name' => $g['kept']['slug'], 'post_content' => $g['kept']['content'] ) );
		foreach ( $g['kept']['terms'] as $tax => $ids ) {
			wp_set_object_terms( (int) $kept, $ids, $tax );
		}
		delete_post_meta( (int) $kept, '_tiagsspace_old_slug' );
		delete_post_meta( (int) $kept, '_wp_old_slug' );
		foreach ( $g['kept']['wp_old_slug'] as $s ) {
			add_post_meta( (int) $kept, '_wp_old_slug', $s );
		}
		$wpdb->update( $wpdb->posts, array( 'post_modified' => $g['kept']['modified'], 'post_modified_gmt' => $g['kept']['modified_gmt'] ), array( 'ID' => (int) $kept ) );
		clean_post_cache( (int) $kept );
		unset( $log[ $kept ] );
	}
	if ( ! $opt['dry-run'] ) {
		if ( $log ) {
			file_put_contents( $log_file, wp_json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		} else {
			unlink( $log_file );
		}
	}
} else {
	// ============================================================ preflight
	$groups = MERGE_GROUPS;
	if ( $opt['only'] ) {
		$groups = array_intersect_key( $groups, array( $opt['only'] => true ) );
	}
	$log = is_file( $log_file ) ? (array) json_decode( file_get_contents( $log_file ), true ) : array();
	$problems = array();
	$plan     = array();
	foreach ( $groups as $kept => $g ) {
		list( $title, $slug, $parts ) = $g;
		if ( isset( $log[ $kept ] ) ) {
			echo "  skip {$kept} \"{$title}\": already merged (in the log)\n";
			continue;
		}
		$new_slug = sanitize_title( $title );
		foreach ( array( $kept => $slug ) + $parts as $id => $want ) {
			$p = get_post( $id );
			if ( ! $p || MERGE_TYPE !== $p->post_type || 'publish' !== $p->post_status || $p->post_name !== $want ) {
				$problems[] = "{$id}: expected a published " . MERGE_TYPE . " with slug {$want}" . ( $p ? " (found {$p->post_type} {$p->post_status} {$p->post_name})" : ' (not found)' );
			}
			foreach ( merge_gallery_order( $id ) as $a ) {
				if ( get_post_meta( $a, 'remove_from_default_gallery', true ) ) {
					$problems[] = "{$id}: image {$a} is hidden from the gallery";
				}
			}
		}
		$clash = get_posts( array( 'post_type' => array( 'cidade', 'dusk', 'hyper' ), 'post_status' => 'any', 'name' => $new_slug, 'exclude' => array( $kept ), 'fields' => 'ids', 'numberposts' => 1, 'suppress_filters' => true ) );
		if ( $clash ) {
			$problems[] = "{$kept}: new slug {$new_slug} is used by post {$clash[0]}";
		}
		$plan[ $kept ] = array( $title, $new_slug, $parts );
	}
	$n_parts = array_sum( array_map( function ( $g ) { return count( $g[2] ); }, $plan ) );
	echo sprintf( "Preflight: %d groups, %d parts to merge, %d problem(s).\n", count( $plan ), $n_parts, count( $problems ) );
	foreach ( $problems as $p ) {
		echo "  PROBLEM {$p}\n";
	}
	if ( $problems ) {
		echo "STOP: nothing changed.\n";
		exit( 1 );
	}

	foreach ( $plan as $kept => $g ) {
		list( $title, $new_slug, $parts ) = $g;
		$kept_post = get_post( $kept );
		$counts    = array( count( merge_gallery_order( $kept ) ) );
		foreach ( array_keys( $parts ) as $pid ) {
			$counts[] = count( merge_gallery_order( $pid ) );
		}
		printf( "  %-5d %-36s → %-20s /%s/%s/  images %s = %d\n", $kept, '"' . html_entity_decode( $kept_post->post_title ) . '"', $title, MERGE_TYPE, $new_slug, implode( '+', $counts ), array_sum( $counts ) );
		if ( $opt['dry-run'] ) {
			continue;
		}
		@set_time_limit( 0 );

		// Log first: everything needed to put it back.
		$entry = array(
			'kept'        => array(
				'id'           => $kept,
				'title'        => $kept_post->post_title,
				'slug'         => $kept_post->post_name,
				'content'      => $kept_post->post_content,
				'modified'     => $kept_post->post_modified,
				'modified_gmt' => $kept_post->post_modified_gmt,
				'terms'        => merge_terms( $kept ),
				'wp_old_slug'  => get_post_meta( $kept, '_wp_old_slug' ),
			),
			'parts'       => array(),
			'attachments' => array(),
		);
		foreach ( array_merge( array( $kept ), array_keys( $parts ) ) as $id ) {
			foreach ( merge_gallery_order( $id ) as $a ) {
				$entry['attachments'][] = array( 'id' => $a, 'parent' => $id, 'menu_order' => (int) get_post_field( 'menu_order', $a ) );
			}
		}
		foreach ( $parts as $pid => $pslug ) {
			$entry['parts'][] = array( 'id' => $pid, 'slug' => $pslug, 'title' => get_the_title( $pid ) );
		}
		$log[ $kept ] = $entry;
		file_put_contents( $log_file, wp_json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		// 1. Media first: one sequence across the group, in the order the galleries show.
		$n = 0;
		foreach ( $entry['attachments'] as $a ) {
			$wpdb->update( $wpdb->posts, array( 'post_parent' => $kept, 'menu_order' => ++$n ), array( 'ID' => $a['id'] ) );
			clean_post_cache( $a['id'] );
		}

		// 2. Text and terms.
		$content = trim( $kept_post->post_content );
		foreach ( array_keys( $parts ) as $pid ) {
			$c = trim( get_post_field( 'post_content', $pid ) );
			if ( '' !== $c && false === strpos( $content, $c ) ) {
				$content = ( '' === $content ) ? $c : $content . "\n\n" . $c;
			}
		}
		foreach ( MERGE_TAXES as $tax ) {
			$ids = $entry['kept']['terms'][ $tax ];
			foreach ( array_keys( $parts ) as $pid ) {
				$ids = array_merge( $ids, merge_terms( $pid )[ $tax ] );
			}
			wp_set_object_terms( $kept, array_values( array_unique( $ids ) ), $tax );
		}

		// 3. Then the new title and slug; the old slugs stay as redirects.
		wp_update_post( array( 'ID' => $kept, 'post_title' => $title, 'post_name' => $new_slug, 'post_content' => $content ) );
		foreach ( array_merge( array( $entry['kept']['slug'] ), array_values( $parts ) ) as $old ) {
			add_post_meta( $kept, '_tiagsspace_old_slug', $old );
		}
		$now = get_post( $kept );
		if ( $now->post_name !== $new_slug ) {
			echo "    WARNING: slug became {$now->post_name}\n";
		}

		// 4. The emptied parts to the trash.
		foreach ( array_keys( $parts ) as $pid ) {
			$left = count( get_children( array( 'post_parent' => $pid, 'post_type' => 'attachment', 'fields' => 'ids' ) ) );
			if ( $left ) {
				echo "    WARNING: {$pid} still has {$left} attachment(s); not trashed\n";
				continue;
			}
			update_post_meta( $pid, '_tiagsspace_merged_into', $kept );
			wp_trash_post( $pid );
		}
	}
}

// ---------------------------------------------------------------- after
$new_jobs = array();
foreach ( array_diff_key( $social_cron(), $cron_before ) as $job ) {
	wp_unschedule_event( $job[0], $job[1], $job[2] );
	$new_jobs[] = $job[1];
}
$after = $snapshot();
echo "\nSocial tables before: " . wp_json_encode( $before ) . "\nSocial tables after:  " . wp_json_encode( $after ) . "\n";
echo 'New scheduled social jobs removed: ' . ( $new_jobs ? implode( ', ', $new_jobs ) : 'none' ) . "\n";
echo 'Outgoing social requests refused: ' . ( $blocked ? implode( ', ', array_unique( $blocked ) ) : 'none' ) . "\n";
echo ( $before === $after ? 'Social tables unchanged.' : 'WARNING: social tables changed.' ) . "\n";
if ( $opt['dry-run'] ) {
	echo "[dry run] nothing changed.\n";
}
