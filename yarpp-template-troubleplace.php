<?php
/*
YARPP Template: Yet Another trouble post
Description: Requires the Yet Another Photoblog plugin
Author: Tiago (trouble.place)
*/

// YARPP replaces $wp_query->posts with related results but may not update
// post_count (notably on previews). Sync them so have_posts() works correctly.
global $wp_query;
if ( is_array( $wp_query->posts ) ) {
    $wp_query->post_count = count( $wp_query->posts );
    $wp_query->found_posts = $wp_query->post_count;
    $wp_query->rewind_posts();
}

get_template_part( 'template-parts/content', 'index' );
