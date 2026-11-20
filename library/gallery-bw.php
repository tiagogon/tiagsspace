<?php
/**
 * Gallery editor: save images in black and white, for everyone.
 *
 * The View ▾ menu of the front-end gallery editor previews images in black and
 * white on the editor's screen only (class tge-bw, nothing saved). Its last
 * item, "Save N in black and white…", sends the previewed images here, one per
 * request (AJAX tiagsspace_save_bw). For each image:
 *
 *   1. the best colour file (the unscaled original when there is one) is
 *      converted with WordPress's image editor plus grayscale()
 *      (library/gallery-bw-editors.php: Rec. 709 luma, like CSS grayscale(1));
 *   2. saved next to it as <name>-bw.<ext>, and every size is built again from
 *      it (<name>-bw-720x960.jpg …) by wp_create_image_subsizes();
 *   3. only once the new files exist, every colour file of the image is
 *      deleted: the old main file, its unscaled original, each size, and the
 *      backups of earlier edits in WordPress's image editor. No undo, by
 *      Tiago's choice (Nov 2026).
 *
 * The attachment keeps its ID, so its gallery position, hidden flag, featured
 * status, caption, alt text, ACF fields and queued social posts stay. The new
 * file names also get past the year-long media cache. Meta _tiagsspace_bw marks
 * a saved image; the editor leaves those out of the count.
 *
 * Afterwards: tiagsspace_gallery_touch_parent() (object cache + W3TC), and the
 * action tiagsspace_attachment_file_replaced( $id, $deleted_files ), on which
 * Visual Archive Search is asked to index the image again as new.
 */

defined( 'ABSPATH' ) || exit;

define( 'TIAGSSPACE_BW_META', '_tiagsspace_bw' );

/** Mime types that can be saved in black and white (GIF may be animated). */
function tiagsspace_bw_mime_types() {
	return array( 'image/jpeg', 'image/png', 'image/webp' );
}

/**
 * Only the editors with grayscale(), for one call (Imagick, else GD). The
 * sizes are made afterwards with the site's usual editors (EWWW's).
 */
function tiagsspace_bw_image_editors() {
	require_once __DIR__ . '/gallery-bw-editors.php';
	return array( 'Tiagsspace_Image_Editor_Imagick', 'Tiagsspace_Image_Editor_GD' );
}

/**
 * Every file of an attachment: main file, unscaled original, sizes, backups
 * of edits made in WordPress's image editor. Absolute paths.
 */
function tiagsspace_bw_files( $attachment_id, $main, $meta ) {
	$dir   = trailingslashit( dirname( $main ) );
	$files = array( $main );
	if ( ! empty( $meta['original_image'] ) ) {
		$files[] = $dir . wp_basename( $meta['original_image'] );
	}
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$files[] = $dir . wp_basename( $size['file'] );
		}
	}
	foreach ( (array) get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true ) as $size ) {
		if ( is_array( $size ) && ! empty( $size['file'] ) ) {
			$files[] = $dir . wp_basename( $size['file'] );
		}
	}
	return array_values( array_unique( $files ) );
}

/** URL of the full file and of every size, keyed by size name. */
function tiagsspace_bw_urls( $attachment_id ) {
	$urls = array( 'full' => wp_get_attachment_url( $attachment_id ) );
	$meta = wp_get_attachment_metadata( $attachment_id );
	foreach ( array_keys( (array) ( $meta['sizes'] ?? array() ) ) as $size ) {
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		if ( $src ) {
			$urls[ $size ] = $src[0];
		}
	}
	return $urls;
}

/**
 * Saves one image in black and white and deletes its colour files.
 *
 * @return array|WP_Error { id, file, replace: [ [ old url, new url ], … ] }
 */
function tiagsspace_save_attachment_bw( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	$mime          = get_post_mime_type( $attachment_id );

	if ( ! in_array( $mime, tiagsspace_bw_mime_types(), true ) ) {
		return new WP_Error( 'bw_type', __( 'Only JPEG, PNG and WebP images can be saved in black and white.', 'tiagsspace' ) );
	}
	if ( get_post_meta( $attachment_id, TIAGSSPACE_BW_META, true ) ) {
		return new WP_Error( 'bw_done', __( 'Already saved in black and white.', 'tiagsspace' ) );
	}

	$main = get_attached_file( $attachment_id );
	if ( ! $main || ! file_exists( $main ) ) {
		return new WP_Error( 'bw_missing', __( 'The image file is missing.', 'tiagsspace' ) );
	}
	$source = wp_get_original_image_path( $attachment_id );
	if ( ! $source || ! file_exists( $source ) ) {
		$source = $main;
	}

	$old_meta  = wp_get_attachment_metadata( $attachment_id );
	$old_urls  = tiagsspace_bw_urls( $attachment_id );
	$old_files = tiagsspace_bw_files( $attachment_id, $main, $old_meta );

	// <name>-bw.<ext>, from the name without WordPress's -scaled / -rotated
	// and without an earlier image-editor suffix (-e1234567890123).
	$info = pathinfo( $source );
	$base = preg_replace( '/-(scaled|rotated)$/', '', $info['filename'] );
	$base = preg_replace( '/-e\d{13}$/', '', $base );
	$dir  = trailingslashit( $info['dirname'] );
	$name = wp_unique_filename( $dir, $base . '-bw.' . $info['extension'] );

	// Last word on the list: EWWW Image Optimizer puts its own editors first.
	add_filter( 'wp_image_editors', 'tiagsspace_bw_image_editors', PHP_INT_MAX );
	$editor = wp_get_image_editor( $source );
	remove_filter( 'wp_image_editors', 'tiagsspace_bw_image_editors', PHP_INT_MAX );

	if ( is_wp_error( $editor ) ) {
		return $editor;
	}
	if ( ! method_exists( $editor, 'grayscale' ) ) {
		return new WP_Error( 'bw_editor', __( 'No image editor on this server can convert the file.', 'tiagsspace' ) );
	}

	// The full-size file is the master from now on: keep it close to the
	// original's quality (sizes use the site's own setting).
	$editor->set_quality( 92 );
	// Turn the pixels upright first: GD drops the EXIF orientation on save.
	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
		$editor->maybe_exif_rotate();
	}
	$done = $editor->grayscale();
	if ( is_wp_error( $done ) ) {
		return $done;
	}
	$saved = $editor->save( $dir . $name, $mime );
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}
	if ( empty( $saved['path'] ) || ! file_exists( $saved['path'] ) ) {
		return new WP_Error( 'bw_save', __( 'The black and white file could not be written.', 'tiagsspace' ) );
	}

	// Point the attachment at the new file and build every size from it.
	require_once ABSPATH . 'wp-admin/includes/image.php';
	update_attached_file( $attachment_id, $saved['path'] );
	$new_meta = wp_create_image_subsizes( $saved['path'], $attachment_id );

	if ( is_wp_error( $new_meta ) || empty( $new_meta ) ) {
		// Put the colour image back as it was and remove what was written.
		$written = tiagsspace_bw_files( $attachment_id, $saved['path'], is_array( $new_meta ) ? $new_meta : array() );
		update_attached_file( $attachment_id, $main );
		wp_update_attachment_metadata( $attachment_id, $old_meta );
		$uploads = wp_get_upload_dir();
		foreach ( $written as $file ) {
			if ( ! in_array( $file, $old_files, true ) ) {
				wp_delete_file_from_directory( $file, $uploads['basedir'] );
			}
		}
		return is_wp_error( $new_meta ) ? $new_meta : new WP_Error( 'bw_sizes', __( 'The image sizes could not be made.', 'tiagsspace' ) );
	}

	// Delete every colour file the new set does not use.
	delete_post_meta( $attachment_id, '_wp_attachment_backup_sizes' );
	$keep    = tiagsspace_bw_files( $attachment_id, $saved['path'], $new_meta );
	$uploads = wp_get_upload_dir();
	$deleted = array();
	foreach ( $old_files as $file ) {
		if ( in_array( $file, $keep, true ) ) {
			continue;
		}
		foreach ( array( $file, $file . '.webp', $file . '.avif' ) as $path ) {
			if ( file_exists( $path ) && wp_delete_file_from_directory( $path, $uploads['basedir'] ) ) {
				$deleted[] = $path;
			}
		}
	}

	update_post_meta( $attachment_id, TIAGSSPACE_BW_META, 1 );

	if ( function_exists( 'tiagsspace_gallery_touch_parent' ) ) {
		tiagsspace_gallery_touch_parent( $attachment_id );
	}

	/**
	 * The files of an attachment were replaced (same ID, new files).
	 *
	 * @param int      $attachment_id
	 * @param string[] $deleted Absolute paths of the files removed.
	 */
	do_action( 'tiagsspace_attachment_file_replaced', $attachment_id, $deleted );

	$new_urls = tiagsspace_bw_urls( $attachment_id );
	$replace  = array();
	foreach ( $old_urls as $size => $url ) {
		if ( $url && ! empty( $new_urls[ $size ] ) && $url !== $new_urls[ $size ] ) {
			$replace[] = array( $url, $new_urls[ $size ] );
		}
	}

	return array(
		'id'      => $attachment_id,
		'file'    => wp_basename( $saved['path'] ),
		'replace' => $replace,
	);
}

/** AJAX: one image per request, from the front-end editor's View ▾. */
function tiagsspace_save_bw_ajax() {
	check_ajax_referer( 'tiagsspace_save_bw', '_nonce' );

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$attachment    = $attachment_id ? get_post( $attachment_id ) : null;

	if ( ! $attachment || 'attachment' !== $attachment->post_type || ! $post_id
		|| (int) $attachment->post_parent !== $post_id
		|| ! current_user_can( 'edit_post', $attachment_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot change this image.', 'tiagsspace' ) ), 403 );
	}

	// A large master and every size: give it room.
	if ( function_exists( 'wp_raise_memory_limit' ) ) {
		wp_raise_memory_limit( 'image' );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	$result = tiagsspace_save_attachment_bw( $attachment_id );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_tiagsspace_save_bw', 'tiagsspace_save_bw_ajax' );

/**
 * Visual Archive Search: index a replaced image again, as if new (its own
 * "Re-index": captions and vectors cleared, status pending), and run a tick.
 * The plugin is not edited; without it this does nothing.
 */
function tiagsspace_bw_reindex_visual_search( $attachment_id ) {
	global $visual_archive_search;
	if ( ! is_object( $visual_archive_search ) || ! method_exists( $visual_archive_search, 'queue_reindex' ) ) {
		return;
	}
	$visual_archive_search->queue_reindex( (int) $attachment_id );
	if ( ! wp_next_scheduled( 'vas_index_tick' ) ) {
		wp_schedule_single_event( time(), 'vas_index_tick' );
	}
}
add_action( 'tiagsspace_attachment_file_replaced', 'tiagsspace_bw_reindex_visual_search' );
