<?php
/*
Author: Eddie Machado
URL: htp://themble.com/bones/

This is where you can drop your custom functions or
just edit things like thumbnail sizes, header images,
sidebars, comments, ect.
*/

// Get Bones Core Up & Running!
require_once('library/bones.php');            // core functions (don't remove)

// Admin Functions (commented out by default)
// require_once('library/admin.php');         // custom admin functions

// Remove icon and number of coments on the top-nav-bar
add_action('wp_before_admin_bar_render', function() { global $wp_admin_bar; $wp_admin_bar->remove_menu('comments'); });

// Custom Backend Footer
add_filter('admin_footer_text', 'wp_bootstrap_custom_admin_footer');
function wp_bootstrap_custom_admin_footer() {
	echo '<span id="footer-thankyou">Follow your vision!</span>';
}

// adding it to the admin area
add_filter('admin_footer_text', 'wp_bootstrap_custom_admin_footer');




/************* THUMBNAIL SIZE OPTIONS *************/

// HD/6
add_image_size( 'thumbnail', 480, 960, false ); // update also on /wp-admin/options-media.php

// container on tablets
add_image_size( 'small', 720, 1440, false );

// container small desktops
add_image_size( 'medium', 940, 1880, false ); // update also on /wp-admin/options-media.php

// container large desktop size
add_image_size( 'large', 1200, 2400, false ); // update also on /wp-admin/options-media.php

// Full -- container size x2 Retina Mac displays
// ->2400px



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

// --- Bottraps STYLE ---
// enqueue styles
    // if( !function_exists("theme_styles") ) {
    //     function theme_styles() {
    //         // This is the compiled css file from LESS - this means you compile the LESS file locally and put it in the appropriate directory if you want to make any changes to the master bootstrap.css.
    //         wp_register_style( 'bootstrap', get_template_directory_uri() . '/library/css/bootstrap.css', array(), '1.0', 'all' );
    //         wp_enqueue_style( 'bootstrap' );

    //         // style.css -- For child themes -- deactivated by me
    //             // wp_register_style( 'wpbs-style', get_stylesheet_directory_uri() . '/style.css', array(), '1.0', 'all' );
    //             //wp_enqueue_style( 'wpbs-style' );
    //     }
    // }
    // add_action( 'wp_enqueue_scripts', 'theme_styles' );

// enqueue javascript
    // removed by me!
    // if( !function_exists( "theme_js" ) ) {
    //   function theme_js(){

    //     wp_register_script( 'bootstrap',
    //       get_template_directory_uri() . '/library/js/bootstrap.min.js',
    //       array('jquery'),
    //       '1.2' );

    //     wp_register_script( 'wpbs-scripts',
    //       get_template_directory_uri() . '/library/js/scripts.js',
    //       array('jquery'),
    //       '1.2' );

    //     wp_register_script(  'modernizr',
    //       get_template_directory_uri() . '/library/js/modernizr.full.min.js',
    //       array('jquery'),
    //       '1.2' );

    //     wp_enqueue_script('bootstrap');
    //     wp_enqueue_script('wpbs-scripts');
    //     wp_enqueue_script('modernizr');

    //   }
    // }
    // add_action( 'wp_enqueue_scripts', 'theme_js' );

// Get <head> <title> to behave like other themes
function wp_bootstrap_wp_title( $title, $sep ) {
  global $paged, $page;

  if ( is_feed() ) {
    return $title;
  }

  // Add the site name.
  $title .= get_bloginfo( 'name' );

  // Add the site description for the home/front page.
  $site_description = get_bloginfo( 'description', 'display' );
  if ( $site_description && ( is_home() || is_front_page() ) ) {
    $title = "$title $sep $site_description";
  }

  // Add a page number if necessary.
  if ( $paged >= 2 || $page >= 2 ) {
    $title = "$title $sep " . sprintf( __( 'Page %s', 'wpbootstrap' ), max( $paged, $page ) );
  }

  return $title;
}
add_filter( 'wp_title', 'wp_bootstrap_wp_title', 10, 2 );




/********************************************************************************************/
/************************************** EDITED BY ME ****************************************/
/********************************************************************************************/




// **********************************************
// custom posts types
// **********************************************


// Register Custom Post Type Film
function custom_post_type_films() {
  $labels = array(
    'name'                => _x( 'Films Section', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Film', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Films', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All Films', 'text_domain' ),
    'view_item'           => __( 'View Film', 'text_domain' ),
    'add_new_item'        => __( 'Add New Film', 'text_domain' ),
    'add_new'             => __( 'Add New Film', 'text_domain' ),
    'edit_item'           => __( 'Edit Film', 'text_domain' ),
    'update_item'         => __( 'Update Film', 'text_domain' ),
    'search_items'        => __( 'Search Film', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'films', 'text_domain' ),
    'description'         => __( 'Films in trouble', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-video-alt',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
    'yarpp_support'       => true,
  );
  register_post_type( 'films', $args );
}
// Hook into the 'init' action
add_action( 'init', 'custom_post_type_films', 0 );



// Register Custom Post Type EMULSION
function custom_post_type_emulsion() {
  $labels = array(
    'name'                => _x( 'Emulsion Series', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Emulsion', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Emulsion', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All Emulsion Posts', 'text_domain' ),
    'view_item'           => __( 'View Emulsion Post', 'text_domain' ),
    'add_new_item'        => __( 'Add New Emulsion Post', 'text_domain' ),
    'add_new'             => __( 'Add New Emulsion Post', 'text_domain' ),
    'edit_item'           => __( 'Edit Emulsion Post', 'text_domain' ),
    'update_item'         => __( 'Update Emulsion Post', 'text_domain' ),
    'search_items'        => __( 'Search Emulsion Post', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'emulsion', 'text_domain' ),
    'description'         => __( 'Suspension of solid particles', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-camera',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
    'yarpp_support'       => true,
  );
  register_post_type( 'emulsion', $args );
}
// Hook into the 'init' action
add_action( 'init', 'custom_post_type_emulsion', 0 );



// Register Custom Post Type DUSK
function custom_post_type_dusk() {
  $labels = array(
    'name'                => _x( 'Dusk Series', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Dusk', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Dusk', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All Dusk Posts', 'text_domain' ),
    'view_item'           => __( 'View Dusk Post', 'text_domain' ),
    'add_new_item'        => __( 'Add New Dusk Post', 'text_domain' ),
    'add_new'             => __( 'Add New Dusk Post', 'text_domain' ),
    'edit_item'           => __( 'Edit Dusk Post', 'text_domain' ),
    'update_item'         => __( 'Update Dusk Post', 'text_domain' ),
    'search_items'        => __( 'Search Dusk Post', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'dusk', 'text_domain' ),
    'description'         => __( 'The sun below the horizon.', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-layout',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
    'yarpp_support'       => true,
  );
  register_post_type( 'dusk', $args );
}
// Hook into the 'init' action
add_action( 'init', 'custom_post_type_dusk', 0 );


// Register Custom Post Type HYPER
function custom_post_type_hyper() {
  $labels = array(
    'name'                => _x( 'Hyper Series', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Hyper', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Hyper', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All Hyper Posts', 'text_domain' ),
    'view_item'           => __( 'View Hyper Post', 'text_domain' ),
    'add_new_item'        => __( 'Add New Hyper Post', 'text_domain' ),
    'add_new'             => __( 'Add New Hyper Post', 'text_domain' ),
    'edit_item'           => __( 'Edit Hyper Post', 'text_domain' ),
    'update_item'         => __( 'Update Hyper Post', 'text_domain' ),
    'search_items'        => __( 'Search Hyper Post', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'hyper', 'text_domain' ),
    'description'         => __( 'Hyper description.', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'post-formats', ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-smartphone',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
    'yarpp_support'       => true,
  );
  register_post_type( 'hyper', $args );
}
// Hook into the 'init' action
add_action( 'init', 'custom_post_type_hyper', 0 );

// Add 30 post per Hyper archive page
    function my_post_queries( $query ) {

        if(is_post_type_archive( 'hyper' ) && !is_feed() && !is_admin() ){
          // show 50 posts on custom taxonomy pages
          $query->set('posts_per_page', 24);
        }
      }
    add_action( 'pre_get_posts', 'my_post_queries' );



// Register Custom Post Type Log
function custom_post_type_log() {
  $labels = array(
    'name'                => _x( 'Log', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Log', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Log', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All Log Posts', 'text_domain' ),
    'view_item'           => __( 'View Log Post', 'text_domain' ),
    'add_new_item'        => __( 'Add New Log Post', 'text_domain' ),
    'add_new'             => __( 'Add New Log Post', 'text_domain' ),
    'edit_item'           => __( 'Edit Log Post', 'text_domain' ),
    'update_item'         => __( 'Update Log Post', 'text_domain' ),
    'search_items'        => __( 'Search Log Post', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'log', 'text_domain' ),
    'description'         => __( 'Suspension of solid particles', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-format-status',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
  );
  register_post_type( 'log', $args );
}
// Hook into the 'init' action
add_action( 'init', 'custom_post_type_log', 0 );


// Add 30 post per log archive page
    function define_number_of_posts_per_page_on_log( $query ) {

        if(is_post_type_archive( 'log' ) && !is_feed() && !is_admin() ){
          // show 50 posts on custom taxonomy pages
          $query->set('posts_per_page', 30);
        }
      }
    add_action( 'pre_get_posts', 'define_number_of_posts_per_page_on_log' );

/**
 * Display a custom taxonomy log-branch dropdown in admin
 * @author Mike Hemberger
 * @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
 */
add_action('restrict_manage_posts', 'tsm_filter_post_type_by_taxonomy');
function tsm_filter_post_type_by_taxonomy() {
    global $typenow;
    $post_type = 'log'; // change to your post type
    $taxonomy  = 'log-branch'; // change to your taxonomy
    if ($typenow == $post_type) {
        $selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy($taxonomy);
        wp_dropdown_categories(array(
            'show_option_all' => __("Show All {$info_taxonomy->label}"),
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'show_count'      => true,
            'hide_empty'      => true,
        ));
    };
}

/**
 * Filter posts by taxonomy log-branch in admin
 * @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
 */
add_filter('parse_query', 'tsm_convert_id_to_term_in_query');
function tsm_convert_id_to_term_in_query($query) {
    global $pagenow;
    $post_type = 'log'; // change to your post type
    $taxonomy  = 'log-branch'; // change to your taxonomy
    $q_vars    = &$query->query_vars;
    if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ) {
        $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
        $q_vars[$taxonomy] = $term->slug;
    }
}


// ---------------------------------------------
// ----- Archeived Custom Post Types Series -----
// ---------------------------------------------

    // Register Custom Post Type cityburns.com // ARCHIVED
    function custom_post_type_cityburns() {
      $labels = array(
        'name'                => _x( 'cityburns', 'Post Type General Name', 'text_domain' ),
        'singular_name'       => _x( 'CB', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'           => __( 'CB', 'text_domain' ),
        'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
        'all_items'           => __( 'All CB Posts', 'text_domain' ),
        'view_item'           => __( 'View CB Post', 'text_domain' ),
        'add_new_item'        => __( 'Add New CB Post', 'text_domain' ),
        'add_new'             => __( 'Add New CB Post', 'text_domain' ),
        'edit_item'           => __( 'Edit CB Post', 'text_domain' ),
        'update_item'         => __( 'Update CB Post', 'text_domain' ),
        'search_items'        => __( 'Search CB Post', 'text_domain' ),
        'not_found'           => __( 'Not found', 'text_domain' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
      );
      $args = array(
        'label'               => __( 'cityburns', 'text_domain' ),
        'description'         => __( 'The sun below the horizon.', 'text_domain' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', ),
        'taxonomies'          => array( 'category', 'post_tag' ),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 20,
        'menu_icon'           => 'dashicons-media-archive',
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'yarpp_support'       => true,
      );
      register_post_type( 'cityburns', $args );
    }
    // Hook into the 'init' action
    add_action( 'init', 'custom_post_type_cityburns', 0 );



// ----- Change admin menu order -----

    // Change Media postion on admin menu
    function change_media_menu_postion() {
        return array( 'index.php', 'upload.php' );
    }

    add_filter( 'custom_menu_order', '__return_true' );
    add_filter( 'menu_order', 'change_media_menu_postion' );




// add custom post types to the MAIN LOOP and/or FEED!!!
//via here: http://justintadlock.com/archives/2010/02/02/showing-custom-post-types-on-your-home-blog-page

function my_get_posts( $query ) {

    if ( is_home() && $query->is_main_query() )
        $query->set( 'post_type', array( 'post', 'dusk', 'emulsion', 'films', 'hyper', 'log', 'cityburns') );

    return $query;
}
add_filter( 'pre_get_posts', 'my_get_posts' );

function myfeed_request($qv) {
    if (isset($qv['feed']) && !isset($qv['post_type']))
        $qv['post_type'] = array('post', 'dusk', 'emulsion', 'films', 'hyper', 'log', 'cityburns');
    return $qv;
}
add_filter('request', 'myfeed_request');



/*
 * Add Custom Post Types to categories and tags archive
 * cia https://wordpress.org/support/topic/custom-posts-not-showing-in-category-archive/
 */

add_filter('pre_get_posts', 'query_post_type');
function query_post_type($query) {
    if(is_category() || is_tag()) {
        $post_type = get_query_var('post_type');
        if($post_type) {
            $post_type = $post_type;
        } else {
            $post_type = array('post', 'dusk', 'emulsion', 'films', 'hyper', 'log', 'cityburns'); // replace CPT to your custom post type
        }
        $query->set('post_type',$post_type);

    }
    return $query;
}


// STOP ***********************************************
// --> custom posts types
// ****************************************************





// START ***********************************************
// --> Custom Taxonomy
// ****************************************************


function places_taxonomy() {
  $labels = array(
    'name'                       => _x( 'Place', 'Taxonomy General Name', 'text_domain' ),
    'singular_name'              => _x( 'Place', 'Taxonomy Singular Name', 'text_domain' ),
    'menu_name'                  => __( 'Places', 'text_domain' ),
    'all_items'                  => __( 'All Places', 'text_domain' ),
    'parent_item'                => __( 'Parent Place', 'text_domain' ),
    'parent_item_colon'          => __( 'Parent Place:', 'text_domain' ),
    'new_item_name'              => __( 'New Place Name', 'text_domain' ),
    'add_new_item'               => __( 'Add New Place', 'text_domain' ),
    'edit_item'                  => __( 'Edit Place', 'text_domain' ),
    'update_item'                => __( 'Update Place', 'text_domain' ),
    'separate_items_with_commas' => __( 'Separate places with commas', 'text_domain' ),
    'search_items'               => __( 'Search places', 'text_domain' ),
    'add_or_remove_items'        => __( 'Add or remove places', 'text_domain' ),
    'choose_from_most_used'      => __( 'Choose from the most used places', 'text_domain' ),
    'not_found'                  => __( 'Not Found', 'text_domain' ),
  );
  $args = array(
    'labels'                     => $labels,
    'hierarchical'               => false,
    'rewrite'                    => array(
                                    'slug' => 'in'),
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
  );
  register_taxonomy( 'places', array( 'post', 'emulsion', 'featuring', 'dusk', 'hyper', 'log', 'films', 'cityburns' ), $args );
}
add_action( 'init', 'places_taxonomy', 0 );


function with_taxonomy() {
  $labels = array(
    'name'                       => _x( 'With', 'Taxonomy General Name', 'text_domain' ),
    'singular_name'              => _x( 'With', 'Taxonomy Singular Name', 'text_domain' ),
    'menu_name'                  => __( 'With', 'text_domain' ),
    'all_items'                  => __( 'All With', 'text_domain' ),
    'parent_item'                => __( 'Parent With', 'text_domain' ),
    'parent_item_colon'          => __( 'Parent With:', 'text_domain' ),
    'new_item_name'              => __( 'New With Name', 'text_domain' ),
    'add_new_item'               => __( 'Add New With', 'text_domain' ),
    'edit_item'                  => __( 'Edit With', 'text_domain' ),
    'update_item'                => __( 'Update With', 'text_domain' ),
    'separate_items_with_commas' => __( 'Separate with with commas', 'text_domain' ),
    'search_items'               => __( 'Search with', 'text_domain' ),
    'add_or_remove_items'        => __( 'Add or remove with', 'text_domain' ),
    'choose_from_most_used'      => __( 'Choose from the most used with', 'text_domain' ),
    'not_found'                  => __( 'Not Found', 'text_domain' ),
  );
  $args = array(
    'labels'                     => $labels,
    'hierarchical'               => false,
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
  );
  register_taxonomy( 'with', array( 'post', 'emulsion', 'featuring', 'dusk', 'hyper', 'log', 'films', 'cityburns' ), $args );
}
add_action( 'init', 'with_taxonomy', 0 );


function medium_taxonomy() {

  $labels = array(
    'name'                       => _x( 'Medium', 'Taxonomy General Name', 'text_domain' ),
    'singular_name'              => _x( 'Medium', 'Taxonomy Singular Name', 'text_domain' ),
    'menu_name'                  => __( 'Medium', 'text_domain' ),
    'all_items'                  => __( 'All Mediums', 'text_domain' ),
    'parent_item'                => __( 'Parent Medium', 'text_domain' ),
    'parent_item_colon'          => __( 'Parent Medium:', 'text_domain' ),
    'new_item_name'              => __( 'New Medium Name', 'text_domain' ),
    'add_new_item'               => __( 'Add New Medium', 'text_domain' ),
    'edit_item'                  => __( 'Edit Medium', 'text_domain' ),
    'update_item'                => __( 'Update Medium', 'text_domain' ),
    'separate_items_with_commas' => __( 'Separate medium with commas', 'text_domain' ),
    'search_items'               => __( 'Search medium', 'text_domain' ),
    'add_or_remove_items'        => __( 'Add or remove medium', 'text_domain' ),
    'choose_from_most_used'      => __( 'Choose from the most used medium', 'text_domain' ),
    'not_found'                  => __( 'Not Found', 'text_domain' ),
  );
  $args = array(
    'labels'                     => $labels,
    'hierarchical'               => true,
    'rewrite'                    => array(
                                    'hierarchical' => true,
                                    'slug' => 'm'),
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
  );
  register_taxonomy( 'medium', array( 'post', 'dusk', 'hyper', 'emulsion', 'log', 'films', 'cityburns' ), $args );

}
add_action( 'init', 'medium_taxonomy', 0 );


function log_branch_taxonomy() {
  $labels = array(
    'name'                       => _x( 'Log branch', 'Taxonomy General Name', 'text_domain' ),
    'singular_name'              => _x( 'Log branch', 'Taxonomy Singular Name', 'text_domain' ),
    'menu_name'                  => __( 'Log branches', 'text_domain' ),
    'all_items'                  => __( 'All Log branches', 'text_domain' ),
    'parent_item'                => __( 'Parent Log branches', 'text_domain' ),
    'parent_item_colon'          => __( 'Parent Log branches:', 'text_domain' ),
    'new_item_name'              => __( 'New Log branches Name', 'text_domain' ),
    'add_new_item'               => __( 'Add New Log branches', 'text_domain' ),
    'edit_item'                  => __( 'Edit Log branches', 'text_domain' ),
    'update_item'                => __( 'Update Log branches', 'text_domain' ),
    'separate_items_with_commas' => __( 'Separate Log branches with commas', 'text_domain' ),
    'search_items'               => __( 'Search Log branches', 'text_domain' ),
    'add_or_remove_items'        => __( 'Add or remove Log branches', 'text_domain' ),
    'choose_from_most_used'      => __( 'Choose from the most used Log branches', 'text_domain' ),
    'not_found'                  => __( 'Not Found', 'text_domain' ),
  );
  $args = array(
    'labels'                     => $labels,
    'hierarchical'               => false,
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
  );
  register_taxonomy( 'log-branch', array( 'log' ), $args );
}
add_action( 'init', 'log_branch_taxonomy', 0 );


    function film_genre_taxonomy() {
      $labels = array(
        'name'                       => _x( 'Film Genres', 'Taxonomy General Name', 'text_domain' ),
        'singular_name'              => _x( 'Film Genre', 'Taxonomy Singular Name', 'text_domain' ),
        'menu_name'                  => __( 'Film Genres', 'text_domain' ),
        'all_items'                  => __( 'All Film Genres', 'text_domain' ),
        'parent_item'                => __( 'Parent Film genre', 'text_domain' ),
        'parent_item_colon'          => __( 'Parent Film Genres:', 'text_domain' ),
        'new_item_name'              => __( 'New Film genre Name', 'text_domain' ),
        'add_new_item'               => __( 'Add New Film genre', 'text_domain' ),
        'edit_item'                  => __( 'Edit Film genre', 'text_domain' ),
        'update_item'                => __( 'Update Film genre', 'text_domain' ),
        'separate_items_with_commas' => __( 'Separate Film Genres with commas', 'text_domain' ),
        'search_items'               => __( 'Search Film genre', 'text_domain' ),
        'add_or_remove_items'        => __( 'Add or remove Film Genres', 'text_domain' ),
        'choose_from_most_used'      => __( 'Choose from the most used Film Genres', 'text_domain' ),
        'not_found'                  => __( 'Not Found', 'text_domain' ),
      );
      $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
      );
      register_taxonomy( 'film_genre', array( 'films' ), $args );
    }
    add_action( 'init', 'film_genre_taxonomy', 0 );


function year_from_taxonomy() {
  $labels = array(
    'name'                       => _x( 'Year Archive', 'Taxonomy General Name', 'text_domain' ),
    'singular_name'              => _x( 'Year Archive', 'Taxonomy Singular Name', 'text_domain' ),
    'menu_name'                  => __( 'From', 'text_domain' ),
    'all_items'                  => __( 'All years', 'text_domain' ),
    'parent_item'                => __( 'Parent years', 'text_domain' ),
    'parent_item_colon'          => __( 'Parent years:', 'text_domain' ),
    'new_item_name'              => __( 'New year', 'text_domain' ),
    'add_new_item'               => __( 'Add New year from', 'text_domain' ),
    'edit_item'                  => __( 'Edit year from', 'text_domain' ),
    'update_item'                => __( 'Update year from', 'text_domain' ),
    'separate_items_with_commas' => __( 'Separate years from with commas', 'text_domain' ),
    'search_items'               => __( 'Search years from', 'text_domain' ),
    'add_or_remove_items'        => __( 'Add or remove years from', 'text_domain' ),
    'choose_from_most_used'      => __( 'Choose from the most used years from', 'text_domain' ),
    'not_found'                  => __( 'Not Found', 'text_domain' ),
  );
  $args = array(
    'labels'                     => $labels,
    'hierarchical'               => false,
    'rewrite'                    => array(
                                    'slug' => 'from'),
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
  );
  register_taxonomy( 'from', array('post', 'dusk', 'hyper', 'emulsion', 'log', 'films', 'cityburns'), $args );
}
add_action( 'init', 'year_from_taxonomy', 0 );

// Set current year as the default one
function current_year_as_default_year_from( $post_id ) {
    $current_post = get_post( $post_id );

    // This makes sure the taxonomy is only set when a new post is created
    if ( $current_post->post_date == $current_post->post_modified ) {
        wp_set_object_terms( $post_id, date( 'Y' ), 'from', true );
    }
}
add_action( 'auto-draft_post', 'current_year_as_default_year_from' );
add_action( 'auto-draft_post_dusk', 'current_year_as_default_year_from' );
add_action( 'auto-draft_post_emulsion', 'current_year_as_default_year_from' );
add_action( 'auto-draft_post_log', 'current_year_as_default_year_from' );
add_action( 'auto-draft_post_hyper', 'current_year_as_default_year_from' );
add_action( 'auto-draft_post_films', 'current_year_as_default_year_from' );


// show taxonomy on the posts lists @admin
function kc_add_taxonomy_filters() {
global $typenow;
// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
$my_taxonomies = array(  'post_tag' );
switch($typenow){
    case 'post':
        foreach ($my_taxonomies as $tax_slug) {
                    $tax_obj = get_taxonomy($tax_slug);
                    $tax_name = $tax_obj->labels->name;
                    $terms = get_terms($tax_slug);
                    if(count($terms) > 0) {
                        echo "<select name='$tax_slug' id='$tax_slug' class='postform alignleft actions'>";
                        echo "<option value=''>Show All $tax_name</option>";
                        foreach ($terms as $term) {
                            echo '<option value="', $term->slug,'" ',selected( @$_GET[$tax_slug] == $term->slug , $current = true, $echo = false ) , '>' , $term->name ,' (' , $term->count ,')</option>';
                        }
                        echo "</select>";
                    }
        }
    break;
}
}
add_action( 'restrict_manage_posts', 'kc_add_taxonomy_filters' );




// ----------------------------
//  ---- Remove Categories ----
// ----------------------------

// Disable categories
function wpse120418_unregister_categories() {
    register_taxonomy( 'category', array() );
}
add_action( 'init', 'wpse120418_unregister_categories' );
//  remove category menu
unregister_widget( 'WP_Widget_Categories' );





// ***********************************************
// --> Custom Functions to use on the theme
// ***********************************************

// LIST OF TAGS
function taxonomy_list($post_id_of_the_tags,$custom_taxonomy, $tag_before, $tag_after, $separator_term, $separator_term_last, $tax_link) {
    //ID of the current post
    $this_id = $post_id_of_the_tags;

    // Get Series terms
    $terms = get_the_terms( $this_id, $custom_taxonomy);

    $count = count( $terms );

    $terms_list = "";

    if ( $terms && ! is_wp_error( $terms ) ) {
        $i = 0;
        foreach ( $terms as $term ) {
            $i++;

            $term_link = get_term_link( $term );

            if ($i==1) {
                $separator = "";
            } elseif($count != $i) {
                $separator = $separator_term;
            } else{
                $separator = $separator_term_last;
            }

            if ($tax_link == 'link') {
                $terms_list = $terms_list.$separator.'<a href="'.$term_link.'" rel="category tag">'.$term->name.'</a>';
            } else {
                $terms_list = $terms_list.$separator.$term->name;
            }
        }

    // add content after and after the string
    $terms_list = $tag_before.$terms_list.$tag_after;
    }

    return $terms_list;
}


// List of tags with post number
function taxonomy_list_w_numbers($post_id_of_the_tags,$custom_taxonomy, $tag_before, $tag_after, $separator_term, $separator_term_last, $tax_link) {

    //ID of the current post
    $this_id = $post_id_of_the_tags;

    // Get Series terms
    $terms = get_the_terms( $this_id, $custom_taxonomy );

    // cont number of terms
    $terms_count = count( $terms );

    $i = 0;

    $terms_list = "";

    if ( $terms && ! is_wp_error( $terms ) ) :

        // Define variables
        $series_number = "";

        foreach ( $terms as $term ) {
            $i++;

            // Get the Slug of ther Series
            $series_slug = $term->slug;

            $series_name = $term->name;

            //Get posts with the Serie of the current post
            $args = array(
                'post_type' => 'log',
                'post_status' => array( 'publish' ),
                'order'     => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => $custom_taxonomy,
                        'field'    => 'slug',
                        'terms'    => $series_slug,
                    ),
                ),
            );
            $series_posts = new WP_Query( $args );

            $count = 0;
            if($series_posts->have_posts()) :
                while($series_posts->have_posts()) :
                    $series_posts->the_post();

                    $count++;

                    $id_loop_post = get_the_ID();
                    if ($id_loop_post == $this_id) {
                        $series_number = $count;
                    }
                endwhile;
            endif;
            wp_reset_postdata();

            if ($i==$terms_count) {
                $separator = "";
            }elseif ($i==($terms_count - 1)) {
                $separator = $separator_term_last;
            }else{
                $separator = $separator_term;
            }

            if ($tax_link == 'link') {

                $terms_list = $terms_list.'<a href="'.get_term_link( $term ).'" rel="category tag">'.$series_name.sprintf('%02d', $series_number).'</a>'.$separator;
            } else {

                $terms_list = $terms_list .$series_name.sprintf('%02d', $series_number).$separator;
            }

        }

        $terms_list = $tag_before.$terms_list.$tag_after;

    endif;

    return $terms_list;
}


// --- STYLE ---

// post content wrap styles
function content_wrap() {
    echo "col-12 offset-0 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-7 offset-lg-2";

}

//add this to your functions.php

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





// ---------------- // ----------------
// ---------------- // ----------------
// --------    ADMIN  AREA   ----------
// ---------------- // ----------------
// ---------------- // ----------------


// -- get diferent favicon on admin backend
// -------- via: http://goo.gl/MWgn4t
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
            <meta name="msapplication-TileColor" content="#f50044">
            <meta name="msapplication-TileImage" content="'.$favicon_path.'/ms-icon-144x144.png">
            <meta name="theme-color" content="#f50057">';
}
function add_my_favicon_admin() {
   $favicon_path = get_template_directory_uri() . '/favicon.ico/admin';

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
            <meta name="theme-color" content="#775DA6">';
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








// ---------------- // ----------------
// ---------------- // ----------------
// -------- FEED Customization --------
// ---------------- // ----------------
// ---------------- // ----------------

// Automatic Feed Links is a theme feature introduced with Version 3.0.
add_theme_support( 'automatic-feed-links' );


// suport for Feedly
// https://blog.feedly.com/10-ways-to-optimize-your-feed-for-feedly/
// https://www.utilitylog.com/optimize-wordpress-blog-for-feedly/
add_filter( 'rss2_ns', 'feedly' );
function feedly() {
 echo 'xmlns:webfeeds="http://webfeeds.org/rss/1.0"';
}

// feedly Logo and Cover image
add_filter( 'rss2_head', 'feedly_head' );
function feedly_head() {
 echo '<webfeeds:cover image="https://trouble.place/wp-content/uploads/2016/07/Trouble-to-dome.png" />';
 echo '<webfeeds:icon>'.get_bloginfo('template_url').'/library/svg/logo.svg</webfeeds:icon>';
 echo '<webfeeds:logo>'.get_bloginfo('template_url').'/library/svg/logo.svg</webfeeds:logo>';
 echo '<webfeeds:accentColor>ec407a</webfeeds:accentColor>';
 echo '<webfeeds:related layout=”card” target=”browser”/>';
}


// Feed titles for diferente post tips
function titlerss($content) {

    // get post ID
    global $post;

    $post_type = get_post_type($post->ID);

    if($post_type == 'dusk') {

        // $places = taxonomy_list($post->ID,'places', '','',', ',' and ', 'no_link');
        // if ($places) {
        //     $content = $content. ' // dusk series, '.$places;
        // } else {
            $content = 'dusk // '.$content;
        // }


    } elseif ($post_type == 'films') {

        $content = 'film // '.$content;

    } elseif ($post_type == 'hyper') {

        $content = 'hyper series #'.number_of_the_post($post->ID).' // '.$content;

    } elseif ($post_type == 'log') {

        $log_series = 'log // '.strtoupper(taxonomy_list_w_numbers($post->ID,'log-branch','','',', ', ' &amp; ', 'no_link'));

        if ($log_series) {
            $content = 'log // '.$log_series.', '.$content;
        }
    }

    return $content;

}
add_filter('the_title_rss', 'titlerss');

// add custom field
// -- read here: http://goo.gl/TsIVG2






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
            $post_type == 'dusk' OR
            $post_type == 'emulsion' OR
            $post_type == 'hyper') {

            $imageshtml = '<a href="'. get_permalink($post->ID) .'" class="webfeedsFeaturedVisual"><img src="'. wp_get_attachment_url( get_post_thumbnail_id($post->ID) ).'"/></a>
                    <p>view the '.$number_of_imgs.' images <a href="'. get_permalink($post->ID) .'">here</a>.</p>';

            // get images atached to the post
            $content =  $imageshtml;

        } elseif ($post_type == 'log') {

            $imageshtml = '<a href="'. get_permalink($post->ID) .'" class="webfeedsFeaturedVisual"><img src="'. wp_get_attachment_url( get_post_thumbnail_id($post->ID) ).'"/></a>
                    <p>view the complete set <a href="'. get_permalink($post->ID) .'">here</a>.</p>';

            // get images atached to the post
            $content =  $imageshtml;

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
add_filter('the_content', 'wptuts_feedimgs'); // for full feeds


function wp_rss_title() {
    if (is_feed()) {

            return 'Diferent feed name';
            // hoe to set a diferent name just for custom post archive?

    } }
// add_filter('wp_title', 'wp_rss_title'); //deactivated by me


// ---------------- // ----------------
// ---------------- // ----------------
// -------- NUMBER OF THE POST --------
// ---------------- // ----------------
// ---------------- // ----------------
function number_of_the_post($post_ID)
{
    $the_post = get_post($post_ID);
    $post_type = get_post_type( $post_ID );
    $date = $the_post->post_date;
    $maintitle = $the_post->post_title;
    $count='';

    global $wpdb;
    $count = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts  WHERE post_status='publish' AND post_type='$post_type' AND post_date<='{$date}'");

    return $count;
}


// ---------------- // ----------------
// ---------------- // ----------------
// - NUMBER OF Images in total --
// ---------------- // ----------------
// ---------------- // ----------------

function number_of_images_in_total() {

global $wpdb;
        $res = $wpdb->get_results("select p1.*
            FROM {$wpdb->posts} p1, {$wpdb->posts} p2
            WHERE p1.post_parent = p2.ID
               AND p1.post_mime_type LIKE 'image%'

               AND p2.post_status = 'publish'
            ;"
        );
        $return = count($res);
        return $return;
}


// ---------------- // ----------------
// ---------------- // ----------------
// - NUMBER OF Images in a post type --
// ---------------- // ----------------
// ---------------- // ----------------

function number_of_images_in_the_post_type($in_post_type) {

    global $wpdb;
        $res = $wpdb->get_results("select p1.*
            FROM {$wpdb->posts} p1, {$wpdb->posts} p2
            WHERE p1.post_parent = p2.ID
               AND p1.post_mime_type LIKE 'image%'
               AND p2.post_type = '$in_post_type'
               AND p2.post_status = 'publish'
            ;"
        );
        $return = count($res);
        return $return;
}



// ---------------- // ----------------
// ---------------- // ----------------
// - NUMBER OF days of a series ---- --
// ---------------- // ----------------
// ---------------- // ----------------

function number_of_days_in_the_post_type($in_post_type) {
    $last_post  =   get_posts("post_type=".$in_post_type."&numberposts=1");
    $first_post     =   get_posts("post_type=".$in_post_type."&numberposts=1&order=asc");

    $last_post_date     = get_the_time('Y-m-d',$last_post[0]->ID);
    $first_post_date    = get_the_time('Y-m-d',$first_post[0]->ID);

    $datetime1 = date_create($first_post_date );
    $datetime2 = date_create($last_post_date );
    $interval = date_diff($datetime1, $datetime2);

    $return = $interval->days;
    return $return;
}


// ---------------- // ----------------
// ---------------- // ----------------
// -------- ROMAN NUMBERS --------
// ---------------- // ----------------
// ---------------- // ----------------
function romanic_number($integer, $upcase = true)
{
    $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
    $return = '';
    while($integer > 0)
    {
        foreach($table as $rom=>$arb)
        {
            if($integer >= $arb)
            {
                $integer -= $arb;
                $return .= $rom;
                break;
            }
        }
    }

    return $return;
}





/// ------------ Background Colours --
// -----------------------------------
// -----------------------------------

// Add specific CSS class by filter

function add_color_class( $classes ) {
    global $post;

    // Get selected color from ACF
    $selected_color = null;
    $selected_color = get_field('background_colour');


    // Is Hyper
    if ((is_singular( 'hyper' ) && $selected_color==0)
                OR is_post_type_archive('hyper')) {


        $classes[] = 'none-white-bg';
        $classes[] = 'deep-purple';

    // Is Dusk
    } elseif ((is_singular( 'dusk' ) && $selected_color==0)
                OR is_post_type_archive('dusk')) {

        $classes[] = 'header-ligh';
        $classes[] = 'yellow';

    // Is Emulsion
    } elseif ((is_singular( 'emulsion' ) && $selected_color==0)
                OR is_post_type_archive('emulsion')) {

        $classes[] = 'header-ligh';
        $classes[] = 'yellow';

    // Is Log
    } elseif ((is_singular( 'log' ) && $selected_color==0)
                OR is_post_type_archive('log')
                OR is_tax('log-branch')) {

        $classes[] = 'header-ligh';
        $classes[] = 'header-white';

    // Is Films
    } elseif ((is_singular( 'films' ) && $selected_color==0)
 				OR is_post_type_archive('films')) {
        $classes[] = 'none-white-bg';
        $classes[] = 'dark';

    // If there is selection on ACF
    } elseif (!$selected_color==0 && is_singular()) {
        $classes[] = $selected_color;
        if ($selected_color=="lime") {
            $classes[] = 'header-ligh';
        } elseif ($selected_color=="blue") {
            $classes[] = 'header-ligh';
        } elseif ($selected_color=="yellow") {
            $classes[] = 'header-ligh';
        } elseif ($selected_color=="magic-pink") {
            $classes[] = 'none-white-bg';
        } elseif ($selected_color=="deep-purple") {
            $classes[] = 'none-white-bg';
        } elseif ($selected_color=="indigo") {
            $classes[] = 'none-white-bg';
        }

    }

    // Other cases
    elseif ( $selected_color==0) {

        if (is_singular( ) && $selected_color==0) {

            $classes[] = 'header-ligh';
            $classes[] = 'header-white';

        // Default color
        } else {
            $classes[] = 'none-white-bg';
            $classes[] = 'magic-pink'; // $default_color;
        }

    }


    // return the $classes array
    return $classes;
}
add_filter( 'body_class', 'add_color_class' );



// ---------------- // -------------------------------- // ----------------
// ---------------- // -------------------------------- // ----------------
// ---------------- // ------------ SEO HOOKS --
// ---------------- // -------------------------------- // ----------------
// ---------------- // -------------------------------- // ----------------

// Change Title
function custom_seo_title( $site_title ) {
    global $post;
    if (is_singular('log')) {

        // With log branch on the title->
        $site_title = taxonomy_list_w_numbers($post->ID,'log-branch','',', ',', ', ' & ', 'link').$site_title;

    } elseif (is_singular('hyper')) {

        $site_title = $site_title.' // Hyper Series #'.number_of_the_post($post->ID);

    } elseif (is_singular('films')) {

        $site_title = $site_title;

    } else {

        // ADD CAPITALIZATION
        // $site_title = strtoupper($site_title);

    }
    return $site_title;
}
add_filter( 'wpseo_title', 'custom_seo_title', 10, 1 );


// Change SEO image to smaller size - compatible with whatsapp

add_filter( 'wpseo_opengraph_image_size', 'cm1_rectangular_facebook_wpseo_image_size', 10, 1 );
function cm1_rectangular_facebook_wpseo_image_size( $string ) {
return 'medium';
}

// Change SEO image -- Hoked by Twitter and Facebook image filter
function seo_image($image) {

    // Is Post Type archive
    if( is_post_type_archive( 'hyper' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'hyper',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_post_type_archive( 'log' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'log',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_post_type_archive( 'dusk' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'dusk',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_post_type_archive( 'emulsion' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'emulsion',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_post_type_archive( 'films' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'films',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_post_type_archive( 'cityburns' )) {

        $args = array(
            'numberposts' => '1',
            'post_type' => 'cityburns',
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_tax( ) ) {

        // Get taxonomy term
        global $wp_query;
        $term = $wp_query->get_queried_object();
        $taxonomy = $term->taxonomy;
        $term_slug = $term->slug;


        $args = array(
            'numberposts' => '1',
            'post_status' => 'publish',
            'post_type' => array('post','dusk','films','log','emulsion','hyper','cityburns'),
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term_slug,
                ),
            )
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   if( is_home() ) {

        $args = array(
            'numberposts' => '1',
            'post_type' => array('post','dusk','films','emulsion','hyper','cityburns'),
            'post_status' => 'publish'
        );
        $last = wp_get_recent_posts( $args );
        $last_id = $last['0']['ID'];

        $image = wp_get_attachment_url( get_post_thumbnail_id($last_id) );
   }

   // Return Final image
   return $image;
}
add_filter('wpseo_opengraph_image', 'seo_image');
add_filter('wpseo_twitter_image', 'seo_image');
// ISSUES with images? try this one: https://github.com/Yoast/wordpress-seo/issues/1060



// Change email content type: https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_mail_content_type
add_filter( 'wp_mail_content_type', 'set_content_type' );
function set_content_type( $content_type ) {
    return 'text/html';
}


// INFINITE SCROLL -- https://infinite-scroll.com/ -- add class to next post link taf
add_filter('next_posts_link_attributes', 'posts_link_attributes');
//add_filter('previous_posts_link_attributes', 'posts_link_attributes');

function posts_link_attributes() {
    return 'class="pagination__next view-more-button"';
}


// Used for log grouping posts
// ID of the next post on index
function index_next_post_type($current_post_id) {

    //Current post time to
    $local_timestamp = get_the_time('U', $current_post_id);
    $gmt_timestamp = get_post_time('U', true, $current_post_id);

    //Get posts with the Serie of the current post
    $args = array(
        'posts_per_page' => 1,
        'post_type' => array( 'post', 'dusk', 'emulsion', 'films', 'hyper', 'log', 'cityburns'),
        'post_status' => array( 'publish' ),
        // 'date_query' => array(
        //     array(
        //         'after'     => $gmt_timestamp,
        //     ),
        // ),
    );
    $inner_query = new WP_Query( $args );

    // if($inner_query->have_posts()) :
    //     while($inner_query->have_posts()) :

    //         $id_loop_post = get_the_ID();

    //         $next_post_type = get_post_type( $id_loop_post );

    //     endwhile;
    // endif;
    // wp_reset_postdata();

    return count($inner_query);
}



// Newsletter HOOK
add_action('trouble_email_hook', 'build_email_and_send_1');

// Newsletter function
function build_email_and_send_1() {

    // Total amount of days this month
    $number_of_day_this_month = date("t");
    $day_of_the_month = date("j");

    // Numeric representation of a month, without leading zeros
    $numeric_representation_of_month = date("n");


    // If is the last day of the month
    if (($day_of_the_month == $number_of_day_this_month) AND ($numeric_representation_of_month % 2 == 0)) {

        global $post;

        $posts_content = "";

        // POSTs
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'post',
            'date_query'        => array('after' => date('Y-m-d', strtotime('-61 days')))
        );
        $posts = get_posts( $args );

        if ($posts) {
            $posts_content = $posts_content." ";
            foreach ( $posts as $post ) {

                if (get_field('send_on_trouble_letter',$post->ID) == true) {

                    $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "small");

                    $post_content = '
                        <a href="'.get_permalink($post->ID).'" target="_blank">
                        <img class="tl-email-image" src="'.$image_attributes[0].'" style="width: 76%!important; max-width: 640px!important; height:auto!important;" width="100%"/>
                        </a>
                        <strong><em><a href="'.get_post_permalink($post->id).'" style="color:#f50044!important; text-decoration: none;">'.strtoupper(get_the_title( $post->id )).'</a></em></strong><br /></br>';

                    $posts_content = $posts_content.$post_content;

                }
            }
        $posts_content = $posts_content."<br /><hr />";
        }

        // FILMs
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'films',
            'date_query'        => array('after' => date('Y-m-d', strtotime('-61 days')))
        );
        $posts = get_posts( $args );

        if ($posts) {
            $posts_content = $posts_content.'</br><span style="font-size: small;"><strong><em>FILMS</em></strong></span><br/>';
            foreach ( $posts as $post ) {

                if (get_field('send_on_trouble_letter',$post->ID) == true) {

                    $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "small");

                    $post_content = '
                        <a href="'.get_permalink($post->ID).'" target="_blank">
                        <img class="tl-email-image" src="'.$image_attributes[0].'" style="width: 76%!important; max-width: 640px!important; height:auto!important;" width="100%" />
                        </a>
                        <strong><em><a href="'.get_post_permalink($post->id).'" style="color:#f50044!important; text-decoration: none;">'.strtoupper(get_the_title( $post->id )).'</a></em></strong><br /></br>';

                    $posts_content = $posts_content.$post_content;

                }
            }
        $posts_content = $posts_content."<br /><hr />";
        }

        // EMULSION
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'emulsion',
            'date_query'        => array('after' => date('Y-m-d', strtotime('-61 days')))
        );
        $posts = get_posts( $args );

        if ($posts) {
            $posts_content = $posts_content.'</br><span style="font-size: small;"><strong><em>EMULSION</em></strong></span><br/>';
            foreach ( $posts as $post ) {

                if (get_field('send_on_trouble_letter',$post->ID) == true) {

                    $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "small");

                    $post_content = '
                        <a href="'.get_permalink($post->ID).'" target="_blank">
                        <img class="tl-email-image" src="'.$image_attributes[0].'" style="width: 76%!important; max-width: 640px!important; height:auto!important;" width="100%" />
                        </a>
                        <strong><em><a href="'.get_post_permalink($post->id).'" style="color:#f50044!important; text-decoration: none;">'.strtoupper(get_the_title( $post->id )).'</a></em></strong><br /></br>';

                    $posts_content = $posts_content.$post_content;

                }
            }
        $posts_content = $posts_content."<br /><hr />";
        }

        // DUSK
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'dusk',
            'date_query'        => array('after' => date('Y-m-d', strtotime('-61 days')))
        );
        $posts = get_posts( $args );

        if ($posts) {
            $posts_content = $posts_content.'</br><span style="font-size: small;"><strong><em>DUSK</em></strong></span><br/>';
            foreach ( $posts as $post ) {

                if (get_field('send_on_trouble_letter',$post->ID) == true) {

                    $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "small");

                    $post_content = '
                        <a href="'.get_permalink($post->ID).'" target="_blank">
                        <img class="tl-email-image" src="'.$image_attributes[0].'" style="width: 76%!important; max-width: 640px!important; height:auto!important;" width="100%" />
                        </a>
                        <strong><em><a href="'.get_post_permalink($post->id).'" style="color:#f50044!important; text-decoration: none;">'.strtoupper(get_the_title( $post->id )).'</a></em></strong><br /></br>';

                    $posts_content = $posts_content.$post_content;

                }
            }
        $posts_content = $posts_content."<br /><hr />";
        }

        // HYPER
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'hyper',
            'date_query'        => array('after' => date('Y-m-d', strtotime('-61 days')))
        );
        $posts = get_posts( $args );

        if ($posts) {
            $posts_content = $posts_content.'</br><span style="font-size: small;"><em><strong>HYPER SERIES</strong></em></span><br/>';
            foreach ( $posts as $post ) {

                if (get_field('send_on_trouble_letter',$post->ID) == true) {

                    $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "small");

                    $post_content = '
                        <a href="'.get_permalink($post->ID).'" target="_blank">
                        <img class="tl-email-image" src="'.$image_attributes[0].'" style="width: 76%!important; max-width: 640px!important; height:auto!important;" width="100%" />
                        </a>
                        <strong><em><a href="'.get_post_permalink($post->id).'" style="color:#f50044!important; text-decoration: none;">'.strtoupper(get_the_title( $post->id )).'</a></em></strong><br /></br>';

                    $posts_content = $posts_content.$post_content;

                }
            }
        $posts_content = $posts_content."<br /><hr />";
        }

        // LOG
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'log',
            'date_query'        => array('after' => date('Y-m-d', strtotime('-61 days')))
        );
        $posts = get_posts( $args );

        if ($posts) {
            $posts_content = $posts_content.'</br><span style="font-size: small;"><strong><em>LOG</em></strong></span></br></br>';

            $posts_content = $posts_content.'<strong><em><a href="https://trouble.place/log/" style="color:#f50044!important; text-decoration: none;">'.count($posts).' NEW ENTRIES</a></em></strong></br></br>';

            $posts_content = $posts_content."</br><hr />";
        }


        $posts_content = $posts_content."</br></br>";



        // Month name
        $month_name = "".date("F", strtotime('-32 days'))." & ".date("F")."";
        $year_numb = date('Y');


        // Combine Variables
        $to[]           = 'beamer-2383360638cc0beb42be76b60ce4d17510528977@tinyletter.com';
        $subject        = 'Trouble Letter // '.$month_name;
        $message        = '
            <div style="text-align: center;">
				<span style="font-size: small;">
					<strong>
					<em><a href="https://trouble.place">TROUBLE.PLACE</a> // NEWSLETTER // '.strtoupper($month_name).' '.strtoupper($year_numb).'</em>
					</strong>
				</span>
			</div>

            <div style="text-align: center;"><br />
            <br />'.$posts_content.'</div>';

        $headers        = array('From:tiago <tiago@trouble.place>');

        // Send Email
        wp_mail( $to, $subject, $message, $headers );

    } // If is the last day of the month

}



/************* Gallery Functions *************/


// Edit atachment media (image/video/etc) -- hide, delete and save gallery order
function gallery_edit_atachement_options($gallery_id,$attachment_count, $attachment_id) {

	// HIDE based on: https://stackoverflow.com/questions/40144638/how-to-remove-the-div-that-a-button-is-contained-in-when-the-button-is-clicked

	echo '
		<script type="text/javascript">
			function removeDiv(btn){
			((btn.parentNode).parentNode).removeChild(btn.parentNode);

			// reiniciate sortable
			var el = document.getElementById("#gallery-'.$gallery_id.'");
			var sortable = Sortable.create(el, { /* options */ });

			// reiniciate masonry
			$("#gallery-'.$gallery_id.'").masonry();

			}
		</script>

		<button class="remove" onclick="removeDiv(this);">HIDE</button>
	';

	// DELETE based on: https://stackoverflow.com/questions/15729334/how-to-trigger-a-link-with-jquery-without-refreshing-the-page

	echo '
		<a class="delete" href="javascript:;" rel="' . wp_nonce_url( get_bloginfo('url') . '/wp-admin/post.php?action=delete&amp;post=' . $attachment_id, 'delete-post_' . $attachment_id) . '" onclick="removeDiv(this);">DELETE</a>

		<script type="text/javascript">
			$(".delete").click(function(e){
			 	e.preventDefault();
				var targetUrl = $(this).attr("rel");
			 	$.ajax({
					url: targetUrl,
					type: "GET",
                    success:function(data) {
                        // This outputs the result of the ajax request
                        console.log(data);
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                    }
				});

				// reiniciate sortable
				var el = document.getElementById("#gallery-'.$gallery_id.'");
				var sortable = Sortable.create(el, { /* options */ });

				// reiniciate masonry
				$("#gallery-'.$gallery_id.'").masonry();

			});
		</script>
	';

	// save order of gallery -- function on single-gallery -- ajax functions on functions.php
	echo '
	<button class="saveorder" onclick="orderAttachmentesOnWpDb();">SAVE ORDER</button>
	';
}

// change order of media atachments on database
	// via: https://wptheming.com/2013/07/simple-ajax-example/

// Activate ajax on the frontend



// define ajaxurl as a global variable on the frontend
function example_ajax_enqueue() {
	// Enqueue javascript on the frontend.
	wp_enqueue_script(
		'example-ajax-script',
		get_template_directory_uri() . '/js/simple-ajax-example.js',
		array('jquery')
	);
	// The wp_localize_script allows us to output the ajax_url path for our script to use.
	wp_localize_script(
		'example-ajax-script',
		'example_ajax_obj',
		array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
	);
}
add_action( 'wp_enqueue_scripts', 'example_ajax_enqueue' );


// Change database wp_posts >> menu_order via Ajax request
function gallery_media_order_change_request() {

    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) ) {


        $attachmentId = $_REQUEST['attachmentId'];
        $attachmentOrder = $_REQUEST['attachmentOrder'];

        // Let's take the data that was sent and do something with it
        if ( $attachmentId ) {
			$debug_result = $attachmentId + $attachmentOrder;

			global $wpdb;
			$wpdb->update( 'wp_posts', array( 'menu_order'=>$attachmentOrder),array('ID'=>$attachmentId));

			if ( false === $updated ) {
			    echo "There was an error tring to move the attachmente ".$attachmentId." to the menu position number ".$attachmentOrder;
			} else {
			    echo "The attachment ".$attachmentId." is now on the position ".$attachmentOrder;
			}

        }

        // If you're debugging, it might be useful to see what was sent in the $_REQUEST
        // print_r($_REQUEST);

    }

    // Always die in functions echoing ajax content
   die();
}

add_action( 'wp_ajax_gallery_media_order_change_request', 'gallery_media_order_change_request' );




// END -- don't add any space after php close ?>
