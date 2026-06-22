<?php
/**
 * Duplicate Post
 *
 * Clone any post (any post type, any status) into a fresh DRAFT, carrying over
 * content, all post meta (every ACF field, featured image, plugin meta) and all
 * taxonomy terms. Attachments are intentionally NOT re-parented — a single
 * attachment cannot have two parents, so the original keeps its media and the
 * duplicate starts with an empty attachment-driven gallery. Meta that stores
 * explicit attachment IDs (featured image, ACF galleries, etc.) is copied as-is
 * and keeps referencing the original's media.
 *
 * The core logic lives in tiagsspace_duplicate_post() so future features can
 * call it directly. On top of it we expose three UI entry points:
 *   - a per-row "Duplicate" link in every post-list table,
 *   - a "Duplicate" bulk action,
 *   - a "Duplicate" icon in the Gutenberg editor header.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post types that get the duplicate UI (core post + theme CPTs).
 *
 * @return string[]
 */
function tiagsspace_duplicatable_post_types() {
	$types = array( 'post', 'films', 'dusk', 'hyper', '4k-lento', 'log', 'cityburns' );

	/**
	 * Filter the list of post types that expose the Duplicate UI.
	 *
	 * @param string[] $types Post type slugs.
	 */
	return apply_filters( 'tiagsspace_duplicatable_post_types', $types );
}

/**
 * Post meta keys that must NOT be carried over to the duplicate.
 *
 * @return string[]
 */
function tiagsspace_duplicate_meta_denylist() {
	$keys = array(
		'_edit_lock',
		'_edit_last',
		'_wp_old_slug',
		'_wp_old_date',
		'_wp_trash_meta_status',
		'_wp_trash_meta_time',
		'_pingme',
		'_encloseme',
	);

	/**
	 * Filter the meta keys excluded when duplicating a post.
	 *
	 * @param string[] $keys Meta keys to skip.
	 */
	return apply_filters( 'tiagsspace_duplicate_meta_denylist', $keys );
}

/**
 * Build a unique "… — copy" title for a duplicate.
 *
 * Strips any existing copy-suffix first so duplicating a copy yields
 * "Foo — copy 2" rather than "Foo — copy — copy". Walks the counter until it
 * finds a title not already used by a post of the same type (any status).
 *
 * @param string $base      Source post title.
 * @param string $post_type Post type the duplicate will belong to.
 * @return string
 */
function tiagsspace_duplicate_title( $base, $post_type ) {
	// Remove a trailing " — copy" or " — copy N" so copies don't stack.
	$base = preg_replace( '/\s+—\s+copy(?:\s+\d+)?$/u', '', $base );
	$base = trim( $base );

	$counter = 1;

	do {
		$candidate = ( 1 === $counter )
			? $base . ' — copy'
			: $base . ' — copy ' . $counter;

		$existing = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'any',
				'title'                  => $candidate,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => true,
			)
		);

		$counter++;
	} while ( ! empty( $existing ) && $counter < 1000 );

	return $candidate;
}

/**
 * Whether the current user may duplicate a given post.
 *
 * @param int $post_id Source post ID.
 * @return bool
 */
function tiagsspace_can_duplicate( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return false;
	}

	$pto = get_post_type_object( $post->post_type );

	if ( ! $pto ) {
		return false;
	}

	return current_user_can( $pto->cap->create_posts );
}

/**
 * Duplicate a post into a new draft. Reusable core function.
 *
 * @param int   $source_post_id Source post ID.
 * @param array $overrides      Optional wp_insert_post fields to override
 *                              (e.g. post_status, post_title, post_author).
 * @return int|WP_Error New post ID on success, WP_Error on failure.
 */
function tiagsspace_duplicate_post( $source_post_id, $overrides = array() ) {
	$source = get_post( $source_post_id );

	if ( ! $source ) {
		return new WP_Error( 'tiagsspace_duplicate_no_source', __( 'Source post not found.', 'tiagsspace' ) );
	}

	$new_post = array(
		'post_title'     => tiagsspace_duplicate_title( $source->post_title, $source->post_type ),
		'post_content'   => $source->post_content,
		'post_excerpt'   => $source->post_excerpt,
		'post_type'      => $source->post_type,
		'post_status'    => 'draft',
		'post_author'    => get_current_user_id() ? get_current_user_id() : $source->post_author,
		'comment_status' => $source->comment_status,
		'ping_status'    => $source->ping_status,
		'menu_order'     => $source->menu_order,
		'post_parent'    => $source->post_parent,
		'post_password'  => $source->post_password,
		'to_ping'        => $source->to_ping,
		'pinged'         => $source->pinged,
	);

	// Caller overrides win (status, title, author, …); fresh slug is generated.
	$new_post = array_merge( $new_post, $overrides );
	unset( $new_post['ID'], $new_post['post_name'] );

	$new_id = wp_insert_post( $new_post, true );

	if ( is_wp_error( $new_id ) ) {
		return $new_id;
	}

	tiagsspace_duplicate_copy_meta( $source->ID, $new_id );
	tiagsspace_duplicate_copy_terms( $source, $new_id );

	/**
	 * Fires after a post has been duplicated.
	 *
	 * @param int $new_id    The new (duplicate) post ID.
	 * @param int $source_id The original post ID.
	 */
	do_action( 'tiagsspace_post_duplicated', $new_id, $source->ID );

	return $new_id;
}

/**
 * Copy all post meta from source to destination, minus the internal denylist.
 *
 * Reading raw meta and re-adding it preserves multiple values per key and
 * copies ACF's value meta together with its "_fieldname" field-key reference,
 * keeping ACF fields intact.
 *
 * @param int $source_id Source post ID.
 * @param int $dest_id   Destination post ID.
 */
function tiagsspace_duplicate_copy_meta( $source_id, $dest_id ) {
	$denylist = tiagsspace_duplicate_meta_denylist();
	$meta     = get_post_meta( $source_id );

	if ( empty( $meta ) || ! is_array( $meta ) ) {
		return;
	}

	foreach ( $meta as $key => $values ) {
		if ( in_array( $key, $denylist, true ) ) {
			continue;
		}

		foreach ( $values as $value ) {
			// get_post_meta() returns raw (serialized) strings — unslash + unserialize
			// so add_post_meta() re-serializes cleanly.
			add_post_meta( $dest_id, $key, maybe_unserialize( wp_unslash( $value ) ) );
		}
	}
}

/**
 * Copy every taxonomy term assignment from source to destination.
 *
 * @param WP_Post $source  Source post object.
 * @param int     $dest_id Destination post ID.
 */
function tiagsspace_duplicate_copy_terms( $source, $dest_id ) {
	$taxonomies = get_object_taxonomies( $source->post_type );

	foreach ( $taxonomies as $taxonomy ) {
		$term_ids = wp_get_object_terms( $source->ID, $taxonomy, array( 'fields' => 'ids' ) );

		if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
			continue;
		}

		wp_set_object_terms( $dest_id, $term_ids, $taxonomy );
	}
}

/**
 * Build a signed admin-post.php URL that duplicates a given post.
 *
 * @param int $post_id Source post ID.
 * @return string
 */
function tiagsspace_duplicate_url( $post_id ) {
	$url = add_query_arg(
		array(
			'action' => 'tiagsspace_duplicate_post',
			'post'   => (int) $post_id,
		),
		admin_url( 'admin-post.php' )
	);

	return wp_nonce_url( $url, 'tiagsspace_duplicate_' . (int) $post_id );
}

/**
 * Handle the single-post duplicate request (row link + editor icon).
 */
function tiagsspace_handle_duplicate_post() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id ) {
		wp_die( esc_html__( 'No post to duplicate.', 'tiagsspace' ) );
	}

	check_admin_referer( 'tiagsspace_duplicate_' . $post_id );

	if ( ! tiagsspace_can_duplicate( $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to duplicate this post.', 'tiagsspace' ) );
	}

	$new_id = tiagsspace_duplicate_post( $post_id );

	if ( is_wp_error( $new_id ) ) {
		$back = add_query_arg( 'tiagsspace_dupe', 'error', wp_get_referer() ? wp_get_referer() : admin_url() );
		wp_safe_redirect( $back );
		exit;
	}

	wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
	exit;
}
add_action( 'admin_post_tiagsspace_duplicate_post', 'tiagsspace_handle_duplicate_post' );

/**
 * Add a "Duplicate" link to post-list row actions.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Current post.
 * @return array
 */
function tiagsspace_add_duplicate_row_action( $actions, $post ) {
	if ( ! in_array( $post->post_type, tiagsspace_duplicatable_post_types(), true ) ) {
		return $actions;
	}

	if ( ! tiagsspace_can_duplicate( $post->ID ) ) {
		return $actions;
	}

	$actions['tiagsspace_duplicate'] = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
		esc_url( tiagsspace_duplicate_url( $post->ID ) ),
		esc_attr( sprintf( __( 'Duplicate "%s" (opens in a new tab)', 'tiagsspace' ), get_the_title( $post ) ) ),
		esc_html__( 'Duplicate', 'tiagsspace' )
	);

	return $actions;
}
add_filter( 'post_row_actions', 'tiagsspace_add_duplicate_row_action', 10, 2 );
add_filter( 'page_row_actions', 'tiagsspace_add_duplicate_row_action', 10, 2 );

/**
 * Register the "Duplicate" bulk action + its handler + admin notice for every
 * duplicatable post type.
 */
function tiagsspace_register_bulk_duplicate() {
	foreach ( tiagsspace_duplicatable_post_types() as $type ) {
		add_filter( "bulk_actions-edit-{$type}", 'tiagsspace_add_bulk_duplicate_action' );
		add_filter( "handle_bulk_actions-edit-{$type}", 'tiagsspace_handle_bulk_duplicate', 10, 3 );
	}
}
add_action( 'admin_init', 'tiagsspace_register_bulk_duplicate' );

/**
 * Add "Duplicate" to the Bulk Actions dropdown.
 *
 * @param array $actions Bulk actions.
 * @return array
 */
function tiagsspace_add_bulk_duplicate_action( $actions ) {
	$actions['tiagsspace_duplicate'] = __( 'Duplicate', 'tiagsspace' );
	return $actions;
}

/**
 * Process the "Duplicate" bulk action.
 *
 * @param string $redirect_to Redirect URL.
 * @param string $doaction    The selected action.
 * @param int[]  $post_ids    Selected post IDs.
 * @return string
 */
function tiagsspace_handle_bulk_duplicate( $redirect_to, $doaction, $post_ids ) {
	if ( 'tiagsspace_duplicate' !== $doaction ) {
		return $redirect_to;
	}

	$count = 0;

	foreach ( $post_ids as $post_id ) {
		if ( ! tiagsspace_can_duplicate( $post_id ) ) {
			continue;
		}

		$new_id = tiagsspace_duplicate_post( $post_id );

		if ( ! is_wp_error( $new_id ) ) {
			$count++;
		}
	}

	return add_query_arg( 'tiagsspace_dupe_count', $count, $redirect_to );
}

/**
 * Show admin notices for duplicate results.
 */
function tiagsspace_duplicate_admin_notice() {
	if ( isset( $_GET['tiagsspace_dupe_count'] ) ) {
		$count = absint( $_GET['tiagsspace_dupe_count'] );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: number of posts duplicated. */
					_n( '%s post duplicated.', '%s posts duplicated.', $count, 'tiagsspace' ),
					number_format_i18n( $count )
				)
			)
		);
	}

	if ( isset( $_GET['tiagsspace_dupe'] ) && 'error' === $_GET['tiagsspace_dupe'] ) {
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Could not duplicate the post.', 'tiagsspace' )
		);
	}
}
add_action( 'admin_notices', 'tiagsspace_duplicate_admin_notice' );

/**
 * Enqueue the editor-header "Duplicate" icon script (Gutenberg only).
 */
function tiagsspace_enqueue_editor_duplicate_button() {
	$post_id = get_the_ID();

	if ( ! $post_id || ! tiagsspace_can_duplicate( $post_id ) ) {
		return;
	}

	wp_enqueue_script(
		'editor-duplicate-button',
		get_template_directory_uri() . '/library/js/editor-duplicate-button.js',
		array( 'wp-data' ),
		'1.0',
		true
	);

	wp_localize_script(
		'editor-duplicate-button',
		'tiagsspaceDuplicate',
		array(
			'url' => tiagsspace_duplicate_url( $post_id ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'tiagsspace_enqueue_editor_duplicate_button' );

/**
 * AJAX: duplicate a post and move the selected attachments onto the copy.
 *
 * Used by the "Add to a duplicated post" call-to-action in the media modal.
 * Selected media are re-parented (post_parent → new post) — they move off the
 * original, since an attachment can only have one parent. Their menu_order is
 * left untouched so the copy keeps the original gallery order.
 */
function tiagsspace_ajax_duplicate_to_post() {
	check_ajax_referer( 'tiagsspace_duplicate_to_post', '_nonce' );

	$source_id      = isset( $_POST['source_id'] ) ? absint( $_POST['source_id'] ) : 0;
	$attachment_ids = isset( $_POST['attachment_ids'] ) ? (array) wp_unslash( $_POST['attachment_ids'] ) : array();
	$attachment_ids = array_filter( array_map( 'absint', $attachment_ids ) );

	if ( ! $source_id || ! tiagsspace_can_duplicate( $source_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tiagsspace' ) ) );
	}

	$new_id = tiagsspace_duplicate_post( $source_id );

	if ( is_wp_error( $new_id ) ) {
		wp_send_json_error( array( 'message' => $new_id->get_error_message() ) );
	}

	$moved = tiagsspace_move_attachments_to_post( $attachment_ids, $new_id, true );

	wp_send_json_success(
		array(
			'edit_url' => get_edit_post_link( $new_id, 'raw' ),
			'moved'    => $moved,
		)
	);
}
add_action( 'wp_ajax_tiagsspace_duplicate_to_post', 'tiagsspace_ajax_duplicate_to_post' );

/**
 * Re-parent attachments onto a destination post.
 *
 * Each attachment moves off its current parent (one attachment can only have
 * one parent). menu_order is left untouched so the destination keeps the
 * attachments' existing gallery order. Shared by the media-modal AJAX handler
 * and the MCP duplicate ability.
 *
 * When $set_featured_from_first is true, the first moved attachment that is an
 * image becomes the destination's featured image (overriding any inherited
 * one), so the copy gets a sensible cover from its new gallery.
 *
 * @param int[] $attachment_ids          Attachment IDs to move (in order).
 * @param int   $dest_post_id            Destination post ID.
 * @param bool  $set_featured_from_first Set the first moved image as featured.
 * @return int Number of attachments successfully re-parented.
 */
function tiagsspace_move_attachments_to_post( $attachment_ids, $dest_post_id, $set_featured_from_first = false ) {
	$moved          = 0;
	$first_image_id = 0;

	foreach ( (array) $attachment_ids as $att_id ) {
		$att_id = absint( $att_id );

		if ( ! $att_id || 'attachment' !== get_post_type( $att_id ) ) {
			continue;
		}

		if ( ! current_user_can( 'edit_post', $att_id ) ) {
			continue;
		}

		// Re-parent only; menu_order is preserved to keep the gallery order.
		$updated = wp_update_post(
			array(
				'ID'          => $att_id,
				'post_parent' => $dest_post_id,
			),
			true
		);

		if ( ! is_wp_error( $updated ) ) {
			$moved++;

			if ( ! $first_image_id && wp_attachment_is_image( $att_id ) ) {
				$first_image_id = $att_id;
			}
		}
	}

	if ( $set_featured_from_first && $first_image_id ) {
		set_post_thumbnail( $dest_post_id, $first_image_id );
	}

	return $moved;
}

/**
 * Enqueue the "Add to a duplicated post" media-modal button on post screens.
 */
function tiagsspace_enqueue_media_duplicate_to_post() {
	$screen = get_current_screen();

	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	wp_enqueue_script(
		'media-duplicate-to-post',
		get_template_directory_uri() . '/library/js/media-duplicate-to-post.js',
		array( 'jquery', 'media-views' ),
		'1.3',
		true
	);

	wp_localize_script(
		'media-duplicate-to-post',
		'tiagsspaceMediaDuplicate',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tiagsspace_duplicate_to_post' ),
			'label'   => __( 'Add to a duplicated post', 'tiagsspace' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'tiagsspace_enqueue_media_duplicate_to_post' );

/* -------------------------------------------------------------------------
 * MCP ability: duplicate a post over the WordPress Abilities API.
 *
 * Surfaces as the MCP tool `mcp-content-duplicate-post`. Reuses the namespace
 * and category registered by the mcp-content-abilities plugin. The
 * wp_abilities_api_init action only fires when the Abilities API is present,
 * so this is a no-op when the MCP stack is inactive.
 * ---------------------------------------------------------------------- */

/**
 * Register the duplicate-post ability.
 */
function tiagsspace_register_duplicate_ability() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	wp_register_ability(
		'mcp-content/duplicate-post',
		array(
			'label'               => __( 'Duplicate post', 'tiagsspace' ),
			'description'         => __( 'Duplicate a post (any type, any status) into a new draft, copying all content, post meta (ACF fields, featured image) and taxonomy terms. Optionally move selected media attachments onto the copy (they are re-parented off the original). Returns the new post id and edit URL.', 'tiagsspace' ),
			'category'            => 'content-management',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'source_post_id'      => array(
						'type'        => 'integer',
						'description' => 'ID of the post to duplicate.',
					),
					'title'               => array(
						'type'        => 'string',
						'description' => 'Optional title override for the copy. Defaults to "<original> — copy".',
					),
					'status'              => array(
						'type'        => 'string',
						'description' => 'Optional post status for the copy: draft, publish, pending, private. Default "draft".',
					),
					'move_attachment_ids' => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'integer' ),
						'description' => 'Optional attachment IDs to re-parent onto the copy. They move off the original (an attachment can only have one parent); their gallery order is preserved.',
					),
				),
				'required'   => array( 'source_post_id' ),
			),
			'permission_callback' => function ( $input ) {
				$id = is_array( $input ) ? (int) ( $input['source_post_id'] ?? 0 ) : 0;
				return $id ? tiagsspace_can_duplicate( $id ) : false;
			},
			'meta'                => array(
				'mcp'         => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
			'execute_callback'    => function ( $input ) {
				$input = is_array( $input ) ? $input : array();
				$id    = (int) ( $input['source_post_id'] ?? 0 );

				if ( ! $id || ! get_post( $id ) ) {
					return new WP_Error( 'not_found', __( 'Source post not found.', 'tiagsspace' ), array( 'status' => 404 ) );
				}

				$overrides = array();
				if ( ! empty( $input['title'] ) ) {
					$overrides['post_title'] = sanitize_text_field( $input['title'] );
				}
				if ( ! empty( $input['status'] ) ) {
					$overrides['post_status'] = sanitize_key( $input['status'] );
				}

				$new_id = tiagsspace_duplicate_post( $id, $overrides );

				if ( is_wp_error( $new_id ) ) {
					return $new_id;
				}

				$moved = 0;
				if ( ! empty( $input['move_attachment_ids'] ) && is_array( $input['move_attachment_ids'] ) ) {
					$att_ids = array_filter( array_map( 'absint', $input['move_attachment_ids'] ) );
					$moved   = tiagsspace_move_attachments_to_post( $att_ids, $new_id, true );
				}

				$post = get_post( $new_id );

				return array(
					'id'                => (int) $new_id,
					'title'             => $post->post_title,
					'status'            => $post->post_status,
					'link'              => (string) get_permalink( $new_id ),
					'edit'              => (string) get_edit_post_link( $new_id, 'raw' ),
					'moved_attachments' => (int) $moved,
				);
			},
		)
	);
}
add_action( 'wp_abilities_api_init', 'tiagsspace_register_duplicate_ability' );

/**
 * Surface the duplicate-post ability as a first-class MCP tool on the default
 * server (mirrors how mcp-content-abilities exposes its own abilities).
 */
function tiagsspace_register_duplicate_mcp_tool( $config ) {
	if ( ! is_array( $config ) ) {
		return $config;
	}

	$config['tools'] = array_values(
		array_unique(
			array_merge(
				isset( $config['tools'] ) && is_array( $config['tools'] ) ? $config['tools'] : array(),
				array( 'mcp-content/duplicate-post' )
			)
		)
	);

	return $config;
}
add_filter( 'mcp_adapter_default_server_config', 'tiagsspace_register_duplicate_mcp_tool' );
