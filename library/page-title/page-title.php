<?php
/**
 * Page title module — entry point.
 *
 * One model and one renderer for every line that says where the visitor is:
 * the fixed header ("Tiags' Space / Dusk Series / Them"), a listing's entry
 * title, and the label a thumbnail shows on hover. Three parts:
 *
 *   config.php   the words (series, taxonomies, short forms) — edit this to add a post type
 *   crumbs.php   builders: data in, data out, no HTML, no globals
 *   render.php   the one place HTML is written
 *
 * Theme-aware on purpose (it knows this theme's post types), unlike the
 * portable library/video/ module. Read README.md before changing it.
 *
 * Callers: template-parts/header-title.php, template-parts/entry-body.php,
 * template-parts/archive-grid.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/crumbs.php';
require_once __DIR__ . '/render.php';

/**
 * Body class "header-entry-title" on the pages whose header line carries a
 * work's title (every singular view but the Index page), so the stylesheet can
 * treat that line differently from an archive's (_region-header-nav.scss).
 */
add_filter( 'body_class', function ( $classes ) {
    $crumbs = tiagsspace_page_crumbs();
    foreach ( $crumbs as $crumb ) {
        if ( ! empty( $crumb['heading'] ) ) {
            $classes[] = 'header-entry-title';
            break;
        }
    }
    return $classes;
} );

/**
 * WP-CLI: print the crumbs and the rendered lines of one or more posts, to
 * check the module against README.md or to see what a work will be called.
 *
 *   wp tiagsspace page-title 35362
 *   wp tiagsspace page-title 35362 36413 --format=json
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'tiagsspace page-title', function ( $args, $assoc ) {
        if ( empty( $args ) ) {
            WP_CLI::error( 'Give one or more post ids.' );
        }
        $json = isset( $assoc['format'] ) && 'json' === $assoc['format'];
        foreach ( $args as $id ) {
            $id     = (int) $id;
            $crumbs = tiagsspace_entry_crumbs( $id );
            if ( ! $crumbs ) {
                WP_CLI::warning( "$id: no such post" );
                continue;
            }
            $header  = tiagsspace_render_crumbs( array_merge( array( tiagsspace_site_crumb() ), $crumbs ), array( 'heading' => 'h1', 'group_from' => 1 ) );
            $listing = tiagsspace_render_crumbs( tiagsspace_entry_crumbs( $id, array( 'link_title' => true ) ), array( 'heading' => 'h2', 'responsive' => false, 'class' => 'single-title' ) );
            $label   = tiagsspace_render_crumbs( $crumbs, array( 'links' => false, 'responsive' => 'xl', 'wrap' => false ) );
            if ( $json ) {
                WP_CLI::line( wp_json_encode( array( 'id' => $id, 'crumbs' => $crumbs, 'header' => $header, 'listing' => $listing, 'label' => $label ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
                continue;
            }
            WP_CLI::line( "# $id" );
            foreach ( $crumbs as $crumb ) {
                WP_CLI::line( sprintf( '  %s%s%s%s', $crumb['label'], $crumb['heading'] ? ' *' : '', $crumb['short'] ? " (short: {$crumb['short']})" : '', $crumb['url'] ? " → {$crumb['url']}" : '' ) );
            }
            WP_CLI::line( "  header:  $header" );
            WP_CLI::line( "  listing: $listing" );
            WP_CLI::line( "  label:   $label" );
        }
    } );
}
