<?php
/**
 * Add media to an existing post.
 *
 * Adds an "Add to existing post" call-to-action to the post-editor "Add media"
 * modal. The editor selects one or more attachments, searches for any existing
 * post by title (any post type, any status), and picks one; the selected
 * attachments are re-parented onto that post.
 *
 * This is intentionally its OWN module (not part of duplicate-post.php): the
 * feature has nothing to do with duplicating — it just moves attachments onto
 * an existing post.
 *
 * Semantics:
 *   - Move, not copy. An attachment has a single parent, so re-parenting moves
 *     it OFF the current post. Works for any attachment type (image/video/…).
 *   - The target's featured image is left untouched.
 *   - Moved attachments keep their relative order and are appended AFTER the
 *     destination's existing attachments (see the reparent-append helper).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Re-parent attachments onto a destination post, appended to the end.
 *
 * The incoming IDs are treated as already being in the intended relative order
 * (the media modal sends them sorted by gallery order / menu_order). They are
 * re-parented onto $dest_post_id and given fresh menu_order values that follow
 * the destination's current highest attachment menu_order, so they land after
 * everything already attached there rather than interleaving with it.
 *
 * No featured-image side effect (unlike duplicate-post.php's
 * tiagsspace_move_attachments_to_post(), whose contract differs).
 *
 * @param int[] $attachment_ids Attachment IDs, in the order they should end up.
 * @param int   $dest_post_id   Destination post ID.
 * @return int Number of attachments successfully re-parented.
 */
function tiagsspace_media_attach_reparent_append( $attachment_ids, $dest_post_id ) {
	global $wpdb;

	$dest_post_id = absint( $dest_post_id );

	if ( ! $dest_post_id ) {
		return 0;
	}

	// Highest menu_order already used by the destination's attachments; new
	// arrivals are appended above it, preserving their given order.
	$base = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'attachment'",
			$dest_post_id
		)
	);

	$moved = 0;
	$order = 0;

	foreach ( (array) $attachment_ids as $att_id ) {
		$att_id = absint( $att_id );

		if ( ! $att_id || 'attachment' !== get_post_type( $att_id ) ) {
			continue;
		}

		if ( ! current_user_can( 'edit_post', $att_id ) ) {
			continue;
		}

		$order++;

		$updated = wp_update_post(
			array(
				'ID'          => $att_id,
				'post_parent' => $dest_post_id,
				'menu_order'  => $base + $order,
			),
			true
		);

		if ( ! is_wp_error( $updated ) ) {
			$moved++;
		}
	}

	return $moved;
}

/**
 * Post types eligible as an "Add to existing post" target.
 *
 * Every UI-editable post type minus attachments — covers post, page and all
 * theme CPTs, i.e. "any post type".
 *
 * @return string[]
 */
function tiagsspace_media_attach_target_post_types() {
	$types = get_post_types( array( 'show_ui' => true ), 'names' );
	unset( $types['attachment'] );

	/**
	 * Filter the post types searchable as an attach target.
	 *
	 * @param string[] $types Post type slugs.
	 */
	return apply_filters( 'tiagsspace_media_attach_target_post_types', array_values( $types ) );
}

/**
 * AJAX: search posts by title across every editable post type and any status.
 *
 * Title-only (not full-content) search so it behaves like "search by the title".
 */
function tiagsspace_media_attach_ajax_search() {
	check_ajax_referer( 'tiagsspace_media_attach_search', '_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tiagsspace' ) ) );
	}

	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$exclude  = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;

	if ( mb_strlen( $search ) < 2 ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	// Title-only match: a one-shot WHERE clause scoped to this query.
	$where_filter = static function ( $where ) use ( $search ) {
		global $wpdb;
		$like   = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $like );
		return $where;
	};

	add_filter( 'posts_where', $where_filter );

	$query = new WP_Query(
		array(
			'post_type'              => tiagsspace_media_attach_target_post_types(),
			'post_status'            => 'any',
			'posts_per_page'         => 20,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'post__not_in'           => $exclude ? array( $exclude ) : array(),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		)
	);

	remove_filter( 'posts_where', $where_filter );

	$results = array();

	foreach ( $query->posts as $post ) {
		$pto   = get_post_type_object( $post->post_type );
		$label = $pto && isset( $pto->labels->singular_name ) ? $pto->labels->singular_name : $post->post_type;
		$title = trim( $post->post_title );

		$results[] = array(
			'id'          => (int) $post->ID,
			'title'       => '' !== $title ? $title : __( '(no title)', 'tiagsspace' ),
			'type_label'  => $label,
			'status'      => $post->post_status,
			'is_viewable' => is_post_publicly_viewable( $post->ID ),
			'edit_url'    => get_edit_post_link( $post->ID, 'raw' ),
			'view_url'    => get_permalink( $post->ID ),
		);
	}

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_tiagsspace_media_attach_search', 'tiagsspace_media_attach_ajax_search' );

/**
 * AJAX: re-parent the selected attachments onto the chosen existing post.
 */
function tiagsspace_media_attach_ajax_attach() {
	check_ajax_referer( 'tiagsspace_media_attach_attach', '_nonce' );

	$target_id      = isset( $_POST['target_id'] ) ? absint( $_POST['target_id'] ) : 0;
	$attachment_ids = isset( $_POST['attachment_ids'] ) ? (array) wp_unslash( $_POST['attachment_ids'] ) : array();
	$attachment_ids = array_filter( array_map( 'absint', $attachment_ids ) );

	$target = $target_id ? get_post( $target_id ) : null;

	if ( ! $target || 'attachment' === $target->post_type || ! current_user_can( 'edit_post', $target_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tiagsspace' ) ) );
	}

	if ( empty( $attachment_ids ) ) {
		wp_send_json_error( array( 'message' => __( 'No media selected.', 'tiagsspace' ) ) );
	}

	$moved = tiagsspace_media_attach_reparent_append( $attachment_ids, $target_id );

	wp_send_json_success(
		array(
			'moved'    => $moved,
			'edit_url' => get_edit_post_link( $target_id, 'raw' ),
			'view_url' => is_post_publicly_viewable( $target_id ) ? get_permalink( $target_id ) : '',
		)
	);
}
add_action( 'wp_ajax_tiagsspace_media_attach_attach', 'tiagsspace_media_attach_ajax_attach' );

/**
 * Enqueue the media-modal "Add to existing post" script on post screens.
 */
function tiagsspace_media_attach_enqueue() {
	$screen = get_current_screen();

	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	$path = get_template_directory() . '/library/js/media-attach-to-post.js';

	wp_enqueue_script(
		'media-attach-to-post',
		get_template_directory_uri() . '/library/js/media-attach-to-post.js',
		array( 'jquery', 'media-views' ),
		file_exists( $path ) ? filemtime( $path ) : '1.0',
		true
	);

	wp_localize_script(
		'media-attach-to-post',
		'tiagsspaceMediaAttach',
		array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'searchNonce' => wp_create_nonce( 'tiagsspace_media_attach_search' ),
			'attachNonce' => wp_create_nonce( 'tiagsspace_media_attach_attach' ),
			'label'       => __( 'Add to existing post', 'tiagsspace' ),
			'i18n'        => array(
				'heading'     => __( 'Add %d item(s) to an existing post', 'tiagsspace' ),
				'placeholder' => __( 'Search posts by title…', 'tiagsspace' ),
				'hint'        => __( 'Type a title to search…', 'tiagsspace' ),
				'searching'   => __( 'Searching…', 'tiagsspace' ),
				'noMatches'   => __( 'No posts match “%s”.', 'tiagsspace' ),
				'searchError' => __( 'Search failed. Try again.', 'tiagsspace' ),
				'attachError' => __( 'Could not add the media to that post.', 'tiagsspace' ),
				'cancel'      => __( 'Cancel', 'tiagsspace' ),
				'close'       => __( 'Close', 'tiagsspace' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'tiagsspace_media_attach_enqueue' );
