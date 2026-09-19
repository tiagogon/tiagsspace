<?php
/**
 * Yoast schema pieces.
 *
 * - Person: alternateName "Tiags" (the site represents a person; settings in Yoast).
 * - Films: a VideoObject piece for self-hosted films, referenced from the WebPage,
 *   so films are eligible for video results. Gallery/[KGVID] videos are not covered.
 * - WebPage: inLanguage follows the per-set language (see seo-and-feed.php).
 *
 * @package tiagsspace
 */

add_filter( 'wpseo_schema_person', function ( $data ) {
    if ( is_array( $data ) && empty( $data['alternateName'] ) ) {
        $data['alternateName'] = 'Tiags';
    }
    return $data;
} );

/** ISO 8601 duration (PT14M03S) from seconds. */
function tiagsspace_iso8601_duration( $seconds ) {
    $seconds = (int) $seconds;
    if ( $seconds <= 0 ) {
        return '';
    }
    $h = intdiv( $seconds, 3600 );
    $m = intdiv( $seconds % 3600, 60 );
    $s = $seconds % 60;
    return 'PT' . ( $h ? $h . 'H' : '' ) . ( $m ? $m . 'M' : '' ) . ( $s || ( ! $h && ! $m ) ? $s . 'S' : '' );
}

/** True on a single film that has a self-hosted video attachment. */
function tiagsspace_is_film_with_video( $post_id ) {
    return get_post_type( $post_id ) === 'films'
        && function_exists( 'tiagsspace_film_attachment_id' )
        && tiagsspace_film_attachment_id( $post_id ) > 0;
}

if ( class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' ) ) {

    class Tiagsspace_Film_Video_Schema extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {

        public $identifier = 'tiagsspace-film-video';

        public function __construct( $context ) {
            $this->context = $context;
        }

        public function is_needed() {
            return is_singular( 'films' )
                && ! empty( $this->context->indexable->object_id )
                && tiagsspace_is_film_with_video( (int) $this->context->indexable->object_id );
        }

        public function generate() {
            $post_id       = (int) $this->context->indexable->object_id;
            $attachment_id = tiagsspace_film_attachment_id( $post_id );
            $canonical     = $this->context->canonical;

            $data = array(
                '@type'    => 'VideoObject',
                '@id'      => $canonical . '#video',
                'name'     => get_the_title( $post_id ),
                'url'      => $canonical,
                'isPartOf' => array( '@id' => $canonical ),
                'embedUrl' => $canonical,
            );

            $description = ! empty( $this->context->description ) ? $this->context->description : '';
            if ( ! $description && function_exists( 'tiagsspace_seo_label' ) ) {
                $description = tiagsspace_seo_label( $post_id );
            }
            if ( $description ) {
                $data['description'] = wp_strip_all_tags( $description );
            }

            $thumb_id = (int) get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $thumbs = array();
                foreach ( array( 'xlarge', 'share' ) as $size ) {
                    $src = wp_get_attachment_image_src( $thumb_id, $size );
                    if ( $src && ! empty( $src[0] ) ) {
                        $thumbs[] = $src[0];
                    }
                }
                if ( $thumbs ) {
                    $data['thumbnailUrl'] = array_values( array_unique( $thumbs ) );
                }
            }

            $post = get_post( $post_id );
            if ( $post ) {
                $data['uploadDate'] = mysql2date( 'c', $post->post_date_gmt ?: $post->post_date, false );
            }

            if ( function_exists( 'tiagsspace_film_duration' ) ) {
                $iso = tiagsspace_iso8601_duration( tiagsspace_film_duration( $post_id ) );
                if ( $iso ) {
                    $data['duration'] = $iso;
                }
            }

            $content_url = wp_get_attachment_url( $attachment_id );
            if ( $content_url ) {
                $data['contentUrl'] = $content_url;
            }

            $lang = function_exists( 'tiagsspace_set_language' ) ? tiagsspace_set_language( $post_id ) : 'en';
            $data['inLanguage'] = ( $lang === 'pt' ) ? 'pt-PT' : 'en-US';

            return $data;
        }
    }

    add_filter( 'wpseo_schema_graph_pieces', function ( $pieces, $context ) {
        $pieces[] = new Tiagsspace_Film_Video_Schema( $context );
        return $pieces;
    }, 11, 2 );
}

add_filter( 'wpseo_schema_webpage', function ( $data ) {
    if ( ! is_singular() || ! is_array( $data ) ) {
        return $data;
    }
    $post_id = get_queried_object_id();
    if ( function_exists( 'tiagsspace_set_language' ) && tiagsspace_set_language( $post_id ) === 'pt' ) {
        $data['inLanguage'] = 'pt-PT';
    }
    if ( tiagsspace_is_film_with_video( $post_id ) && ! empty( $data['@id'] ) ) {
        $data['video'] = array( '@id' => $data['@id'] . '#video' );
    }
    return $data;
} );
