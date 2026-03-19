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

// change order of media atachments on database
	// via: https://wptheming.com/2013/07/simple-ajax-example/

// Activate ajax on the frontend

// define ajax url as a global variable on the frontend
if ( is_user_logged_in() ) {
	function example_ajax_enqueue() {
		// Enqueue javascript on the frontend.
		wp_enqueue_script(
			'example-ajax-script',
			get_template_directory_uri() . '/library/js/simple-ajax-example.js',
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
}

// Reset gallery ACF fields in the Gutenberg UI after save
function gallery_enqueue_editor_reset_script() {
	wp_enqueue_script(
		'gallery-reset-after-save',
		get_template_directory_uri() . '/library/js/gallery-reset-after-save.js',
		array(),
		'1.0',
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'gallery_enqueue_editor_reset_script' );

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


/************* Order images by chronological order *************/

// Reorder post attachments by date (chronological) on save.
// Triggered when ACF field 'order_media_attachments' is set to 'chronological'.
function reorder_images_by_date( $ID, $post ) {

    if( get_field('order_media_attachments') !== 'chronological') {
        return;
    }

    $args = array(
        'numberposts'       => -1,
        'orderby'           => 'date',
        'order'             => 'ASC',
        'post_parent'       => $post->ID,
        'post_status'       => null,
        'post_type'         => 'attachment'
    );

    $images = get_children( $args );

    if($images){

        // Order just the new added media
        if (get_field('order_just_the_new_added_pictures')) {

            // Find the highest existing menu_order
            $highest_menu_order = 0;

            foreach($images as $image){
                if ($highest_menu_order < $image->menu_order) {
                    $highest_menu_order = $image->menu_order;
                }
            }

            // Order unordered images from the highest menu order value up
            $count_item = $highest_menu_order;

            foreach($images as $image){
                if ($image->menu_order == 0) {
                    $count_item++;
                    wp_update_post( array(
                        'ID'           => $image->ID,
                        'menu_order'   => $count_item,
                        'post_type'    => 'attachment',
                    ));
                }
            }

        // Order all the media items
        } else {

            $count_item = 0;

            foreach($images as $image){
                $count_item++;
                wp_update_post( array(
                    'ID'           => $image->ID,
                    'menu_order'   => $count_item,
                    'post_type'    => 'attachment',
                ));
            }

        }

    }

    // Reset checkboxes after reordering
    update_field('order_media_attachments', false);
    update_field('order_just_the_new_added_pictures', false);
}
add_action( 'save_post', 'reorder_images_by_date', 10, 2 );


/************* Order images by capture time (EXIF) *************/

// Reorder post attachments by EXIF capture time on save.
// Triggered when ACF field 'order_media_attachments' is set to 'capture_time'.
// Falls back to DB upload date for attachments with no EXIF timestamp.
function reorder_images_by_capture_time( $ID, $post ) {

    if( get_field('order_media_attachments') !== 'capture_time') {
        return;
    }

    $args = array(
        'numberposts'       => -1,
        'orderby'           => 'menu_order',
        'order'             => 'ASC',
        'post_parent'       => $post->ID,
        'post_status'       => null,
        'post_type'         => 'attachment'
    );

    $attachments = get_children( $args );

    if ( $attachments ) {

        // Helper: get capture timestamp from EXIF, falling back to upload date
        $get_capture_time = function( $attachment ) {
            $meta = wp_get_attachment_metadata( $attachment->ID );
            $ts = isset( $meta['image_meta']['created_timestamp'] ) ? (int) $meta['image_meta']['created_timestamp'] : 0;
            return $ts > 0 ? $ts : strtotime( $attachment->post_date );
        };

        if ( get_field('order_just_the_new_added_pictures') ) {

            // Find the highest existing menu_order
            $highest_menu_order = 0;
            foreach ( $attachments as $attachment ) {
                if ( $highest_menu_order < $attachment->menu_order ) {
                    $highest_menu_order = $attachment->menu_order;
                }
            }

            // Collect unordered attachments (menu_order == 0) and sort by capture time
            $new_attachments = array();
            foreach ( $attachments as $attachment ) {
                if ( $attachment->menu_order == 0 ) {
                    $new_attachments[] = $attachment;
                }
            }
            usort( $new_attachments, function( $a, $b ) use ( $get_capture_time ) {
                return $get_capture_time( $a ) - $get_capture_time( $b );
            });

            $count_item = $highest_menu_order;
            foreach ( $new_attachments as $attachment ) {
                $count_item++;
                wp_update_post( array(
                    'ID'           => $attachment->ID,
                    'menu_order'   => $count_item,
                    'post_type'    => 'attachment',
                ));
            }

        } else {

            // Sort all attachments by capture time
            $attachments_array = array_values( $attachments );
            usort( $attachments_array, function( $a, $b ) use ( $get_capture_time ) {
                return $get_capture_time( $a ) - $get_capture_time( $b );
            });

            $count_item = 0;
            foreach ( $attachments_array as $attachment ) {
                $count_item++;
                wp_update_post( array(
                    'ID'           => $attachment->ID,
                    'menu_order'   => $count_item,
                    'post_type'    => 'attachment',
                ));
            }

        }

    }

    // Reset checkboxes after reordering
    update_field('order_media_attachments', false);
    update_field('order_just_the_new_added_pictures', false);
}
add_action( 'save_post', 'reorder_images_by_capture_time', 10, 2 );


/************* Order images by random order *************/

// Reorder post attachments in random order on save.
// Triggered when ACF field 'order_media_attachments' is set to 'random'.
// Attachments with ACF field 'skip_random' are placed first in their existing order.
function reorder_images_by_random_order( $ID, $post ) {

    if( get_field('order_media_attachments') !== 'random') {
        return;
    }

    $args = array(
        'numberposts'       => -1,
        'orderby'           => 'menu_order',
        'order'             => 'ASC',
        'post_parent'       => $post->ID,
        'post_status'       => null,
        'post_type'         => 'attachment'
    );

    $attachments = get_children($args);

    if($attachments){

        $number_attachments_skiping_order = 0;

        // Assign first menu_order positions to attachments that skip random order
        foreach($attachments as $attachment){
            if ( get_field("skip_random", $attachment->ID) == true ) {
                $number_attachments_skiping_order++;
                wp_update_post( array(
                    'ID'           => $attachment->ID,
                    'menu_order'   => $number_attachments_skiping_order,
                    'post_type'    => 'attachment',
                ));
            }
        }

        // Create a shuffled array of order numbers for the remaining attachments
        $atachments_count = count($attachments);
        $menu_order = range($number_attachments_skiping_order + 1, $atachments_count);
        shuffle($menu_order);

        $count_item = 0;

        // Assign random order to every non-skipped attachment
        foreach($attachments as $attachment){
            if ( get_field("skip_random", $attachment->ID) == false ) {
                wp_update_post( array(
                    'ID'           => $attachment->ID,
                    'menu_order'   => $menu_order[$count_item],
                    'post_type'    => 'attachment',
                ));
                $count_item++;
            }
        }

    }

    // Reset checkbox after reordering
    update_field('order_media_attachments', false);
}
add_action( 'save_post', 'reorder_images_by_random_order', 10, 2 );


/************* Re-attach images from post editor *************/

// Move attachments from one post to another on save.
// Supports two modes: 'keep' (move all EXCEPT selected) or 'reattach' (move only selected).
function re_attach_images_from_post_editor( $ID, $post ) {

    if( get_field('re-attach_images_from_post_editor') != true ) {
        return;
    }

    $re_attach_mode = get_field('re-attach_mode');

    // Get the IDs of manually selected images
    $images_selected = get_field('selected_images');
    $image_selected_IDs = array();
    if( $images_selected ) {
        foreach( $images_selected as $image ) {
            $image_selected_IDs[] = $image['ID'];
        }
    }

    // Get orientation checkboxes
    $select_vertical   = get_field('select_all_vertical_images');
    $select_horizontal = get_field('select_all_horizontal_images');

    // Get the destination post ID
    $destination_post_ids = get_field('destination_post');
    $destination_post_id = $destination_post_ids[0];

    $args = array(
        'numberposts'       => -1,
        'orderby'           => 'date',
        'order'             => 'ASC',
        'post_parent'       => $post->ID,
        'post_status'       => null,
        'post_type'         => 'attachment'
    );

    $images = get_children( $args );

    if($images){

        // Merge orientation-based images into the selection
        if ( $select_vertical || $select_horizontal ) {
            foreach ( $images as $image ) {
                if ( in_array( $image->ID, $image_selected_IDs ) ) {
                    continue;
                }
                $meta = wp_get_attachment_metadata( $image->ID );
                if ( ! $meta || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
                    continue;
                }
                $is_vertical   = $meta['height'] > $meta['width'];
                $is_horizontal = $meta['width'] > $meta['height'];
                if ( ( $select_vertical && $is_vertical ) || ( $select_horizontal && $is_horizontal ) ) {
                    $image_selected_IDs[] = $image->ID;
                }
            }
        }

        foreach($images as $image){

            // In 'keep' mode, move images NOT in the selection
            // In 'reattach' mode, move images IN the selection
            $in_selection = in_array($image->ID, $image_selected_IDs);
            $should_move = ($re_attach_mode == 'keep') ? !$in_selection : $in_selection;

            if($should_move) {
                wp_update_post( array(
                    'ID'           => $image->ID,
                    'post_parent'  => $destination_post_id,
                    'post_type'    => 'attachment',
                ));
            }

        }

    }

    // Reset fields after re-attaching
    update_field('re-attach_images_from_post_editor', false);
    update_field('destination_post', array());
    update_field('selected_images', array());
    update_field('select_all_vertical_images', false);
    update_field('select_all_horizontal_images', false);
}
add_action( 'save_post', 're_attach_images_from_post_editor', 10, 2 );
