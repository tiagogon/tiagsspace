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
