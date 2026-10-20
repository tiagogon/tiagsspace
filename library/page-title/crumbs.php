<?php
/**
 * Page title — builders.
 *
 * A page title is a list of crumbs read left to right: "Tiags' Space / Dusk
 * Series / Them". These functions build that list, as data, for one work or
 * for a query. They write no HTML (render.php does) and read no globals: give
 * them a post id or a WP_Query, and they return arrays you can test.
 *
 * A crumb is a plain array made only through tiagsspace_crumb(), with exactly
 * four keys:
 *   label    string   the words, plain text, unescaped          (required)
 *   short    string   the form shown below the responsive breakpoint, or ''
 *   url      string   where the crumb links, or ''
 *   heading  bool     true on the one crumb that is the page's heading (the work's title)
 *
 * @see README.md for worked examples.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tiagsspace_page_title_config' ) ) {
    /**
     * The module's configuration (config.php), read once.
     *
     * @param string $key  One key of the config, or '' for the whole array.
     * @return mixed
     */
    function tiagsspace_page_title_config( $key = '' ) {
        static $config = null;
        if ( null === $config ) {
            $config = require __DIR__ . '/config.php';
        }
        if ( '' === $key ) {
            return $config;
        }
        return isset( $config[ $key ] ) ? $config[ $key ] : null;
    }
}

if ( ! function_exists( 'tiagsspace_crumb' ) ) {
    /**
     * Make one crumb.
     *
     * Example: tiagsspace_crumb( 'Dusk Series', array( 'short' => 'Dusk', 'url' => 'https://…/dusk/' ) )
     *
     * @param string $label  Plain text.
     * @param array  $args   Optional 'short', 'url', 'heading'.
     * @return array
     */
    function tiagsspace_crumb( $label, $args = array() ) {
        return array(
            'label'   => (string) $label,
            'short'   => isset( $args['short'] ) ? (string) $args['short'] : '',
            'url'     => ! empty( $args['url'] ) ? (string) $args['url'] : '',
            'heading' => ! empty( $args['heading'] ),
        );
    }
}

if ( ! function_exists( 'tiagsspace_site_crumb' ) ) {
    /**
     * The first crumb of every page: the site, linked home.
     *
     * @return array
     */
    function tiagsspace_site_crumb() {
        $site = tiagsspace_page_title_config( 'site' );
        return tiagsspace_crumb( $site['label'], array( 'short' => $site['short'], 'url' => home_url() ) );
    }
}

if ( ! function_exists( 'tiagsspace_entry_crumbs' ) ) {
    /**
     * The crumbs that name one work: its series, its Log branches, its title.
     *
     * Examples (ids from the local site):
     *   tiagsspace_entry_crumbs( 35362 )   → [ Dusk Series → /dusk/, Them* ]
     *   tiagsspace_entry_crumbs( 36413 )   → [ Log → /log/, Blwww → /log-branch/blwww/, Thunder* ]
     *   tiagsspace_entry_crumbs( 30179 )   → [ 32 Sec Brush* ]                       (a film: no series)
     *   tiagsspace_entry_crumbs( 36413, array( 'omit' => array( 'type' ) ) )
     *                                      → [ Blwww → …, Thunder* ]                  (a Log listing)
     *   (* = the heading crumb)
     *
     * @param int   $post_id
     * @param array $args {
     *     @type string[] $omit        Crumbs a listing leaves out because its page header
     *                                 already says them: 'type' (the series or Log), 'branch'.
     *     @type bool     $link_title  Link the title crumb to the post (listings do).
     * }
     * @return array[]  Crumbs; empty if the post does not exist.
     */
    function tiagsspace_entry_crumbs( $post_id, $args = array() ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }
        $omit       = isset( $args['omit'] ) ? (array) $args['omit'] : array();
        $link_title = ! empty( $args['link_title'] );
        $config     = tiagsspace_page_title_config();
        $post_type  = $post->post_type;
        $crumbs     = array();

        // The series, or the Log, the work belongs to.
        if ( ! in_array( 'type', $omit, true ) && ! in_array( $post_type, $config['no_type_crumb'], true ) ) {
            $short    = isset( $config['short'][ $post_type ] ) ? $config['short'][ $post_type ] : '';
            $crumbs[] = tiagsspace_crumb( tiagsspace_series_name( $post_type, 'single' ), array(
                'short' => $short,
                'url'   => get_post_type_archive_link( $post_type ),
            ) );
        }

        // The Log branches (any post type may carry them, as today).
        if ( ! in_array( 'branch', $omit, true ) ) {
            $branches = get_the_terms( $post->ID, $config['branch_taxonomy'] );
            if ( $branches && ! is_wp_error( $branches ) ) {
                foreach ( $branches as $term ) {
                    $link     = get_term_link( $term );
                    $crumbs[] = tiagsspace_crumb( $term->name, array( 'url' => is_wp_error( $link ) ? '' : $link ) );
                }
            }
        }

        // The work itself.
        $crumbs[] = tiagsspace_crumb( get_the_title( $post ), array(
            'heading' => true,
            'url'     => $link_title ? get_permalink( $post ) : '',
        ) );

        return $crumbs;
    }
}

if ( ! function_exists( 'tiagsspace_page_crumbs' ) ) {
    /**
     * The crumbs of the page a query shows.
     *
     *   home, search, 404     → [ Tiags' Space ]
     *   the Index page        → [ Tiags' Space, Index ]
     *   a series archive      → [ Tiags' Space, Dusk Series ]          (films: "Film", see config)
     *   the Log archive       → [ Tiags' Space, Log ]
     *   a Log branch          → [ Tiags' Space, Log → /log/, Blwww ]
     *   a taxonomy term       → [ Tiags' Space, Place, Berlin ]
     *   a single work         → [ Tiags' Space ] + tiagsspace_entry_crumbs()
     *
     * On every line but a single work the whole line is the page's heading; on a
     * single work only the title crumb is (its 'heading' flag).
     *
     * Filter 'tiagsspace_page_crumbs' receives the list and the query, for
     * anything config.php cannot express.
     *
     * @param WP_Query|null $query  Defaults to the main query.
     * @return array[]
     */
    function tiagsspace_page_crumbs( $query = null ) {
        if ( ! $query instanceof WP_Query ) {
            $query = $GLOBALS['wp_query'];
        }
        $config = tiagsspace_page_title_config();
        $crumbs = array( tiagsspace_site_crumb() );

        if ( $query->is_page( $config['index_page'] ) ) {
            $crumbs[] = tiagsspace_crumb( get_the_title( $query->get_queried_object_id() ) );

        } elseif ( $query->is_singular() ) {
            $crumbs = array_merge( $crumbs, tiagsspace_entry_crumbs( $query->get_queried_object_id() ) );

        } elseif ( $query->is_post_type_archive() ) {
            $object    = $query->get_queried_object();
            $post_type = $object instanceof WP_Post_Type ? $object->name : (string) $query->get( 'post_type' );
            $word      = isset( $config['archive_word'][ $post_type ] )
                ? $config['archive_word'][ $post_type ]
                : tiagsspace_series_name( $post_type, 'archive' );
            $short     = isset( $config['short'][ $post_type ] ) ? $config['short'][ $post_type ] : '';
            $crumbs[]  = tiagsspace_crumb( $word, array( 'short' => $short ) );

        } elseif ( $query->is_tax() || $query->is_tag() ) {
            $term = $query->get_queried_object();
            if ( $term instanceof WP_Term ) {
                if ( $term->taxonomy === $config['branch_taxonomy'] ) {
                    $crumbs[] = tiagsspace_crumb( tiagsspace_series_name( $config['branch_post_type'], 'archive' ), array(
                        'url' => get_post_type_archive_link( $config['branch_post_type'] ),
                    ) );
                    $crumbs[] = tiagsspace_crumb( $term->name );
                } elseif ( isset( $config['taxonomies'][ $term->taxonomy ] ) ) {
                    $crumbs[] = tiagsspace_crumb( $config['taxonomies'][ $term->taxonomy ] );
                    $crumbs[] = tiagsspace_crumb( $term->name );
                }
            }
        }
        // Home, search, 404 and anything unmatched: the site alone.

        return apply_filters( 'tiagsspace_page_crumbs', $crumbs, $query );
    }
}
