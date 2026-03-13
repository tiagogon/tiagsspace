<?php
/**
 * SEO hooks and RSS feed customization.
 *
 * Includes: feed links, Feedly support, feed title/content formatting,
 * Yoast OpenGraph and Twitter image overrides.
 *
 * @package tiagsspace
 */


/************* Feed Customization *************/

// Automatic Feed Links is a theme feature introduced with Version 3.0.
add_theme_support( 'automatic-feed-links' );


// suport for Feedly
// https://blog.feedly.com/10-ways-to-optimize-your-feed-for-feedly/
// https://www.utilitylog.com/optimize-wordpress-blog-for-feedly/
add_filter( 'rss2_ns', 'feedly' );
function feedly() {
 echo 'xmlns:webfeeds="http://webfeeds.org/rss/1.0"';
}



// Feed titles for diferente post tips
function titlerss($content) {

    // get post ID
    global $post;

    $post_type = get_post_type($post->ID);

    if($post_type == 'dusk') {

            $content = 'dusk // '.$content;


    } elseif ($post_type == 'films') {

        $content = 'film // '.$content;

    } elseif ($post_type == 'log') {

        $log_series = 'log // '.strtoupper(taxonomy_list_w_numbers($post->ID,'log-branch','','',', ', ' &amp; ', 'no_link'));

        if ($log_series) {
            $content = 'log // '.$log_series.', '.$content;
        }
    }

    return $content;

}
add_filter('the_title_rss', 'titlerss');


// thumbnails is RSS, linking them to the associated post
function wptuts_feedimgs($content) {

    if (is_feed()) {

        // empety Images HTML array
        $imageshtml = "";

        // Info about the post
        global $post;
        $post_type = get_post_type($post->ID);

        // Get Images
        $args = array(
            'posts_per_page'   => -1, // Using -1 loads all posts
            'offset'           => 0,
            'orderby'           => 'menu_order', // set in the page media manager
            'order'             => 'ASC',
            'post_mime_type'    => 'image', // Make sure it doesn't pull other resources, like videos
            'post_parent'       => $post->ID, // Important part - ensures the associated images are loaded
            //'post_status'       => null,
            'post_type'         => 'attachment'

        );
        $images = get_posts( $args );

        // Count images
        $number_of_imgs = count($images);

        // Output the "view more" depending on the post type
        if ($post_type == 'post' OR
            $post_type == 'dusk') {

            $imageshtml = '<a href="'. get_permalink($post->ID) .'" class="webfeedsFeaturedVisual"><img src="'. wp_get_attachment_url( get_post_thumbnail_id($post->ID) ).'"/></a>
                    <p>view the '.$number_of_imgs.' images <a href="'. get_permalink($post->ID) .'">here</a>.</p>';

            // get images atached to the post
            $content =  $imageshtml;

        } elseif ($post_type == 'log') {

			$imageshtml = '<a href="'. get_permalink($post->ID) .'" class="webfeedsFeaturedVisual"><img src="'. wp_get_attachment_url( get_post_thumbnail_id($post->ID) ).'"/></a>
                    <p>view the '.$number_of_imgs.' images <a href="'. get_permalink($post->ID) .'">here</a>.</p>';

            // get images atached to the post
            $content =  $imageshtml;

        } elseif ($post_type == 'hyper' OR $post_type == '4k-lento') {

            $imageshtml = '<a href="'. get_permalink($post->ID) .'" class="webfeedsFeaturedVisual"><img src="'. wp_get_attachment_url( get_post_thumbnail_id($post->ID) ).'"/></a>
                    <p>view the complete set <a href="'. get_permalink($post->ID) .'">here</a>.</p>';

            // get images atached to the post
            $content =  $post->post_content.$imageshtml;

        } elseif ($post_type == 'films') {

            $imageshtml = '<a href="'. get_permalink($post->ID) .'" class="webfeedsFeaturedVisual"><img src="'. wp_get_attachment_url( get_post_thumbnail_id($post->ID) ).'"/></a>
                    <p>watch it <a href="'. get_permalink($post->ID) .'">here</a>.</p>';

            // get images atached to the post
            $content =  $imageshtml;
        }

        // Get the full gallery and content
        else {


            if ($images) {
                foreach ( $images as $image ) {
                    if (!get_field('remove_from_default_gallery',$image->ID)) {

                        // If this is the featured image
                        if (get_post_thumbnail_id($post->ID) == $image->ID) {
                            $featured_image = 'class="webfeedsFeaturedVisual"';
                        } else {
                            $featured_image = '';
                        }

                        $imageshtml .= '<a href="'. get_permalink($post->ID) .' '.$featured_image.'"><img src="'. esc_url( wp_get_attachment_url($image->ID)) .'"/></a>';
                    }
                }
                wp_reset_postdata();
            }

            // get images atached to the post
            $content =  $imageshtml.$content;
        }
    }


    return $content;

}
add_filter('the_content', 'wptuts_feedimgs');


/************* SEO Hooks *************/
// Change SEO image to smaller size - compatible with whatsapp

add_filter( 'wpseo_opengraph_image_size', 'cm1_rectangular_facebook_wpseo_image_size', 10, 1 );
function cm1_rectangular_facebook_wpseo_image_size( $string ) {
return 'medium';
}

// Change SEO image -- Hoked by Twitter and Facebook image filter
function seo_image($image) {
    $last_id = '';

    // Is Post Type archive
    if( is_post_type_archive( 'hyper' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'hyper',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];
   }

   if( is_post_type_archive( 'log' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'log',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];
   }

   if( is_post_type_archive( 'dusk' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'dusk',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];
   }

   if( is_post_type_archive( 'films' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'films',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];
   }

   if( is_post_type_archive( 'cityburns' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'cityburns',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];
   }

   if( is_post_type_archive( '4k-lento' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => '4k-lento',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];
   }

   if( is_tax( ) ) {

        // Get taxonomy term
        global $wp_query;
        $term = $wp_query->get_queried_object();
        $taxonomy = $term->taxonomy;
        $term_slug = $term->slug;


        $args = array(
            'numberposts' => '1',
            'post_status' => 'publish',
            'post_type' => array('post','dusk','films','log','hyper','cityburns','4k-lento'),
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term_slug,
                ),
            )
        );
        $last = wp_get_recent_posts( $args );
        if (!empty($last) && isset($last[0]['ID'])) {
            $last_id = $last[0]['ID'];
        }
   }

   if( is_home() ) {

        $args = array(
            'numberposts' => '1',
            'post_type' => array('post','dusk','films','hyper','cityburns'),
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        if (!empty($last) && isset($last[0]['ID'])) {
            $last_id = $last[0]['ID'];
        }
   }

	 // Get URL from last post ID
	 $thumbnail_id = get_post_thumbnail_id( $last_id );
	 $thumbnail_url = wp_get_attachment_image_src( $thumbnail_id, 'medium' );
	 $image = ($thumbnail_url && isset($thumbnail_url[0])) ? $thumbnail_url[0] : '';

   // Return Final image
   return $image;
}

add_filter('wpseo_opengraph_image', 'seo_image');
add_filter('wpseo_twitter_image', 'seo_image');
