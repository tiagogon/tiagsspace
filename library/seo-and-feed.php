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
/*
 * Share image, Open Graph image selection, the %%label%% Yoast variable,
 * hand-written description + label ("Layer 2"), per-set language, and the
 * machine-readable AI/TDM reservation. Schema pieces live in seo-schema.php.
 */

/** Byte ceiling for the link-preview image. WhatsApp stops rendering link
 *  previews somewhere around 300 KB, so stay clearly under it. */
if ( ! defined( 'TIAGSSPACE_SHARE_IMAGE_MAX_BYTES' ) ) {
    define( 'TIAGSSPACE_SHARE_IMAGE_MAX_BYTES', 280000 );
}

/** Public URL of the AI / text-and-data-mining policy page. Empty until the
 *  page exists; the tdm-policy meta/header is only emitted when set. */
if ( ! defined( 'TIAGSSPACE_AI_POLICY_URL' ) ) {
    define( 'TIAGSSPACE_AI_POLICY_URL', 'https://tiags.space/ai-policy/' );
}

/** Public post types that carry archive material (used for share images,
 *  the label variable and the language field). */
function tiagsspace_seo_post_types() {
    return array( 'post', 'dusk', 'films', 'log', 'hyper', 'cityburns', '4k-lento' );
}


// ----- Open Graph / Twitter image -----
// Yoast asks for images through the `wpseo_add_opengraph_images` container; when
// something is added there it is used as-is and Yoast computes the width/height
// from the real file, so the og:image:width/height tags match. The Twitter card
// falls back to the same image. Yoast's default image (Social settings) still
// applies when nothing suitable is found.

/** Attachment ID to share for the current request, or 0. */
function tiagsspace_share_image_id() {
    if ( is_singular() ) {
        $post_id = get_queried_object_id();
        $thumb   = (int) get_post_thumbnail_id( $post_id );
        if ( $thumb ) {
            return $thumb;
        }
        $ids = tiagsspace_post_gallery_ids( $post_id, 'image' );
        return ! empty( $ids ) ? (int) $ids[0] : 0;
    }

    $args = array(
        'numberposts'      => 5,
        'post_status'      => 'publish',
        'post_type'        => tiagsspace_seo_post_types(),
        'fields'           => 'ids',
        'suppress_filters' => true,
    );

    if ( is_post_type_archive() ) {
        $type = get_query_var( 'post_type' );
        $args['post_type'] = is_array( $type ) ? $type : array( $type );
    } elseif ( is_tax() || is_tag() || is_category() ) {
        $term = get_queried_object();
        if ( ! $term || empty( $term->taxonomy ) ) {
            return 0;
        }
        $args['tax_query'] = array( array(
            'taxonomy' => $term->taxonomy,
            'field'    => 'term_id',
            'terms'    => (int) $term->term_id,
        ) );
    } elseif ( is_home() || is_front_page() ) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array( 'key' => 'hide_post_from_main_page_archives_and_feed', 'compare' => 'NOT EXISTS' ),
            array( 'key' => 'hide_post_from_main_page_archives_and_feed', 'value' => '1', 'compare' => '!=' ),
        );
    } else {
        return 0;
    }

    foreach ( (array) get_posts( $args ) as $candidate ) {
        $thumb = (int) get_post_thumbnail_id( $candidate );
        if ( $thumb ) {
            return $thumb;
        }
    }
    return 0;
}

/**
 * Image array for the Yoast container: the LARGEST existing size of the
 * attachment whose file is under the byte ceiling (medium 940 → medium_large
 * 768 → small 720 → thumbnail 480). No dedicated size is generated: the sizes
 * the theme already keeps cover it, and only the oversized `medium` files
 * (about a quarter of them) fall through to the next rung.
 */
function tiagsspace_share_image_array( $attachment_id ) {
    $meta = wp_get_attachment_metadata( $attachment_id );
    if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
        return null;
    }
    $original = get_attached_file( $attachment_id );
    $dir      = $original ? dirname( $original ) : '';
    foreach ( array( 'medium', 'medium_large', 'small', 'thumbnail' ) as $size ) {
        if ( empty( $meta['sizes'][ $size ]['file'] ) ) {
            continue;
        }
        $s     = $meta['sizes'][ $size ];
        $bytes = ! empty( $s['filesize'] ) ? (int) $s['filesize'] : 0; // stored since WP 6.0
        if ( ! $bytes && $dir ) {
            $path  = $dir . '/' . $s['file'];
            $bytes = file_exists( $path ) ? (int) filesize( $path ) : 0;
        }
        if ( ! $bytes || $bytes > TIAGSSPACE_SHARE_IMAGE_MAX_BYTES ) {
            continue;
        }
        $src = wp_get_attachment_image_src( $attachment_id, $size );
        if ( ! $src || empty( $src[3] ) ) {
            continue; // $src[3] is false when WordPress fell back to the full-size original
        }
        return array(
            'url'    => $src[0],
            'width'  => (int) $src[1],
            'height' => (int) $src[2],
            'type'   => isset( $s['mime-type'] ) ? $s['mime-type'] : get_post_mime_type( $attachment_id ),
            'size'   => $size,
            'id'     => (int) $attachment_id,
            'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            'pixels' => (int) $src[1] * (int) $src[2],
        );
    }
    return null;
}

/** URL of the image this theme chose for the current request ('' when none). */
function tiagsspace_chosen_share_image_url( $set = null ) {
    static $url = '';
    if ( $set !== null ) {
        $url = (string) $set;
    }
    return $url;
}

function tiagsspace_add_opengraph_image( $container ) {
    $id = tiagsspace_share_image_id();
    if ( $id && is_object( $container ) && method_exists( $container, 'add_image' ) ) {
        $image = tiagsspace_share_image_array( $id );
        if ( $image ) {
            $container->add_image( $image );
            tiagsspace_chosen_share_image_url( $image['url'] );
        }
    }
    return $container;
}
add_filter( 'wpseo_add_opengraph_images', 'tiagsspace_add_opengraph_image' );

// Yoast still appends the image cached in its indexable after ours (add_from_indexable
// has no has_images() guard) and that cache can be stale, while the per-image
// `wpseo_opengraph_image` filter cannot remove an image (an empty result keeps the
// original URL). So when we have an image, replace the presentation's whole image
// list with it before the presenters run. Twitter falls back to og:image on its own.
add_filter( 'wpseo_frontend_presentation', function ( $presentation, $context ) {
    if ( ! is_object( $presentation ) ) {
        return $presentation;
    }
    $id = tiagsspace_share_image_id();
    if ( ! $id ) {
        return $presentation;
    }
    $image = tiagsspace_share_image_array( $id );
    if ( ! $image ) {
        return $presentation;
    }
    $presentation->open_graph_images = array( $image['url'] => $image );
    tiagsspace_chosen_share_image_url( $image['url'] );
    return $presentation;
}, 10, 2 );


// ----- Per-set language -----
// ACF select `set_language` (en | pt | mixed) on every archive post type.
// `pt` declares the page as Portuguese; `mixed` keeps the site default and
// leaves per-block lang attributes to the editor.

/** @return string 'en' | 'pt' | 'mixed' */
function tiagsspace_set_language( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : get_queried_object_id();
    if ( ! $post_id ) {
        return 'en';
    }
    $value = get_post_meta( $post_id, 'set_language', true );
    return in_array( $value, array( 'pt', 'mixed' ), true ) ? $value : 'en';
}

/** True when the current singular view is a Portuguese set. */
function tiagsspace_is_portuguese_view() {
    return is_singular() && tiagsspace_set_language( get_queried_object_id() ) === 'pt';
}

add_filter( 'language_attributes', function ( $output ) {
    if ( tiagsspace_is_portuguese_view() ) {
        $output = preg_replace( '/lang="[^"]*"/', 'lang="pt-PT"', $output, 1 );
    }
    return $output;
} );

add_filter( 'wpseo_locale', function ( $locale ) {
    return tiagsspace_is_portuguese_view() ? 'pt_PT' : $locale;
} );


// ----- %%label%% : the catalogue line used as meta description -----
// Museum wall label built from data that already exists on the post:
//   "Nine photographs. Berlin, 2016."
//   "Twelve photographs, iPhone. Favacal and Berlin, 2025."
//   "Film, 14 min. Cacinheira, 2008."
// Parts that are missing are omitted; no empty separators.

function tiagsspace_number_word( $n, $lang = 'en', $feminine = true ) {
    $n = (int) $n;
    $words = array(
        'en' => array( 1 => 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve' ),
        'pt' => array( 1 => 'Um', 'Dois', 'Três', 'Quatro', 'Cinco', 'Seis', 'Sete', 'Oito', 'Nove', 'Dez', 'Onze', 'Doze' ),
    );
    if ( $n < 1 || $n > 12 ) {
        return (string) $n;
    }
    $word = $words[ $lang === 'pt' ? 'pt' : 'en' ][ $n ];
    if ( $lang === 'pt' && $feminine ) {
        if ( $n === 1 ) { $word = 'Uma'; }
        if ( $n === 2 ) { $word = 'Duas'; }
    }
    return $word;
}

/** Deepest assigned term of a hierarchical taxonomy, or null. */
function tiagsspace_deepest_term( $post_id, $taxonomy ) {
    $terms = get_the_terms( $post_id, $taxonomy );
    if ( ! $terms || is_wp_error( $terms ) ) {
        return null;
    }
    $deepest = null;
    $depth   = -1;
    foreach ( $terms as $term ) {
        $d = count( get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) );
        if ( $d > $depth ) {
            $depth   = $d;
            $deepest = $term;
        }
    }
    return $deepest;
}

/** Film attachment ID for a post, or 0. */
function tiagsspace_film_attachment_id( $post_id ) {
    if ( ! function_exists( 'get_field' ) ) {
        return 0;
    }
    $value = get_field( 'self_host_film', $post_id );
    if ( is_array( $value ) ) {
        $value = isset( $value['ID'] ) ? $value['ID'] : ( isset( $value['id'] ) ? $value['id'] : 0 );
    }
    if ( is_string( $value ) && ! is_numeric( $value ) ) {
        $value = attachment_url_to_postid( $value );
    }
    return (int) $value;
}

/** Film duration in seconds (HLS bundle or attachment metadata), or 0. */
function tiagsspace_film_duration( $post_id ) {
    $attachment_id = tiagsspace_film_attachment_id( $post_id );
    if ( ! $attachment_id ) {
        return 0;
    }
    if ( function_exists( 'tiagsspace_video_is_hls' ) && function_exists( 'tiagsspace_hls_bundle_duration' )
        && tiagsspace_video_is_hls( $attachment_id ) ) {
        return (int) round( tiagsspace_hls_bundle_duration( $attachment_id ) );
    }
    $meta = wp_get_attachment_metadata( $attachment_id );
    return ( is_array( $meta ) && ! empty( $meta['length'] ) ) ? (int) $meta['length'] : 0;
}

/**
 * The label. Two sentences at most: what it is, then where and when.
 *
 * @param int         $post_id
 * @param string|null $lang     'en' | 'pt' (defaults to the post's set_language)
 * @param array       $omit     Parts to leave out: 'medium', 'count' (used to fit the length budget)
 */
function tiagsspace_seo_label( $post_id, $lang = null, $omit = array() ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) {
        return '';
    }
    $lang = $lang ? $lang : tiagsspace_set_language( $post_id );
    $pt   = ( $lang === 'pt' );

    // --- Sentence 1: kind + count (+ medium) ---
    $first       = '';
    $medium_term = taxonomy_exists( 'medium' ) ? tiagsspace_deepest_term( $post_id, 'medium' ) : null;
    $medium_name = $medium_term ? $medium_term->name : '';
    $medium_lc   = strtolower( $medium_name );

    $is_film = ( get_post_type( $post_id ) === 'films' ) || tiagsspace_film_attachment_id( $post_id );

    if ( get_post_type( $post_id ) === '4k-lento' ) {
        // A mix, not a photo set: the deepest medium term names the work ("DJ Mix", "Mixfile");
        // the single cover image is not counted.
        $first       = $medium_name ? $medium_name : 'Mix';
        $medium_name = '';
    } elseif ( $is_film ) {
        $first    = $pt ? 'Filme' : 'Film';
        $duration = tiagsspace_film_duration( $post_id );
        if ( $duration >= 60 ) {
            $first .= ', ' . (int) round( $duration / 60 ) . ' min';
        } elseif ( $duration > 0 ) {
            $first .= ', ' . $duration . ( $pt ? ' s' : ' sec' );
        }
    } elseif ( ! in_array( 'count', $omit, true ) ) {
        $count = count( tiagsspace_post_gallery_ids( $post_id, 'image' ) );
        if ( $count > 0 ) {
            // kind from the deepest medium term; the medium is then redundant and skipped
            if ( strpos( $medium_lc, 'screenshot' ) !== false ) {
                $kind = $pt ? array( 'captura de ecrã', 'capturas de ecrã', true ) : array( 'screenshot', 'screenshots', true );
                $medium_name = '';
            } elseif ( strpos( $medium_lc, 'still' ) !== false ) {
                $kind = $pt ? array( 'fotograma', 'fotogramas', false ) : array( 'still', 'stills', true );
                $medium_name = '';
            } else {
                $kind = $pt ? array( 'fotografia', 'fotografias', true ) : array( 'photograph', 'photographs', true );
            }
            $first = tiagsspace_number_word( $count, $lang, $kind[2] ) . ' ' . ( $count === 1 ? $kind[0] : $kind[1] );
        }
    }
    // medium: skipped when it only repeats the kind ("Photography") or is omitted for length
    if ( $first && $medium_name && ! in_array( 'medium', $omit, true )
        && strpos( $medium_lc, 'photograph' ) === false && strpos( $medium_lc, 'fotograf' ) === false
        && strpos( $medium_lc, 'video' ) === false && strpos( $medium_lc, 'film' ) === false ) {
        $first .= ', ' . $medium_name;
    }

    // --- Sentence 2: places, years ---
    $places = array();
    $terms  = taxonomy_exists( 'places' ) ? get_the_terms( $post_id, 'places' ) : array();
    if ( $terms && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $t ) {
            $places[] = $t->name;
        }
    }
    $place_str = '';
    if ( $places ) {
        $last      = array_pop( $places );
        $place_str = $places ? implode( ', ', $places ) . ( $pt ? ' e ' : ' and ' ) . $last : $last;
    }

    $years = array();
    $terms = taxonomy_exists( 'from' ) ? get_the_terms( $post_id, 'from' ) : array();
    if ( $terms && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $t ) {
            $years[] = $t->name;
        }
    }
    $year_str = '';
    if ( $years ) {
        $numeric = array_filter( $years, 'is_numeric' );
        if ( count( $numeric ) === count( $years ) ) {
            sort( $numeric, SORT_NUMERIC );
            $year_str = ( count( $numeric ) > 1 ) ? reset( $numeric ) . '–' . end( $numeric ) : reset( $numeric );
        } else {
            sort( $years );
            $year_str = implode( ', ', $years );
        }
    }

    $second = trim( implode( ', ', array_filter( array( $place_str, $year_str ) ) ) );

    $sentences = array();
    if ( $first ) {
        $sentences[] = $first . '.';
    }
    if ( $second ) {
        $sentences[] = $second . '.';
    }
    return implode( ' ', $sentences );
}

/**
 * %%pagenumber%% renders "1" on the first page; archive titles use it as
 * "Hyper %%pagenumber%% / %%sitename%%", so blank it unless we are past page 1
 * (Yoast collapses the leftover double space).
 */
add_filter( 'wpseo_replacements', function ( $replacements ) {
    if ( is_array( $replacements ) && array_key_exists( '%%pagenumber%%', $replacements ) && (int) get_query_var( 'paged' ) < 2 ) {
        $replacements['%%pagenumber%%'] = '';
    }
    return $replacements;
} );

/**
 * Figures for an archive, always current: how many sets, how many images, and
 * the first and last year among the `from` terms of those sets.
 *
 * $scope is what tiagsspace_archive_scope() returns:
 *   array( 'type' => 'all' )                                  the whole archive
 *   array( 'type' => 'post_type', 'post_type' => 'dusk' )     one series
 *   array( 'type' => 'term', 'taxonomy' => …, 'term_id' => … ) one place, medium, year, branch or tag
 *
 * @return array{sets:int,images:int,first:int,last:int,years:string}
 */
function tiagsspace_archive_stats( $scope = null ) {
    global $wpdb;
    if ( ! is_array( $scope ) || empty( $scope['type'] ) ) {
        $scope = array( 'type' => 'all' );
    }

    // One cache entry per scope. The salt changes whenever a set is published or
    // unpublished (see below), so every scope refreshes at once.
    $salt = (int) get_option( 'tiagsspace_archive_stats_salt', 1 );
    $key  = 'tiagsspace_astats_' . md5( wp_json_encode( $scope ) . '|' . $salt );
    $cached = get_transient( $key );
    if ( is_array( $cached ) && isset( $cached['sets'], $cached['images'], $cached['first'], $cached['last'] ) ) {
        return $cached;
    }

    $types = tiagsspace_seo_post_types();
    if ( $scope['type'] === 'post_type' ) {
        $types = array_values( array_intersect( $types, (array) $scope['post_type'] ) );
    }
    $empty = array( 'sets' => 0, 'images' => 0, 'first' => 0, 'last' => 0, 'years' => '' );
    if ( ! $types ) {
        return $empty;
    }
    $params = $types;
    $in     = implode( ',', array_fill( 0, count( $types ), '%s' ) );

    // Only what a visitor can reach: published sets that are not flagged
    // "hide from archives and feed", and images not flagged "hide from gallery".
    // ACF true/false stores '1' when checked; '0' or no row at all when not.
    $visible_set = "p.post_status = 'publish' AND p.post_type IN ($in)
           AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} h
                            WHERE h.post_id = p.ID
                              AND h.meta_key = 'hide_post_from_main_page_archives_and_feed'
                              AND h.meta_value = '1' )";

    if ( $scope['type'] === 'term' ) {
        $term = get_term( (int) $scope['term_id'], $scope['taxonomy'] );
        if ( ! $term || is_wp_error( $term ) ) {
            return $empty;
        }
        // A parent term counts its children ("Photography" includes the iPhone sets).
        $term_ids = array( (int) $term->term_id );
        if ( is_taxonomy_hierarchical( $term->taxonomy ) ) {
            $children = get_term_children( $term->term_id, $term->taxonomy );
            if ( ! is_wp_error( $children ) ) {
                $term_ids = array_merge( $term_ids, array_map( 'intval', $children ) );
            }
        }
        // Integers and an escaped taxonomy name only, so the clause carries no
        // placeholder of its own and can sit inside the prepared queries below.
        $ids_in   = implode( ',', array_map( 'intval', $term_ids ) );
        $taxonomy = esc_sql( $term->taxonomy );
        $visible_set .= " AND EXISTS ( SELECT 1 FROM {$wpdb->term_relationships} tr
                           JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                           WHERE tr.object_id = p.ID AND tt.taxonomy = '{$taxonomy}' AND tt.term_id IN ($ids_in) )";
    }

    $sets = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE $visible_set",
        $params
    ) );
    $images = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} a
         JOIN {$wpdb->posts} p ON p.ID = a.post_parent
         WHERE a.post_type = 'attachment' AND a.post_mime_type LIKE 'image/%%'
           AND $visible_set
           AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} g
                            WHERE g.post_id = a.ID
                              AND g.meta_key = 'remove_from_default_gallery'
                              AND g.meta_value = '1' )",
        $params
    ) );
    // First and last year among the numeric `from` terms attached to those sets.
    $years = $wpdb->get_row( $wpdb->prepare(
        "SELECT MIN( CAST( t.name AS UNSIGNED ) ) AS first, MAX( CAST( t.name AS UNSIGNED ) ) AS last
         FROM {$wpdb->posts} p
         JOIN {$wpdb->term_relationships} yr ON yr.object_id = p.ID
         JOIN {$wpdb->term_taxonomy} yt ON yt.term_taxonomy_id = yr.term_taxonomy_id AND yt.taxonomy = 'from'
         JOIN {$wpdb->terms} t ON t.term_id = yt.term_id AND t.name REGEXP '^[0-9]{4}$'
         WHERE $visible_set",
        $params
    ) );
    $first = $years ? (int) $years->first : 0;
    $last  = $years ? (int) $years->last : 0;

    $span = '';
    if ( $first ) {
        $span = ( $first === $last ) ? (string) $first : $first . '–' . $last;
    }

    $stats = array( 'sets' => $sets, 'images' => $images, 'first' => $first, 'last' => $last, 'years' => $span );
    set_transient( $key, $stats, 6 * HOUR_IN_SECONDS );
    return $stats;
}

// A set published, unpublished or trashed changes the figures: drop every cached scope.
add_action( 'transition_post_status', function ( $new, $old, $post ) {
    if ( $new === $old || ! $post || ! in_array( $post->post_type, tiagsspace_seo_post_types(), true ) ) {
        return;
    }
    if ( $new === 'publish' || $old === 'publish' ) {
        update_option( 'tiagsspace_archive_stats_salt', time(), false );
    }
}, 10, 3 );

/**
 * Which archive the current page is: a term, a series, or the whole archive.
 * $args is what Yoast hands to a replacement callback (it carries term_id and
 * taxonomy on term pages), used when the main query cannot tell.
 */
function tiagsspace_archive_scope( $args = array() ) {
    $args = (array) $args;
    if ( is_tax() || is_tag() || is_category() ) {
        $term = get_queried_object();
        if ( $term && ! empty( $term->term_id ) && ! empty( $term->taxonomy ) ) {
            return array( 'type' => 'term', 'taxonomy' => $term->taxonomy, 'term_id' => (int) $term->term_id );
        }
    }
    if ( is_post_type_archive() ) {
        $pt = get_query_var( 'post_type' );
        $pt = is_array( $pt ) ? reset( $pt ) : $pt;
        if ( $pt ) {
            return array( 'type' => 'post_type', 'post_type' => (string) $pt );
        }
    }
    if ( ! empty( $args['term_id'] ) && ! empty( $args['taxonomy'] ) ) {
        return array( 'type' => 'term', 'taxonomy' => (string) $args['taxonomy'], 'term_id' => (int) $args['term_id'] );
    }
    return array( 'type' => 'all' );
}

/**
 * The rounding rule for image counts: down to the hundred from 100 up, down to
 * the ten from 10 to 99, exact below 10. Never up, so a sentence never overstates.
 *
 * @return array{0:int,1:bool} the figure, and whether it was rounded
 */
function tiagsspace_round_figure( $n ) {
    $n = max( 0, (int) $n );
    if ( $n >= 100 ) {
        $r = (int) ( floor( $n / 100 ) * 100 );
    } elseif ( $n >= 10 ) {
        $r = (int) ( floor( $n / 10 ) * 10 );
    } else {
        $r = $n;
    }
    return array( $r, $r !== $n );
}

/** A count in words from one to twelve, in digits from 13. */
function tiagsspace_count_words( $n, $capitalise = false ) {
    $n = (int) $n;
    $w = ( $n >= 1 && $n <= 12 ) ? tiagsspace_number_word( $n, 'en' ) : number_format( $n );
    return $capitalise ? $w : lcfirst( $w );
}

/** "more than 1,100 images", "eight images", "one image". Empty for zero. */
function tiagsspace_images_phrase( $n ) {
    list( $r, $rounded ) = tiagsspace_round_figure( $n );
    if ( $r < 1 ) {
        return '';
    }
    return ( $rounded ? 'more than ' : '' ) . tiagsspace_count_words( $r ) . ' ' . ( $r === 1 ? 'image' : 'images' );
}

/** "between 2007 and 2025", or "in 2016" when there is one year. Empty when unknown. */
function tiagsspace_years_phrase( $stats, $single = 'in' ) {
    if ( empty( $stats['first'] ) ) {
        return '';
    }
    if ( (int) $stats['first'] === (int) $stats['last'] ) {
        return $single . ' ' . (int) $stats['first'];
    }
    return 'between ' . (int) $stats['first'] . ' and ' . (int) $stats['last'];
}

/**
 * The description of an archive page, one sentence per archive type. The wording
 * is Tiago's (27 Sep 2026); only the figures are computed.
 *
 *   series   A series of 127 sets and more than 1,100 images made between 2007 and 2025.
 *   log      A log of 115 entries and more than 400 images made between 2016 and 2025.
 *   mixes    A series of 36 DJ mixes recorded between 2016 and 2025.
 *   films    Eight films made between 2008 and 2025.
 *   place    335 sets and more than 3,600 images made in Berlin between 2010 and 2026.
 *   medium   iPhone, the medium of 212 sets and more than 2,800 images made between 2014 and 2026.
 *   year     23 sets and more than 100 images dated from 2024.
 *   branch   Still, a branch of the Log with 20 entries and more than 70 images made between 2017 and 2023.
 *   tag      beach, a tag on one set of eight images dated from 2016.
 *
 * Returns '' when the archive holds no sets, so no description is printed.
 */
function tiagsspace_archive_label( $scope = null ) {
    $scope = $scope ? $scope : tiagsspace_archive_scope();
    $s     = tiagsspace_archive_stats( $scope );
    $n     = (int) $s['sets'];
    if ( $n < 1 ) {
        return '';
    }
    $images = tiagsspace_images_phrase( $s['images'] );
    $made   = tiagsspace_years_phrase( $s, 'in' );           // between 2007 and 2025 | in 2016
    $dated  = tiagsspace_years_phrase( $s, 'from' );         // between 2014 and 2015 | from 2016
    $tail   = function ( $verb, $when ) {
        return $when !== '' ? ' ' . $verb . ' ' . $when : '';
    };
    $plural = function ( $count, $one, $many ) {
        return (int) $count === 1 ? $one : $many;
    };

    if ( $scope['type'] === 'post_type' ) {
        switch ( $scope['post_type'] ) {
            case 'films':
                return tiagsspace_count_words( $n, true ) . ' ' . $plural( $n, 'film', 'films' ) . $tail( 'made', $made ) . '.';
            case '4k-lento':
                return 'A series of ' . tiagsspace_count_words( $n ) . ' ' . $plural( $n, 'DJ mix', 'DJ mixes' ) . $tail( 'recorded', $made ) . '.';
            case 'log':
                return 'A log of ' . tiagsspace_count_words( $n ) . ' ' . $plural( $n, 'entry', 'entries' )
                    . ( $images ? ' and ' . $images : '' ) . $tail( 'made', $made ) . '.';
            default:
                return 'A series of ' . tiagsspace_count_words( $n ) . ' ' . $plural( $n, 'set', 'sets' )
                    . ( $images ? ' and ' . $images : '' ) . $tail( 'made', $made ) . '.';
        }
    }

    if ( $scope['type'] === 'term' ) {
        $term = get_term( (int) $scope['term_id'], $scope['taxonomy'] );
        $name = ( $term && ! is_wp_error( $term ) ) ? html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) : '';
        $sets = tiagsspace_count_words( $n, true ) . ' ' . $plural( $n, 'set', 'sets' );
        switch ( $scope['taxonomy'] ) {
            case 'places':
                return $sets . ( $images ? ' and ' . $images : '' ) . ' made in ' . $name . ( $made !== '' ? ' ' . $made : '' ) . '.';
            case 'from':
                return $sets . ( $images ? ' and ' . $images : '' ) . ' dated from ' . $name . '.';
            case 'medium':
                return $name . ', the medium of ' . tiagsspace_count_words( $n ) . ' ' . $plural( $n, 'set', 'sets' )
                    . ( $images ? ' and ' . $images : '' ) . $tail( 'made', $made ) . '.';
            case 'log-branch':
                return $name . ', a branch of the Log with ' . tiagsspace_count_words( $n ) . ' ' . $plural( $n, 'entry', 'entries' )
                    . ( $images ? ' and ' . $images : '' ) . $tail( 'made', $made ) . '.';
            default: // tags and any other taxonomy
                return $name . ', a tag on ' . tiagsspace_count_words( $n ) . ' ' . $plural( $n, 'set', 'sets' )
                    . ( $images ? ( $n === 1 ? ' of ' : ' with ' ) . $images : '' ) . $tail( 'dated', $dated ) . '.';
        }
    }

    // Whole archive.
    return tiagsspace_count_words( $n, true ) . ' ' . $plural( $n, 'set', 'sets' )
        . ( $images ? ' and ' . $images : '' ) . $tail( 'made', $made ) . '.';
}

add_action( 'wpseo_register_extra_replacements', function () {
    if ( ! function_exists( 'wpseo_register_var_replacement' ) ) {
        return;
    }
    // The three figures always describe the WHOLE archive (home and Index sentences).
    wpseo_register_var_replacement( '%%archive_sets%%', function () {
        $s = tiagsspace_archive_stats();
        return number_format( $s['sets'] );
    }, 'advanced', 'Number of published sets in the whole archive.' );
    wpseo_register_var_replacement( '%%archive_images%%', function () {
        $s = tiagsspace_archive_stats();
        list( $r, $rounded ) = tiagsspace_round_figure( $s['images'] );
        return ( $rounded ? 'more than ' : '' ) . number_format( $r );
    }, 'advanced', 'Images in the whole archive, rounded down, with "more than" when rounded.' );
    wpseo_register_var_replacement( '%%archive_years%%', function () {
        $s = tiagsspace_archive_stats();
        return $s['years'];
    }, 'advanced', 'First–last year of the whole archive, as "2007–2026".' );
    wpseo_register_var_replacement( '%%archive_between%%', function () {
        return tiagsspace_years_phrase( tiagsspace_archive_stats(), 'in' );
    }, 'advanced', 'Years of the whole archive as words: "between 2007 and 2026".' );
    // The sentence for the archive page being viewed.
    wpseo_register_var_replacement( '%%archive_label%%', function ( $var, $args ) {
        return tiagsspace_archive_label( tiagsspace_archive_scope( $args ) );
    }, 'advanced', 'Description sentence for the archive page being viewed.' );
} );


// ----- Share cards: bare titles -----
// Yoast's free version ignores its own "social title" templates and repeats the
// search title on share cards. A card carries the bare name instead: the work's
// title, the series name, the term name. The home card keeps Yoast's own setting.

function tiagsspace_bare_share_title( $title ) {
    if ( is_front_page() || is_home() ) {
        return $title;
    }
    if ( is_singular() ) {
        $id   = get_queried_object_id();
        $bare = get_the_title( $id );
        // A page with its own search title ("Links / %%sitename%%") shares under the
        // first segment of that title, so the card and the search result agree.
        $own = trim( (string) get_post_meta( $id, '_yoast_wpseo_title', true ) );
        if ( $own !== '' && strpos( $own, ' / ' ) !== false ) {
            $first = trim( strstr( $own, ' / ', true ) );
            if ( $first !== '' && strpos( $first, '%%' ) === false ) {
                $bare = $first;
            }
        }
    } elseif ( is_post_type_archive() ) {
        $pt   = get_query_var( 'post_type' );
        $bare = tiagsspace_series_name( is_array( $pt ) ? reset( $pt ) : $pt, 'archive' );
    } elseif ( is_tax() || is_tag() || is_category() ) {
        $term = get_queried_object();
        $bare = ( $term && ! empty( $term->name ) ) ? $term->name : '';
    } else {
        $bare = '';
    }
    $bare = trim( wp_strip_all_tags( html_entity_decode( (string) $bare, ENT_QUOTES, 'UTF-8' ) ) );
    return $bare !== '' ? $bare : $title;
}
add_filter( 'wpseo_opengraph_title', 'tiagsspace_bare_share_title', 20 );
add_filter( 'wpseo_twitter_title', 'tiagsspace_bare_share_title', 20 );

/** Yoast replacement variable %%label%%. */
add_action( 'wpseo_register_extra_replacements', function () {
    if ( ! function_exists( 'wpseo_register_var_replacement' ) ) {
        return;
    }
    wpseo_register_var_replacement(
        '%%label%%',
        function ( $var, $args ) {
            $post_id = ( is_object( $args ) && ! empty( $args->ID ) ) ? (int) $args->ID : get_queried_object_id();
            return $post_id ? tiagsspace_seo_label( $post_id ) : '';
        },
        'advanced',
        'Catalogue label built from the post: count and kind, medium, places, years.'
    );
} );

/**
 * Layer 2: when a post carries a hand-written Yoast description (the post meta
 * Yoast stores from its editor box), render it followed by the label — unless
 * it already ends with it. Fits ~155 chars by dropping the medium first, then
 * the count. Reads the post meta rather than Yoast's cached indexable so a
 * description written through any path (editor, MCP, wp-cli) is honoured.
 */
function tiagsspace_description_with_label( $description, $presentation ) {
    if ( ! is_singular() ) {
        return $description;
    }
    $post_id = get_queried_object_id();
    if ( ! $post_id || ! in_array( get_post_type( $post_id ), tiagsspace_seo_post_types(), true ) ) {
        return $description;
    }
    $stored = trim( wp_strip_all_tags( (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ) );
    if ( $stored === '' ) {
        return $description; // template output (%%label%%): nothing to append
    }
    foreach ( array( array(), array( 'medium' ), array( 'medium', 'count' ) ) as $omit ) {
        $label = tiagsspace_seo_label( $post_id, null, $omit );
        if ( $label === '' ) {
            return $stored;
        }
        if ( substr( $stored, -strlen( $label ) ) === $label ) {
            return $stored; // already there
        }
        $candidate = $stored . ' ' . $label;
        if ( mb_strlen( $candidate ) <= 155 ) {
            return $candidate;
        }
    }
    return $stored;
}
add_filter( 'wpseo_metadesc', 'tiagsspace_description_with_label', 10, 2 );
add_filter( 'wpseo_opengraph_desc', 'tiagsspace_description_with_label', 10, 2 );


// ----- AI / text-and-data-mining reservation -----
// Machine-readable reservation under Art. 4(3) Directive (EU) 2019/790, in every
// form crawlers look for: robots directives, TDMRep meta + headers. robots.txt
// and the nginx layer live on the server; /.well-known/tdmrep.json is a static file.

add_filter( 'wpseo_robots_array', function ( $robots ) {
    if ( is_array( $robots ) ) {
        $robots['noai']      = 'noai';
        $robots['noimageai'] = 'noimageai';
    }
    return $robots;
} );

add_action( 'wp_head', function () {
    echo "<meta name=\"tdm-reservation\" content=\"1\">\n";
    if ( TIAGSSPACE_AI_POLICY_URL ) {
        echo '<meta name="tdm-policy" content="' . esc_url( TIAGSSPACE_AI_POLICY_URL ) . "\">\n";
    }
}, 1 );

add_action( 'send_headers', function () {
    if ( headers_sent() || is_admin() ) {
        return;
    }
    header( 'tdm-reservation: 1' );
    if ( TIAGSSPACE_AI_POLICY_URL ) {
        header( 'tdm-policy: ' . TIAGSSPACE_AI_POLICY_URL );
    }
} );

// Keep IPTC/XMP metadata (creator, rights, data-mining reservation) in generated sizes.
add_filter( 'image_strip_meta', '__return_false' );
