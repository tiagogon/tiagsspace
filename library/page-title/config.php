<?php
/**
 * Page title — the words.
 *
 * Everything the page-title module needs to know about this theme's content
 * lives here, as data. Adding a post type or a taxonomy is one line in this
 * file; the builders in crumbs.php read it and never spell a name themselves.
 * The long spelling of a series ("Dusk Series", "4K Lento Mix") stays in
 * tiagsspace_series_name() (library/template-functions.php), which is the
 * single source of those words for titles, share cards and the menu alike.
 *
 * @see README.md for the contract and worked examples.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(

    // The first crumb of every page. 'short' is the form shown below the
    // responsive breakpoint (a phone header holds "S / Dusk" and little more).
    'site' => array(
        'label' => "Tiags' Space",
        'short' => 'S',
    ),

    // Post types whose works get NO type crumb before the title. Every other
    // post type gets one, spelled by tiagsspace_series_name( $type, 'single' )
    // (which falls back to the post type's label, so plain posts read "Posts").
    // Films are not an artistic series; a page belongs to no series.
    'no_type_crumb' => array( 'films', 'page' ),

    // The short form of a series name: the part that is always shown; the rest
    // of the name ("Series", "Mix") only from the responsive breakpoint up.
    'short' => array(
        'dusk'      => 'Dusk',
        'hyper'     => 'Hyper',
        'cityburns' => 'City',
        '4k-lento'  => '4K Lento',
    ),

    // Post types whose hover label never shortens (City always reads "City Series").
    'label_always_full' => array( 'cityburns' ),

    // The header word of a series archive, when it differs from
    // tiagsspace_series_name( $type, 'archive' ).
    'archive_word' => array(
        'films' => 'Film',
    ),

    // The word before a term on a taxonomy archive: "Tiags' Space / Place / Berlin".
    'taxonomies' => array(
        'medium'   => 'Medium',
        'from'     => 'Dating',
        'places'   => 'Place',
        'post_tag' => 'Tag',
    ),

    // The Log's branches: a term archive reads "Tiags' Space / Log / Blwww" and a
    // Log entry "Log / Blwww / Thunder".
    'branch_taxonomy'  => 'log-branch',
    'branch_post_type' => 'log',

    // The page whose header is "Tiags' Space / Index".
    'index_page' => 'index',
);
