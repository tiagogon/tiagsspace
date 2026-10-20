<?php
/*
Header >> Left title (conditional per page type)
The fixed top-left line that says where the visitor is: "Tiags' Space / Dusk Series".
Built by library/page-title/ (tiagsspace_page_crumbs() → tiagsspace_render_crumbs()).

It is the page's <h1> on the front page, on archives and on the Index page (where
"Tiags' Space / Log" IS the page heading); on search and 404 it is a <p> so the body
heading stays the single <h1>. The #over-text span is the hover label's target; a Log
listing has no thumbnail grid, so no span.
*/

if ( is_singular() && ! is_page( 'index' ) ) {
	// Singles keep the short "S" for now; the work's own title in entry-body.php is the <h1>.
	echo '<p class="site-title"><a href="' . home_url() . '">S</a><span id="over-text" class="d-none d-sm-inline"></span></p>';
	return;
}

$crumbs = tiagsspace_page_crumbs();

echo tiagsspace_render_crumbs( $crumbs, array(
	'heading'    => ( is_search() || is_404() ) ? 'none' : 'h1',
	'responsive' => count( $crumbs ) > 1 ? 'sm' : false,   // the site's name alone never shortens
	'over_text'  => ! ( is_post_type_archive( 'log' ) || is_tax( 'log-branch' ) ),
) );
