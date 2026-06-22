<?php
/**
 * Trash Post — with attached-media choice.
 *
 * By default WordPress never deletes a post's attached media (gallery images and
 * uploads parented to the post). This module asks the admin, at trash time, how
 * to handle that media via a three-way choice:
 *
 *   1. Trash just the post           — native behaviour, media untouched.
 *   2. Trash + delete media on purge — media is deleted when the post is later
 *                                      permanently deleted from Trash.
 *   3. Delete everything now         — bypass Trash; delete the post and all of
 *                                      its attached media immediately.
 *
 * The whole thing hangs off ONE post-meta flag:
 *
 *   _tiagsspace_delete_attached_media = '1'
 *
 * set for choices 2 and 3, and read by a single before_delete_post handler that
 * deletes every attachment whose post_parent is the post. Choice 2 trashes (the
 * flag waits in the trashed post); choice 3 force-deletes right away (the flag is
 * read in the same request). The flag is cleared if the post is restored.
 *
 * The choice UI is a shared JS modal (library/js/trash-post-modal.js) wired into
 * three entry points: the list-table row "Trash" link, the bulk "Move to Trash"
 * action, and the Gutenberg editor "Move to trash" button.
 *
 * Mirrors the conventions in library/duplicate-post.php (per-post nonced
 * admin-post.php URLs, row/bulk actions, admin_notices, enqueue + localize).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta flag: when truthy, the post's attached media is deleted on permanent
 * deletion.
 */
const TIAGSSPACE_DELETE_MEDIA_META = '_tiagsspace_delete_attached_media';

/**
 * Whether the current user may trash / delete a given post.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function tiagsspace_can_trash( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	return current_user_can( 'delete_post', $post_id );
}

/**
 * Build a signed admin-post.php URL for a trash/delete-with-media action.
 *
 * @param int    $post_id Post ID.
 * @param string $action  'trash' or 'delete'.
 * @return string
 */
function tiagsspace_trash_action_url( $post_id, $action ) {
	$post_id  = (int) $post_id;
	$wp_action = 'delete' === $action
		? 'tiagsspace_delete_with_media'
		: 'tiagsspace_trash_with_media';
	$nonce_action = 'delete' === $action
		? 'tiagsspace_delete_media_' . $post_id
		: 'tiagsspace_trash_media_' . $post_id;

	$url = add_query_arg(
		array(
			'action' => $wp_action,
			'post'   => $post_id,
		),
		admin_url( 'admin-post.php' )
	);

	return wp_nonce_url( $url, $nonce_action );
}

/**
 * Convenience wrappers used by the row-action and editor enqueues.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function tiagsspace_trash_with_media_url( $post_id ) {
	return tiagsspace_trash_action_url( $post_id, 'trash' );
}

/**
 * @param int $post_id Post ID.
 * @return string
 */
function tiagsspace_delete_with_media_url( $post_id ) {
	return tiagsspace_trash_action_url( $post_id, 'delete' );
}

/**
 * The "All Posts" list-table URL for a post's type.
 *
 * Used as the post-action redirect so deleting/trashing from the editor lands on
 * the list rather than the now-gone edit screen. Capture before deletion.
 *
 * @param int $post_id Post ID (must still exist).
 * @return string
 */
function tiagsspace_post_type_list_url( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( ! $post_type || 'post' === $post_type ) {
		return admin_url( 'edit.php' );
	}

	return add_query_arg( 'post_type', $post_type, admin_url( 'edit.php' ) );
}

/* -------------------------------------------------------------------------
 * Media deletion — the shared core.
 * ---------------------------------------------------------------------- */

/**
 * Count the attachments parented to a post.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function tiagsspace_count_post_attachments( $post_id ) {
	$ids = get_children(
		array(
			'post_parent' => (int) $post_id,
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);

	return count( $ids );
}

/**
 * Permanently delete every attachment parented to a post (files included).
 *
 * @param int $post_id Post ID.
 * @return int Number of attachments deleted.
 */
function tiagsspace_delete_post_attachments( $post_id ) {
	$ids = get_children(
		array(
			'post_parent' => (int) $post_id,
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);

	$count = 0;

	foreach ( $ids as $aid ) {
		if ( current_user_can( 'delete_post', $aid ) && wp_delete_attachment( $aid, true ) ) {
			$count++;
		}
	}

	return $count;
}

/**
 * Delete attached media when a flagged post is permanently deleted.
 *
 * Fires for both choice 2 (later purge) and choice 3 (immediate force delete).
 * Attachments never carry the flag, so there is no recursion concern.
 *
 * @param int $post_id Post being deleted.
 */
function tiagsspace_maybe_delete_attached_media( $post_id ) {
	if ( get_post_meta( $post_id, TIAGSSPACE_DELETE_MEDIA_META, true ) ) {
		tiagsspace_delete_post_attachments( $post_id );
	}
}
add_action( 'before_delete_post', 'tiagsspace_maybe_delete_attached_media' );

/**
 * Safety: clear the flag if a post is restored from Trash, so a later native
 * permanent-delete doesn't silently take the media with it.
 *
 * @param int $post_id Restored post ID.
 */
function tiagsspace_clear_delete_media_flag( $post_id ) {
	delete_post_meta( $post_id, TIAGSSPACE_DELETE_MEDIA_META );
}
add_action( 'untrashed_post', 'tiagsspace_clear_delete_media_flag' );

/* -------------------------------------------------------------------------
 * Single-post handlers (admin-post.php) — driven by the row link / editor.
 * ---------------------------------------------------------------------- */

/**
 * Handle "Trash + delete media on permanent delete" for a single post.
 */
function tiagsspace_handle_trash_with_media() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id ) {
		wp_die( esc_html__( 'No post to trash.', 'tiagsspace' ) );
	}

	check_admin_referer( 'tiagsspace_trash_media_' . $post_id );

	if ( ! tiagsspace_can_trash( $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to trash this post.', 'tiagsspace' ) );
	}

	$list = tiagsspace_post_type_list_url( $post_id );

	update_post_meta( $post_id, TIAGSSPACE_DELETE_MEDIA_META, '1' );
	wp_trash_post( $post_id );

	wp_safe_redirect( add_query_arg( 'tiagsspace_trashed', 'media', $list ) );
	exit;
}
add_action( 'admin_post_tiagsspace_trash_with_media', 'tiagsspace_handle_trash_with_media' );

/**
 * Handle "Permanently delete the post and its media now" for a single post.
 */
function tiagsspace_handle_delete_with_media() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id ) {
		wp_die( esc_html__( 'No post to delete.', 'tiagsspace' ) );
	}

	check_admin_referer( 'tiagsspace_delete_media_' . $post_id );

	if ( ! tiagsspace_can_trash( $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to delete this post.', 'tiagsspace' ) );
	}

	$media_count = tiagsspace_count_post_attachments( $post_id );
	$list        = tiagsspace_post_type_list_url( $post_id );

	update_post_meta( $post_id, TIAGSSPACE_DELETE_MEDIA_META, '1' );
	wp_delete_post( $post_id, true );

	wp_safe_redirect(
		add_query_arg(
			array(
				'tiagsspace_deleted' => 1,
				'tiagsspace_media'   => $media_count,
			),
			$list
		)
	);
	exit;
}
add_action( 'admin_post_tiagsspace_delete_with_media', 'tiagsspace_handle_delete_with_media' );

/* -------------------------------------------------------------------------
 * Bulk handlers — driven by the bulk "Move to Trash" modal.
 *
 * The two custom bulk actions are NOT shown in the dropdown; the JS modal
 * rewrites the selected "trash" action to one of these and submits. Core has
 * already verified the bulk-posts nonce by the time these run.
 * ---------------------------------------------------------------------- */

/**
 * Register the bulk handlers for every post type with an admin list table.
 */
function tiagsspace_register_bulk_trash() {
	foreach ( get_post_types( array( 'show_ui' => true ) ) as $type ) {
		add_filter( "handle_bulk_actions-edit-{$type}", 'tiagsspace_handle_bulk_trash_with_media', 10, 3 );
	}
}
add_action( 'admin_init', 'tiagsspace_register_bulk_trash' );

/**
 * Process the two custom bulk actions.
 *
 * @param string $redirect_to Redirect URL.
 * @param string $doaction    Selected action.
 * @param int[]  $post_ids    Selected post IDs.
 * @return string
 */
function tiagsspace_handle_bulk_trash_with_media( $redirect_to, $doaction, $post_ids ) {
	$delete = 'tiagsspace_delete_with_media' === $doaction;
	$trash  = 'tiagsspace_trash_with_media' === $doaction;

	if ( ! $delete && ! $trash ) {
		return $redirect_to;
	}

	$posts = 0;
	$media = 0;

	foreach ( $post_ids as $post_id ) {
		if ( ! tiagsspace_can_trash( $post_id ) ) {
			continue;
		}

		update_post_meta( $post_id, TIAGSSPACE_DELETE_MEDIA_META, '1' );

		if ( $delete ) {
			$media += tiagsspace_count_post_attachments( $post_id );
			wp_delete_post( $post_id, true );
		} else {
			wp_trash_post( $post_id );
		}

		$posts++;
	}

	if ( $delete ) {
		return add_query_arg(
			array(
				'tiagsspace_deleted' => $posts,
				'tiagsspace_media'   => $media,
			),
			$redirect_to
		);
	}

	return add_query_arg( 'tiagsspace_trashed_bulk', $posts, $redirect_to );
}

/* -------------------------------------------------------------------------
 * Admin notices.
 * ---------------------------------------------------------------------- */

/**
 * Show feedback after a trash/delete-with-media action.
 */
function tiagsspace_trash_admin_notice() {
	if ( isset( $_GET['tiagsspace_trashed'] ) && 'media' === $_GET['tiagsspace_trashed'] ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Post moved to Trash — its attached media will be deleted when the post is permanently deleted.', 'tiagsspace' )
		);
	}

	if ( isset( $_GET['tiagsspace_trashed_bulk'] ) ) {
		$posts = absint( $_GET['tiagsspace_trashed_bulk'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: number of posts. */
					_n(
						'%s post moved to Trash — its attached media will be deleted on permanent deletion.',
						'%s posts moved to Trash — their attached media will be deleted on permanent deletion.',
						$posts,
						'tiagsspace'
					),
					number_format_i18n( $posts )
				)
			)
		);
	}

	if ( isset( $_GET['tiagsspace_deleted'] ) ) {
		$posts = absint( $_GET['tiagsspace_deleted'] );
		$media = isset( $_GET['tiagsspace_media'] ) ? absint( $_GET['tiagsspace_media'] ) : 0;
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: number of posts, 2: number of attachments. */
					__( 'Permanently deleted %1$s and %2$s.', 'tiagsspace' ),
					sprintf(
						/* translators: %s: number of posts. */
						_n( '%s post', '%s posts', $posts, 'tiagsspace' ),
						number_format_i18n( $posts )
					),
					sprintf(
						/* translators: %s: number of attachments. */
						_n( '%s attachment', '%s attachments', $media, 'tiagsspace' ),
						number_format_i18n( $media )
					)
				)
			)
		);
	}
}
add_action( 'admin_notices', 'tiagsspace_trash_admin_notice' );

/* -------------------------------------------------------------------------
 * Row-action data injection.
 * ---------------------------------------------------------------------- */

/**
 * Attach the two per-post action URLs to the native "Trash" row link so the
 * modal can read them. Runs at priority 11 (after core adds the trash action).
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Current post.
 * @return array
 */
function tiagsspace_add_trash_data_attributes( $actions, $post ) {
	if ( empty( $actions['trash'] ) || ! tiagsspace_can_trash( $post->ID ) ) {
		return $actions;
	}

	// Already in Trash: the row shows "Restore / Delete Permanently", not "Trash".
	if ( 'trash' === $post->post_status ) {
		return $actions;
	}

	$data = sprintf(
		'<a data-tiagsspace-trash-media="%s" data-tiagsspace-delete-now="%s" ',
		esc_url( tiagsspace_trash_with_media_url( $post->ID ) ),
		esc_url( tiagsspace_delete_with_media_url( $post->ID ) )
	);

	$actions['trash'] = preg_replace( '/^<a /', $data, $actions['trash'], 1 );

	return $actions;
}
add_filter( 'post_row_actions', 'tiagsspace_add_trash_data_attributes', 11, 2 );
add_filter( 'page_row_actions', 'tiagsspace_add_trash_data_attributes', 11, 2 );

/* -------------------------------------------------------------------------
 * Script enqueues + localization.
 * ---------------------------------------------------------------------- */

/**
 * Strings shared by every modal entry point.
 *
 * @return array
 */
function tiagsspace_trash_modal_strings() {
	return array(
		'title'      => __( 'Move to Trash', 'tiagsspace' ),
		'intro'      => __( 'What should happen to the media attached to this post?', 'tiagsspace' ),
		'introBulk'  => __( 'What should happen to the media attached to the selected posts?', 'tiagsspace' ),
		'trashOnly'  => __( 'Trash just the post', 'tiagsspace' ),
		'trashMedia' => __( 'Trash + delete its media when permanently deleted', 'tiagsspace' ),
		'deleteNow'  => __( 'Permanently delete the post and its media now', 'tiagsspace' ),
		'cancel'     => __( 'Cancel', 'tiagsspace' ),
		'confirmNow' => __( 'This permanently deletes the post and ALL its attached media right now. This cannot be undone. Continue?', 'tiagsspace' ),
		'bulkTrash'  => 'tiagsspace_trash_with_media',
		'bulkDelete' => 'tiagsspace_delete_with_media',
	);
}

/**
 * Enqueue the modal on post-list screens.
 *
 * @param string $hook Current admin page.
 */
function tiagsspace_enqueue_trash_modal_list( $hook ) {
	if ( 'edit.php' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'tiagsspace-trash-modal',
		get_template_directory_uri() . '/library/js/trash-post-modal.js',
		array( 'jquery' ),
		'1.0',
		true
	);

	wp_localize_script(
		'tiagsspace-trash-modal',
		'tiagsspaceTrash',
		tiagsspace_trash_modal_strings()
	);
}
add_action( 'admin_enqueue_scripts', 'tiagsspace_enqueue_trash_modal_list' );

/**
 * Enqueue the modal in the block editor, with the per-post URLs.
 */
function tiagsspace_enqueue_trash_modal_editor() {
	$post_id = get_the_ID();

	if ( ! $post_id || ! tiagsspace_can_trash( $post_id ) ) {
		return;
	}

	wp_enqueue_script(
		'tiagsspace-trash-modal',
		get_template_directory_uri() . '/library/js/trash-post-modal.js',
		array( 'jquery', 'wp-data' ),
		'1.0',
		true
	);

	wp_localize_script(
		'tiagsspace-trash-modal',
		'tiagsspaceTrash',
		array_merge(
			tiagsspace_trash_modal_strings(),
			array(
				'editorTrashUrl'  => tiagsspace_trash_with_media_url( $post_id ),
				'editorDeleteUrl' => tiagsspace_delete_with_media_url( $post_id ),
			)
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'tiagsspace_enqueue_trash_modal_editor' );
