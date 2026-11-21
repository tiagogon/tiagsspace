<?php
/**
 * Gallery admin functions and AJAX handlers.
 *
 * Includes: gallery edit UI (hide/delete/reorder/resize), AJAX handlers for
 * media order, attachment size, and margin changes.
 *
 * @package tiagsspace
 */

/************* Gallery edit mode: where the front-end editor runs *************/

// One published post's own choice, made with the "Gallery edit: on/off" link in
// the entry footer: 'on' or 'off'. Absent = follow Appearance → Theme Settings.
const TIAGSSPACE_GALLERY_EDIT_META = '_tiagsspace_gallery_edit';
// Before Nov 2026 the link could only switch the editor off: 1 here = 'off'.
const TIAGSSPACE_GALLERY_EDIT_OFF_META = '_tiagsspace_gallery_edit_off';

/** Does Theme Settings open published posts in the editor by default? */
function tiagsspace_gallery_edit_default_on() {
	return function_exists( 'tiagsspace_setting' ) && (bool) tiagsspace_setting( 'gallery_edit_published' );
}

/**
 * The post's own choice: 'on', 'off', or '' (follow the site default).
 */
function tiagsspace_gallery_edit_post_choice( $post_id ) {
	$choice = get_post_meta( (int) $post_id, TIAGSSPACE_GALLERY_EDIT_META, true );
	if ( 'on' === $choice || 'off' === $choice ) {
		return $choice;
	}
	return get_post_meta( (int) $post_id, TIAGSSPACE_GALLERY_EDIT_OFF_META, true ) ? 'off' : '';
}

/**
 * May the published post's gallery be edited on the front end at all?
 * (Published, not an animation gallery, current user can edit it.) The footer
 * link shows whenever this is true, whatever Theme Settings says; whether the
 * editor is on is tiagsspace_gallery_edit_mode().
 */
function tiagsspace_gallery_edit_available( $post_id ) {
	$post_id = (int) $post_id;
	return $post_id
		&& 'publish' === get_post_status( $post_id )
		&& ! get_field( 'animation_number_of_attachment_shown', $post_id )
		&& current_user_can( 'edit_post', $post_id );
}

/**
 * The one gate for the front-end gallery editor (per-item controls, Sortable,
 * keyboard keys, selection and toolbar). True on:
 *   - a preview, for a logged-in user (as it has always been), or
 *   - a single published post the user can edit, when the post was switched on
 *     with the footer link, or Theme Settings opens posts in the editor and the
 *     post was not switched off.
 * Never on listings, never for an animation gallery. On a published post it also
 * keeps W3TC from caching the page (it carries nonces and edit controls).
 *
 * @param int $post_id The gallery's post (not a revision).
 */
function tiagsspace_gallery_edit_mode( $post_id = 0 ) {
	static $cache = array();

	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( wp_is_post_revision( $post_id ) ) {
		$post_id = (int) wp_get_post_parent_id( $post_id );
	}
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$on = false;
	if ( ! get_field( 'animation_number_of_attachment_shown', $post_id ) ) {
		if ( is_preview() && is_user_logged_in() ) {
			$on = true;
		} elseif ( is_singular() && (int) get_queried_object_id() === $post_id
			&& tiagsspace_gallery_edit_available( $post_id ) ) {
			$choice = tiagsspace_gallery_edit_post_choice( $post_id );
			$on     = 'on' === $choice || ( 'off' !== $choice && tiagsspace_gallery_edit_default_on() );
			if ( $on && ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
		}
	}

	return $cache[ $post_id ] = $on;
}

/** URL of the footer link that switches edit mode off / on for one post. */
function tiagsspace_gallery_edit_toggle_url( $post_id ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'tiagsspace_gallery_edit_toggle',
				'post'   => (int) $post_id,
			),
			admin_url( 'admin-post.php' )
		),
		'tiagsspace_gallery_edit_toggle_' . (int) $post_id
	);
}

add_action( 'admin_post_tiagsspace_gallery_edit_toggle', 'tiagsspace_handle_gallery_edit_toggle' );
function tiagsspace_handle_gallery_edit_toggle() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'tiagsspace_gallery_edit_toggle_' . $post_id );
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You cannot edit this post.', 'tiagsspace' ), 403 );
	}

	// Flip what the post shows now. Store the choice only when it differs from
	// the site default; otherwise the post simply follows the default again.
	$default = tiagsspace_gallery_edit_default_on();
	$choice  = tiagsspace_gallery_edit_post_choice( $post_id );
	$now_on  = 'on' === $choice || ( 'off' !== $choice && $default );
	$want_on = ! $now_on;

	delete_post_meta( $post_id, TIAGSSPACE_GALLERY_EDIT_OFF_META );
	if ( $want_on === $default ) {
		delete_post_meta( $post_id, TIAGSSPACE_GALLERY_EDIT_META );
	} else {
		update_post_meta( $post_id, TIAGSSPACE_GALLERY_EDIT_META, $want_on ? 'on' : 'off' );
	}

	wp_safe_redirect( get_permalink( $post_id ) );
	exit;
}

// A duplicate starts with the site default, not the source's switch.
add_filter( 'tiagsspace_duplicate_meta_denylist', function ( $keys ) {
	$keys[] = TIAGSSPACE_GALLERY_EDIT_META;
	$keys[] = TIAGSSPACE_GALLERY_EDIT_OFF_META;
	return $keys;
} );

/**
 * Words only the front-end editor uses (the menus themselves reuse the media
 * modal's tables, see tiagsspace_gallery_edit_config()).
 */
function tiagsspace_gallery_edit_i18n() {
	return array(
		'selectItem'     => __( 'Select %s', 'tiagsspace' ),
		'selectedOne'    => __( '%d selected', 'tiagsspace' ),
		'selectedN'      => __( '%d selected', 'tiagsspace' ),
		'attachmentsOne' => __( '%d attachment', 'tiagsspace' ),
		'attachmentsN'   => __( '%d attachments', 'tiagsspace' ),
		'clear'          => __( 'Clear', 'tiagsspace' ),
		'selectKeys'     => __( 'Select  S · Shift+arrows extend · %s all · Esc clears', 'tiagsspace' ),
		'tipSelect'      => __( 'Select  S · Shift-click a range', 'tiagsspace' ),
		'hide'           => __( 'Hide (H)', 'tiagsspace' ),
		'hideN'          => __( 'Hide %d (H)', 'tiagsspace' ),
		'tipHide'        => __( 'Hide from gallery  H · undo  U', 'tiagsspace' ),
		'tipHideBar'     => __( 'Hide the selected from the gallery  H · undo  U', 'tiagsspace' ),
		'restoreN'       => __( 'Restore hidden (%d)', 'tiagsspace' ),
		'tipRestore'     => __( 'Put every hidden image back in the gallery · one at a time  U', 'tiagsspace' ),
		'delete'         => __( 'Delete', 'tiagsspace' ),
		'tipDelete'      => __( 'Delete permanently…', 'tiagsspace' ),
		'feature'        => __( 'Feature (F)', 'tiagsspace' ),
		'featured'       => __( 'Featured', 'tiagsspace' ),
		'tipFeature'     => __( 'Set as the featured image of this post  F', 'tiagsspace' ),
		'tipFeatured'    => __( 'The featured image of this post', 'tiagsspace' ),
		'setFeaturedKey' => __( 'Set as featured image (F)', 'tiagsspace' ),
		'nextHidden'     => __( 'Next hidden (N)', 'tiagsspace' ),
		// Download: a zip of the original files (download_all_attachments()).
		'downloadAll'    => __( 'Download all', 'tiagsspace' ),
		'downloadN'      => __( 'Download %d', 'tiagsspace' ),
		'tipDownload'    => __( 'A zip of the original files: the selected, or every attachment when nothing is selected', 'tiagsspace' ),
		'downloadBusy'   => __( 'Preparing the zip…', 'tiagsspace' ),
		'tipNextHidden'  => __( 'Show the next hidden image here  N', 'tiagsspace' ),
		'tipSmaller'     => __( 'Smaller', 'tiagsspace' ),
		'tipBigger'      => __( 'Bigger', 'tiagsspace' ),
		'margins'        => __( 'Margins', 'tiagsspace' ),
		'tipMargins'     => __( 'Margins and stacking', 'tiagsspace' ),
		'marginAll'      => __( 'All', 'tiagsspace' ),
		'marginLeft'     => __( 'Left', 'tiagsspace' ),
		'marginRight'    => __( 'Right', 'tiagsspace' ),
		'marginTop'      => __( 'Top', 'tiagsspace' ),
		'marginBottom'   => __( 'Bottom', 'tiagsspace' ),
		'stack'          => __( 'Stack', 'tiagsspace' ),
		'less'           => __( 'less', 'tiagsspace' ),
		'more'           => __( 'more', 'tiagsspace' ),
		'reset'          => __( 'reset', 'tiagsspace' ),
		'movedTo'        => __( '%d moved to %s', 'tiagsspace' ),
		'movedToCopy'    => __( '%d moved to a new draft', 'tiagsspace' ),
		'open'           => __( 'Open', 'tiagsspace' ),
		// Inside the lightbox (gallery-edit/lightbox.js).
		'lbSelected'     => __( 'Selected', 'tiagsspace' ),
		'lbHidden'       => __( 'Hidden · U to undo', 'tiagsspace' ),
		// View ▾: black and white preview, on this screen only.
		'view'           => __( 'View', 'tiagsspace' ),
		'viewHeader'     => __( 'Only on your screen · reload brings colour back', 'tiagsspace' ),
		'tipView'        => __( 'Preview in black and white  B', 'tiagsspace' ),
		'bwSelected'     => __( 'Black and white', 'tiagsspace' ),
		'colourSelected' => __( 'Back to colour', 'tiagsspace' ),
		'bwAll'          => __( 'All black and white', 'tiagsspace' ),
		'colourAll'      => __( 'All back to colour', 'tiagsspace' ),
		// View ▾ → save for everyone (library/gallery-bw.php).
		'saveBwHeader'   => __( 'Save for everyone · cannot be undone', 'tiagsspace' ),
		'saveBwN'        => __( 'Save %d in black and white…', 'tiagsspace' ),
		'saveBwTitleOne' => __( 'Save 1 image in black and white?', 'tiagsspace' ),
		'saveBwTitleN'   => __( 'Save %d images in black and white?', 'tiagsspace' ),
		'saveBwText1'    => __( 'New black and white files replace the colour ones everywhere: the gallery, the lightbox, share previews and downloads. Their names end in -bw.', 'tiagsspace' ),
		'saveBwText2'    => __( 'The colour files are deleted. This cannot be undone.', 'tiagsspace' ),
		'saveBwText3'    => __( 'The images are indexed again for the visual search.', 'tiagsspace' ),
		'saveBwConfirm'  => __( 'Save in black and white', 'tiagsspace' ),
		'saveBwProgress' => __( 'Saving %d of %d in black and white…', 'tiagsspace' ),
		'saveBwDone'     => __( '%d saved in black and white', 'tiagsspace' ),
		'saveBwFailedTitle' => __( '%d not saved in black and white', 'tiagsspace' ),
		'saveBwFailedText'  => __( 'These images keep their colour files:', 'tiagsspace' ),
		'saveBwLeave'    => __( 'Images are still being saved in black and white.', 'tiagsspace' ),
		// The shortcut list after the counter on the bar: [ keys, word ] in
		// order; %s is ⌘A or Ctrl+A, chosen in the browser. A new key is one row.
		'keys'           => array(
			array( '← →', __( 'move', 'tiagsspace' ) ),
			array( '↑ ↓', __( 'swap', 'tiagsspace' ) ),
			array( 'S', __( 'select', 'tiagsspace' ) ),
			array( 'H', __( 'hide', 'tiagsspace' ) ),
			array( 'U', __( 'undo', 'tiagsspace' ) ),
			array( 'N', __( 'next hidden', 'tiagsspace' ) ),
			array( 'F', __( 'featured', 'tiagsspace' ) ),
			array( 'B', __( 'b&w', 'tiagsspace' ) ),
			array( '%s', __( 'all', 'tiagsspace' ) ),
			array( 'Esc', __( 'clear', 'tiagsspace' ) ),
		),
	);
}

/**
 * window.tiagsspaceGalleryEditCfg for library/js/gallery-edit/*.js: ids, the
 * nonces of the media modal's own AJAX handlers, and the modal's word tables.
 *
 * @param int $post_id The gallery's post.
 * @param int $loop_id The id the grid is printed with (#gallery-<id>).
 */
function tiagsspace_gallery_edit_config( $post_id, $loop_id ) {
	return array(
		'postId' => (int) $post_id,
		'featuredId' => (int) get_post_thumbnail_id( $post_id ),
		'gridId' => 'gallery-' . (int) $loop_id,
		'poolId' => 'gallery-hidden-pool-' . (int) $loop_id,
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonces' => array(
			'reorder'        => wp_create_nonce( 'gallery_reorder_attachments' ),
			'hidden'         => wp_create_nonce( 'gallery_hidden_check' ),
			'deleteSelected' => wp_create_nonce( 'tiagsspace_delete_attachments' ),
			'duplicate'      => wp_create_nonce( 'tiagsspace_duplicate_to_post' ),
			'attachSearch'   => wp_create_nonce( 'tiagsspace_media_attach_search' ),
			'attach'         => wp_create_nonce( 'tiagsspace_media_attach_attach' ),
			'featured'       => wp_create_nonce( 'tiagsspace_set_featured_image' ),
			'saveBw'         => wp_create_nonce( 'tiagsspace_save_bw' ),
			'download'       => wp_create_nonce( 'download_all_attachments' ),
		),
		'i18n' => array(
			'toolbar'   => tiagsspace_media_toolbar_i18n(),
			'gallery'   => tiagsspace_media_gallery_actions_i18n(),
			'duplicate' => tiagsspace_media_duplicate_i18n(),
			'attach'    => tiagsspace_media_attach_i18n(),
			'edit'      => tiagsspace_gallery_edit_i18n(),
		),
	);
}

/**
 * The editor's stylesheet. Printed BEFORE the grid (entry-gallery.php): the
 * per-image controls are in each item's markup, and until this file applies
 * they sit in the flow and make every item taller. A stylesheet ahead of the
 * grid blocks the scripts after it, so Masonry never measures that state
 * (it did on production, where the file arrived late: rows spaced far apart).
 */
function tiagsspace_gallery_edit_style() {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	printf(
		'<link rel="stylesheet" href="%s">' . "\n",
		esc_url( get_template_directory_uri() . '/library/styles/gallery-edit.css?v=' . tiagsspace_asset_ver( '/library/styles/gallery-edit.css' ) )
	);
}

/**
 * Lightboxes the editor can work inside: the gallery's lightbox name (as
 * entry-gallery.php sets $light_box) => its adapter in library/js/gallery-edit/.
 * A lightbox without an entry keeps the editor out of its view, as before.
 */
function tiagsspace_gallery_edit_lightbox_adapters() {
	return (array) apply_filters( 'tiagsspace_gallery_edit_lightbox_adapters', array(
		'lightbox' => 'lightbox-photoswipe',
	) );
}

/**
 * Config + <script> tags for the editor, printed inside the gallery's edit block.
 *
 * @param int    $post_id   The gallery's post.
 * @param int    $loop_id   The id the grid is printed with (#gallery-<id>).
 * @param string $light_box The gallery's lightbox; with an adapter for it, the
 *                          editor keys work inside it (gallery-edit/lightbox.js).
 */
function tiagsspace_gallery_edit_assets( $post_id, $loop_id, $light_box = '' ) {
	$base = get_template_directory_uri();
	printf(
		'<script>window.tiagsspaceGalleryEditCfg = %s;</script>' . "\n",
		wp_json_encode( tiagsspace_gallery_edit_config( $post_id, $loop_id ) )
	);
	$files    = array( 'tips', 'selection', 'toolbar', 'actions' );
	$adapters = tiagsspace_gallery_edit_lightbox_adapters();
	if ( $light_box && ! empty( $adapters[ $light_box ] ) && preg_match( '/^[a-z0-9-]+$/', $adapters[ $light_box ] ) ) {
		$files[] = 'lightbox';
		$files[] = $adapters[ $light_box ];
	}
	foreach ( $files as $file ) {
		$path = '/library/js/gallery-edit/' . $file . '.js';
		printf( '<script src="%s"></script>' . "\n", esc_url( $base . $path . '?v=' . tiagsspace_asset_ver( $path ) ) );
	}
}

/**
 * Nonce + capability check shared by the per-item AJAX handlers (order, size,
 * margins). The edit page prints the nonce as window.galleryHideNonce.
 */
function tiagsspace_gallery_edit_guard( $attachment_id ) {
	check_ajax_referer( 'gallery_hide_attachment', '_nonce' );
	if ( ! $attachment_id || ! current_user_can( 'edit_post', $attachment_id ) ) {
		wp_die( 'Permission denied.', 403 );
	}
}

/**
 * After a gallery change on a published post, drop its cached copies so
 * visitors see it: the object cache and W3TC's page cache for the parent post.
 * The order save sends one request per item, so the page-cache flush runs at
 * most once per post every few seconds (the last request of a burst still
 * lands inside a later window or the logged-in view, which W3TC never caches).
 */
function tiagsspace_gallery_touch_parent( $attachment_or_post_id, $is_parent = false ) {
	$parent = $is_parent ? (int) $attachment_or_post_id : (int) wp_get_post_parent_id( $attachment_or_post_id );
	if ( ! $parent || 'publish' !== get_post_status( $parent ) ) {
		return;
	}
	clean_post_cache( $parent );

	$lock = 'tiagsspace_gallery_flush_' . $parent;
	if ( get_transient( $lock ) ) {
		// A flush already ran in this window: run one more after it ends.
		if ( ! wp_next_scheduled( 'tiagsspace_gallery_flush_post', array( $parent ) ) ) {
			wp_schedule_single_event( time() + 6, 'tiagsspace_gallery_flush_post', array( $parent ) );
		}
		return;
	}
	set_transient( $lock, 1, 5 );
	add_action( 'shutdown', function () use ( $parent ) {
		tiagsspace_gallery_flush_post( $parent );
	} );
}

// Deleting or re-parenting an attachment (per-image DELETE, Delete ▾, Move to ▾)
// changes the parent's gallery too; for a move, both posts.
add_action( 'delete_attachment', 'tiagsspace_gallery_touch_parent' );
add_action( 'attachment_updated', function ( $id, $after, $before ) {
	if ( (int) $after->post_parent !== (int) $before->post_parent ) {
		tiagsspace_gallery_touch_parent( $before->post_parent, true );
		tiagsspace_gallery_touch_parent( $after->post_parent, true );
	}
}, 10, 3 );

add_action( 'tiagsspace_gallery_flush_post', 'tiagsspace_gallery_flush_post' );
function tiagsspace_gallery_flush_post( $post_id ) {
	if ( function_exists( 'w3tc_flush_post' ) ) {
		w3tc_flush_post( (int) $post_id );
	}
}

/************* Gallery Functions *************/


// Edit attachment media (image/video/etc): the controls drawn on each image in
// gallery edit mode. Top-left: what happens to the image (Hide, Delete, Next
// hidden). Bottom-left: how it sits in the grid (smaller, bigger, margins).
// The check box (top-right) is added by library/js/gallery-edit/selection.js.
// Styles: library/styles/gallery-edit.css. Classes .remove, .delete and
// .nexthidden are hooks for gallery-keyboard-reorder.js and entry-gallery.php.
function gallery_edit_atachement_options($gallery_id,$attachment_count, $attachment_id) {

	// This function runs once per attachment, but the script block below is
	// shared — print it only on the first call to avoid stacked handlers.
	static $scripts_printed = false;

	$w             = tiagsspace_gallery_edit_i18n();
	$attachment_id = (int) $attachment_id;

	if ( ! $scripts_printed ) {
		?>
		<script type="text/javascript">
			// Take an image's item out of the grid (after a delete) and relayout.
			function removeDiv(btn){
				var item = btn.closest ? btn.closest('.item') : null;
				if (!item || !item.parentNode) return;
				item.parentNode.removeChild(item);

				if (typeof window.galleryKeyboardReorderReset === "function") {
					window.galleryKeyboardReorderReset();
				}

				// relayout masonry
				var $grid = jQuery("#gallery-<?php echo (int) $gallery_id; ?>");
				if ($grid.data('masonry')) { $grid.masonry('reloadItems').masonry('layout'); }
			}

			// Per-image Delete: ask first (shared confirm panel), then delete
			// through core's nonced post.php link and take the item away.
			jQuery(document).on("click", ".delete", function(e){
				e.preventDefault();
				var link = this;
				var targetUrl = jQuery(link).attr("rel");
				var ge = window.tiagsspaceGalleryEdit;
				var words = (window.tiagsspaceGalleryEditCfg && window.tiagsspaceGalleryEditCfg.i18n && window.tiagsspaceGalleryEditCfg.i18n.duplicate) || {};
				var title = words.deleteTitle1 || 'Delete one attachment permanently?';

				function go() {
					jQuery.ajax({
						url: targetUrl,
						type: "GET",
						success: function(data) { console.log('Attachment deleted.'); },
						error: function(errorThrown) { console.log(errorThrown); }
					});
					removeDiv(link);
				}

				if (ge && typeof ge.confirm === 'function') {
					ge.confirm({
						title:        title,
						text:         words.deleteText || 'It is removed from the Media Library and from disk. This cannot be undone.',
						confirmLabel: words.deleteConfirm1 || 'Delete one',
						danger:       true
					}).then(function (ok) { if (ok) go(); });
				} else if (window.confirm(title)) {
					go();
				}
			});
		</script>
		<?php
		$scripts_printed = true;
	}

	// "Next hidden" only when the post has hidden attachments (once per gallery).
	static $has_hidden_by_gallery = array();
	if ( ! isset( $has_hidden_by_gallery[ $gallery_id ] ) ) {
		$hidden_ids = get_posts( array(
			'numberposts' => 1,
			'post_parent' => $gallery_id,
			'post_status' => 'any',
			'post_type'   => 'attachment',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'   => 'remove_from_default_gallery',
					'value' => '1',
				),
			),
		) );
		$has_hidden_by_gallery[ $gallery_id ] = ! empty( $hidden_ids );
	}

	$delete_url = wp_nonce_url( admin_url( 'post.php?action=delete&post=' . $attachment_id ), 'delete-post_' . $attachment_id );

	// What happens to the image.
	echo '<div class="tge-item-actions">';
	printf( '<button type="button" class="remove tge-chip" data-tip="%s">%s</button>', esc_attr( $w['tipHide'] ), esc_html( $w['hide'] ) );
	printf( '<a class="delete tge-chip" href="#" role="button" rel="%s" data-tip="%s">%s</a>', esc_url( $delete_url ), esc_attr( $w['tipDelete'] ), esc_html( $w['delete'] ) );
	// Images only: a video or audio file cannot be a featured image.
	if ( wp_attachment_is_image( $attachment_id ) ) {
		$is_featured = (int) get_post_thumbnail_id( $gallery_id ) === $attachment_id;
		printf(
			'<button type="button" class="tge-chip tge-feature" data-attachment="%d" aria-pressed="%s" data-tip="%s">%s</button>',
			$attachment_id,
			$is_featured ? 'true' : 'false',
			esc_attr( $is_featured ? $w['tipFeatured'] : $w['tipFeature'] ),
			esc_html( $is_featured ? $w['featured'] : $w['feature'] )
		);
	}
	if ( $has_hidden_by_gallery[ $gallery_id ] ) {
		printf( '<button type="button" class="nexthidden tge-chip" onclick="revealNextHidden(this);" data-tip="%s">%s</button>', esc_attr( $w['tipNextHidden'] ), esc_html( $w['nextHidden'] ) );
	}
	echo '</div>';

	// How it sits in the grid: size steps, then one panel for the margins.
	echo '<div class="tge-item-layout">';
	printf( '<button type="button" class="tge-chip tge-chip--square" onclick="atachementGridSizeChange(%d, \'decrease\');" data-tip="%s" aria-label="%s">&minus;</button>', $attachment_id, esc_attr( $w['tipSmaller'] ), esc_attr( $w['tipSmaller'] ) );
	printf( '<button type="button" class="tge-chip tge-chip--square" onclick="atachementGridSizeChange(%d, \'increase\');" data-tip="%s" aria-label="%s">+</button>', $attachment_id, esc_attr( $w['tipBigger'] ), esc_attr( $w['tipBigger'] ) );
	printf( '<button type="button" class="tge-chip tge-margins-toggle" aria-expanded="false" data-tip="%s">%s <span aria-hidden="true">▾</span></button>', esc_attr( $w['tipMargins'] ), esc_html( $w['margins'] ) );

	echo '<div class="tge-margins" role="group" aria-label="' . esc_attr( $w['tipMargins'] ) . '" hidden>';
	$rows = array(
		'margin'        => $w['marginAll'],
		'margin-left'   => $w['marginLeft'],
		'margin-right'  => $w['marginRight'],
		'margin-top'    => $w['marginTop'],
		'margin-bottom' => $w['marginBottom'],
		'z-index'       => $w['stack'],
	);
	foreach ( $rows as $name => $label ) {
		if ( 'z-index' === $name ) {
			echo '<div class="tge-margins-sep" role="separator"></div>';
		}
		echo '<div class="tge-margins-row"><span class="tge-margins-label">' . esc_html( $label ) . '</span>';
		foreach ( array( array( -1, '&minus;', $w['less'] ), array( "'clear'", '0', $w['reset'] ), array( 1, '+', $w['more'] ) ) as $step ) {
			printf(
				'<button type="button" class="tge-chip tge-chip--square" onclick="atachementChangeMargin(%d, \'%s\', %s);" aria-label="%s">%s</button>',
				$attachment_id,
				esc_attr( $name ),
				$step[0],
				esc_attr( $label . ': ' . $step[2] ),
				$step[1]
			);
		}
		echo '</div>';
	}
	echo '</div>';
	echo '</div>';
}

// Make ajax_url available on the frontend for gallery AJAX calls
if ( is_user_logged_in() ) {
	function example_ajax_enqueue() {
		// Register a dummy handle so wp_localize_script has something to attach to.
		wp_register_script( 'example-ajax-script', '' );
		wp_enqueue_script( 'example-ajax-script' );
		wp_localize_script(
			'example-ajax-script',
			'example_ajax_obj',
			array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
		);
	}
	add_action( 'wp_enqueue_scripts', 'example_ajax_enqueue' );
}

// Enqueue gallery editor helper scripts (Gutenberg)
function gallery_enqueue_editor_reset_script() {
	wp_enqueue_script(
		'gallery-hidden-attachments-check',
		get_template_directory_uri() . '/library/js/gallery-hidden-attachments-check.js',
		array( 'jquery' ),
		'1.0',
		true
	);
	wp_localize_script(
		'gallery-hidden-attachments-check',
		'galleryHiddenCheck',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gallery_hidden_check' ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'gallery_enqueue_editor_reset_script' );

// Admin media stylesheet: the modal toolbar menus and dialogs, the uncropped
// grid, the list tables' featured-image column (library/styles/admin-media.css).
function tiagsspace_enqueue_admin_media_style() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, array( 'post', 'edit' ), true ) ) {
		return;
	}
	wp_enqueue_style(
		'tiagsspace-admin-media',
		get_template_directory_uri() . '/library/styles/admin-media.css',
		array(),
		tiagsspace_asset_ver( '/library/styles/admin-media.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'tiagsspace_enqueue_admin_media_style' );

// Shared frame hook for every media-modal script (see library/js/media-frame-wire.js).
// Registered early (priority 5) so the scripts below can list it as a dependency.
function tiagsspace_register_media_frame_wire() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}
	$path = get_template_directory() . '/library/js/media-frame-wire.js';
	wp_register_script(
		'media-frame-wire',
		get_template_directory_uri() . '/library/js/media-frame-wire.js',
		array( 'jquery', 'media-views' ),
		file_exists( $path ) ? filemtime( $path ) : '1.0',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'tiagsspace_register_media_frame_wire', 5 );

// Default the media library modal filter to "Uploaded to this post" on post edit screens
function enqueue_media_library_default_uploaded() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}
	wp_enqueue_script(
		'media-library-default-uploaded',
		get_template_directory_uri() . '/library/js/media-library-default-uploaded.js',
		array( 'media-views', 'media-frame-wire' ),
		tiagsspace_asset_ver( '/library/js/media-library-default-uploaded.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_media_library_default_uploaded' );

/**
 * Media toolbar words: buttons, menu headers, dialog buttons.
 * Shared by the wp-admin media modal and the front-end gallery toolbar
 * (tiagsspace_gallery_edit_config()), so both read the same words.
 */
function tiagsspace_media_toolbar_i18n() {
	return array(
		'groupLabel'      => __( 'Gallery actions', 'tiagsspace' ),
		// Buttons
		'delete'          => __( 'Delete', 'tiagsspace' ),
		'select'          => __( 'Select', 'tiagsspace' ),
		'selectN'         => __( 'Select (%d)', 'tiagsspace' ),
		'sort'            => __( 'Sort', 'tiagsspace' ),
		'move'            => __( 'Move to', 'tiagsspace' ),
		'moveN'           => __( 'Move %d to', 'tiagsspace' ),
		'actions'         => __( 'Actions', 'tiagsspace' ),
		'actionsN'        => __( 'Actions (%d)', 'tiagsspace' ),
		'setFeatured'     => __( 'Set as featured image', 'tiagsspace' ),
		'featuredBadge'   => __( 'Featured', 'tiagsspace' ),
		// Menu headers
		'cannotUndo'      => __( 'Cannot be undone', 'tiagsspace' ),
		'shiftAdds'       => __( 'Shift-click adds to the selection', 'tiagsspace' ),
		'sortAll'         => __( 'Sort all attachments', 'tiagsspace' ),
		'sortAllN'        => __( 'Sort all %d attachments', 'tiagsspace' ),
		'sortSelectedN'   => __( 'Sort the %d selected', 'tiagsspace' ),
		'sortKeepSlots'   => __( 'They keep their slots', 'tiagsspace' ),
		'sortOneSelected' => __( 'One item selected: nothing to sort', 'tiagsspace' ),
		'moveSelectedN'   => __( 'Move the %d selected', 'tiagsspace' ),
		'moveLeave'       => __( 'They leave this post', 'tiagsspace' ),
		'moveNone'        => __( 'Select items to move', 'tiagsspace' ),
		'nothingHere'     => __( 'Nothing registered', 'tiagsspace' ),
		'loading'         => __( 'Loading…', 'tiagsspace' ),
		// Dialogs
		'close'           => __( 'Close', 'tiagsspace' ),
		'cancel'          => __( 'Cancel', 'tiagsspace' ),
		'ok'              => __( 'OK', 'tiagsspace' ),
	);
}

/**
 * Select ▾ / Sort ▾ / Delete ▾ hidden words.
 * Shared by the wp-admin media modal and the front-end gallery toolbar
 * (tiagsspace_gallery_edit_config()), so both read the same words.
 */
function tiagsspace_media_gallery_actions_i18n() {
	return array(
		'postError'         => __( 'Could not determine the current post.', 'tiagsspace' ),
		// Select ▾
		'selectAll'         => __( 'All', 'tiagsspace' ),
		'selectNone'        => __( 'None', 'tiagsspace' ),
		'portrait'          => __( 'Portrait', 'tiagsspace' ),
		'landscape'         => __( 'Landscape', 'tiagsspace' ),
		'images'            => __( 'Images', 'tiagsspace' ),
		'videos'            => __( 'Videos', 'tiagsspace' ),
		'audio'             => __( 'Audio', 'tiagsspace' ),
		'hidden'            => __( 'Hidden from gallery', 'tiagsspace' ),
		'visible'           => __( 'In the gallery', 'tiagsspace' ),
		// Sort ▾
		'byCapture'         => __( 'By capture time (EXIF)', 'tiagsspace' ),
		'byUpload'          => __( 'By upload date', 'tiagsspace' ),
		'shuffle'           => __( 'Shuffle', 'tiagsspace' ),
		'sortAllTitle'      => __( 'Sort all %d attachments?', 'tiagsspace' ),
		'sortAllTitleNoCount' => __( 'Sort all attachments?', 'tiagsspace' ),
		'sortAllText'       => __( 'The current order of the gallery is replaced.', 'tiagsspace' ),
		'sortConfirm'       => __( 'Sort', 'tiagsspace' ),
		'sortError'         => __( 'Could not sort the gallery.', 'tiagsspace' ),
		// Set as featured image
		'featuredDone'      => __( 'Featured image set', 'tiagsspace' ),
		'featuredError'     => __( 'Could not set the featured image.', 'tiagsspace' ),
		// Delete ▾ hidden
		'hiddenItem'        => __( 'Hidden from gallery (%d)…', 'tiagsspace' ),
		'hiddenTitle'       => __( 'Delete %d hidden attachments permanently?', 'tiagsspace' ),
		'hiddenTitle1'      => __( 'Delete one hidden attachment permanently?', 'tiagsspace' ),
		'hiddenText'        => __( 'They are the attachments flagged “remove from default gallery”.', 'tiagsspace' ),
		'deleteText'        => __( 'They are removed from the Media Library and from disk. This cannot be undone.', 'tiagsspace' ),
		'deleteConfirm'     => __( 'Delete %d', 'tiagsspace' ),
		'deleteConfirm1'    => __( 'Delete one', 'tiagsspace' ),
		'deleteError'       => __( 'Could not delete the hidden attachments.', 'tiagsspace' ),
	);
}

/**
 * Media-modal toolbar: Delete ▾ · Select ▾ · Sort ▾ · Move to ▾ before
 * "Insert into post" (library/js/media-toolbar.js). The buttons and their
 * menu headers are here; the items are registered by the three provider
 * scripts (media-gallery-actions, media-duplicate-to-post,
 * media-attach-to-post), which list `media-toolbar` as a dependency.
 */
function tiagsspace_enqueue_media_toolbar() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}

	wp_enqueue_script(
		'media-toolbar',
		get_template_directory_uri() . '/library/js/media-toolbar.js',
		array( 'jquery', 'media-views', 'media-frame-wire' ),
		tiagsspace_asset_ver( '/library/js/media-toolbar.js' ),
		true
	);

	wp_localize_script(
		'media-toolbar',
		'tiagsspaceMediaToolbarCfg',
		array(
			'i18n' => tiagsspace_media_toolbar_i18n(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'tiagsspace_enqueue_media_toolbar' );

// Gallery actions on the media-modal toolbar: Select ▾ items, Sort ▾ items,
// Delete ▾ "Hidden from gallery (N)…".
function enqueue_media_gallery_actions() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}

	wp_enqueue_script(
		'media-gallery-actions',
		get_template_directory_uri() . '/library/js/media-gallery-actions.js',
		array( 'jquery', 'media-views', 'media-frame-wire', 'media-toolbar' ),
		tiagsspace_asset_ver( '/library/js/media-gallery-actions.js' ),
		true
	);

	wp_localize_script(
		'media-gallery-actions',
		'galleryMediaActions',
		array(
			'ajaxurl'      => admin_url( 'admin-ajax.php' ),
			'reorderNonce' => wp_create_nonce( 'gallery_reorder_attachments' ),
			'hiddenNonce'  => wp_create_nonce( 'gallery_hidden_check' ),
			'featuredNonce' => wp_create_nonce( 'tiagsspace_set_featured_image' ),
			'i18n'         => tiagsspace_media_gallery_actions_i18n(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_media_gallery_actions' );

/**
 * Expose the `remove_from_default_gallery` flag on the JS attachment model.
 *
 * The media modal's Select ▾ menu filters the loaded library in the browser
 * (Hidden from gallery / In the gallery), so the flag has to travel with each
 * attachment. Read straight from post meta: the meta cache is primed for every
 * attachment the query returns, so this adds no queries.
 *
 * @param array   $response   Attachment data for JS.
 * @param WP_Post $attachment Attachment post.
 * @return array
 */
function tiagsspace_attachment_js_hidden_flag( $response, $attachment ) {
	$response['tiagsspaceHidden'] = '1' === (string) get_post_meta( $attachment->ID, 'remove_from_default_gallery', true );

	// Featured image of the post being edited (the modal sends its post_id with
	// every library query), else of the attachment's own parent.
	$for_post = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	if ( ! $for_post ) {
		$for_post = (int) $attachment->post_parent;
	}
	$response['tiagsspaceFeatured'] = $for_post && (int) get_post_thumbnail_id( $for_post ) === (int) $attachment->ID;
	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'tiagsspace_attachment_js_hidden_flag', 10, 2 );

// ----------------------
//
// AJAX: Atachments Order -- Change database wp_posts >> menu_order via Ajax request
// ----------------------
function gallery_media_order_change_request() {

    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) ) {


        $attachmentId = isset( $_REQUEST['attachmentId'] ) ? absint( $_REQUEST['attachmentId'] ) : 0;
        $attachmentOrder = isset( $_REQUEST['attachmentOrder'] ) ? intval( $_REQUEST['attachmentOrder'] ) : 0;
        tiagsspace_gallery_edit_guard( $attachmentId );

        // Let's take the data that was sent and do something with it
        if ( $attachmentId ) {
			global $wpdb;
			$updated = $wpdb->update( $wpdb->posts, array( 'menu_order'=>$attachmentOrder),array('ID'=>$attachmentId));
			clean_post_cache( $attachmentId );
			tiagsspace_gallery_touch_parent( $attachmentId );

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

// ----------------------
// AJAX: Set an image as the post's featured image (gallery editor chip and
// toolbar button, media-modal "Set as featured image"). Saved at once.
// ----------------------
function tiagsspace_set_featured_image() {
	check_ajax_referer( 'tiagsspace_set_featured_image', '_nonce' );

	$post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tiagsspace' ) ), 403 );
	}
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Only an image can be the featured image.', 'tiagsspace' ) ), 400 );
	}

	// set_post_thumbnail() returns false when the value is already stored.
	if ( (int) get_post_thumbnail_id( $post_id ) !== $attachment_id ) {
		if ( ! set_post_thumbnail( $post_id, $attachment_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not set the featured image.', 'tiagsspace' ) ), 500 );
		}
		tiagsspace_gallery_touch_parent( $post_id, true );
	}

	wp_send_json_success( array(
		'post_id'       => $post_id,
		'attachment_id' => $attachment_id,
	) );
}
add_action( 'wp_ajax_tiagsspace_set_featured_image', 'tiagsspace_set_featured_image' );

// ----------------------
// AJAX: Count hidden attachments for a post
// ----------------------
function gallery_count_hidden_attachments() {
	check_ajax_referer( 'gallery_hidden_check', '_nonce' );

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( 'Permission denied.' );
	}

	$hidden = get_posts( array(
		'numberposts' => -1,
		'post_parent' => $post_id,
		'post_status' => 'any',
		'post_type'   => 'attachment',
		'fields'      => 'ids',
		'meta_query'  => array(
			array(
				'key'   => 'remove_from_default_gallery',
				'value' => '1',
			),
		),
	) );

	wp_send_json_success( array( 'count' => count( $hidden ) ) );
}
add_action( 'wp_ajax_gallery_count_hidden_attachments', 'gallery_count_hidden_attachments' );

// ----------------------
// AJAX: Permanently delete hidden attachments for a post
// ----------------------
function gallery_delete_hidden_attachments() {
	check_ajax_referer( 'gallery_hidden_check', '_nonce' );

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( 'Permission denied.' );
	}

	$hidden = get_posts( array(
		'numberposts' => -1,
		'post_parent' => $post_id,
		'post_status' => 'any',
		'post_type'   => 'attachment',
		'meta_query'  => array(
			array(
				'key'   => 'remove_from_default_gallery',
				'value' => '1',
			),
		),
	) );

	$deleted = 0;
	foreach ( $hidden as $att ) {
		if ( wp_delete_attachment( $att->ID, true ) ) {
			$deleted++;
		}
	}

	if ( $deleted ) {
		tiagsspace_gallery_touch_parent( $post_id, true );
	}

	wp_send_json_success( array( 'deleted' => $deleted ) );
}
add_action( 'wp_ajax_gallery_delete_hidden_attachments', 'gallery_delete_hidden_attachments' );

// ----------------------
// AJAX: Toggle attachment "Remove from default gallery" ACF field
// ----------------------
function gallery_toggle_hide_attachment() {
	check_ajax_referer( 'gallery_hide_attachment', '_nonce' );

	$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
	$hide          = isset( $_POST['hide'] ) ? $_POST['hide'] : '1';

	if ( ! $attachment_id || ! current_user_can( 'edit_post', $attachment_id ) ) {
		wp_send_json_error( 'Permission denied.' );
	}

	update_field( 'remove_from_default_gallery', $hide === '1' ? 1 : 0, $attachment_id );
	tiagsspace_gallery_touch_parent( $attachment_id );

	wp_send_json_success( array(
		'attachment_id' => $attachment_id,
		'hidden'        => $hide === '1',
	) );
}
add_action( 'wp_ajax_gallery_toggle_hide_attachment', 'gallery_toggle_hide_attachment' );

// ----------------------
// AJAX: Attachmens Diferent size on Gallery
// ----------------------
function change_attachment_field_diferent_size_on_gallery() {

    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) ) {

        $attachmentId = isset( $_REQUEST['attachmentID'] ) ? absint( $_REQUEST['attachmentID'] ) : 0;
        $changeSize = isset( $_REQUEST['changeSize'] ) ? sanitize_key( $_REQUEST['changeSize'] ) : '';
        tiagsspace_gallery_edit_guard( $attachmentId );

        // Let's take the data that was sent and do something with it
        if ( $attachmentId ) {

			// Grid scale steps -- get it from ACF, does not work: https://support.advancedcustomfields.com/forums/topic/list-all-values-in-select-field/
			$GridScaleDenominators = array(0.25, 0.3333333, 0.5, 0.75, 1, 2, 3, 4, 5);

			// Current Denominator
			$GridScaleDenominatorOld = get_field( 'diferent_size_on_gallery',$attachmentId );
			if (empty($GridScaleDenominatorOld) OR !in_array($GridScaleDenominatorOld, $GridScaleDenominators)) {
				$GridScaleDenominatorOld = 1;
			}

			// Index and logic for new denominator
			$index = array_search($GridScaleDenominatorOld, $GridScaleDenominators);
			if($index !== false && $index > 0 ) $prev = $GridScaleDenominators[$index-1];
			if($index !== false && $index < count($GridScaleDenominators)-1) $next = $GridScaleDenominators[$index+1];

			if ($changeSize == "increase") {
					$GridScaleDenominatorNew = $prev;
			}
			if ($changeSize == "decrease") {
					$GridScaleDenominatorNew = $next;
			}

			// Update Database
			$updated = false;
			if (!empty($GridScaleDenominatorNew)) {
				update_field('diferent_size_on_gallery', $GridScaleDenominatorNew, $attachmentId);
				$updated = true;
				tiagsspace_gallery_touch_parent( $attachmentId );
			}

			// Output log message to the front end
			if ( false === $updated ) {
			    echo "There was an error tring to move the change attachment ".$attachmentId." grid size in ".$changeSize." driection";
			} else {
			    echo "The attachment ".$attachmentId." chage the grid size in ".$changeSize." driection. DEBUG - old: ".$GridScaleDenominatorOld." New: ".$GridScaleDenominatorNew;

			}

        }

    }

    // Always die in functions echoing ajax content
   die();
}
add_action( 'wp_ajax_change_attachment_field_diferent_size_on_gallery', 'change_attachment_field_diferent_size_on_gallery' );

// ----------------------
// AJAX: Attachmens Change Margins
// ----------------------
function change_attachment_margin() {

    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) ) {

        $attachmentId = isset( $_REQUEST['attachmentID'] ) ? absint( $_REQUEST['attachmentID'] ) : 0;
        $marginName = isset( $_REQUEST['marginName'] ) ? sanitize_key( $_REQUEST['marginName'] ) : '';
        $incrementalValue = isset( $_REQUEST['incrementalValue'] ) ? sanitize_key( $_REQUEST['incrementalValue'] ) : '';
        if ( 'clear' !== $incrementalValue ) {
            $incrementalValue = intval( $_REQUEST['incrementalValue'] );
        }
        tiagsspace_gallery_edit_guard( $attachmentId );

        // Let's take the data that was sent and do something with it
        if ( $attachmentId ) {

			// Get Current Margin Values
			$margin = get_field( 'attachment_margin',$attachmentId );
			$marginTop = get_field( 'attachment_margin_top',$attachmentId );
			$marginRight = get_field( 'attachment_margin_right',$attachmentId );
			$marginBottom = get_field( 'attachment_margin_bottom',$attachmentId );
			$marginLeft = get_field( 'attachment_margin_left',$attachmentId );
			$zIndex = get_field( 'attachment_z_index',$attachmentId );

			// Compute new margin Values and update database
			$updated = false;

			if ($marginName == "margin") {
				if ($incrementalValue== "clear") {
					$margin = "";
					update_field('attachment_margin', $margin, $attachmentId);
					$updated = true;
				}else {
					$margin = $margin + $incrementalValue;
					update_field('attachment_margin', $margin, $attachmentId);
					$updated = true;
				}
			}
			if ($marginName == "margin-top") {
				if ($incrementalValue== "clear") {
					$marginTop = "";
					update_field('attachment_margin_top', $marginTop, $attachmentId);
					$updated = true;
				}else {
					$marginTop = $marginTop + $incrementalValue;
					update_field('attachment_margin_top', $marginTop, $attachmentId);
					$updated = true;
				}
			}
			if ($marginName == "margin-right") {
				if ($incrementalValue== "clear") {
					$marginRight = "";
					update_field('attachment_margin_right', $marginRight, $attachmentId);
					$updated = true;
				}else {
					$marginRight = $marginRight + $incrementalValue;
					update_field('attachment_margin_right', $marginRight, $attachmentId);
					$updated = true;
				}
			}
			if ($marginName == "margin-bottom") {
				if ($incrementalValue== "clear") {
					$marginBottom = "";
					update_field('attachment_margin_bottom', $marginBottom, $attachmentId);
					$updated = true;
				}else {
					$marginBottom = $marginBottom + $incrementalValue;
					update_field('attachment_margin_bottom', $marginBottom, $attachmentId);
					$updated = true;
				}
			}
			if ($marginName == "margin-left") {
				if ($incrementalValue== "clear") {
					$marginLeft = "";
					update_field('attachment_margin_left', $marginLeft, $attachmentId);
					$updated = true;
				}else {
					$marginLeft = $marginLeft + $incrementalValue;
					update_field('attachment_margin_left', $marginLeft, $attachmentId);
					$updated = true;
				}
			}
			if ($marginName == "z-index") {
				if ($incrementalValue== "clear") {
					$zIndex = "";
					update_field('attachment_z_index', $zIndex, $attachmentId);
					$updated = true;
				}else {
					$zIndex = $zIndex + $incrementalValue;
					update_field('attachment_z_index', $zIndex, $attachmentId);
					$updated = true;
				}
			}

			if ( $updated ) {
				tiagsspace_gallery_touch_parent( $attachmentId );
			}

			// Output log message to the front end
			if ( $updated === false ) {
			    echo "There was an ERROR with attachment ".$attachmentId." chage of the ".$marginName." in ".$incrementalValue."%";
			} else {
			    echo "The attachment ".$attachmentId." chage the ".$marginName." in ".$incrementalValue."%. The value on the DB for all Margins are: [".$margin."%]. For each one are: [".$marginTop."%,".$marginRight."%,".$marginBottom."%,".$marginLeft."%]. Z-index is ".$zIndex;
			}
        }
    }

    // Always die in functions echoing ajax content
   die();
}
add_action( 'wp_ajax_change_attachment_margin', 'change_attachment_margin' );


/************* Gallery attachment ordering *************/

/**
 * Reorder a post's attachments by a given mode, returning the new id order.
 *
 * Modes:
 *   - 'chronological' : by upload date (post_date) ascending
 *   - 'capture_time'  : by EXIF capture timestamp, falling back to upload date
 *   - 'random'        : shuffled (whole-gallery random honors the per-attachment
 *                       'skip_random' flag, placing those first in existing order)
 *
 * When $ids is empty, ALL of the post's attachments are reordered and their
 * menu_order renumbered 1..N. When $ids lists a subset (a modal selection),
 * only those are reordered IN PLACE: the menu_order slots they currently occupy
 * are kept, the selected items are sorted by $mode and reassigned to those same
 * slots, and unselected items don't move. The whole gallery is then renumbered
 * 1..N so the front end reflects the result.
 *
 * @param int    $post_id Post whose attachments to order.
 * @param string $mode    chronological | capture_time | random.
 * @param int[]  $ids     Optional subset of attachment IDs to reorder in place.
 * @return int[] The post's attachment IDs in their new order.
 */
function gallery_apply_attachment_order( $post_id, $mode, $ids = array() ) {
	$post_id = absint( $post_id );
	$allowed = array( 'chronological', 'capture_time', 'random' );

	if ( ! $post_id || ! in_array( $mode, $allowed, true ) ) {
		return array();
	}

	$attachments = get_children( array(
		'numberposts' => -1,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
		'post_parent' => $post_id,
		'post_status' => null,
		'post_type'   => 'attachment',
	) );

	if ( empty( $attachments ) ) {
		return array();
	}

	// Current menu_order sequence as a plain list of attachment objects.
	$ordered = array_values( $attachments );

	// EXIF capture time with upload-date fallback.
	$capture_time = function ( $att ) {
		$meta = wp_get_attachment_metadata( $att->ID );
		$ts   = isset( $meta['image_meta']['created_timestamp'] ) ? (int) $meta['image_meta']['created_timestamp'] : 0;
		return $ts > 0 ? $ts : strtotime( $att->post_date );
	};

	// Sort a list of attachment objects in place by the chosen mode.
	$sort = function ( &$list ) use ( $mode, $capture_time ) {
		if ( 'random' === $mode ) {
			shuffle( $list );
		} elseif ( 'capture_time' === $mode ) {
			usort( $list, function ( $a, $b ) use ( $capture_time ) {
				return $capture_time( $a ) - $capture_time( $b );
			} );
		} else { // chronological
			usort( $list, function ( $a, $b ) {
				return strtotime( $a->post_date ) - strtotime( $b->post_date );
			} );
		}
	};

	$ids = array_filter( array_map( 'absint', (array) $ids ) );

	if ( empty( $ids ) ) {
		// Whole gallery.
		if ( 'random' === $mode ) {
			// Keep skip_random attachments first, in their existing order.
			$fixed  = array();
			$random = array();
			foreach ( $ordered as $att ) {
				if ( get_field( 'skip_random', $att->ID ) ) {
					$fixed[] = $att;
				} else {
					$random[] = $att;
				}
			}
			shuffle( $random );
			$ordered = array_merge( $fixed, $random );
		} else {
			$sort( $ordered );
		}
	} else {
		// Subset: sort the selected items and drop them back into their own slots.
		$selected = array();
		$slots    = array();
		foreach ( $ordered as $i => $att ) {
			if ( in_array( (int) $att->ID, $ids, true ) ) {
				$selected[] = $att;
				$slots[]    = $i;
			}
		}
		if ( count( $selected ) > 1 ) {
			$sort( $selected );
			foreach ( $slots as $k => $slot ) {
				$ordered[ $slot ] = $selected[ $k ];
			}
		}
	}

	// Renumber menu_order 1..N to match the final order.
	$new_ids = array();
	$pos     = 0;
	foreach ( $ordered as $att ) {
		$pos++;
		wp_update_post( array(
			'ID'         => $att->ID,
			'menu_order' => $pos,
			'post_type'  => 'attachment',
		) );
		$new_ids[] = (int) $att->ID;
	}

	return $new_ids;
}

// ----------------------
// AJAX: Reorder a post's attachments by mode (media modal "Order" CTA)
// ----------------------
function gallery_reorder_attachments() {
	check_ajax_referer( 'gallery_reorder_attachments', '_nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$mode    = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : '';
	$ids     = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array();
	$ids     = array_filter( array_map( 'absint', $ids ) );

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ) );
	}

	$order = gallery_apply_attachment_order( $post_id, $mode, $ids );

	if ( empty( $order ) ) {
		wp_send_json_error( array( 'message' => 'Nothing to reorder.' ) );
	}

	tiagsspace_gallery_touch_parent( $post_id, true );

	wp_send_json_success( array( 'order' => $order ) );
}
add_action( 'wp_ajax_gallery_reorder_attachments', 'gallery_reorder_attachments' );


// ----------------------
// AJAX: Download a post's attachments as a single zip: all of them (the footer
// link, the editor bar's "Download all"), or only `ids` (comma-separated, the
// bar's "Download N" with a selection). Names keep each file's position in the
// whole gallery either way.
// ----------------------
function download_all_attachments() {
	check_ajax_referer( 'download_all_attachments', '_nonce' );

	$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Permission denied.', 'Download error', array( 'response' => 403 ) );
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( 'ZipArchive is not available on this server.', 'Download error', array( 'response' => 500 ) );
	}

	// Same attachment set AND order as the per-image download list in entry-body.php,
	// so the per-attachment position number matches between the two.
	$attachments = get_posts( array(
		'post_type'   => 'attachment',
		'numberposts' => -1,
		'post_parent' => $post_id,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
	) );

	if ( ! $attachments ) {
		wp_die( 'No attachments found for this post.', 'Download error', array( 'response' => 404 ) );
	}

	$only = array();
	if ( ! empty( $_REQUEST['ids'] ) ) {
		$only = array_flip( array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_REQUEST['ids'] ) ) ) ) );
	}

	$post_title = get_the_title( $post_id );

	$tmp_file = wp_tempnam( 'attachments-' . $post_id . '.zip' );
	$zip      = new ZipArchive();
	if ( $zip->open( $tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		@unlink( $tmp_file );
		wp_die( 'Could not create the zip archive.', 'Download error', array( 'response' => 500 ) );
	}

	$used_names = array();
	$added      = 0;
	$position   = 0;

	foreach ( $attachments as $attachment ) {
		$position++; // Menu-order position; incremented before any skip to stay in sync with the list.
		if ( $only && ! isset( $only[ $attachment->ID ] ) ) {
			continue; // Not in the selection.
		}
		$file_path = get_attached_file( $attachment->ID );
		if ( ! $file_path || ! is_readable( $file_path ) ) {
			continue; // Skip files missing on disk.
		}

		$extension = pathinfo( $file_path, PATHINFO_EXTENSION );
		$base_name = sanitize_file_name( $post_title . ' - attachement-' . sprintf( '%03d', $position ) . ' - ' . $attachment->post_title );
		$zip_name  = $base_name . ( $extension ? '.' . $extension : '' );

		// De-duplicate colliding names.
		if ( isset( $used_names[ $zip_name ] ) ) {
			$used_names[ $zip_name ]++;
			$zip_name = $base_name . '-' . $used_names[ $zip_name ] . ( $extension ? '.' . $extension : '' );
		} else {
			$used_names[ $zip_name ] = 1;
		}

		$zip->addFile( $file_path, $zip_name );
		$added++;
	}

	$zip->close();

	if ( ! $added ) {
		@unlink( $tmp_file );
		wp_die( 'None of the attachment files could be read.', 'Download error', array( 'response' => 404 ) );
	}

	$download_name = sanitize_file_name( ( get_post_field( 'post_name', $post_id ) ?: 'attachments-' . $post_id ) . ( $only ? '-' . $added : '' ) . '.zip' );

	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
	header( 'Content-Length: ' . filesize( $tmp_file ) );
	readfile( $tmp_file );
	@unlink( $tmp_file );
	exit;
}
add_action( 'wp_ajax_download_all_attachments', 'download_all_attachments' );
