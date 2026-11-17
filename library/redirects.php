<?php
/**
 * Redirects particular to this installation: one list, two layers.
 *
 * 1. tiagsspace_redirect_rules() is the only place a fixed redirect is written
 *    (an old address prefix → its new one). Change redirects here, nowhere else.
 * 2. Production: nginx answers these before WordPress or the W3TC page cache are
 *    reached. The snippet it includes is GENERATED from the list:
 *
 *      MAMP php bin/redirects-nginx.php > server/nginx/tiagsspace-redirects.conf
 *      (or: wp tiagsspace redirects nginx)
 *
 *    The committed copy is installed by hand as /etc/nginx/snippets/tiagsspace-redirects.conf
 *    and included in the tiags.space server block BEFORE the W3TC include (W3TC's
 *    server-level rewrite to a cached file ends in `last`, so a rule after it never
 *    runs for a cached URL). nginx never includes it straight from the theme: a bad
 *    commit must not be able to break a reload.
 * 3. PHP (this file, on template_redirect) applies the same list, so the local site
 *    (MAMP/Apache) behaves like production and a rule missing from nginx still works.
 *
 * Lookup redirects need the database, so they exist only in PHP and run only on a
 * 404, in this order:
 *   a. /post/<old id>: a restored Urbanoid post by meta `_urbanoid_old_id`;
 *   b. /<series>/<slug>/ of a set moved to another series (it keeps its slug);
 *   c. an OLD SLUG STORED ON THE POST, under a series or at the root. Every stored
 *      old slug lives in one of the meta keys of tiagsspace_redirect_old_slug_keys():
 *      `_tiagsspace_old_slug` (sets merged or renamed here, one row per slug) and
 *      `_urbanoid_old_slug` (restored Urbanoid posts). Never add a fixed rule for a
 *      single post: store its old slug instead.
 *
 * The full list of stored old slugs is generated, never hand-written:
 *
 *   MAMP php bin/redirects-list.php > server/redirects/OLD-SLUGS.md
 *   (or: wp tiagsspace redirects slugs)
 *
 * Every redirect issued here sets DONOTCACHEPAGE: in enhanced mode W3TC would
 * otherwise store a redirect response as an empty page.
 *
 * @package tiagsspace
 */

/**
 * The fixed redirects. Paths are relative to the site root, without trailing slash.
 *
 *   prefix: `from`, `from/` and `from/anything` → `to` + the rest (query kept).
 *   exact:  `from` and `from/` only → `to`.
 *
 * Exact rules are checked first. All are permanent (301).
 *
 * @return array[]
 */
function tiagsspace_redirect_rules() {
	return array(
		array( 'type' => 'exact',  'from' => '/log-branch/nxty', 'to' => '/log-branch/blwww/', 'note' => 'branch nxty merged into blwww' ),
		array( 'type' => 'exact',  'from' => '/log-branch',      'to' => '/log/',              'note' => 'the branch index is the Log' ),
		array( 'type' => 'prefix', 'from' => '/cityburns',       'to' => '/cidade',            'note' => 'City Series renamed Cidade Series, Nov 2026' ),
		array( 'type' => 'prefix', 'from' => '/sidewalk',        'to' => '/log',               'note' => 'Sidewalk became the Log' ),
		array( 'type' => 'prefix', 'from' => '/branch',          'to' => '/log-branch',        'note' => 'old taxonomy base' ),
	);
}

/**
 * Applies the fixed rules to a path (relative to the site root, raw as requested,
 * query string included). Returns the target path or null.
 */
function tiagsspace_redirect_match( $path ) {
	foreach ( tiagsspace_redirect_rules() as $rule ) {
		$from = preg_quote( $rule['from'], '#' );
		if ( 'exact' === $rule['type'] && preg_match( '#^' . $from . '/?(\?.*)?$#', $path, $m ) ) {
			return $rule['to'] . ( isset( $m[1] ) ? $m[1] : '' );
		}
		if ( 'prefix' === $rule['type'] && preg_match( '#^' . $from . '(/.*|\?.*)?$#', $path, $m ) ) {
			return $rule['to'] . ( isset( $m[1] ) ? $m[1] : '' );
		}
	}
	return null;
}

/**
 * The nginx snippet for the same rules. Server-level `if ($request_uri …) { return 301 … }`:
 * $request_uri is the raw address, so encoded characters (several slugs hold an
 * encoded "§") and the query string reach the target unchanged.
 */
function tiagsspace_redirects_nginx_snippet() {
	$out   = array();
	$out[] = '# tiags.space redirects. GENERATED from library/redirects.php in the theme; do not edit here.';
	$out[] = '# Regenerate: MAMP php bin/redirects-nginx.php > server/nginx/tiagsspace-redirects.conf';
	$out[] = '# Installed as /etc/nginx/snippets/tiagsspace-redirects.conf, included in the';
	$out[] = '# tiags.space server block before `include /var/www/tiagsspace/nginx.conf;` (W3TC).';
	$out[] = '';
	foreach ( tiagsspace_redirect_rules() as $rule ) {
		$from  = preg_quote( $rule['from'], '"' );
		$out[] = '# ' . $rule['note'];
		if ( 'exact' === $rule['type'] ) {
			$out[] = 'if ($request_uri ~ "^' . $from . '/?(\?.*)?$") { return 301 ' . $rule['to'] . '$1; }';
		} else {
			$out[] = 'if ($request_uri ~ "^' . $from . '(/.*|\?.*)?$") { return 301 ' . $rule['to'] . '$1; }';
		}
	}
	return implode( "\n", $out ) . "\n";
}

if ( ! function_exists( 'add_action' ) ) {
	return; // Loaded by bin/redirects-nginx.php without WordPress.
}

/** The requested path relative to the site root, raw, with its query string. */
function tiagsspace_redirect_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $base && 0 === strpos( $uri, $base . '/' ) ) {
		$uri = substr( $uri, strlen( $base ) );
	} elseif ( '' !== $base && $uri === $base ) {
		$uri = '/';
	}
	return $uri;
}

/** Redirect and stop, never cached by W3TC. */
function tiagsspace_redirect_to( $path_or_url, $status = 301 ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	$url = ( 0 === strpos( $path_or_url, 'http' ) ) ? $path_or_url : home_url( $path_or_url );
	wp_safe_redirect( $url, $status, 'tiagsspace' );
	exit;
}

/**
 * Meta keys that hold an old slug of a post, with the reason shown in the listing.
 * A post may carry several rows of the same key.
 *
 * @return string[] key => reason
 */
function tiagsspace_redirect_old_slug_keys() {
	return array(
		'_tiagsspace_old_slug' => 'merged or renamed',
		'_urbanoid_old_slug'   => 'restored from Urbanoid',
	);
}

/** Post types whose old slugs are looked up. */
function tiagsspace_redirect_series_types() {
	return array( 'cidade', 'dusk', 'hyper' );
}

/**
 * The published post whose meta (any of `$keys`) equals `$value`.
 *
 * @param string|string[] $keys
 * @return int Post id or 0.
 */
function tiagsspace_redirect_find_by_meta( $keys, $value ) {
	$query = array( 'relation' => 'OR' );
	foreach ( (array) $keys as $k ) {
		$query[] = array( 'key' => $k, 'value' => (string) $value );
	}
	$ids = get_posts( array(
		'post_type'        => tiagsspace_redirect_series_types(),
		'post_status'      => 'publish',
		'meta_query'       => $query,
		'fields'           => 'ids',
		'numberposts'      => 1,
		'no_found_rows'    => true,
		'suppress_filters' => true,
	) );
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Every stored old slug, for the listing: old path, new path, reason, post id.
 * Sorted by reason, then old path.
 *
 * @return array[]
 */
function tiagsspace_redirect_stored_slugs() {
	global $wpdb;
	$keys = tiagsspace_redirect_old_slug_keys();
	$in   = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT m.meta_key, m.meta_value, p.ID, p.post_type FROM {$wpdb->postmeta} m
		 JOIN {$wpdb->posts} p ON p.ID = m.post_id
		 WHERE m.meta_key IN ($in) AND p.post_status = 'publish' AND m.meta_value <> ''",
		array_keys( $keys )
	) );
	$out = array();
	foreach ( $rows as $r ) {
		$new = wp_make_link_relative( get_permalink( (int) $r->ID ) );
		// Urbanoid slugs lived at the root of the old site; the others under their series.
		$old = ( '_urbanoid_old_slug' === $r->meta_key ) ? '/' . $r->meta_value . '/' : '/' . $r->post_type . '/' . $r->meta_value . '/';
		$home = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( '' !== $home && 0 === strpos( $new, $home . '/' ) ) {
			$new = substr( $new, strlen( $home ) );
		}
		if ( $old === $new ) {
			continue; // Slug never changed: nothing to redirect.
		}
		$out[] = array( 'old' => $old, 'new' => $new, 'reason' => $keys[ $r->meta_key ], 'id' => (int) $r->ID );
	}
	usort( $out, function ( $a, $b ) {
		return array( $a['reason'], $a['new'], $a['old'] ) <=> array( $b['reason'], $b['new'], $b['old'] );
	} );
	return $out;
}

/** The listing of stored old slugs as Markdown (server/redirects/OLD-SLUGS.md). */
function tiagsspace_redirect_old_slugs_markdown() {
	$rows  = tiagsspace_redirect_stored_slugs();
	$out   = array();
	$out[] = '# Old slugs that redirect';
	$out[] = '';
	$out[] = 'GENERATED from the database by `bin/redirects-list.php` (or `wp tiagsspace redirects slugs`); do not edit by hand.';
	$out[] = 'Each row is an old address answered with a 301 by `library/redirects.php` (a lookup on a 404, PHP only).';
	$out[] = 'The fixed rules (`/cityburns/` → `/cidade/` and others) are in `server/nginx/tiagsspace-redirects.conf`.';
	$out[] = '';
	$reason = null;
	foreach ( $rows as $r ) {
		if ( $r['reason'] !== $reason ) {
			$reason = $r['reason'];
			$out[]  = '';
			$out[]  = '## ' . ucfirst( $reason );
			$out[]  = '';
			$out[]  = '| Old address | New address | Post |';
			$out[]  = '|---|---|---|';
		}
		$out[] = sprintf( '| `%s` | `%s` | %d |', $r['old'], $r['new'], $r['id'] );
	}
	$out[] = '';
	$out[] = count( $rows ) . ' old addresses.';
	return implode( "\n", $out ) . "\n";
}

add_action( 'template_redirect', function () {
	$path = tiagsspace_redirect_request_path();

	// Fixed rules (the same list nginx applies in production).
	$target = tiagsspace_redirect_match( $path );
	if ( null !== $target ) {
		tiagsspace_redirect_to( $target, 301 );
	}

	if ( ! is_404() ) {
		return;
	}
	$bare = (string) strtok( $path, '?' );

	// Old site: /post/<old id> (posts after June 2012).
	if ( preg_match( '#^/post/(\d+)/?$#', $bare, $m ) ) {
		$id = tiagsspace_redirect_find_by_meta( '_urbanoid_old_id', (int) $m[1] );
		tiagsspace_redirect_to( $id ? get_permalink( $id ) : '/', $id ? 301 : 302 );
	}

	// A set moved from one series to another keeps its slug (the 2012–2014 digital
	// sets went from Dusk to Cidade in Nov 2026): /dusk/<slug>/ → /cidade/<slug>/.
	if ( preg_match( '#^/(dusk|cidade|hyper)/([^/]+)/?$#', $bare, $m ) ) {
		$moved = get_posts( array(
			'post_type'        => array_values( array_diff( array( 'dusk', 'cidade', 'hyper' ), array( $m[1] ) ) ),
			'post_status'      => 'publish',
			'name'             => sanitize_title( rawurldecode( $m[2] ) ),
			'fields'           => 'ids',
			'numberposts'      => 1,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		) );
		if ( $moved ) {
			tiagsspace_redirect_to( get_permalink( $moved[0] ), 301 );
		}
	}

	// An old slug stored on a post (merged or renamed sets, restored Urbanoid posts),
	// under a series or at the root (the old Urbanoid form).
	if ( preg_match( '#^/(?:(?:' . implode( '|', tiagsspace_redirect_series_types() ) . ')/)?([^/]+)/?$#', $bare, $m ) ) {
		$slug = sanitize_title( rawurldecode( $m[1] ) );
		$id   = $slug ? tiagsspace_redirect_find_by_meta( array_keys( tiagsspace_redirect_old_slug_keys() ), $slug ) : 0;
		if ( $id ) {
			tiagsspace_redirect_to( get_permalink( $id ), 301 );
		}
	}
}, 1 );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Redirects of this installation.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : `nginx` prints the snippet for nginx; `list` prints the fixed rules;
	 * `slugs` prints the stored old slugs as Markdown (server/redirects/OLD-SLUGS.md).
	 */
	WP_CLI::add_command( 'tiagsspace redirects', function ( $args ) {
		$action = isset( $args[0] ) ? $args[0] : 'list';
		if ( 'nginx' === $action ) {
			WP_CLI::line( rtrim( tiagsspace_redirects_nginx_snippet() ) );
			return;
		}
		if ( 'slugs' === $action ) {
			WP_CLI::line( rtrim( tiagsspace_redirect_old_slugs_markdown() ) );
			return;
		}
		foreach ( tiagsspace_redirect_rules() as $r ) {
			WP_CLI::line( sprintf( '%-6s %-20s → %-20s %s', $r['type'], $r['from'], $r['to'], $r['note'] ) );
		}
	} );
}
