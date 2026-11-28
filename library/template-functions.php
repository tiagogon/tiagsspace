<?php
/**
 * Template helper functions used by theme templates.
 *
 * Includes: taxonomy_list, content_wrap, background colors, number/stats helpers,
 * attachment margins, media counts, [last_post_link] shortcode.
 *
 * @package tiagsspace
 */


/**
 * Attachment IDs that belong to a post's default gallery, in menu order.
 *
 * Excludes attachments flagged with the ACF true/false `remove_from_default_gallery`.
 * ACF may store '0' or no row at all when unchecked, hence the OR meta_query.
 *
 * @param int    $post_id
 * @param string $mime_type  e.g. 'image' or 'video'; '' for every attachment.
 * @return int[]
 */
function tiagsspace_post_gallery_ids( $post_id, $mime_type = '' ) {
    $args = array(
        'post_type'      => 'attachment',
        'posts_per_page' => -1,
        'post_parent'    => (int) $post_id,
        'post_status'    => 'any',
        'fields'         => 'ids',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => 'remove_from_default_gallery',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'remove_from_default_gallery',
                'value'   => '1',
                'compare' => '!=',
            ),
        ),
    );
    if ( $mime_type ) {
        $args['post_mime_type'] = $mime_type;
    }
    return array_map( 'intval', (array) get_posts( $args ) );
}


/**
 * The name a series is shown under: titles, headings, archive headers, share
 * cards, the menu and the label shown when the mouse is over a thumbnail.
 *
 * The post types keep their short labels ("Dusk"), which the admin uses.
 * Everywhere a visitor or a search engine reads the name of the series, it is
 * spelled as returned here, so a set and its archive say the same thing.
 *
 * @param string $post_type
 * @param string $context  'archive' for the series page, 'single' for one work in it.
 *                         They differ only where a work has a singular: a mix, a film.
 * @return string          Falls back to the post type's own label.
 */
function tiagsspace_series_name( $post_type, $context = 'archive' ) {
    $names = array(
        'dusk'      => array( 'archive' => 'Dusk Series',    'single' => 'Dusk Series' ),
        'hyper'     => array( 'archive' => 'Hyper Series',   'single' => 'Hyper Series' ),
        'strings'   => array( 'archive' => 'Strings Series', 'single' => 'Strings Series' ),
        'cidade'    => array( 'archive' => 'Cidade Series',  'single' => 'Cidade Series' ),
        'campo'     => array( 'archive' => 'Campo Series',   'single' => 'Campo Series' ),
        '4k-lento'  => array( 'archive' => '4K Lento Mixes', 'single' => '4K Lento Mix' ),
        'films'     => array( 'archive' => 'Films',          'single' => 'Film' ),
        'log'       => array( 'archive' => 'Log',            'single' => 'Log' ),
    );
    $context = ( $context === 'single' ) ? 'single' : 'archive';
    if ( isset( $names[ $post_type ] ) ) {
        return $names[ $post_type ][ $context ];
    }
    $obj = get_post_type_object( $post_type );
    return $obj ? $obj->labels->name : '';
}

/**
 * The name of a series as the page itself shows it: the menu, the header line, a set's
 * type crumb, the thumbnail hover label. Plain words, no "Series" (Tiago, Nov 2026: simple,
 * not pretentious). "Dusk Series" and the like stay in what search engines read
 * (tiagsspace_series_name(): <title> templates, share cards).
 *
 * @param string $post_type
 * @param string $context  'archive' or 'single', as tiagsspace_series_name().
 * @return string
 */
function tiagsspace_series_ui_name( $post_type, $context = 'archive' ) {
    $plain = array(
        'dusk'   => 'Dusk',
        'hyper'  => 'Hyper',
        'strings' => 'Strings',
        'cidade' => 'Cidade',
        'campo'  => 'Campo',
    );
    return isset( $plain[ $post_type ] ) ? $plain[ $post_type ] : tiagsspace_series_name( $post_type, $context );
}

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
	$terms_count = 0;
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
    echo "col-48 offset-0 col-sm-40 offset-sm-4 col-md-32 offset-md-8 col-lg-37 offset-lg-8";
}


/************* Background Colours *************/

// Map color palet with custom pages
function color_background_parameters ($parameter) {

	global $post;

	if (is_singular( array( 'hyper', 'strings' ) )) {
			$background_color_class = 'white-darkmode';
	} elseif (is_singular( 'dusk' )) {
			$background_color_class = 'white-darkmode';
	} elseif (is_singular( array( 'cidade', 'campo' ) )) {
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
	$background_day_night_mode = 'background-w-light-color';
	if ($background_color_class=="tiagsssss-color") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="white") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="white-darkmode") {
			$background_day_night_mode = 'background-w-light-color';
	} elseif ($background_color_class=="dark") {
			$background_day_night_mode = 'background-w-dark-color';
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
// Gallery gap -- the one number the space between images comes from
// ------------------------------------------------------------

/**
 * The gaps an editor can choose, in px. 'default' (the page gutter) is not in
 * the list: it is what every other stored value falls back to.
 */
function tiagsspace_gallery_gap_values() {
	return array( 0, 2, 4, 8, 16, 20 );
}

/**
 * Turn what is stored in the `spacement` field (and the retired `no_space`
 * tick) into a gap: one of tiagsspace_gallery_gap_values() or 'default'.
 *
 * Stored values over the years: 'spacement-N', the label
 * ': 40px (Theme Gallery)' (the old first choice, meaning the theme's own
 * gutter), an empty string, nothing. Anything not on the whitelist is
 * 'default', so no field text can reach the markup.
 *
 * @param mixed $spacement Stored `spacement` value.
 * @param mixed $no_space  Stored `no_space` value (retired field, still read).
 * @return int|string
 */
function tiagsspace_gallery_gap_normalise( $spacement, $no_space = false ) {
	if ( $no_space && '0' !== $no_space ) {
		return 0;
	}
	if ( is_string( $spacement ) && preg_match( '/^spacement-(\d+)$/', $spacement, $m ) ) {
		$px = (int) $m[1];
		if ( in_array( $px, tiagsspace_gallery_gap_values(), true ) ) {
			return $px;
		}
	}
	return 'default';
}

/**
 * The gap of the current post's alternative gallery. Reads the fields with no
 * post id, as the gallery template does, so an ACF preview shows unsaved values.
 *
 * @return int|string One of tiagsspace_gallery_gap_values() or 'default'.
 */
function tiagsspace_gallery_gap() {
	if ( ! function_exists( 'get_field' ) ) {
		return 'default';
	}
	return tiagsspace_gallery_gap_normalise( get_field( 'spacement' ), get_field( 'no_space' ) );
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


/*
 * Append a filemtime-based version query to an uploads URL.
 *
 * Media is served with a one-year Cache-Control, so replacing a file in place
 * (same filename) leaves visitors on the old copy until their cache expires.
 * Versioning the URL makes it a distinct cache entry and forces a refetch.
 * Returns the URL untouched if it isn't an uploads file or is missing on disk.
 */
function tiagsspace_versioned_media_url($url) {
	if (empty($url)) {
		return $url;
	}

	$uploads = wp_upload_dir();
	if (empty($uploads['baseurl']) || empty($uploads['basedir'])) {
		return $url;
	}

	// Compare protocol-relative so http/https mismatches still match.
	$baseurl = preg_replace('#^https?:#', '', $uploads['baseurl']);
	$target  = preg_replace('#^https?:#', '', $url);

	if (strpos($target, $baseurl) !== 0) {
		return $url;
	}

	$relative = substr($target, strlen($baseurl));
	$relative = explode('?', $relative);
	$path     = $uploads['basedir'] . urldecode($relative[0]);

	if (!file_exists($path)) {
		return $url;
	}

	return add_query_arg('v', filemtime($path), $url);
}


/*
 * Gallery grid spans.
 *
 * An item's width on each breakpoint, in columns of the theme's 48-column
 * grid, from the gallery's column counts and the attachment's "Diferent size
 * on Gallery" factor (0.25 = 4x … 1 = same … 5 = 1/5). A factor that makes
 * the item smaller is ignored on phones (xs). Each column count is held
 * between 1 and 12, as gallery-item.php always did.
 *
 * $cols = [ xs, sm, md, lg ] column counts. Returns [ xs, sm, md, lg ] spans.
 */
function tiagsspace_gallery_item_spans( $factor, $cols ) {
	$factor = (float) $factor; // the '5: 1/5' choice reads as 5
	if ( $factor <= 0 ) {
		$factor = 1;
	}
	$spans = array();
	foreach ( array_values( $cols ) as $i => $count ) {
		$count = (float) $count;
		$n     = ( 0 === $i && $factor > 1 ) ? $count : $count * $factor;
		$n     = min( 12, max( 1, $n ) );
		$spans[] = (int) ( 48 / $n );
	}
	return $spans;
}

/*
 * The attachment's size factor, as stored ('' or null = same size).
 */
function tiagsspace_gallery_item_factor( $attachment_id ) {
	$factor = get_field( 'diferent_size_on_gallery', $attachment_id );
	return $factor ? $factor : 1;
}

/*
 * Masonry column for a gallery: on each breakpoint, the greatest common
 * divisor of every item's span. Masonry rounds an item's span UP to whole
 * sizer columns, so an item that is not a multiple of the sizer leaves a hole
 * beside it (a 1.5x image, 21/48, on a 4/48 sizer took 24/48). A sizer of 1/48
 * everywhere would fix that, but Masonry measures the sizer to 1/64 px and the
 * error grows with every column (up to ~0.25px at the right edge), which shows
 * as hairlines in edge-to-edge galleries. The GCD keeps uniform galleries on
 * their own columns (16/48 for three) and goes finer only when sizes mix.
 *
 * Returns [ xs, sm, md, lg ] spans.
 */
/**
 * The cell shape of every audio item without a cover, from its neighbours (Tiago, 24 Nov 2026):
 * the picture just before it in the gallery; if it is the first, the picture just after it; if
 * the gallery has no pictures, 3:4. So an audio tile sits in the gallery's rhythm instead of
 * breaking it. Only pictures (image/*) count as neighbours; the list is the visible gallery, in
 * order (hidden items are not in it).
 *
 * @param WP_Post[] $attachments The gallery's visible attachments, in order.
 * @return array<int, float> attachment ID => padding-bottom ratio (height x 100 / width).
 */
function tiagsspace_gallery_audio_ratios( $attachments ) {
	$ratio = function ( $post ) {
		if ( 0 !== strpos( (string) $post->post_mime_type, 'image/' ) ) {
			return 0;
		}
		$src = wp_get_attachment_image_src( $post->ID, false );
		return ( $src && ! empty( $src[1] ) ) ? $src[2] * 100 / $src[1] : 0;
	};
	$list = array_values( (array) $attachments );
	$out  = array();
	foreach ( $list as $i => $post ) {
		if ( 0 !== strpos( (string) $post->post_mime_type, 'audio/' ) || get_post_thumbnail_id( $post->ID ) ) {
			continue;
		}
		$found = 0;
		for ( $j = $i - 1; $j >= 0 && ! $found; $j-- ) {
			$found = $ratio( $list[ $j ] );
		}
		for ( $j = $i + 1; $j < count( $list ) && ! $found; $j++ ) {
			$found = $ratio( $list[ $j ] );
		}
		$out[ $post->ID ] = $found ? $found : 400 / 3;
	}
	return $out;
}

function tiagsspace_gallery_sizer_spans( $attachments, $cols ) {
	$gcd = array( 0, 0, 0, 0 );
	foreach ( $attachments as $attachment ) {
		$spans = tiagsspace_gallery_item_spans( tiagsspace_gallery_item_factor( $attachment->ID ), $cols );
		foreach ( $spans as $i => $span ) {
			$a = $gcd[ $i ];
			$b = $span;
			while ( $b ) {
				$t = $b;
				$b = $a % $b;
				$a = $t;
			}
			$gcd[ $i ] = $a;
		}
	}
	foreach ( $gcd as $i => $value ) {
		$gcd[ $i ] = $value ? $value : 48;
	}
	return $gcd;
}

/*
 * How many attachments a post shows in its default gallery: the ones not flagged
 * remove_from_default_gallery, as tiagsspace_post_gallery_ids() counts them.
 * Counted for a whole list of posts in one query and remembered for the request:
 * call tiagsspace_prime_attachment_counts() with a loop's post IDs first; a post
 * not primed is counted with the posts of the current main loop around it.
 */
function tiagsspace_prime_attachment_counts( $post_ids ) {
	global $wpdb;
	$counts  = &tiagsspace_attachment_counts_store();
	$missing = array_values( array_diff( array_unique( array_map( 'intval', (array) $post_ids ) ), array_keys( $counts ) ) );
	$missing = array_filter( $missing );
	if ( ! $missing ) {
		return;
	}
	foreach ( $missing as $id ) {
		$counts[ $id ] = 0;
	}
	$in   = implode( ',', $missing );   // integers only
	$rows = $wpdb->get_results(
		"SELECT p.post_parent AS parent, COUNT(DISTINCT p.ID) AS n
		 FROM {$wpdb->posts} p
		 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'remove_from_default_gallery'
		 WHERE p.post_type = 'attachment'
		   AND p.post_status NOT IN ('trash', 'auto-draft')
		   AND p.post_parent IN ($in)
		   AND ( m.meta_value IS NULL OR m.meta_value <> '1' )
		 GROUP BY p.post_parent"
	);
	foreach ( (array) $rows as $row ) {
		$counts[ (int) $row->parent ] = (int) $row->n;
	}
}

function &tiagsspace_attachment_counts_store() {
	static $counts = array();
	return $counts;
}

function tiagsspace_visible_attachment_count( $post_id ) {
	$post_id = (int) $post_id;
	$counts  = &tiagsspace_attachment_counts_store();
	if ( ! isset( $counts[ $post_id ] ) ) {
		global $wp_query;
		$ids = ( $wp_query && $wp_query->posts ) ? wp_list_pluck( $wp_query->posts, 'ID' ) : array();
		$ids[] = $post_id;
		tiagsspace_prime_attachment_counts( $ids );
	}
	return $counts[ $post_id ] ?? 0;
}

/*
 * What a link to a post opens, for its cursor (_component-cursors.scss): a film
 * plays (data-opens="film", the play cursor), a 4K Lento mix plays its sound
 * (data-opens="mix", the waveform), any other post type except pages opens its
 * pictures: a stack of photographs when the gallery shows two or more
 * (data-opens="set"), one picture when it shows one or none (data-opens="single").
 * Returns the attribute with a leading space, or '' (pages, attachments).
 */
function tiagsspace_opens_attr( $post = null ) {
	$post = get_post( $post );
	$type = $post ? $post->post_type : '';
	if ( ! $type || in_array( $type, array( 'page', 'attachment' ), true ) ) {
		return '';
	}
	$opens = array( 'films' => 'film', '4k-lento' => 'mix' );
	if ( isset( $opens[ $type ] ) ) {
		$what = $opens[ $type ];
	} else {
		$what = tiagsspace_visible_attachment_count( $post->ID ) > 1 ? 'set' : 'single';
	}
	return ' data-opens="' . $what . '"';
}

/*
 * True the first time a key is seen in this request. For assets a template part
 * prints inline and that must appear once per page even when the part renders
 * many times (one gallery per entry on the Log listing).
 */
function tiagsspace_once( $key ) {
	static $seen = array();
	if ( isset( $seen[ $key ] ) ) {
		return false;
	}
	$seen[ $key ] = true;
	return true;
}
