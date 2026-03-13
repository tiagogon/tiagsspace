<?php
/**
 * Helpers
 *
 * WordPress cleanup and utility functions:
 * - Head cleanup (removes WP version, unnecessary link tags)
 * - Comment reply script loading
 * - Excerpt "Read more" link formatting
 * - Numeric pagination for archive/search pages
 * - Strips <p> tags WordPress wraps around images
 */

// --------------------------------------------------
// Head cleanup — remove unnecessary tags from <head>
// --------------------------------------------------


function wp_bootstrap_head_cleanup() {
	remove_action( 'wp_head', 'wlwmanifest_link' );                       // Windows Live Writer
	remove_action( 'wp_head', 'index_rel_link' );                         // index link
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );            // previous link
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );             // start link
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 ); // adjacent post links
	remove_action( 'wp_head', 'wp_generator' );                           // WP version number
}
add_action('init', 'wp_bootstrap_head_cleanup');

// Hide WP version from RSS feeds
function wp_bootstrap_rss_version() { return ''; }
add_filter('the_generator', 'wp_bootstrap_rss_version');

// --------------------------------------------------
// Comment reply script
// --------------------------------------------------

// Load comment-reply JS on single posts with open comments
function wp_bootstrap_queue_js() {
	if ( !is_admin() && is_singular() && comments_open() && get_option('thread_comments') ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action('wp_print_scripts', 'wp_bootstrap_queue_js');

// --------------------------------------------------
// Excerpt "Read more" link
// --------------------------------------------------

function wp_bootstrap_excerpt_more($more) {
	global $post;
	return '...  <a href="'. get_permalink($post->ID) . '" class="more-link" title="Read '.get_the_title($post->ID).'">Read more &raquo;</a>';
}
add_filter('excerpt_more', 'wp_bootstrap_excerpt_more');

// --------------------------------------------------
// Numeric pagination (used in search.php)
// --------------------------------------------------

function page_navi($before = '', $after = '') {
	global $wpdb, $wp_query;
	$posts_per_page = intval(get_query_var('posts_per_page'));
	$paged = intval(get_query_var('paged'));
	$numposts = $wp_query->found_posts;
	$max_page = $wp_query->max_num_pages;

	if ( $numposts <= $posts_per_page ) { return; }
	if ( empty($paged) || $paged == 0 ) { $paged = 1; }

	$pages_to_show = 7;
	$pages_to_show_minus_1 = $pages_to_show - 1;
	$half_page_start = floor($pages_to_show_minus_1 / 2);
	$half_page_end = ceil($pages_to_show_minus_1 / 2);
	$start_page = $paged - $half_page_start;

	if ($start_page <= 0) { $start_page = 1; }

	$end_page = $paged + $half_page_end;
	if (($end_page - $start_page) != $pages_to_show_minus_1) {
		$end_page = $start_page + $pages_to_show_minus_1;
	}
	if ($end_page > $max_page) {
		$start_page = $max_page - $pages_to_show_minus_1;
		$end_page = $max_page;
	}
	if ($start_page <= 0) { $start_page = 1; }

	echo $before . '<ul class="pagination">';

	if ($paged > 1) {
		echo '<li class="prev"><a href="' . get_pagenum_link() . '" title="First">&laquo</a></li>';
	}

	$prevposts = get_previous_posts_link('&larr; Previous');
	if ($prevposts) {
		echo '<li>' . $prevposts . '</li>';
	} else {
		echo '<li class="disabled"><a href="#">&larr; Previous</a></li>';
	}

	for ($i = $start_page; $i <= $end_page; $i++) {
		if ($i == $paged) {
			echo '<li class="active"><a href="#">' . $i . '</a></li>';
		} else {
			echo '<li><a href="' . get_pagenum_link($i) . '">' . $i . '</a></li>';
		}
	}

	echo '<li class="">';
	next_posts_link('Next &rarr;');
	echo '</li>';

	if ($end_page < $max_page) {
		echo '<li class="next"><a href="' . get_pagenum_link($max_page) . '" title="Last">&raquo;</a></li>';
	}

	echo '</ul>' . $after;
}

// --------------------------------------------------
// Strip <p> tags WordPress wraps around <img> elements
// --------------------------------------------------

function filter_ptags_on_images($content) {
	return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}
add_filter('the_content', 'filter_ptags_on_images');