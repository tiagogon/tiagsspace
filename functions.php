<?php
/*
Author: Eddie Machado
URL: htp://themble.com/bones/

This is where you can drop your custom functions or
just edit things like thumbnail sizes, header images,
sidebars, comments, ect.
*/

// Theme helper functions
require_once('library/helpers.php');          // WP cleanup, pagination, content filters

// Custom Video Player Functions
require_once('library/plyr-player.php');      // Plyr video player helper

// Admin Functions
require_once('library/admin.php');              // custom admin functions

// Theme modules
require_once('library/custom-post-types.php');  // CPT registrations, main loop/feed/archive inclusion
require_once('library/custom-taxonomies.php');  // Taxonomy registrations, admin filters
require_once('library/template-functions.php'); // Template helpers (taxonomy lists, colors, stats)
require_once('library/seo-and-feed.php');       // Yoast OG/Twitter hooks, RSS feed customization
require_once('library/gallery-functions.php');  // Gallery admin UI, AJAX handlers
require_once('library/query-filters.php');      // Hide posts from archives, cache purge
require_once('library/acf-fields.php');         // ACF field groups (registered via PHP)

// Remove icon and number of coments on the top-nav-bar
add_action('wp_before_admin_bar_render', function() { global $wp_admin_bar; $wp_admin_bar->remove_menu('comments'); });

// Custom Backend Footer
add_filter('admin_footer_text', 'wp_bootstrap_custom_admin_footer');
function wp_bootstrap_custom_admin_footer() {
	echo '<span id="footer-thankyou">Follow your vision!</span>';
}

// adding it to the admin area
add_filter('admin_footer_text', 'wp_bootstrap_custom_admin_footer');

// Let WordPress handle the document <title> tag in a modern way
add_theme_support('title-tag');

// Enqueue CSS and Scripts
function tiagsspace_enqueue_assets() {
    $template_dir = get_template_directory_uri();

    // ----- CSS ----- (order matters)
    wp_enqueue_style('plyr', $template_dir . '/library/js/plyr/plyr.css', [], null);
    wp_enqueue_style('swiper', $template_dir . '/library/js/swiper/swiper-bundle.min.css', [], null);
    wp_enqueue_style('theme-style', $template_dir . '/library/styles/main.min.css', [], null);

    // ----- jQuery -----
    // Use local jQuery to match existing theme expectations
    wp_deregister_script('jquery');
    wp_register_script('jquery', $template_dir . '/library/js/jquery/jquery.min.js', [], null, false); // header
    wp_enqueue_script('jquery');

    // ----- JS -----
    // Modernizr (must be in header)
    wp_enqueue_script('modernizr', $template_dir . '/library/js/modernizr/modernizr.min.js', [], null, false);

    // Picturefill (polyfill) for legacy browsers that need it
    wp_enqueue_script('picturefill', $template_dir . '/library/js/picturefill/picturefill.min.js', [], null, true);
    // HTML5 shiv for <picture> element creation (before polyfill execution)
    wp_add_inline_script('picturefill', 'document.createElement("picture");', 'before');

        // Classie removed: no longer needed; use classList/jQuery

        // Header helpers to replace inline scripts
        wp_enqueue_script('header-helpers', $template_dir . '/library/js/header-helpers.js', array('jquery'), null, true);
    // Plyr
    wp_enqueue_script('plyr', $template_dir . '/library/js/plyr/plyr.js', [], null, true);

    // Bootstrap
    wp_enqueue_script('bootstrap', $template_dir . '/library/js/bootstrap.min.js', ['jquery'], null, true);

    // ImagesLoaded (ensures Masonry runs after images are loaded)
    wp_enqueue_script('imagesloaded');
    // Theme's Masonry package (load in header so inline init in template works)
    wp_enqueue_script('masonry-pkgd', $template_dir . '/library/js/masonry/masonry.pkgd.min.js', ['jquery', 'imagesloaded'], null, false);

}

// Theme supports
add_action('after_setup_theme', function(){
    add_theme_support('title-tag');
    add_theme_support('editor-styles');
    add_editor_style('library/styles/editor-style.css');
});
add_action('wp_enqueue_scripts', 'tiagsspace_enqueue_assets');

// Helper: render responsive image with full srcset so browser can pick optimal size
// Uses <img> with srcset/sizes instead of <picture> for better resolution selection
function tiagsspace_render_picture_from_attachment($attachment_id, $sizes_map, $fallback_sizes_attr, $img_class = '', $img_alt = '') {
    // Build a comprehensive srcset with all available image sizes
    $srcset_parts = array();
    $fallback_src = ''; // Will be set to smallest available size
    
    // Add all registered sizes to srcset (thumbnail, small, medium, large)
    $all_sizes = array('thumbnail', 'small', 'medium', 'large');
    foreach ($all_sizes as $size) {
        $attrs = wp_get_attachment_image_src($attachment_id, $size);
        if ($attrs && !empty($attrs[0]) && !empty($attrs[1])) {
            $srcset_parts[] = esc_url($attrs[0]) . ' ' . intval($attrs[1]) . 'w';
            // Set fallback_src to first available (smallest) size to avoid downloading full-size in Firefox
            if (empty($fallback_src)) {
                $fallback_src = esc_url($attrs[0]);
            }
        }
    }
    
    // Add full-size image as final option in srcset
    $full = wp_get_attachment_image_src($attachment_id, false);
    if ($full && !empty($full[0]) && !empty($full[1])) {
        $srcset_parts[] = esc_url($full[0]) . ' ' . intval($full[1]) . 'w';
        // Only use full as fallback if no other sizes exist
        if (empty($fallback_src)) {
            $fallback_src = esc_url($full[0]);
        }
    }
    
    // Combine all srcset entries
    $srcset_attr = implode(', ', $srcset_parts);
    
    // Build alt attribute
    $alt = $img_alt ? esc_attr($img_alt) : esc_attr(get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
    $class_attr = $img_class ? ' class="' . esc_attr($img_class) . '"' : '';
    
    // Return <img> with smallest size as src (fallback) and full srcset - browser will choose optimal image
    return '<img src="' . $fallback_src . '" srcset="' . $srcset_attr . '" sizes="' . esc_attr($fallback_sizes_attr) . '"' . $class_attr . ' alt="' . $alt . '">';
}


/************* THUMBNAIL SIZE OPTIONS *************/
add_theme_support( 'post-thumbnails' );

// HD/6
add_image_size( 'thumbnail', 480, 960, false ); // update also on /wp-admin/options-media.php

// container on tablets
add_image_size( 'small', 720, 1440, false );

// container small desktops
add_image_size( 'medium', 940, 1880, false ); // update also on /wp-admin/options-media.php

// container large desktop size
add_image_size( 'large', 1200, 2400, false ); // update also on /wp-admin/options-media.php

// Disable Wordpress -scaled version created on 5.3 https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
add_filter( 'big_image_size_threshold', '__return_false' );



// Allow wordpress to upload 3D file formats as atthacmenets - Source Chat GTP
function custom_upload_mimes($existing_mimes) {
    // Add GLTF/GLB and OBJ to the list of allowed file types
    $existing_mimes['gltf'] = 'model/gltf';
    $existing_mimes['glb'] = 'model/gltf-binary';
    $existing_mimes['obj'] = 'application/octet-stream';
    return $existing_mimes;
}
add_filter('upload_mimes', 'custom_upload_mimes');

/************* SEARCH FORM LAYOUT *****************/

/****************** password protected post form *****/

add_filter( 'the_password_form', 'custom_password_form' );

function custom_password_form() {
	global $post;
	$label = 'pwbox-'.( empty( $post->ID ) ? rand() : $post->ID );
	$o = '<div class="clearfix"><form class="protected-post-form" action="' . get_option('siteurl') . '/wp-login.php?action=postpass" method="post">
	' . '<p>' . __( "This post is password protected. To view it please enter your password below:" ,'wpbootstrap') . '</p>' . '
	<label for="' . $label . '">' . __( "Password:" ,'wpbootstrap') . ' </label><div class="input-append"><input name="post_password" id="' . $label . '" type="password" size="20" /><input type="submit" name="Submit" class="btn btn-primary" value="' . esc_attr__( "Submit",'wpbootstrap' ) . '" /></div>
	</form></div>
	';
	return $o;
}


/************* YARPP Style Dequeue *************/

add_action('wp_print_styles','lm_dequeue_header_styles');
function lm_dequeue_header_styles()
{
  wp_dequeue_style('yarppWidgetCss');
}

add_action('get_footer','lm_dequeue_footer_styles');
function lm_dequeue_footer_styles()
{
  wp_dequeue_style('yarppRelatedCss');
}




/************* Favicon *************/

function add_my_favicon() {
   $favicon_path = get_template_directory_uri() . '/favicon.ico';

   echo '   <link rel="apple-touch-icon" sizes="57x57" href="'.$favicon_path.'/apple-icon-57x57.png">
            <link rel="apple-touch-icon" sizes="60x60" href="'.$favicon_path.'/apple-icon-60x60.png">
            <link rel="apple-touch-icon" sizes="72x72" href="'.$favicon_path.'/apple-icon-72x72.png">
            <link rel="apple-touch-icon" sizes="76x76" href="'.$favicon_path.'/apple-icon-76x76.png">
            <link rel="apple-touch-icon" sizes="114x114" href="'.$favicon_path.'/apple-icon-114x114.png">
            <link rel="apple-touch-icon" sizes="120x120" href="'.$favicon_path.'/apple-icon-120x120.png">
            <link rel="apple-touch-icon" sizes="144x144" href="'.$favicon_path.'/apple-icon-144x144.png">
            <link rel="apple-touch-icon" sizes="152x152" href="'.$favicon_path.'/apple-icon-152x152.png">
            <link rel="apple-touch-icon" sizes="180x180" href="'.$favicon_path.'/apple-icon-180x180.png">
            <link rel="icon" type="image/png" sizes="192x192"  href="'.$favicon_path.'/android-icon-192x192.png">
            <link rel="icon" type="image/png" sizes="32x32" href="'.$favicon_path.'/favicon-32x32.png">
            <link rel="icon" type="image/png" sizes="96x96" href="'.$favicon_path.'/favicon-96x96.png">
            <link rel="icon" type="image/png" sizes="16x16" href="'.$favicon_path.'/favicon-16x16.png">
            <link rel="manifest" href="'.$favicon_path.'/manifest.json">
            <meta name="msapplication-TileImage" content="'.$favicon_path.'/ms-icon-144x144.png">';

}
function add_my_favicon_admin() {
   $favicon_path = get_template_directory_uri() . '/favicon.ico/adminarea';

   echo '   <link rel="apple-touch-icon" sizes="57x57" href="'.$favicon_path.'/apple-icon-57x57.png">
            <link rel="apple-touch-icon" sizes="60x60" href="'.$favicon_path.'/apple-icon-60x60.png">
            <link rel="apple-touch-icon" sizes="72x72" href="'.$favicon_path.'/apple-icon-72x72.png">
            <link rel="apple-touch-icon" sizes="76x76" href="'.$favicon_path.'/apple-icon-76x76.png">
            <link rel="apple-touch-icon" sizes="114x114" href="'.$favicon_path.'/apple-icon-114x114.png">
            <link rel="apple-touch-icon" sizes="120x120" href="'.$favicon_path.'/apple-icon-120x120.png">
            <link rel="apple-touch-icon" sizes="144x144" href="'.$favicon_path.'/apple-icon-144x144.png">
            <link rel="apple-touch-icon" sizes="152x152" href="'.$favicon_path.'/apple-icon-152x152.png">
            <link rel="apple-touch-icon" sizes="180x180" href="'.$favicon_path.'/apple-icon-180x180.png">
            <link rel="icon" type="image/png" sizes="192x192"  href="'.$favicon_path.'/android-icon-192x192.png">
            <link rel="icon" type="image/png" sizes="32x32" href="'.$favicon_path.'/favicon-32x32.png">
            <link rel="icon" type="image/png" sizes="96x96" href="'.$favicon_path.'/favicon-96x96.png">
            <link rel="icon" type="image/png" sizes="16x16" href="'.$favicon_path.'/favicon-16x16.png">
            <link rel="manifest" href="'.$favicon_path.'/manifest.json">
            <meta name="msapplication-TileColor" content="#3c00f5">
            <meta name="msapplication-TileImage" content="'.$favicon_path.'/ms-icon-144x144.png">
            <meta name="theme-color" content="#3c00f5">';
}
add_action( 'wp_head', 'add_my_favicon' ); //front end
add_action( 'admin_head', 'add_my_favicon_admin' ); //admin end



// Style Featured Images Size and style on Post lists
add_action('admin_head', 'my_custom_featured_image_css');
function my_custom_featured_image_css() {
  echo '<style>
    .featured-image.column-featured-image img {
        max-width: 60px;
        height: auto;
        vertical-align: top;
        max-height: none;
    }
  </style>';
}



// remove <p> from the archive description
function custom_archive_description($description) {

    $remove = array( '<p>', '</p>' );

    $description = str_replace( $remove, "", $description );

    return $description;
}
add_filter( 'get_the_archive_description', 'custom_archive_description' );

// remove "Archives:" from the archive title
function custom_archive_title($description) {

    $remove = array( 'Archives: ');

    $description = str_replace( $remove, "", $description );

    return $description;
}
add_filter( 'get_the_archive_title', 'custom_archive_title' );


/************* Misc Hooks *************/

// Change email content type: https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_mail_content_type
add_filter( 'wp_mail_content_type', 'set_content_type' );
function set_content_type( $content_type ) {
    return 'text/html';
}


// INFINITE SCROLL -- https://infinite-scroll.com/ -- add class to next post link taf
add_filter('next_posts_link_attributes', 'posts_link_attributes');

function posts_link_attributes() {
    return 'class="pagination__next view-more-button"';
}


// Used for log grouping posts
// ID of the next post on index
function index_next_post_type($current_post_id) {

    $args = array(
        'posts_per_page' => 1,
        'post_type' => array( 'post', 'dusk', 'films', 'hyper', 'log', 'cityburns'),
        'post_status' => array( 'publish' ),
    );
    $inner_query = new WP_Query( $args );


    // WP_Query is not Countable; use post_count for matched posts
    return (int) $inner_query->post_count;
}

// END -- don't add any space after php close ?>
