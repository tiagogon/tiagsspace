<?php
/*
YARPP Template: Yet Another trouble post
Description: Requires the Yet Another Photoblog plugin
Author: Tiago (trouble.place)
*/

// YARPP sets $related_query and $related_count in template scope.
// It also sets $wp_query->posts via a filter but may not sync post_count
// (especially on previews where the inner 'p'=>ID query returns 0 rows).
// Force all three counters to match the actual related posts array.
global $wp_query;

// $related_query is available here from YARPP template scope
if ( ! empty( $related_query ) && $related_query instanceof WP_Query ) {
    $wp_query = $related_query;
}

if ( is_array( $wp_query->posts ) ) {
    $count = count( $wp_query->posts );
    $wp_query->post_count  = $count;
    $wp_query->found_posts = $count;
    $wp_query->rewind_posts();
}

// Only render the grid if there are related posts to show
if ( $wp_query->post_count > 0 ) {
    get_template_part( 'template-parts/content', 'index' );
}
