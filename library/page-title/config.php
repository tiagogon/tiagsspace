<?php
/**
 * Page title — the words.
 *
 * Everything the page-title module needs to know about this theme's content
 * lives here, as data. Adding a post type or a taxonomy is one line in this
 * file; the builders in crumbs.php read it and never spell a name themselves.
 * The words of a series live in library/template-functions.php: what the page
 * shows (menu, header, hover labels) comes from tiagsspace_series_ui_name(),
 * plain words ("Dusk", "Hyper", "Cidade", "4K Lento Mixes"); what search engines
 * read (<title>, share cards) from tiagsspace_series_name() ("Dusk Series").
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
    // post type gets one, spelled by tiagsspace_series_ui_name( $type, 'single' )
    // (which falls back to the post type's label, so plain posts read "Posts").
    // Films are not an artistic series; a page belongs to no series.
    'no_type_crumb' => array( 'films', 'page' ),

    // The short form of a series name: the part that is always shown; the rest
    // of the name ("Mixes", "Mix") only from the responsive breakpoint up. Where the
    // UI name is the short form itself (Dusk, Hyper, Cidade, Campo) nothing is added.
    'short' => array(
        'dusk'      => 'Dusk',
        'hyper'     => 'Hyper',
        'cidade'    => 'Cidade',
        'campo'     => 'Campo',
        '4k-lento'  => '4K Lento',
    ),

    // Post types whose hover label never shortens. Empty since Nov 2026: it held
    // the City Series ("City" alone read oddly); "Cidade" needs no suffix.
    'label_always_full' => array(),

    // The header word of a series archive, when it differs from
    // tiagsspace_series_ui_name( $type, 'archive' ).
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
