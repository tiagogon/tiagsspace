<?php
/*
Header >> Left title (conditional per page type)
The fixed top-left line that says where the visitor is: "Tiags' Space / Dusk Series"
on an archive, "Tiags' Space / Dusk Series / Them" on a single work.
Built by library/page-title/ (tiagsspace_page_crumbs() → tiagsspace_render_crumbs()).

It is the page's <h1> on the front page, on archives and on the Index page (where
"Tiags' Space / Log" IS the page heading). On a single work only the title crumb is
the <h1>, inline in the line; everything after the site crumb sits in one span
(.site-title-entry) that header-helpers.js swaps for the hovered related set's label.
On search and 404 the line is a <p> so the body heading stays the single <h1>.
The #over-text span is the hover label's target; a Log listing has no thumbnail
grid, so no span.
*/

$crumbs = tiagsspace_page_crumbs();

echo tiagsspace_render_crumbs( $crumbs, array(
	'heading'    => ( is_search() || is_404() ) ? 'none' : 'h1',
	'responsive' => count( $crumbs ) > 1 ? 'sm' : false,   // the site's name alone never shortens
	'over_text'  => ! ( is_post_type_archive( 'log' ) || is_tax( 'log-branch' ) ),
	'group_from' => 1,
) );
