<?php
/**
 * One-off data migration: post type `cityburns` ("City Series") → `cidade` ("Cidade Series").
 *
 * Run once per environment, right after the theme that registers `cidade` is deployed:
 *
 *   wp tiagsspace cidade-migrate --dry-run   </dev/null    # production (WP-CLI)
 *   wp tiagsspace cidade-migrate             </dev/null
 *   MAMP php bin/cidade-migrate.php --dry-run                 # local (no WP-CLI)
 *
 * `--reverse` moves everything back (cidade → cityburns), for the local rehearsal.
 *
 * NEVER replace the string "cityburns" across the database: it is also the name of
 * the database, the stored home/siteurl, 2,000+ attachment file names, guids and
 * external URLs. Only what names the POST TYPE changes, and only these places:
 *
 *   1. wp_posts.post_type
 *   2. Option keys / values that name the post type: wpseo_titles (keys such as
 *      title-cityburns, metadesc-ptarchive-cityburns), wpseo, featured_image_column,
 *      fediverse_posting_settings. Only array keys or values that are exactly the
 *      post type are touched; nothing is printed from their values (one holds a secret).
 *   3. User meta keys of the edit screens (edit_cityburns_per_page, …).
 *   4. Yoast indexables of the posts and the archive (deleted, then rebuilt so their
 *      stored permalinks read /cidade/).
 *   5. Archive stats salt, object cache, rewrite rules.
 *
 * Idempotent: a second run finds nothing to do. Old addresses are redirected by
 * library/redirects.php.
 *
 * @package tiagsspace
 */

/**
 * @param array $args {
 *     @type bool     $dry_run Only report.
 *     @type bool     $reverse cidade → cityburns.
 *     @type callable $log     Receives one line of text.
 * }
 * @return array Counts per step.
 */
function tiagsspace_migrate_cityburns_to_cidade( $args = array() ) {
	global $wpdb;

	$dry   = ! empty( $args['dry_run'] );
	$log   = isset( $args['log'] ) && is_callable( $args['log'] ) ? $args['log'] : function ( $line ) { echo $line, "\n"; };
	$from  = empty( $args['reverse'] ) ? 'cityburns' : 'cidade';
	$to    = empty( $args['reverse'] ) ? 'cidade' : 'cityburns';
	$done  = array();
	$label = $dry ? '[dry run] ' : '';

	$log( "{$label}Moving post type {$from} → {$to}" );

	if ( ! $dry && ! post_type_exists( $to ) && empty( $args['force'] ) ) {
		$log( "STOP: post type {$to} is not registered by the active theme. Deploy the theme first (or pass --force)." );
		return array( 'stopped' => true );
	}

	// 1. Posts.
	$by_status = $wpdb->get_results( $wpdb->prepare(
		"SELECT post_status, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status",
		$from
	) );
	$total = 0;
	foreach ( $by_status as $row ) {
		$total += (int) $row->n;
		$log( sprintf( '  posts %-8s %d', $row->post_status, $row->n ) );
	}
	$already = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", $to ) );
	$log( "  posts to move: {$total} (already {$to}: {$already})" );
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $from ) );
	if ( ! $dry && $total ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", $to, $from ) );
		foreach ( $post_ids as $id ) {
			clean_post_cache( (int) $id );
		}
	}
	$done['posts'] = $total;

	// 2. Options that name the post type.
	$replace_in = function ( $value, &$changes, $path = '' ) use ( &$replace_in, $from, $to ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$nk = ( $k === $from ) ? $to : $k;
				if ( $nk !== $k ) {
					$changes[] = "{$path}[key {$k}]";
				}
				$out[ $nk ] = $replace_in( $v, $changes, $path . '/' . $k );
			}
			return $out;
		}
		if ( $value === $from ) {
			$changes[] = "{$path} (value)";
			return $to;
		}
		return $value;
	};

	// 2a. wpseo_titles: keys that contain the post type (title-cityburns, …).
	// Read the option AS STORED: get_option() goes through Yoast's filter, which adds
	// its defaults for every registered post type, so every new key would look present.
	// Even stored, a new key may already exist, holding Yoast's default: whoever saves the
	// option first (the theme's title routine in seo-and-feed.php, Yoast itself) merges the
	// defaults in. So a stored value of the new key proves nothing:
	//  - a key the theme's title list owns (title-cidade, title-ptarchive-cidade) is written
	//    from that list, here, every run (its own routine only runs when the list changes);
	//  - every other key takes the value the old post type had (descriptions, noindex, schema).
	$titles    = maybe_unserialize( $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'wpseo_titles'" ) );
	$templates = function_exists( 'tiagsspace_title_templates' ) ? tiagsspace_title_templates() : array();
	if ( is_array( $titles ) ) {
		$moved = $set = array();
		foreach ( $titles as $key => $value ) {
			if ( ! preg_match( '/(^|-)' . preg_quote( $from, '/' ) . '(-|$)/', $key ) ) {
				continue;
			}
			$new_key = preg_replace( '/(^|-)' . preg_quote( $from, '/' ) . '(-|$)/', '$1' . $to . '$2', $key );
			if ( ! isset( $templates[ $new_key ] ) ) {
				$titles[ $new_key ] = $value;
				$moved[]            = $new_key;
			}
			unset( $titles[ $key ] );
		}
		foreach ( $templates as $key => $value ) {
			if ( preg_match( '/(^|-)' . preg_quote( $to, '/' ) . '(-|$)/', $key ) && ( $titles[ $key ] ?? null ) !== $value ) {
				$titles[ $key ] = $value;
				$set[]          = $key;
			}
		}
		$log( '  wpseo_titles: ' . count( $moved ) . ' keys moved from the old post type, ' . count( $set ) . ' set from the theme\'s title list' );
		foreach ( $moved as $k ) {
			$log( "    + {$k}" );
		}
		foreach ( $set as $k ) {
			$log( "    = {$k} (theme)" );
		}
		if ( ! $dry && ( $moved || $set ) ) {
			update_option( 'wpseo_titles', $titles );
		}
		$done['wpseo_titles'] = count( $moved ) + count( $set );
	}

	// 2b. Options whose arrays list the post type as a key or a value.
	foreach ( array( 'wpseo', 'featured_image_column', 'fediverse_posting_settings' ) as $option ) {
		$value = get_option( $option, null );
		if ( null === $value ) {
			continue;
		}
		$changes = array();
		$new     = $replace_in( $value, $changes, $option );
		$log( "  {$option}: " . count( $changes ) . ' change(s)' . ( $changes ? ' at ' . implode( ', ', $changes ) : '' ) );
		if ( ! $dry && $changes ) {
			update_option( $option, $new );
		}
		$done[ $option ] = count( $changes );
	}

	// 3. User meta keys of the edit screens.
	$like = '%' . $wpdb->esc_like( $from ) . '%';
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT umeta_id, user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
		$like
	) );
	$n = 0;
	foreach ( $rows as $row ) {
		$new_key = preg_replace( '/(^|[-_])' . preg_quote( $from, '/' ) . '(?=[-_]|columns|$)/', '$1' . $to, $row->meta_key );
		if ( $new_key === $row->meta_key ) {
			$log( "    user {$row->user_id}: {$row->meta_key} left alone (not a post-type key)" );
			continue;
		}
		$exists = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",
			$row->user_id,
			$new_key
		) );
		$log( "    user {$row->user_id}: {$row->meta_key} → {$new_key}" . ( $exists ? ' (target exists, old key dropped)' : '' ) );
		if ( ! $dry ) {
			if ( $exists ) {
				$wpdb->delete( $wpdb->usermeta, array( 'umeta_id' => $row->umeta_id ) );
			} else {
				$wpdb->update( $wpdb->usermeta, array( 'meta_key' => $new_key ), array( 'umeta_id' => $row->umeta_id ) );
			}
			wp_cache_delete( (int) $row->user_id, 'user_meta' );
		}
		$n++;
	}
	$log( "  user meta keys: {$n}" );
	$done['usermeta'] = $n;

	// 4. Yoast indexables: drop the old rows, rebuild them under the new type.
	$table = $wpdb->prefix . 'yoast_indexable';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE object_sub_type = %s AND object_type IN ('post','post-type-archive')",
			$from
		) );
		$log( '  yoast indexables: ' . count( $ids ) . ' to rebuild' );
		if ( ! $dry && $ids ) {
			$in = implode( ',', array_map( 'intval', $ids ) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}yoast_indexable_hierarchy WHERE indexable_id IN ({$in}) OR ancestor_id IN ({$in})" );
			$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" );
		}
		$done['yoast_indexables'] = count( $ids );
	}

	if ( $dry ) {
		$log( '[dry run] nothing written.' );
		return $done;
	}

	// 5. Caches, stats, rewrite rules.
	update_option( 'tiagsspace_archive_stats_salt', time(), false );
	wp_cache_flush();
	flush_rewrite_rules( false );

	// Rebuild Yoast's records now (they would also be rebuilt on the first visit).
	$rebuilt = 0;
	if ( function_exists( 'YoastSEO' ) && $total ) {
		try {
			foreach ( $post_ids as $id ) {
				YoastSEO()->meta->for_post( (int) $id );
				$rebuilt++;
			}
			if ( post_type_exists( $to ) ) {
				YoastSEO()->meta->for_post_type_archive( $to );
			}
		} catch ( \Throwable $e ) {
			$log( '  yoast rebuild stopped: ' . $e->getMessage() . ' (the rest is rebuilt on first visit)' );
		}
	}
	$log( "  yoast indexables requested: {$rebuilt} (Yoast stores them only where the environment type is production)" );

	if ( empty( $args['reverse'] ) ) {
		update_option( 'tiagsspace_cidade_migrated', current_time( 'mysql' ), false );
	} else {
		delete_option( 'tiagsspace_cidade_migrated' );
	}
	$log( 'Done.' );
	return $done;
}

/**
 * The digital sets that sat in Dusk between the 2011 film posts and Instax (Feb 2012 – Sep 2014),
 * moved to Cidade (Tiago, 2026-11-15: Dusk keeps the analog work). id => slug; both must match
 * on the environment or nothing moves. Old addresses (/dusk/<slug>/) are redirected by
 * library/redirects.php (a 404 under one series finds the post under another).
 */
const TIAGSSPACE_DUSK_TO_CIDADE = array(
	6881  => 'delirio-dei-sensi-kottbusser-tor',
	6882  => 'wildcat',
	6884  => 'introducing-oya',
	2604  => 'schwarz-zu-blau-night-at-the-lake',
	6886  => 'vergeude-deine-jugend',
	3330  => 'sidewalk',
	6887  => 'animal-collective-and-prince-rama',
	7050  => 'waiting-in-nytorget',
	6889  => 'a-beat-that-kills',
	7941  => 'at-the-harbor-1',
	7949  => 'at-the-harbor-2',
	8067  => 'so-viele-schnee-again',
	7973  => 'forster-strase',
	8175  => 'immekath',
	8304  => 'erster-mai',
	8365  => 'time-offf',
	8402  => 'oslo-hills',
	8487  => 'saints',
	9248  => 'catequese',
	8764  => 'spontaneity',
	8641  => 'miradouro',
	8702  => 'harley',
	9591  => 'wig',
	11778 => 'woods',
	12435 => 'instax',
);

/**
 * Moves the posts of TIAGSSPACE_DUSK_TO_CIDADE from dusk to cidade (or back with 'reverse').
 * Same arguments as tiagsspace_migrate_cityburns_to_cidade(). Idempotent. The move itself is
 * tiagsspace_move_sets() (library/move-sets.php).
 */
function tiagsspace_move_dusk_to_cidade( $args = array() ) {
	$reverse = ! empty( $args['reverse'] );
	return tiagsspace_move_sets( TIAGSSPACE_DUSK_TO_CIDADE, $reverse ? 'dusk' : 'cidade', array(
		'from'    => $reverse ? 'cidade' : 'dusk',
		'dry_run' => ! empty( $args['dry_run'] ),
		'log'     => isset( $args['log'] ) ? $args['log'] : null,
	) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Move the 2012–2014 digital Dusk sets to Cidade.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Only report what would change.
	 *
	 * [--reverse]
	 * : Move them back to Dusk.
	 */
	WP_CLI::add_command( 'tiagsspace dusk-to-cidade', function ( $args, $assoc ) {
		tiagsspace_move_dusk_to_cidade( array(
			'dry_run' => ! empty( $assoc['dry-run'] ),
			'reverse' => ! empty( $assoc['reverse'] ),
			'log'     => function ( $line ) { WP_CLI::log( $line ); },
		) );
	} );

	/**
	 * Move the City Series posts to the post type `cidade`.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Only report what would change.
	 *
	 * [--reverse]
	 * : Move back from cidade to cityburns.
	 *
	 * [--force]
	 * : Run even when the target post type is not registered.
	 */
	WP_CLI::add_command( 'tiagsspace cidade-migrate', function ( $args, $assoc ) {
		tiagsspace_migrate_cityburns_to_cidade( array(
			'dry_run' => ! empty( $assoc['dry-run'] ),
			'reverse' => ! empty( $assoc['reverse'] ),
			'force'   => ! empty( $assoc['force'] ),
			'log'     => function ( $line ) { WP_CLI::log( $line ); },
		) );
	} );
}
