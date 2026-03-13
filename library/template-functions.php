<?php
/**
 * Template helper functions used by theme templates.
 *
 * Includes: taxonomy_list, content_wrap, background colors, number/stats helpers,
 * attachment margins, media counts, [last_post_link] shortcode.
 *
 * @package tiagsspace
 */


// LIST OF TAGS
function taxonomy_list($post_id_of_the_tags,$custom_taxonomy, $tag_before, $tag_after, $separator_term, $separator_term_last, $tax_link) {
    //ID of the current post
    $this_id = $post_id_of_the_tags;

    // Get Series terms
    $terms = get_the_terms( $this_id, $custom_taxonomy);

	if ($terms && ! is_wp_error( $terms )) {
		$count = count( $terms );
	} else {
		$count = 0;
	}


    $terms_list = "";

    if ( $terms && ! is_wp_error( $terms ) ) {
        $i = 0;
        foreach ( $terms as $term ) {
            $i++;

            $term_link = get_term_link( $term );

            if ($i==1) {
                $separator = "";
            } elseif($count != $i) {
                $separator = $separator_term;
            } else{
                $separator = $separator_term_last;
            }

            if ($tax_link == 'link') {
                $terms_list = $terms_list.$separator.'<a href="'.$term_link.'" rel="category tag">'.$term->name.'</a>';
            } else {
                $terms_list = $terms_list.$separator.$term->name;
            }
        }

    // add content after and after the string
    $terms_list = $tag_before.$terms_list.$tag_after;
    }

    return $terms_list;
}


// List of tags with post number
function taxonomy_list_w_numbers($post_id_of_the_tags,$custom_taxonomy, $tag_before, $tag_after, $separator_term, $separator_term_last, $tax_link) {

    //ID of the current post
    $this_id = $post_id_of_the_tags;

    // Get Series terms
    $terms = get_the_terms( $this_id, $custom_taxonomy );

    // cont number of terms
	if ($terms) {
		$terms_count = count( $terms );
	}


    $i = 0;

    $terms_list = "";

    if ( $terms && ! is_wp_error( $terms ) ) :

        // Define variables
        $series_number = "";

        foreach ( $terms as $term ) {
            $i++;

            // Get the Slug of ther Series
            $series_slug = $term->slug;

            $series_name = $term->name;

            //Get posts with the Serie of the current post
            $args = array(
                'post_type' => 'log',
                'post_status' => array( 'publish', 'private' ),
                'order'     => 'ASC',
				'posts_per_page'   => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => $custom_taxonomy,
                        'field'    => 'slug',
                        'terms'    => $series_slug,
                    ),
                ),
            );
            $series_posts = new WP_Query( $args );

            $count = 0;
            if($series_posts->have_posts()) :
                while($series_posts->have_posts()) :
                    $series_posts->the_post();

                    $count++;

                    $id_loop_post = get_the_ID();
                    if ($id_loop_post == $this_id) {
                        $series_number = $count;
                    }
                endwhile;
            endif;
            wp_reset_postdata();

            if ($i==$terms_count) {
                $separator = "";
            }elseif ($i==($terms_count - 1)) {
                $separator = $separator_term_last;
            }else{
                $separator = $separator_term;
            }

            if ($tax_link == 'link') {

                $terms_list = $terms_list.'<a href="'.get_term_link( $term ).'" rel="category tag">'.$series_name.sprintf('%02d', $series_number).'</a>'.$separator;
            } else {

                $terms_list = $terms_list .$series_name.sprintf('%02d', $series_number).$separator;
            }

        }

        $terms_list = $tag_before.$terms_list.$tag_after;

    endif;

    return $terms_list;
}


// --- STYLE ---

// post content wrap styles
function content_wrap() {
    echo "col-48 offset-0 col-sm-40 offset-sm-4 col-md-32 offset-md-8 col-lg-27 offset-lg-9";
}


/************* Background Colours *************/

// Map color palet with custom pages
function color_background_parameters ($parameter) {

	global $post;

	if (is_singular( 'hyper' )) {
			$background_color_class = 'white-darkmode';
	} elseif (is_singular( 'dusk' )) {
			$background_color_class = 'white-darkmode';
	} elseif (  is_singular( 'log' )
							OR is_post_type_archive('log')
							OR is_tax('log-branch')) {
								$background_color_class = 'white-darkmode';
	} elseif ((is_singular( 'films' ) )) {
		$background_color_class = 'dark';
  } elseif (is_singular( '4k-lento' )) {
	  $background_color_class = 'dark';
	} elseif ( is_page_template( 'page-links.php' ) ) {
		$background_color_class = 'white-darkmode';
	} elseif ( is_page_template( 'page-table-index.php' ) ) {
		$background_color_class = 'tiagsssss-color';
	} elseif ( is_singular( )) {
		$background_color_class = 'white';
	} else {
		$background_color_class = "tiagsssss-color";
	}

	// Mapping color class to HEX code and font mode
	if ($background_color_class=="tiagsssss-color") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="white") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="white-darkmode") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="dark") {
			$background_day_night_mode = 'background-w-dark-color';
	} elseif ($background_color_class=="deep-purple") {
			$background_day_night_mode = 'background-w-dark-color';
	} elseif ($background_color_class=="blue") {
			$background_day_night_mode = 'background-w-dark-color';
	} elseif ($background_color_class=="yellow") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="lime") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="earth") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="sky") {
			$background_day_night_mode = 'background-w-light-color';
	}

	// Return logic
	if ($parameter == "background_color_class") {
		return $background_color_class;
	} elseif ($parameter == "background_day_night_mode") {
		return $background_day_night_mode;
	}
}



// Add css classes to BODY tag
function add_color_class( $classes ) {
    global $post;

		$classes[] = color_background_parameters('background_color_class');
		$classes[] = color_background_parameters('background_day_night_mode');

    // return the $classes array
    return $classes;
}
add_filter( 'body_class', 'add_color_class' );


/************* Number / Stats Helpers *************/

function number_of_the_post($post_ID)
{
    $the_post = get_post($post_ID);
    $post_type = get_post_type( $post_ID );
    $date = $the_post->post_date;
    $maintitle = $the_post->post_title;
    $count='';

    global $wpdb;
    $count = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts  WHERE (post_status='publish' OR post_status='private') AND post_type='$post_type' AND post_date<='{$date}'");

    return $count;
}


function number_of_images_in_total() {

global $wpdb;
        $res = $wpdb->get_results("select p1.*
            FROM {$wpdb->posts} p1, {$wpdb->posts} p2
            WHERE p1.post_parent = p2.ID
               AND p1.post_mime_type LIKE 'image%'

               AND p2.post_status = 'publish'
            ;"
        );
        $return = count($res);
        return $return;
}


function number_of_images_in_the_post_type($in_post_type) {

    global $wpdb;
        $res = $wpdb->get_results("select p1.*
            FROM {$wpdb->posts} p1, {$wpdb->posts} p2
            WHERE p1.post_parent = p2.ID
               AND p1.post_mime_type LIKE 'image%'
               AND p2.post_type = '$in_post_type'
               AND p2.post_status = 'publish'
            ;"
        );
        $return = count($res);
        return $return;
}


function number_of_days_in_the_post_type($in_post_type) {
    $last_post  =   get_posts("post_type=".$in_post_type."&numberposts=1");
    $first_post     =   get_posts("post_type=".$in_post_type."&numberposts=1&order=asc");

    $last_post_date     = get_the_time('Y-m-d',$last_post[0]->ID);
    $first_post_date    = get_the_time('Y-m-d',$first_post[0]->ID);

    $datetime1 = date_create($first_post_date );
    $datetime2 = date_create($last_post_date );
    $interval = date_diff($datetime1, $datetime2);

    $return = $interval->days;
    return $return;
}


function romanic_number($integer, $upcase = true)
{
    $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
    $return = '';
    while($integer > 0)
    {
        foreach($table as $rom=>$arb)
        {
            if($integer >= $arb)
            {
                $integer -= $arb;
                $return .= $rom;
                break;
            }
        }
    }

    return $return;
}


function atachement_custom_margin($attachmentId) {

		$margin = get_field( 'attachment_margin',$attachmentId );
		$marginTop = get_field( 'attachment_margin_top',$attachmentId );
		$marginRight = get_field( 'attachment_margin_right',$attachmentId );
		$marginBottom = get_field( 'attachment_margin_bottom',$attachmentId );
		$marginLeft = get_field( 'attachment_margin_left',$attachmentId );
		$zIndex = get_field( 'attachment_z_index',$attachmentId );

		$marginEchoString = "";

		if ($margin) {
			$marginEchoString = $marginEchoString."margin: ".$margin."%;";
		}
		if ($marginTop) {
			$marginEchoString = $marginEchoString."margin-top: ".$marginTop."%;";
		}
		if ($marginRight) {
			$marginEchoString = $marginEchoString."margin-right: ".$marginRight."%;";
		}
		if ($marginBottom) {
			$marginEchoString = $marginEchoString."margin-bottom: ".$marginBottom."%;";
		}
		if ($marginLeft) {
			$marginEchoString = $marginEchoString."margin-left: ".$marginLeft."%;";
		}
		if ($zIndex) {
			$marginEchoString = $marginEchoString."z-index: ".$zIndex.";";
		}

		echo $marginEchoString;
}

// ------------------------------------------------------------
// Count media attached -- images, video, sound
// ------------------------------------------------------------

function count_media_files_in_published_posts() {

	global $wpdb ;

	/* Adapted from https://snippets.khromov.se/get-all-attachments-whose-post-parent-is-published-in-wordpress/ */
	$sql = "SELECT
				ID,
				post_parent as parent,
				post_status,
				(SELECT post_status
					FROM {$wpdb->prefix}posts wp2
					WHERE wp2.ID = wp.post_parent
				) as parent_status,
				(SELECT post_date
					FROM {$wpdb->prefix}posts wp3
					WHERE wp3.ID = wp.post_parent
				) as parent_date,
				(SELECT post_type
					FROM {$wpdb->prefix}posts wp4
					WHERE wp4.ID = wp.post_parent
				) as parent_type
			FROM {$wpdb->prefix}posts wp
			WHERE post_type = 'attachment'
				AND post_status = 'inherit'
				AND post_parent <> 0
			HAVING parent_status = 'publish'
			ORDER BY parent_date DESC" ;

	$count_media_files = count($wpdb->get_results ($sql)) ;

	// Count Films (from VIMEO)
	$count_films = wp_count_posts('films');
	$published_films = $count_films->publish;

	return ($count_media_files + $published_films);

}

function count_media_files_in_published_post_type($post_type) {

	global $wpdb ;

	/* Adapted from https://snippets.khromov.se/get-all-attachments-whose-post-parent-is-published-in-wordpress/ */
	$sql = "SELECT
				ID,
				post_parent as parent,
				post_status,
				(SELECT post_status
					FROM {$wpdb->prefix}posts wp2
					WHERE wp2.ID = wp.post_parent
				) as parent_status,
				(SELECT post_date
					FROM {$wpdb->prefix}posts wp3
					WHERE wp3.ID = wp.post_parent
				) as parent_date,
				(SELECT post_type
					FROM {$wpdb->prefix}posts wp4
					WHERE wp4.ID = wp.post_parent
				) as parent_type
			FROM {$wpdb->prefix}posts wp
			WHERE post_type = 'attachment'
				AND post_status = 'inherit'
				AND post_parent <> 0
			HAVING parent_status = 'publish'
				AND parent_type =  '{$post_type}'
			ORDER BY parent_date DESC" ;

	$count_media_files = count($wpdb->get_results ($sql)) ;

	return $count_media_files;

}

// The Shortcode https://codex.wordpress.org/Shortcode_API
// [last_post_link post-type="post]
function last_post_link_function( $atts ) {
	$attributes = shortcode_atts( array(
		'post-type' => 'post',
		'sticky' => 0,
	), $atts );


	//query
	$args = array(
		'post_type' => $attributes['post-type'],
		'posts_per_page' => 1
		);
	$the_query = new WP_Query( $args );

	// The Loop
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			return '<a href="'.get_permalink().'">' . get_the_title() . '</a>';
		}
	} else {
	// no posts found
	}
	/* Restore original Post Data */
	wp_reset_postdata();
}
add_shortcode( 'last_post_link', 'last_post_link_function' );
