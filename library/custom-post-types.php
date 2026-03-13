<?php
/**
 * Custom Post Type registrations and related query modifications.
 *
 * Registers: films, dusk, hyper, 4k-lento, log, cityburns (archived).
 * Includes: log-branch admin filter, admin menu order, CPTs in main loop/feed/archives.
 *
 * @package tiagsspace
 */


// Register Custom Post Type Film
function custom_post_type_films() {
  $labels = array(
    'name'                => _x( 'Film', 'Post Type General Name', 'text_domain' ),
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
	'show_in_rest'        => true,
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



// Register Custom Post Type DUSK
function custom_post_type_dusk() {
  $labels = array(
    'name'                => _x( 'Dusk', 'Post Type General Name', 'text_domain' ),
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
	'show_in_rest'        => true,
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
    'name'                => _x( 'Hyper', 'Post Type General Name', 'text_domain' ),
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
	'show_in_rest'        => true,
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


// Register Custom Post Type 4K LENTO
function custom_post_type_4klento() {
  $labels = array(
    'name'                => _x( '4K Lento', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( '4K Lento', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( '4K Lento', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All 4K Lento Mixes', 'text_domain' ),
    'view_item'           => __( 'View 4K Lento Mix', 'text_domain' ),
    'add_new_item'        => __( 'Add New 4K Lento Mix', 'text_domain' ),
    'add_new'             => __( 'Add New 4K Lento Mix', 'text_domain' ),
    'edit_item'           => __( 'Edit 4K Lento Mix', 'text_domain' ),
    'update_item'         => __( 'Update 4K Lento Mix', 'text_domain' ),
    'search_items'        => __( 'Search 4K Lento Mix', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( '4k-lento', 'text_domain' ),
    'description'         => __( '4K Lento description.', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'post-formats', ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => true,
    'show_ui'             => true,
	'show_in_rest'        => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-album',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
    'yarpp_support'       => true,
  );
  register_post_type( '4k-lento', $args );
}
// Hook into the 'init' action
add_action( 'init', 'custom_post_type_4klento', 0 );




// Register Custom Post Type Log
function custom_post_type_log() {
  $labels = array(
    'name'                => _x( 'Log', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Log', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Log', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
    'all_items'           => __( 'All Log', 'text_domain' ),
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
	'show_in_rest'        => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 4,
    'menu_icon'           => 'dashicons-smiley',
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
        'name'                => _x( 'City Series', 'Post Type General Name', 'text_domain' ),
        'singular_name'       => _x( 'City', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'           => __( 'City', 'text_domain' ),
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
		'show_in_rest'        => true,
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
        $query->set( 'post_type', array( 'post', 'dusk', 'films', 'hyper', 'log', 'cityburns', '4k-lento') );

    return $query;
}
add_filter( 'pre_get_posts', 'my_get_posts' );

function myfeed_request($qv) {
    if (isset($qv['feed']) && !isset($qv['post_type']))
        $qv['post_type'] = array('post', 'dusk', 'films', 'hyper', 'log', 'cityburns', '4k-lento');
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
            $post_type = array('post', 'dusk', 'films', 'hyper', 'log', 'cityburns', '4k-lento'); // replace CPT to your custom post type
        }
        $query->set('post_type',$post_type);

    }
    return $query;
}
