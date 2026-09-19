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

/** Byte ceiling for the `share` image size. WhatsApp stops rendering link
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


// ----- Share image size: keep the `share` file under the byte ceiling -----
// The size itself is registered in functions.php (1200x1200 box, no crop).
// After WordPress generates the sizes, re-save only the `share` file at a
// lower JPEG quality until it fits. Originals and every other size are untouched.

function tiagsspace_cap_share_image_bytes( $metadata, $attachment_id ) {
    if ( empty( $metadata['sizes']['share']['file'] ) ) {
        return $metadata;
    }
    $mime = isset( $metadata['sizes']['share']['mime-type'] ) ? $metadata['sizes']['share']['mime-type'] : get_post_mime_type( $attachment_id );
    if ( ! in_array( $mime, array( 'image/jpeg', 'image/webp' ), true ) ) {
        return $metadata; // PNG/GIF quality knobs do not shrink files predictably; leave them
    }
    $original = get_attached_file( $attachment_id );
    if ( ! $original ) {
        return $metadata;
    }
    $path = dirname( $original ) . '/' . $metadata['sizes']['share']['file'];
    if ( ! file_exists( $path ) ) {
        return $metadata;
    }

    clearstatcache( true, $path );
    $size = filesize( $path );
    if ( $size > TIAGSSPACE_SHARE_IMAGE_MAX_BYTES ) {
        foreach ( array( 76, 70, 64, 58, 52 ) as $quality ) {
            $editor = wp_get_image_editor( $path );
            if ( is_wp_error( $editor ) ) {
                break;
            }
            $editor->set_quality( $quality );
            $saved = $editor->save( $path );
            if ( is_wp_error( $saved ) ) {
                break;
            }
            clearstatcache( true, $path );
            $size = filesize( $path );
            if ( $size <= TIAGSSPACE_SHARE_IMAGE_MAX_BYTES ) {
                break;
            }
        }
    }
    $metadata['sizes']['share']['filesize'] = $size;
    return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'tiagsspace_cap_share_image_bytes', 20, 2 );

/**
 * Generate (or refresh) the `share` size for one attachment without touching
 * the other sizes, then apply the byte cap. Used by the WP-CLI command below
 * for the existing library; new uploads go through the normal metadata path.
 *
 * @return string 'ok' | 'skipped' | 'small' | 'missing-file' | 'error'
 */
function tiagsspace_generate_share_image( $attachment_id, $force = false ) {
    if ( ! wp_attachment_is_image( $attachment_id ) ) {
        return 'skipped';
    }
    $file = get_attached_file( $attachment_id );
    if ( ! $file || ! file_exists( $file ) ) {
        return 'missing-file';
    }
    $metadata = wp_get_attachment_metadata( $attachment_id );
    if ( ! is_array( $metadata ) ) {
        $metadata = array();
    }
    if ( ! $force && ! empty( $metadata['sizes']['share']['file'] )
        && file_exists( dirname( $file ) . '/' . $metadata['sizes']['share']['file'] ) ) {
        return 'skipped';
    }
    $editor = wp_get_image_editor( $file );
    if ( is_wp_error( $editor ) ) {
        return 'error';
    }
    $dims = $editor->get_size();
    if ( ! empty( $dims['width'] ) && $dims['width'] <= 1200 && $dims['height'] <= 1200 ) {
        return 'small'; // WordPress never upscales: no share size for originals inside the 1200 box (medium/full is used instead)
    }
    $resized = $editor->resize( 1200, 1200, false );
    if ( is_wp_error( $resized ) ) {
        return 'error';
    }
    $saved = $editor->save();
    if ( is_wp_error( $saved ) || empty( $saved['file'] ) ) {
        return 'error';
    }
    unset( $saved['path'] );
    $metadata['sizes']['share'] = $saved;
    $metadata = tiagsspace_cap_share_image_bytes( $metadata, $attachment_id );
    wp_update_attachment_metadata( $attachment_id, $metadata );
    return 'ok';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    /**
     * Build the `share` image size for the existing library.
     *
     * ## OPTIONS
     * [<id>...]   Attachment IDs. Default: every image attachment.
     * [--force]   Rebuild even when a share file already exists.
     *
     * ## EXAMPLES
     *     wp tiagsspace share-images
     *     wp tiagsspace share-images 39345 --force
     */
    WP_CLI::add_command( 'tiagsspace share-images', function ( $args, $assoc ) {
        // Something in the stack re-arms a 300 s execution limit under wp-cli; the
        // whole library takes hours, so lift it here and again on every iteration.
        ignore_user_abort( true );
        $force = ! empty( $assoc['force'] );
        $ids   = array_map( 'intval', $args );
        if ( empty( $ids ) ) {
            $ids = get_posts( array(
                'post_type'      => 'attachment',
                'post_status'    => 'any',
                'post_mime_type' => 'image',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'DESC',
            ) );
        }
        $tally = array();
        $total = count( $ids );
        foreach ( $ids as $i => $id ) {
            set_time_limit( 0 );
            $result = tiagsspace_generate_share_image( $id, $force );
            $tally[ $result ] = isset( $tally[ $result ] ) ? $tally[ $result ] + 1 : 1;
            if ( ! in_array( $result, array( 'ok', 'skipped', 'small' ), true ) ) {
                WP_CLI::warning( "$id: $result" );
            }
            if ( ( $i + 1 ) % 200 === 0 ) {
                WP_CLI::log( sprintf( '%d / %d', $i + 1, $total ) );
            }
        }
        WP_CLI::success( 'share-images: ' . json_encode( $tally ) );
    } );
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
 * Image array for the Yoast container: the `share` size when it exists,
 * otherwise `medium` (older uploads until `wp tiagsspace share-images` has run).
 */
function tiagsspace_share_image_array( $attachment_id ) {
    foreach ( array( 'share', 'medium' ) as $size ) {
        $src = wp_get_attachment_image_src( $attachment_id, $size );
        if ( ! $src || empty( $src[3] ) ) {
            continue; // $src[3] is false when WordPress fell back to the full-size original
        }
        $meta = wp_get_attachment_metadata( $attachment_id );
        return array(
            'url'    => $src[0],
            'width'  => (int) $src[1],
            'height' => (int) $src[2],
            'type'   => isset( $meta['sizes'][ $size ]['mime-type'] ) ? $meta['sizes'][ $size ]['mime-type'] : get_post_mime_type( $attachment_id ),
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
