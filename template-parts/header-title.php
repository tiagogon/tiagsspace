<?php
/*
Header >> Left title (conditional per page type)
Outputs breadcrumb-style <h1> with #over-text span for JS hover label.
Each condition overrides the previous — last match wins.
*/

$siteName = "Space";
// Responsive site name: "S" on mobile, full name on ≥sm
$Webpage_name = '<span class="d-inline d-sm-none">S</span><span class="d-none d-sm-inline">'.$siteName.'</span>';
$over_text = '<span id="over-text" class="d-none d-sm-inline"></span>';

// Default: homepage
$header_left_title = '<h1><a href="'.home_url().'">'.$siteName.'</a>'.$over_text.'</h1>';

// ----- Singular pages -----
if (is_singular()) {
	$header_left_title = '<h1><a href="'.home_url().'">S</a>'.$over_text.'</h1>';
}
if (is_page('index')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Index'.$over_text.'</h1>';
}

// ----- CPT archives -----
if (is_post_type_archive('hyper')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Hyper'.$over_text.'</h1>';
}
if (is_post_type_archive('4k-lento')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / 4K Lento'.$over_text.'</h1>';
}
if (is_post_type_archive('films')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Film'.$over_text.'</h1>';
}
if (is_post_type_archive('dusk')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Dusk'.$over_text.'</h1>';
}
if (is_post_type_archive('cityburns')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / City'.$over_text.'</h1>';
}
if (is_post_type_archive('log')) {
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Log</h1>';
}

// ----- Taxonomies -----
if (is_tax('log-branch')) {
	$term = $wp_query->queried_object;
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / <a href="'.get_post_type_archive_link('log').'">Log</a> / '.$term->name.'</h1>';
}
if (is_tax('medium')) {
	$term = $wp_query->queried_object;
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Medium / '.$term->name.$over_text.'</h1>';
}
if (is_tax('from')) {
	$term = $wp_query->queried_object;
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Dating  / '.$term->name.$over_text.'</h1>';
}
if (is_tax('places')) {
	$term = $wp_query->queried_object;
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Place / '.$term->name.$over_text.'</h1>';
}
if (is_tag()) {
	$tag = get_queried_object();
	$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Tag / '.$tag->name.'</h1>';
}

echo $header_left_title; ?>
