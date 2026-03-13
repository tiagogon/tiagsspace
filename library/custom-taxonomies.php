<?php
/**
 * Custom Taxonomy registrations.
 *
 * Registers: places, medium, log-branch, from (year archive).
 * Includes: admin taxonomy filters, CPTs on tag archives, disable categories.
 *
 * @package tiagsspace
 */


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
    'show_in_rest'               => true,
  );
  register_taxonomy( 'places', array( 'post', 'featuring', 'dusk', 'hyper', 'log', 'films', 'cityburns', '4k-lento'), $args );
}
add_action( 'init', 'places_taxonomy', 0 );


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
                                    'slug' => 'medium'),
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
    'show_in_rest'               => true,
  );
  register_taxonomy( 'medium', array( 'post', 'dusk', 'hyper', 'log', 'films', 'cityburns', '4k-lento'  ), $args );

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
    'show_in_rest'               => true,
  );
  register_taxonomy( 'log-branch', array( 'log' ), $args );
}
add_action( 'init', 'log_branch_taxonomy', 0 );





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
    'show_in_rest'               => true,
  );
  register_taxonomy( 'from', array('post', 'dusk', 'hyper', 'log', 'films', 'cityburns', '4k-lento' ), $args );
}
add_action( 'init', 'year_from_taxonomy', 0 );



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


// Show custom Post Types on post_tags archive
// https://wordpress.stackexchange.com/questions/108067/custom-post-type-taxonomy-tag-archive-no-post-found
// Tag and Category archive queries default to querying only the post post type, to add your custom post type to those queries, you can use the pre_get_posts action:
function wpa_cpt_tags( $query ) {
    if ( $query->is_tag() && $query->is_main_query() ) {
        $query->set( 'post_type', array( 'post', 'dusk', 'hyper', 'log', 'films', 'cityburns', '4k-lento' ) );
    }
}
add_action( 'pre_get_posts', 'wpa_cpt_tags' );


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
