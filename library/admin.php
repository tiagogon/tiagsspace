<?php
/* 
This file handles the admin area and functions.
You can use this file to make changes to the
dashboard. Updates to this page are coming soon.
It's turned off by default, but you can call it
via the functions file.

Developed by: Eddie Machado
URL: http://themble.com/bones/

Special Thanks for code & inspiration to:
@jackmcconnell - http://www.voltronik.co.uk/
Digging into WP - http://digwp.com/2010/10/customize-wordpress-dashboard/

*/

/************* DASHBOARD WIDGETS *****************/

// disable default dashboard widgets
function disable_default_dashboard_widgets() {
	// remove_meta_box('dashboard_right_now', 'dashboard', 'core');    // Right Now Widget
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'core'); // Comments Widget
	remove_meta_box('dashboard_incoming_links', 'dashboard', 'core');  // Incoming Links Widget
	remove_meta_box('dashboard_plugins', 'dashboard', 'core');         // Plugins Widget

	// remove_meta_box('dashboard_quick_press', 'dashboard', 'core');  // Quick Press Widget
	remove_meta_box('dashboard_recent_drafts', 'dashboard', 'core');   // Recent Drafts Widget
	remove_meta_box('dashboard_primary', 'dashboard', 'core');         // 
	remove_meta_box('dashboard_secondary', 'dashboard', 'core');       //
	
	// removing plugin dashboard boxes 
	remove_meta_box('yoast_db_widget', 'dashboard', 'normal');         // Yoast's SEO Plugin Widget
	
	/* 
	have more plugin widgets you'd like to remove? 
	share them with us so we can get a list of 
	the most commonly used. :D
	https://github.com/eddiemachado/bones/issues
	*/
}

/*
Now let's talk about adding your own custom Dashboard widget.
Sometimes you want to show clients feeds relative to their 
site's content. For example, the NBA.com feed for a sports
site. Here is an example Dashboard Widget that displays recent
entries from an RSS Feed.

For more information on creating Dashboard Widgets, view:
http://digwp.com/2010/10/customize-wordpress-dashboard/
*/

// RSS Dashboard Widget 

	// DELETED

/************* META BOX ORDER *****************/

/**
 * The meta boxes pinned to the top of every edit screen, in this order.
 * The three posting queues first (what the set becomes elsewhere), then the
 * set's own options. Everything else (Yoast, Videopack, Set language, …)
 * follows in its registered order.
 *
 * IDs: a plain add_meta_box() box keeps its id; an ACF group is 'acf-' + key.
 */
function tiagsspace_meta_box_order_first() {
	return array(
		'instagram-queue-meta-box', // Instagram   (instagram-posting-api)
		'acf-group_tumblr',         // Tumblr      (tumblr-posting-api, ACF group)
		'fediverse-posting-box',    // Fediverse   (wp-fediverse-posting)
		'acf-group_55d328d2d8225',  // Gallery
		'acf-group_58247bb8c1dbd',  // Video Player Options
		'acf-group_576838826520e',  // Background & Around
	);
}

/**
 * Bind the order filter to the screen being built. add_meta_boxes fires with
 * the post type, which is also the screen id do_meta_boxes() reads the
 * "meta-box-order_<screen>" user option under — in the block editor and the
 * classic editor alike. Late priority so every plugin has registered by then.
 */
add_action( 'add_meta_boxes', 'tiagsspace_bind_meta_box_order', 999 );
function tiagsspace_bind_meta_box_order( $post_type ) {
	add_filter( "get_user_option_meta-box-order_{$post_type}", 'tiagsspace_filter_meta_box_order', 10, 3 );
}

/**
 * Rewrite the saved (or absent) meta box order so the 'normal' column starts
 * with tiagsspace_meta_box_order_first(). do_meta_boxes() moves every id
 * listed here into the 'sorted' priority, in list order, and renders that
 * before 'high', so Yoast's high-priority box no longer jumps the queue.
 * The tail keeps a drag order saved in the classic editor, if any; boxes it
 * does not know about are appended in their registered order.
 */
function tiagsspace_filter_meta_box_order( $saved, $option, $user ) {
	global $wp_meta_boxes;

	$screen = get_current_screen();
	if ( ! $screen || empty( $wp_meta_boxes[ $screen->id ] ) ) {
		return $saved;
	}
	$boxes      = $wp_meta_boxes[ $screen->id ];
	$priorities = array( 'high', 'sorted', 'core', 'default', 'low' );

	// Every real box on this screen (any context), and the normal column in registered order.
	$present = array();
	$normal  = array();
	foreach ( $boxes as $context => $by_priority ) {
		foreach ( $priorities as $priority ) {
			foreach ( (array) ( $by_priority[ $priority ] ?? array() ) as $id => $box ) {
				if ( ! $box || empty( $box['title'] ) ) {
					continue;
				}
				$present[ $id ] = true;
				if ( 'normal' === $context ) {
					$normal[] = $id;
				}
			}
		}
	}

	$first = array_values( array_filter( tiagsspace_meta_box_order_first(), function ( $id ) use ( $present ) {
		return isset( $present[ $id ] );
	} ) );
	if ( ! $first ) {
		return $saved; // Not one of our screens (comment, link, …).
	}

	$saved = is_array( $saved ) ? $saved : array();

	// The pinned boxes live in the normal column, whatever an old drag did with them.
	foreach ( array( 'side', 'advanced' ) as $context ) {
		if ( ! empty( $saved[ $context ] ) ) {
			$saved[ $context ] = implode( ',', array_diff( explode( ',', $saved[ $context ] ), $first ) );
		}
	}

	$rest = array_values( array_diff( $normal, $first ) );
	if ( ! empty( $saved['normal'] ) ) {
		$rank = array_flip( explode( ',', $saved['normal'] ) );
		usort( $rest, function ( $a, $b ) use ( $rank ) {
			return ( $rank[ $a ] ?? PHP_INT_MAX ) <=> ( $rank[ $b ] ?? PHP_INT_MAX );
		} );
	}

	$saved['normal'] = implode( ',', array_merge( $first, $rest ) );
	return $saved;
}

// "Preview in new tab" icon for draft/pending posts (published posts get a native one)
function enqueue_editor_draft_preview_link() {
	wp_enqueue_script(
		'editor-draft-preview-link',
		get_template_directory_uri() . '/library/js/editor-draft-preview-link.js',
		array(),
		'1.2',
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'enqueue_editor_draft_preview_link' );


/************* ADMIN BAR *****************/

// Toolbar items dropped everywhere (wp-admin and the front end), and the posting
// shortcuts in the order they should read. Ids that don't exist on a site are skipped.
function tiagsspace_admin_bar_layout() {
	return array(
		'remove' => array( 'wp-logo', 'command-palette', 'new-content', 'search', 'w3tc' ),
		'order'  => array( 'queue-posts-planner', 'instagram-planner', 'fediverse-planner' ),
	);
}

// Runs last, so every plugin node exists. The toolbar draws top-level items in the
// order they were added, so the shortcuts are re-added in the wanted order where the
// first of them stood, and every top-level item after that point (the dark-mode
// toggle) is re-added behind them to keep its place. Children keep their parent id.
add_action( 'admin_bar_menu', 'tiagsspace_admin_bar_tidy', PHP_INT_MAX );
function tiagsspace_admin_bar_tidy( $bar ) {
	$layout = tiagsspace_admin_bar_layout();

	foreach ( $layout['remove'] as $id ) {
		$bar->remove_node( $id );
	}

	$top = array();
	foreach ( (array) $bar->get_nodes() as $id => $node ) {
		if ( empty( $node->parent ) && empty( $node->group ) ) {
			$top[] = $id;
		}
	}

	$start = null;
	foreach ( $top as $i => $id ) {
		if ( in_array( $id, $layout['order'], true ) ) {
			$start = $i;
			break;
		}
	}
	if ( null === $start ) {
		return;
	}

	$tail  = array_slice( $top, $start );
	$nodes = array();
	foreach ( $tail as $id ) {
		$nodes[ $id ] = (array) $bar->get_node( $id );
		$bar->remove_node( $id );
	}

	$sequence = array_merge(
		array_intersect( $layout['order'], $tail ),
		array_diff( $tail, $layout['order'] )
	);
	foreach ( $sequence as $id ) {
		$bar->add_node( $nodes[ $id ] );
	}
}
