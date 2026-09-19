<?php
/**
 * Gallery admin functions and AJAX handlers.
 *
 * Includes: gallery edit UI (hide/delete/reorder/resize), AJAX handlers for
 * media order, attachment size, and margin changes.
 *
 * @package tiagsspace
 */

/************* Gallery Functions *************/


// Edit atachment media (image/video/etc) -- hide, delete and save gallery order
function gallery_edit_atachement_options($gallery_id,$attachment_count, $attachment_id) {

	// This function runs once per attachment, but the script blocks below are
	// shared — print them only on the first call to avoid stacked handlers.
	static $scripts_printed = false;

	// HIDE based on: https://stackoverflow.com/questions/40144638/how-to-remove-the-div-that-a-button-is-contained-in-when-the-button-is-clicked

	if ( ! $scripts_printed ) {
		echo '
			<script type="text/javascript">
				function removeDiv(btn){
					((btn.parentNode).parentNode).removeChild(btn.parentNode);

					if (typeof window.galleryKeyboardReorderReset === "function") {
						window.galleryKeyboardReorderReset();
					}

					// relayout masonry
					$("#gallery-'.$gallery_id.'").masonry(\'reloadItems\').masonry(\'layout\');
				}
			</script>
		';

		// DELETE based on: https://stackoverflow.com/questions/15729334/how-to-trigger-a-link-with-jquery-without-refreshing-the-page

		echo '
			<script type="text/javascript">
				$(document).on("click", ".delete", function(e){
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
				});
			</script>
		';

		$scripts_printed = true;
	}

	echo '
		<button class="remove" onclick="removeDiv(this);">HIDE (H/U)</button>
	';

	echo '
		<a class="delete" href="javascript:;" rel="' . wp_nonce_url( get_bloginfo('url') . '/wp-admin/post.php?action=delete&amp;post=' . $attachment_id, 'delete-post_' . $attachment_id) . '" onclick="removeDiv(this);">DELETE</a>
	';

	// Reveal the next hidden attachment right after this one. Order now
	// auto-saves on every drag (see entry-gallery.php onSort), so the old
	// "SAVE ORDER (S)" button is gone. Only render this when the post actually
	// has hidden attachments (count computed once per request per gallery).
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
	if ( $has_hidden_by_gallery[ $gallery_id ] ) {
		echo '
	<button class="nexthidden" onclick="revealNextHidden(this);">NEXT HIDDEN (N)</button>
	';
	}

	// Change Atachement Grid Size
	echo '
		<div class="itemPosition">
			<div class="GridSize">
				<button class="GridSizePlus" onclick="atachementGridSizeChange('.$attachment_id.', changeSize=\'increase\');">+ </button>
				<button class="GridSizeMinus" onclick="atachementGridSizeChange('.$attachment_id.', changeSize=\'decrease\');">- </button>
			</div>

			<div class="attachmentPosition">
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin\', changeSize=1);">M+ </button>
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin\', changeSize=\'clear\');">Mc</button>
				<button class="GridSizeMinus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin\', changeSize=-1);">M- </button>
			</div>

			<div class="attachmentPositionX">
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-left\', changeSize=1);">ML+ </button>
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-left\', changeSize=\'clear\');">MLc</button>
				<button class="GridSizeMinus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-left\', changeSize=-1);">ML- </button>
				<-->
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-right\', changeSize=1);">MR+ </button>
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-right\', changeSize=\'clear\');">MRc</button>
				<button class="GridSizeMinus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-right\', changeSize=-1);">MR- </button>
			</div>

			<div class="attachmentPositionY">
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-top\', changeSize=1);">MT+ </button>
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-top\', changeSize=\'clear\');">MTc</button>
				<button class="GridSizeMinus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-top\', changeSize=-1);">MT- </button>
				/--/
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-bottom\', changeSize=1);">MB+ </button>
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-bottom\', changeSize=\'clear\');">MBc</button>
				<button class="GridSizeMinus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'margin-bottom\', changeSize=-1);">MB- </button>
			</div>

			<div class="attachmentPositionZ">
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'z-index\', changeSize=1);">Z+ </button>
				<button class="GridSizePlus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'z-index\', changeSize=\'clear\');">Zc</button>
				<button class="GridSizeMinus" onclick="atachementChangeMargin('.$attachment_id.', marginName=\'z-index\', changeSize=-1);">Z- </button>
			</div>
		</div>
	';
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

// Default the media library modal filter to "Uploaded to this post" on post edit screens
function enqueue_media_library_default_uploaded() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}
	wp_enqueue_script(
		'media-library-default-uploaded',
		get_template_directory_uri() . '/library/js/media-library-default-uploaded.js',
		array( 'media-views' ),
		'1.1',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_media_library_default_uploaded' );

// Media-modal gallery CTAs: "Order" (Chronological/EXIF/Random) + "Delete hidden attachments"
function enqueue_media_gallery_actions() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' ) {
		return;
	}

	$path = get_template_directory() . '/library/js/media-gallery-actions.js';

	wp_enqueue_script(
		'media-gallery-actions',
		get_template_directory_uri() . '/library/js/media-gallery-actions.js',
		array( 'jquery', 'media-views' ),
		file_exists( $path ) ? filemtime( $path ) : '1.0',
		true
	);

	wp_localize_script(
		'media-gallery-actions',
		'galleryMediaActions',
		array(
			'ajaxurl'      => admin_url( 'admin-ajax.php' ),
			'reorderNonce' => wp_create_nonce( 'gallery_reorder_attachments' ),
			'hiddenNonce'  => wp_create_nonce( 'gallery_hidden_check' ),
			'i18n'         => array(
				'orderLabel'   => __( 'Order', 'tiagsspace' ),
				'chronological'=> __( 'Chronological', 'tiagsspace' ),
				'captureTime'  => __( 'Capture time (EXIF)', 'tiagsspace' ),
				'random'       => __( 'Random', 'tiagsspace' ),
				'applyAll'     => __( 'Apply to all', 'tiagsspace' ),
				'applyN'       => __( 'Apply (%d)', 'tiagsspace' ),
				'deleteHidden' => __( 'Delete hidden attachments (%d)', 'tiagsspace' ),
				'confirmDelete'=> __( 'Permanently delete %d hidden attachment(s)? This cannot be undone.', 'tiagsspace' ),
				'reorderError' => __( 'Could not reorder the gallery.', 'tiagsspace' ),
				'deleteError'  => __( 'Could not delete the hidden attachments.', 'tiagsspace' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_media_gallery_actions' );

// ----------------------
//
// AJAX: Atachments Order -- Change database wp_posts >> menu_order via Ajax request
// ----------------------
function gallery_media_order_change_request() {

    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) ) {


        $attachmentId = $_REQUEST['attachmentId'];
        $attachmentOrder = $_REQUEST['attachmentOrder'];

        // Let's take the data that was sent and do something with it
        if ( $attachmentId ) {
			$debug_result = $attachmentId + $attachmentOrder;

			global $wpdb;
			$updated = $wpdb->update( 'wp_posts', array( 'menu_order'=>$attachmentOrder),array('ID'=>$attachmentId));

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
// AJAX: Reorder hidden attachments after visible ones
// ----------------------
function gallery_reorder_hidden_attachments() {

	$post_id       = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
	$visible_count = isset( $_REQUEST['visible_count'] ) ? intval( $_REQUEST['visible_count'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( 'Permission denied.' );
	}

	$hidden = get_posts( array(
		'numberposts' => -1,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
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

	global $wpdb;
	$order = $visible_count;
	foreach ( $hidden as $att ) {
		$order++;
		$wpdb->update( 'wp_posts', array( 'menu_order' => $order ), array( 'ID' => $att->ID ) );
	}

	wp_send_json_success( 'Reordered ' . count( $hidden ) . ' hidden attachments after position ' . $visible_count );
}
add_action( 'wp_ajax_gallery_reorder_hidden_attachments', 'gallery_reorder_hidden_attachments' );

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

        $attachmentId = $_REQUEST['attachmentID'];
        $changeSize = $_REQUEST['changeSize'];

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

        $attachmentId = $_REQUEST['attachmentID'];
        $marginName = $_REQUEST['marginName'];
        $incrementalValue = $_REQUEST['incrementalValue'];

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

	wp_send_json_success( array( 'order' => $order ) );
}
add_action( 'wp_ajax_gallery_reorder_attachments', 'gallery_reorder_attachments' );


// ----------------------
// AJAX: Download all of a post's attachments as a single zip
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

	$download_name = sanitize_file_name( ( get_post_field( 'post_name', $post_id ) ?: 'attachments-' . $post_id ) . '.zip' );

	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
	header( 'Content-Length: ' . filesize( $tmp_file ) );
	readfile( $tmp_file );
	@unlink( $tmp_file );
	exit;
}
add_action( 'wp_ajax_download_all_attachments', 'download_all_attachments' );
