<?php
/*
Header >> Left title (conditional per page type)
Outputs the breadcrumb-style site title with #over-text span for JS hover label.
It is the page's <h1> on the front page and on archives (where "Tiags' Space / Log" IS the page
heading); on singular views, search and 404 it is a <p class="site-title"> so the work's own title
(entry-body.php) or the search heading stays the single <h1>. Each condition overrides the
previous — last match wins.
*/

// The Index page is a table with no heading of its own, so there the header
// ("Tiags' Space / Index") is the page's <h1>, as on the archives.
$site_title_is_h1 = ( ! is_singular() || is_page( 'index' ) ) && ! is_search() && ! is_404();
$t_open  = $site_title_is_h1 ? '<h1 class="site-title">' : '<p class="site-title">';
$t_close = $site_title_is_h1 ? '</h1>' : '</p>';

$siteName = "Tiags' Space";
// Responsive site name: "S" on mobile, full name on ≥sm
$Webpage_name = '<span class="d-inline d-sm-none">S</span><span class="d-none d-sm-inline">'.$siteName.'</span>';
$over_text = '<span id="over-text" class="d-none d-sm-inline"></span>';

// Default: homepage
$header_left_title = $t_open.'<a href="'.home_url().'">'.$siteName.'</a>'.$over_text.$t_close;

// ----- Singular pages -----
if (is_singular()) {
	$header_left_title = $t_open.'<a href="'.home_url().'">S</a>'.$over_text.$t_close;
}
if (is_page('index')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Index'.$over_text.$t_close;
}

// ----- CPT archives -----
// Series names are spelled as in the page titles ("Dusk Series", "4K Lento Mixes"),
// see tiagsspace_series_name(). Films keeps its header word "Film".
// On phones the header is one line wide enough for "S / 4K Lento" only, so the
// added word ("Series", "Mixes") shows from the sm breakpoint up, exactly like the
// site name does. It stays in the markup either way.
$series_header = function ( $post_type ) {
	return tiagsspace_series_name_html( $post_type, 'archive', 'sm' );
};
if (is_post_type_archive('hyper')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / '.$series_header( 'hyper' ).$over_text.$t_close;
}
if (is_post_type_archive('4k-lento')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / '.$series_header( '4k-lento' ).$over_text.$t_close;
}
if (is_post_type_archive('films')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Film'.$over_text.$t_close;
}
if (is_post_type_archive('dusk')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / '.$series_header( 'dusk' ).$over_text.$t_close;
}
if (is_post_type_archive('cityburns')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / '.$series_header( 'cityburns' ).$over_text.$t_close;
}
if (is_post_type_archive('log')) {
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Log'.$t_close;
}

// ----- Taxonomies -----
if (is_tax('log-branch')) {
	$term = get_queried_object();
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / <a href="'.get_post_type_archive_link('log').'">Log</a> / '.$term->name.$t_close;
}
if (is_tax('medium')) {
	$term = get_queried_object();
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Medium / '.$term->name.$over_text.$t_close;
}
if (is_tax('from')) {
	$term = get_queried_object();
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Dating / '.$term->name.$over_text.$t_close;
}
if (is_tax('places')) {
	$term = get_queried_object();
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Place / '.$term->name.$over_text.$t_close;
}
if (is_tag()) {
	$tag = get_queried_object();
	$header_left_title = $t_open.'<a href="'.home_url().'">'.$Webpage_name.'</a> / Tag / '.$tag->name.$t_close;
}

echo $header_left_title; ?>
