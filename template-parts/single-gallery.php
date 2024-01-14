<?php
/*
Gallery template for single pages
*/

// if Gallery is Activated
if (!get_field('deactivate_gallery')) {

    // get attachmens atached to the post
    $args = array(
        'numberposts'       => -1, // Using -1 loads all posts
        'orderby'           => 'menu_order', // set in the page media manager
        'order'             => 'ASC',
        // 'post_mime_type'    => 'image', // Make sure it doesn't pull other resources, like videos
        'post_parent'       => $post->ID, // Important part - ensures the associated images are loaded
        'post_status'       => null,
        'post_type'         => 'attachment'
    );
    $attachmens = get_children( $args );


    if($attachmens){

		// --- Extra contente // Videos ---
	    // --------------------------------

	    // creat a empety array where the IDs of the images before the content will be
		$extra_content = array();

		if (have_rows('extra_content')) {
			while ( have_rows('extra_content')) : the_row();

				$extra_content[] = array( get_sub_field('the_image_before'), get_sub_field('vimeo_id'));

			endwhile;

            // GET VIMEO API SCRIPT from CDN?>
            <script src="//f.vimeocdn.com/js/froogaloop2.min.js"></script>

        <?php }


        // -------------------------------
        // --- Sefault Galerry Settings ---
        // --------------------------------

        // Define Variables
        $spacement = '';
        $deactivat_masonry = false;
        $there_is_video = '';
        $there_is_3d = '';
        $light_box = 'none';


        if ( !get_field('alternative_gallery')) {

            // Dusk posts
            if ( is_singular( 'dusk' )) {
                $class_container        = 'container-fluid';
                $number_of_columns_xs   = 1;
                $number_of_columns_sm   = 2;
                $number_of_columns_md   = 3;
                $number_of_columns_lg   = 3;
                $no_space               = 'no-pad';
                $spacement              = '';
                $light_box              = 'magnific_popup';

            }

            // HYPER
            elseif ( is_singular( 'hyper' )) {
                $class_container        = 'container-fluid';
                $number_of_columns_xs   = 2;
                $number_of_columns_sm   = 4;
                $number_of_columns_md   = 4;
                $number_of_columns_lg   = 4;
                $no_space               = 'no-pad';
                $light_box              = 'intense-images';
                $deactivat_masonry      = true;
            }

            // Emulsion
            elseif ( is_singular( 'emulsion' )) {
                $class_container        = 'container';
                $number_of_columns_xs   = 1;
                $number_of_columns_sm   = 1;
                $number_of_columns_md   = 1;
                $number_of_columns_lg   = 1;
                $no_space               = '';
                $light_box              = 'magnific_popup';
            }

            // City Burns
            elseif ( is_singular( 'cityburns' )) {
                $class_container        = 'container-fluid';
                $number_of_columns_xs   = 1;
                $number_of_columns_sm   = 1;
                $number_of_columns_md   = 3;
                $number_of_columns_lg   = 3;
                $no_space               = '';
                $spacement              = 'spacement-20';
                $light_box              = 'magnific_popup';
            }

            // Others
            else {
                if (has_term( 'blwww', 'log-branch' ) ) {
                    $class_container        = 'container-fluid';
                    $number_of_columns_xs   = 2;
                    $number_of_columns_sm   = 4;
                    $number_of_columns_md   = 4;
                    $number_of_columns_lg   = 4;
                    $no_space               = 'no-pad';
                    $light_box              = 'none';
                    $deactivat_masonry      = true;
                } elseif (has_term( 'hrzn', 'log-branch' ) ) {
                    $class_container        = 'container-fluid';
                    $number_of_columns_xs   = 1;
                    $number_of_columns_sm   = 1;
                    $number_of_columns_md   = 1;
                    $number_of_columns_lg   = 1;
                    $no_space               = 'no-pad';
                    $light_box              = 'none';
                    $deactivat_masonry      = true;
                } elseif (has_term( 'still', 'log-branch' ) ) {
                      $class_container        = 'container-fluid';
                      $number_of_columns_xs   = 1;
                      $number_of_columns_sm   = 1;
                      $number_of_columns_md   = 1;
                      $number_of_columns_lg   = 1;
                      $no_space               = 'no-pad';
                      $light_box              = 'none';
                      $deactivat_masonry      = true;
                  } else {
                    $class_container        = 'container';
                    $number_of_columns_xs   = 1;
                    $number_of_columns_sm   = 1;
                    $number_of_columns_md   = 1;
                    $number_of_columns_lg   = 1;
                    $no_space               = '';
                    $light_box              = 'none';
                }
            }
        }




        // DEFAULT VIDEO PLAYER OPTIONS
        // THESE WILL OVERRIDE ANYTHING YOU’VE SET IN THE PLUGIN SETTINGS OR ATTACHMENT DETAILS

            // $video_endofvideooverlay = "http://www.example.com/end_image.jpg"; // sets the image shown when the video ends.
            // $video_volume = "0.x"; // pre-sets the volume for unusually loud videos. Value between 0 and 1.
                // $post_video_mute = "true/false"; // sets the mute button on or off.
            // $post_video_controlbar = "docked/floating/none"; // sets the controlbar position. Video.js only responds to the “none” option.
                // $post_video_loop = "true/false"
                // $post_video_autoplay = "true/false"
                // $post_video_pauseothervideos = "false"; // video will pause other videos on the page when it starts playing.
                // $post_video_preload = "metadata/auto"; // indicate how much of the video should be loaded when the page loads. Metadata just loads video dimentsions for rendering
            // $post_video_start = "mm:ss"; // video will start playing at this timecode.
            // $post_video_watermark = "http://www.example.com/image.png"; // or "false"; // to disable.
            // $post_video_watermark_link_to = "home/parent/attachment/download/false"
            // $post_video_watermark_url = "http://www.example.com/"; // or "false"; // to disable. If this is set, it will override the watermark_link_to setting.
            // $post_video_title = "Video Title"; // or "false"; // to disable.
            // $post_video_embedcode = "html code"; // changes text displayed in the embed code overlay in order to provide a custom method for embedding a video or "false"; // to disable.
            // $post_video_view_count = "true/false"; // turns the view count on or off.
            // $post_video_caption = "Caption"; // text that is displayed below the video (not subtitles or closed captioning)
            // $post_video_description = "Description"; // Used for metadata only.
            // $post_video_downloadlink = "true/false"; // generates a link below the video to make it easier for users to save the video file to their computers.
            // post_video_right_click = "true/false"; // allow or disable right-clicking on the video player.
            // $post_video_resize = "true/false"; // allow or disable responsive resizing.
            // $post_video_auto_res = "automatic/highest/lowest"; // specify the video resolution when the page loads.
            // $post_video_pixel_ratio = "true/false"; // account for high-density (retina) displays when choosing automatic video resolution.
            // $post_video_schema = "true/false"; // allow or disable Schema.org search engine metadata.


        //THESE OPTIONS WILL ONLY AFFECT VIDEO.JS PLAYBACK

            // $post_video_skin = "example-css-class"; // Completely change the look of the video player. Video.js provides a custom skin designer here.
            // $post_video_nativecontrolsfortouch = "true/false disable Video.js"; // styling and show the built-in video controls on mobile devices. This will disable the resolution selection button.


        // DEFAULT VIDEO PLAYER OPTIONS PER POST TYPE

            //define variables:
            $post_video_controls = true;
            $post_video_mute = '';
            $post_video_loop = '';
            $post_video_autoplay = '';
            $post_video_pauseothervideos = '';
            $post_video_preload = '';
            $post_video_schema = '';

            // Dusk posts
            if ( is_singular( 'dusk' )) {

            }

            // HYPER
            elseif ( is_singular( 'hyper' )) {
                $post_video_schema = false;
                $post_video_controls = true;
            }

            // Emulsion
            elseif ( is_singular( 'log' )) {
                $post_video_controls = false;
                $post_video_mute = true;
                $post_video_loop = true;
                $post_video_autoplay = true;
                $post_video_pauseothervideos = false;
                $post_video_schema = "false";

            }

            // Others
            else {
                // LOG > blwww brunch
                if (has_term( 'blwww', 'log-branch' ) ) {
                    $post_video_controls = false;
                    $post_video_mute = true;
                    $post_video_loop = true;
                    $post_video_autoplay = true;
                    $post_video_pauseothervideos = false;
                    $post_video_schema = "false";

                }
            }


        // DEFAULT VIDEO PLAYER OPTIONS PER POST TYPE
            if ( get_field('alternative_video_player_options_on_post')) {
                $post_video_controls = get_field('post_video_controls');
                $post_video_mute = get_field('post_video_mute');
                $post_video_loop = get_field('post_video_loop');
                $post_video_autoplay = get_field('post_video_autoplay');
                $post_video_pauseothervideos = get_field('post_video_pauseothervideos');
                $post_video_preload = get_field('post_video_preload');
                $post_video_schema = get_field('post_video_schema');
            }


        // -------------------------------
        // --- Alternative Settings gallery checked
        // -------------------------------
        if ( get_field('alternative_gallery')) {

            // Container or Full width
            $class_container = get_field('container');

            // Number of columns
            // Get the number of columns or set 1 as default
            if (get_field('columns_xs')) {
                $number_of_columns_xs = get_field('columns_xs');
            } else {
                $number_of_columns_xs = 1;
            }
            if (get_field('columns_sm')) {
                $number_of_columns_sm = get_field('columns_sm');
            } else {
                $number_of_columns_sm = $number_of_columns_xs ;
            }
            if (get_field('columns_md')) {
                $number_of_columns_md = get_field('columns_md');
            } else {
                $number_of_columns_md = $number_of_columns_sm;
            }
            if (get_field('columns_lg')) {
                $number_of_columns_lg = get_field('columns_lg');
            } else {
                $number_of_columns_lg = $number_of_columns_md;
            }

            // Space betwene images
            $no_space           = '';
            if ( get_field('no_space')) {
                $no_space           = 'no-pad';
            }

            if (get_field('spacement')) {
                $spacement = get_field('spacement');
            }

            $deactivat_masonry = get_field('deactivat_masonry');

            // Image Box
            $light_box = get_field('light_box');
        }


        // -------------------------------
        // --- Final calculations
        // -------------------------------


        // Row -- add margin on the sides
        $row = 'row';
        if (($class_container == 'container-fluid') AND ($no_space == '')) {
            $row = 'row row-inverted'; // add margin on the sides
        }

        // Max Height for the Img
        $max_height = '';
        if ($number_of_columns_lg == 1) {
            $max_height = 'max-height';
        }



        // Intense Images
        $intense = '';
        if ($light_box == 'intense-images') {
            $intense = 'intense';?>
            <script src='<?php bloginfo('template_url'); ?>/library/js/intense-images/intense.min.js'></script><?php
        }

        // Magnific Pop Up gallery
        $magnific_popup = "";
        if ($light_box == 'magnific_popup') {
            $magnific_popup = "magnific_popup-gallery-".$post->ID;
            // Magnific Popup core JS file
            ?><script src="<?php bloginfo('template_url'); ?>/library/js/Magnific-Popup/dist/jquery.magnific-popup.min.js"></script>
        <?php } ?>

        <div class="<?php echo $class_container; ?> <?php echo $spacement;?> container-gallery">

            <div id="gallery-<?php the_ID(); ?>" class="gallery clearfix <?php echo $magnific_popup;?>  <?php echo $row;?> <?php echo $no_space;?>" itemscope itemtype="http://schema.org/ImageGallery">

                <?php
                $count_item = 0;

                // loop the atached images
                foreach($attachmens as $attachmen){

                	//Check IF atachment is not to be removed
                	if (!get_field('remove_from_default_gallery',$attachmen->ID)) {

                        $count_item++;

                        // Gallery item size factor
                        $size_on_gallery_factor = 1;

                        // Diferent image size factor
                        if (get_field('diferent_size_on_gallery',$attachmen->ID)) {
                            $size_on_gallery_factor = get_field('diferent_size_on_gallery',$attachmen->ID);
                        }

                        // If Gallery item size factor is making item smaller, deactivate it on mobile
                        if ($size_on_gallery_factor > 1) {
                            $number_of_columns_item_xs   = $number_of_columns_xs; // deactiave on mobile //* $size_on_gallery_factor;
                        } else {
                            $number_of_columns_item_xs   = $number_of_columns_xs * $size_on_gallery_factor;
                        }


                        if ($number_of_columns_item_xs < 1) {
                            $number_of_columns_item_xs = 1;
                        } elseif(
                            $number_of_columns_item_xs > 12) { $number_of_columns_item_xs = 12;
                        }

                        $number_of_columns_item_sm   = $number_of_columns_sm * $size_on_gallery_factor;
                        if ($number_of_columns_item_sm < 1) {
                            $number_of_columns_item_sm = 1;
                        } elseif(
                            $number_of_columns_item_sm > 12) { $number_of_columns_item_sm = 12;
                        }

                        $number_of_columns_item_md   = $number_of_columns_md * $size_on_gallery_factor;
                        if ($number_of_columns_item_md < 1) {
                            $number_of_columns_item_md = 1;
                        } elseif(
                            $number_of_columns_item_md > 12) { $number_of_columns_item_md = 12;
                        }

                        $number_of_columns_item_lg   = $number_of_columns_lg * $size_on_gallery_factor;
                        if ($number_of_columns_item_lg < 1) {
                            $number_of_columns_item_lg = 1;
                        } elseif(
                            $number_of_columns_item_lg > 12) { $number_of_columns_item_lg = 12;
                        }


                        // calculate bootstrap responsive classes from the number of columns WITH FACTOR
                        $class_thumbnail_xs = "";
                        $class_thumbnail_sm = "";
                        $class_thumbnail_md = "";
                        $class_thumbnail_lg = "";


                        if ($number_of_columns_item_xs) {
                            $grid_number_xs        = (int) (48 / $number_of_columns_item_xs);
                            // If its 48, so its just one colum and thats not necessary
                                $class_thumbnail_xs    = "col-".$grid_number_xs." ";
                        }

                        if ($number_of_columns_item_sm) {
                            $grid_number_sm        = (int) (48 / $number_of_columns_item_sm);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_sm != $grid_number_xs) {
                                $class_thumbnail_sm    = "col-sm-".$grid_number_sm." ";
                            }
                        }

                        if ($number_of_columns_item_md) {
                            $grid_number_md        = (int) (48 / $number_of_columns_item_md);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_md != $grid_number_sm) {
                                $class_thumbnail_md    = "col-md-".$grid_number_md." ";
                            }
                        }

                        if ($number_of_columns_item_lg) {
                            $grid_number_lg        = (int) (48 / $number_of_columns_item_lg);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_lg != $grid_number_md) {
                                $class_thumbnail_lg    = "col-lg-".$grid_number_lg." ";
                            }
                        }

                        $class_thumbnail = "";
                        $class_thumbnail = $class_thumbnail_xs.$class_thumbnail_sm.$class_thumbnail_md.$class_thumbnail_lg;



                        // calculate bootstrap responsive classes from the number of columns WITHOUT FACTOR
                        $class_thumbnail_xs_without_factor = "";
                        $class_thumbnail_sm_without_factor = "";
                        $class_thumbnail_md_without_factor = "";
                        $class_thumbnail_lg_without_factor = "";


                        if ($number_of_columns_xs) {
                            $grid_number_xs_without_factor        = (int) (48 / $number_of_columns_xs);
                            // If its 48, so its just one colum and thats not necessary
                                $class_thumbnail_xs_without_factor    = "col-".$grid_number_xs_without_factor." ";
                        }

                        if ($number_of_columns_sm) {
                            $grid_number_sm_without_factor        = (int) (48 / $number_of_columns_sm);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_sm_without_factor != $grid_number_xs_without_factor) {
                                $class_thumbnail_sm_without_factor    = "col-sm-".$grid_number_sm_without_factor." ";
                            }
                        }

                        if ($number_of_columns_md) {
                            $grid_number_md_without_factor        = (int) (48 / $number_of_columns_md);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_md_without_factor != $grid_number_sm_without_factor) {
                                $class_thumbnail_md_without_factor    = "col-md-".$grid_number_md_without_factor." ";
                            }
                        }

                        if ($number_of_columns_lg) {
                            $grid_number_lg_without_factor        = (int) (48 / $number_of_columns_lg);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_lg_without_factor != $grid_number_md_without_factor) {
                                $class_thumbnail_lg_without_factor    = "col-lg-".$grid_number_lg_without_factor." ";
                            }
                        }

                        $class_thumbnail_without_factor = "";
                        $class_thumbnail_without_factor = $class_thumbnail_xs_without_factor.$class_thumbnail_sm_without_factor.$class_thumbnail_md_without_factor.$class_thumbnail_lg_without_factor;




                        // --- SRC calculation ---

                        // If not a 3D atachement
                        if ($attachmen->post_mime_type != "application/octet-stream") {

                            // Original image File
    	                    $attachmen_thumb_attributes = wp_get_attachment_image_src($attachmen->ID, false);
                          if ($attachmen_thumb_attributes) {
                            $attachmen_thumb_srcset = $attachmen_thumb_attributes[0]." ".$attachmen_thumb_attributes[1]."w";
                          }


                            // IF diferent sizes exist

                            // thumbnail
                            $attachmen_thumb_thumbnail_srcset ="";
                            $attachmen_thumb_thumbnail_attributes = wp_get_attachment_image_src($attachmen->ID, "thumbnail");
                            if ($attachmen_thumb_thumbnail_attributes) {
                              if ($attachmen_thumb_thumbnail_attributes[3]) {
                                  $attachmen_thumb_thumbnail_srcset = $attachmen_thumb_thumbnail_attributes[0]." ".$attachmen_thumb_thumbnail_attributes[1]."w, ";
                              }
                            }


                            // small
                            $attachmen_thumb_small_srcset ="";
                            $attachmen_thumb_small_attributes = wp_get_attachment_image_src($attachmen->ID, "small");
                            if ($attachmen_thumb_small_attributes) {
                              if ($attachmen_thumb_small_attributes[3]) {
                                  $attachmen_thumb_small_srcset = $attachmen_thumb_small_attributes[0]." ".$attachmen_thumb_small_attributes[1]."w, ";
                              }
                            }


                            // medium
                            $attachmen_thumb_medium_srcset ="";
                            $attachmen_thumb_medium_attributes = wp_get_attachment_image_src($attachmen->ID, "medium");
                            if ($attachmen_thumb_medium_attributes) {
                              if ($attachmen_thumb_medium_attributes[3]) {
                                  $attachmen_thumb_medium_srcset = $attachmen_thumb_medium_attributes[0]." ".$attachmen_thumb_medium_attributes[1]."w, ";
                              }
                            }


                            // large
                            $attachmen_thumb_large_srcset ="";
                            $attachmen_thumb_large_attributes = wp_get_attachment_image_src($attachmen->ID, "large");
                            if ($attachmen_thumb_large_attributes) {
                              if ($attachmen_thumb_large_attributes[3]) {
                                  $attachmen_thumb_large_srcset = $attachmen_thumb_large_attributes[0]." ".$attachmen_thumb_large_attributes[1]."w, ";
                              }
                            }

                            // FINAL SRCSET
                            $attachmen_srcset = $attachmen_thumb_thumbnail_srcset.
                                            $attachmen_thumb_small_srcset.
                                            $attachmen_thumb_medium_srcset.
                                            $attachmen_thumb_large_srcset.
                                            $attachmen_thumb_srcset;
                            // --- Sizes ---
                            //Container or Full width?
                            if ($class_container == 'container') {
                                $container_size_lg = 1200;
                                // $container_size_md = 940;
                                // $container_size_sm = 720;

                                $size_lg = ($container_size_lg / $number_of_columns_item_lg)."px";
                                // $size_md = ($container_size_md / $number_of_columns_item_md)."px";
                                // $size_sm = ($container_size_sm / $number_of_columns_item_sm)."px";
                                $size_md = (100 / $number_of_columns_item_md)."vw";
                                $size_sm = (100 / $number_of_columns_item_sm)."vw";
                                $size_xs = (100 / $number_of_columns_item_xs)."vw";

                                $attachmen_sizes = "(min-width: 1240px) ".$size_lg.",
                                                 ".$size_xs;
                            } else {
                                $size_lg = (100 / $number_of_columns_item_lg)."vw";
                                $size_md = (100 / $number_of_columns_item_md)."vw";
                                $size_sm = (100 / $number_of_columns_item_sm)."vw";
                                $size_xs = (100 / $number_of_columns_item_xs)."vw";

                                $attachmen_sizes = "(min-width: 992px) ".$size_lg.",
                                                (min-width: 768px) ".$size_md.",
                                                (min-width: 576px) ".$size_sm.",
                                                 ".$size_xs;
                            }

                            // Source code
                            if (!get_field('insert_src_of_higher_resolution',$attachmen->ID)) {
                                $source = 'srcset="'.$attachmen_srcset.'" sizes="'.$attachmen_sizes.'" ';
                            } else {
                                $source = 'src="'.$attachmen_thumb_attributes[0].'"';
                            }

                          } // Close: If not 3D

                        // --- AlT ---
                            $alt = get_post_meta($attachmen->ID, '_wp_attachment_image_alt', true);
                            $caption = $attachmen->post_excerpt;
                            $attachmen_post_title = get_the_title();
                            if ($alt) {
                                $attachmen_alt = $alt;
                            } elseif($caption) {
                                $attachmen_alt = $caption." – ".$attachmen_post_title;
                            }else{
                                $attachmen_alt = $attachmen_post_title." – ".$count_item;
                            }


                        // INTRINSTIC RATIO

                            // AUDIO or 3D
                            if ( $attachmen->post_mime_type == "audio/wav" OR $attachmen->post_mime_type == "audio/mpeg") {

                                // TO CODE
                                // $intrinsic_ratio of audio container

                            // VIDEO
                            } elseif ( $attachmen->post_mime_type == "video/mpeg" OR $attachmen->post_mime_type == "video/mp4" OR $attachmen->post_mime_type == "video/quicktime" ) {

                              // --- Videopacl bug does not make this work:
                              // $video_metadata = wp_get_attachment_metadata( $attachmen->ID );
                            	// $intrinsic_ratio = $video_metadata['height'] * 100 / $video_metadata['width'];

                              // Get Heigh and width from featured thumbnail instead of video proprieties
                              $attachmen_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($attachmen->ID), false);
                              $intrinsic_ratio = $attachmen_thumb_attributes[2] * 100 / $attachmen_thumb_attributes[1];

                            // 3D
                            } elseif ( $attachmen->post_mime_type == "application/octet-stream" ) {
                              $intrinsic_ratio = 1*100/1;

                            }else {

                                $intrinsic_ratio = $attachmen_thumb_attributes[2] * 100 / $attachmen_thumb_attributes[1];

                            }


                        // COMPILE VIDEO OPTIONS

                            // IF there are DEFAULT VIDEO PLAYER OPTIONS of the ATTACHEMENT
                            if (get_field('alternative_video_player_options',$attachmen->ID)) {
                                // need to convert boleans into true/false strings >> https://stackoverflow.com/questions/2795177/how-to-convert-boolean-to-string
                                $video_controls = (get_field('video_controls',$attachmen->ID)) ? 'true' : 'false';
                                $video_mute = (get_field('video_mute',$attachmen->ID)) ? 'true' : 'false';
                                $video_loop = (get_field('video_loop',$attachmen->ID)) ? 'true' : 'false';
                                $video_autoplay = (get_field('video_autoplay',$attachmen->ID)) ? 'true' : 'false';
                                $video_pauseothervideos = (get_field('video_pauseothervideos',$attachmen->ID)) ? 'true' : 'false';
                                $video_preload = get_field('video_preload',$attachmen->ID); // NOT A BOLEAN - string
                                $post_video_schema = get_field('video_schema',$attachmen->ID);

                            // Else use them on the post level
                            } else {
                                $video_controls = ($post_video_controls) ? 'true' : 'false';
                                $video_mute = ($post_video_mute) ? 'true' : 'false';
                                $video_loop = ($post_video_loop) ? 'true' : 'false';
                                $video_autoplay = ($post_video_autoplay) ? 'true' : 'false';
                                $video_pauseothervideos = ($post_video_pauseothervideos) ? 'true' : 'false';
                                $video_preload = $post_video_preload; // NOT A BOLEAN - string
                                $video_schema = ($post_video_schema) ? 'true' : 'false';
                            }

                            // COMPILE VIDEO OPTIONS STRING

                                $video_otions = '
                                    controls="'.$video_controls.'"
                                    mute="'.$video_mute.'"
                                    loop="'.$video_loop.'"
                                    autoplay="'.$video_autoplay.'"
                                    pauseothervideos="'.$video_pauseothervideos.'"
                                    preload="'.$video_preload.'"
                                    Schema="'.$video_schema.'"';


                        // add masonry-item-sizer for Masonry responsive calculations
                        if ($count_item == 1 AND $number_of_columns_lg > 1 AND $deactivat_masonry == false) { ?>

                            <div class="masonry-item-sizer <?php //echo $class_thumbnail_without_factor;?> col-4"></div>

                        <?php }?>


                        <?php

                        // ----------------------------------------
                        // --- Images and AUDIO from Media library
                        // ----------------------------------------

                        // AUDIO
                        if ( $attachmen->post_mime_type == "audio/wav" OR $attachmen->post_mime_type == "audio/mpeg" ) { ?>

                            <div class="thumbnail item <?php echo $class_thumbnail;?> media-audio attachmen-<?php echo $count_item;?>"  attachmentId="<?php echo $attachmen->ID;?>" attachmentOrder="<?php echo $attachmen->menu_order;?>">

                                <?php
                                // Edit atachment media -- hide and delete
                                if (is_user_logged_in() && is_preview() && !get_field('animation_number_of_attachment_shown') ) {
                                    $gallery_id = get_the_ID();
                                    gallery_edit_atachement_options($gallery_id, $count_item, $attachmen->ID );
                                }
                                ?>

                                <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject" style="<?php atachement_custom_margin($attachmen->ID); ?>">
                                    <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">

                                            <?php

                                            $audio_url = wp_get_attachment_url( $attachmen->ID );?>

                                            <audio class="plyr" controls>
                                                  <source src="<?php echo $audio_url;?>" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>




                                    </div>
                                </figure>
                            </div><?php

                        // VIDEO
                        } elseif ( $attachmen->post_mime_type == "video/mpeg" OR $attachmen->post_mime_type == "video/mp4" OR $attachmen->post_mime_type == "video/quicktime" ) {

                            ?>

                            <div class="thumbnail item <?php echo $class_thumbnail;?> media-video video-id-<?php echo $attachmen->ID;?> attachmen-<?php echo $count_item;?>"  attachmentId="<?php echo $attachmen->ID;?>" attachmentOrder="<?php echo $attachmen->menu_order;?>">

                                <?php
                                // Edit atachment media -- hide and delete
                                if (is_user_logged_in() && is_preview() && !get_field('animation_number_of_attachment_shown') ) {
                                    $gallery_id = get_the_ID();
                                    gallery_edit_atachement_options($gallery_id, $count_item, $attachmen->ID );
                                }
                                ?>

                                <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/VideoObject" style="<?php atachement_custom_margin($attachmen->ID); ?>">
                                    <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">

                                            <?php

                                            echo do_shortcode('[KGVID id="'.$attachmen->ID.'" '.$video_otions.' ]');

                                            echo "<style>
                                                        .video-id-".$attachmen->ID." .vjs-fluid {padding-top: ".$intrinsic_ratio."%!important;
                                                        }
                                                    </style>";
                                            if ($video_mute == "true") {
                                                echo "<style>
                                                    .video-id-".$attachmen->ID." .vjs-volume-menu-button {display: none!important;}
                                                </style>";
                                            }

                                            // To activate scripts on the bootom
                                            $there_is_video = true;
                                             ?>
                                    </div>
                                </figure>
                            </div><?php

                        // 3D render
                        } elseif ( $attachmen->post_mime_type == "application/octet-stream" ) {
                          $there_is_3d = true;
                          ?>

                          <div class="thumbnail item <?php echo $class_thumbnail;?> media-3d 3d-id-<?php echo $attachmen->ID;?> attachmen-<?php echo $count_item;?>"  attachmentId="<?php echo $attachmen->ID;?>" attachmentOrder="<?php echo $attachmen->menu_order;?>">

                              <?php
                              // Edit atachment media -- hide and delete
                              if (is_user_logged_in() && is_preview() && !get_field('animation_number_of_attachment_shown') ) {
                                  $gallery_id = get_the_ID();
                                  gallery_edit_atachement_options($gallery_id, $count_item, $attachmen->ID );
                              }
                              ?>

                              <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/VisualArtwork" style="<?php atachement_custom_margin($attachmen->ID); ?>">
                                  <!-- <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;"> -->
                                  <div class="imgcontainer" style="position: relative; height: auto; overflow: hidden; max-width: 100%;">

                                    <model-viewer id="aID<?php echo $attachmen->ID;?>" src="<?php echo wp_get_attachment_url($attachmen->ID); ?>" ar ar-modes="webxr scene-viewer quick-look"
                                        camera-controls
                                        interaction-prompt="none"
                                        auto-rotate-delay="1000"
                                        rotation-per-second="-120%"
                                        shadow-intensity="2"
                                        exposure="1"
                                        camera-orbit="-93.26deg 75.00deg 2m"
                                        auto-rotate
                                    >
                            				</model-viewer>
                                    <script>
                                      // get the model
                                      const modelViewer = document.querySelector('#aID<?php echo $attachmen->ID;?>');

                                      // Rotate effect
                                      // const orbitCycle = [
                                      //   '45deg 55deg 4m',
                                      //   '-60deg 110deg 2m',
                                      //   modelViewer.cameraOrbit
                                      // ];
                                      //
                                      // setInterval(() => {
                                      //   const currentOrbitIndex = orbitCycle.indexOf(modelViewer.cameraOrbit);
                                      //   modelViewer.cameraOrbit =
                                      //       orbitCycle[(currentOrbitIndex + 1) % orbitCycle.length];
                                      // }, 3000);

                                      modelViewer.addEventListener('load', () => {
                                        // Get the model's materials
                                        const materials = modelViewer.model.materials;
                                        // Loop through the materials and set the occlusion texture strength to 0
                                        for (const material of materials) {
                                          material.occlusionTexture.setTexture(null);
                                          //material.pbrMetallicRoughness.setBaseColorFactor('#ff0000'); //Works!
                                        }
                                        // modelViewer.update(); // works without
                                      });
                                    </script>

                                    </div>
                                </figure>
                            </div><?php

                        // IMAGE
                        } else { ?>

                            <div class="thumbnail item <?php echo $class_thumbnail;?> attachmen-<?php echo $count_item;?>"
                                attachmentId="<?php echo $attachmen->ID;?>"
                                attachmentOrder="<?php echo $attachmen->menu_order;?>"  attachment_field_diferent_size_on_gallery="<?php
                                    if (get_field('diferent_size_on_gallery',$attachmen->ID)) {
                                        echo get_field('diferent_size_on_gallery',$attachmen->ID);
                                    }else{
                                        echo "1";
                                    };?>">

                                <?php
                                // Edit atachment media -- hide and delete
                                if (is_user_logged_in() && is_preview() && !get_field('animation_number_of_attachment_shown') ) {
                                    $gallery_id = get_the_ID();
                                    gallery_edit_atachement_options($gallery_id, $count_item, $attachmen->ID );
                                }
                                ?>

                                <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject" style="<?php atachement_custom_margin($attachmen->ID); ?>">

                                    <?php // IMAGE CONTAINER
                                    //if ($number_of_columns_lg > 1 AND $deactivat_masonry == false) { ?>
                                    <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">
                                    <?php //} ?>
                                        <?php
                                        // Image Link
                                        if ($light_box == 'magnific_popup') { // print thumbnails with a link ?>
                                        <a href="<?php echo wp_get_attachment_url($attachmen->ID); ?>"
                                                class="magnific-popup-link" caption="<?php if ($caption) { echo " – <i>".$caption."</i>";} ?>"
                                        itemprop="contentUrl">
                                        <?php }?>
                                            <img    <?php echo $source; ?>
                                                    alt="<?php echo $attachmen_alt; ?>"
                                                    class=" <?php echo $max_height; ?>        <?php echo $intense; ?>"
                                                    itemprop="http://schema.org/image"
                                                    data-image="
                                                    <?php
                                                    // data-image ofr Intense Images Gallery
                                                    if ($light_box == 'intense-images') {
                                                        echo $attachmen_thumb_attributes[0];
                                                    } ?>"/>

                                        <?php
                                        // Image Link
                                        if ($light_box == 'magnific_popup') {?>
                                            </a>
                                        <?php }?>

                                    <?php // IMAGE container
                                    //if ($number_of_columns_lg > 1 AND $deactivat_masonry == false) { ?>
                                    </div>
                                    <?php //} ?>

                                    <?php //Caption
                                    if ($caption) { ?>
                                    <figcaption itemprop="caption description">
                                        <?php echo $caption; ?>
                                    </figcaption>
                                    <?php } ?>

                                </figure>

                        </div>
                        <?php } ?>

	                    <?php
	                    // --- Add Extra Contente // Youtube Video ---

	                    // If Image is an image before extra content
	                    foreach ($extra_content as $k => $v) {

	                    	// If ID of image before is the ID of current image, then output Youtube code
	                    	if ($v[0] == $attachmen->ID ) {

                                $count_item++; ?>

								<style>
									.embed-container { position: relative; padding-bottom: 177%; height: 0; overflow: hidden; max-width: 100%; }
									.embed-container iframe, .embed-container object, .embed-container embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
								</style>

	                    		<div id="x" class="thumbnail item <?php echo $class_thumbnail;?>">

	                    			<div class="embed-container">


										<iframe class="vimdeoiframe" src='https://player.vimeo.com/video/<?php echo $v[1]; ?>?api=1;title=0&byline=0&portrait=0&color=666666&autoplay=1&loop=1&badge=0' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>



									</div>

	                    		</div>


	                    	<?php } // end if
	                    } ?>

                    <?php } // end IF image is not to be removed ?>

                    <?php wp_reset_postdata(); ?>

                <?php } // end loop ?>

            </div>

        </div>

        <?php
        // ---------------
        // --- Scripts ---
        // ---------------
        ?>

		<?php
        // --- Masonry ----
        if ($number_of_columns_lg > 1 && $deactivat_masonry == false) { ?>
            <script type="text/javascript">
               // var $grid = $('#gallery-<?php the_ID(); ?>').masonry({
               //      percentPosition: true,
               //      itemSelector: ".item",
               //      // slow transitions
               //      transitionDuration: '0.4s',//speed of loading
               //  });
               //  $grid.masonry('layout');

                $('#gallery-<?php the_ID(); ?>').masonry({
                  // set itemSelector so .grid-sizer is not used in layout
                  itemSelector: '.item',
                  // use element for option
                  columnWidth: '.masonry-item-sizer',
                  percentPosition: true,
                  transitionDuration: '0.6s',
                  gutter: 0,
                  percentPosition: true,
                })
            </script>
        <?php }



        // --- Magnific Popup
        if ($light_box == 'magnific_popup') {?>

            <script type="text/javascript">
                // $(document).ready(function() {
                    $('.magnific_popup-gallery-<?php the_ID(); ?>').magnificPopup({
                      delegate: 'a',
                      type: 'image',
                      tLoading: '...',
                      mainClass: 'mfp-fade',
                      midClick: true,
                      gallery: {
                        enabled: true,
                        navigateByImgClick: true,
                        preload: [1,2], // Will preload 0 - before current, and 1 after the current image
                        tCounter: '<span class="mfp-counter">%curr%/%total%</span>' // markup of counter
                      },
                      image: {
                        tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
                        titleSrc: function(item) {
                          return '<?php echo taxonomy_list_w_numbers($post->ID,'log-branch','',', ',', ', ' & ', 'link');?><?php the_title(); ?>' + item.el.attr('caption');
                        }
                      },
                      zoom: {
                          enabled: true, // By default it's false, so don't forget to enable it

                          duration: 500, // duration of the effect, in milliseconds
                          easing: 'ease-in-out', // CSS transition easing function

                          // The "opener" function should return the element from which popup will be zoomed in
                          // and to which popup will be scaled down
                          // By defailt it looks for an image tag:
                          opener: function(openerElement) {
                            // openerElement is the element on which popup was initialized, in this case its <a> tag
                            // you don't need to add "opener" option if this code matches your needs, it's defailt one.
                            return openerElement.is('img') ? openerElement : openerElement.find('img');
                          }
                        }
                    });
                // });
            </script><?php


        // --- Intense Images
        } elseif ($light_box == 'intense-images') { ?>
            <script type="text/javascript">
            window.onload = function() {
                // Intensify all images with the 'intense' classname.
                var elements = document.querySelectorAll( '.intense' );
                Intense( elements );
            }
            </script><?php
        }

        // VIDEO
        if ($there_is_video == true) { ?>
            <script type="text/javascript">
                // var video = document.querySelector('video');
                // enableInlineVideo(video);
            </script><?php
        }
        // 3d
        if ($there_is_3d == true) { ?>
            <script type="module" src="<?php bloginfo('template_url'); ?>/library/js/model-viewer/model-viewer.min.js"></script>
            <?php
        }


        //////// GALERY ANIMATION //////////////

        // Flash images by changing divs postion
        // via :

        if( get_field('animation') == 'swap-randomly-positions-constant-frequency' ) {

            $animation_frequency = 135; // in Beats per minute
            $animation_frequency = get_field('animation_bpm');
            $animation_number_of_attachment_shown = get_field('animation_number_of_attachment_shown');
            $animation_period = 1 / ( $animation_frequency / 60000 ); // miliseconds
        ?>

            <script type="text/javascript">

                // Randam Swap function
                function randomDivsPosition<?php the_ID(); ?>() {
                    $("#gallery-<?php the_ID(); ?>").html(
                        $("#gallery-<?php the_ID(); ?>").children().sort(
                            function() { return 0.5 - Math.random() }
                        )
                    );
                }

                // Repeat functions every X miliseconds
                var myVar<?php the_ID(); ?> = setInterval(randomDivsPosition<?php the_ID(); ?>, <?php echo $animation_period; ?>);

                // // call this line to stop the loop:
                // clearInterval(myVar<?php the_ID(); ?>);

            </script>
            <?php if ($animation_number_of_attachment_shown) {
              echo "<style>#gallery-".get_the_ID()." .item {display: none }</style>";
              $i = 1;
              while ( $i<= $animation_number_of_attachment_shown ) {
                echo "<style>#gallery-".get_the_ID()." :nth-child(".$i.") {display: block }</style> ";
                $i++;
              }
            } ?>

        <?php }

        // When previeweing posts
        if (is_user_logged_in() && is_preview() && !get_field('animation_number_of_attachment_shown') ) {

            // Manual order images ?>
            <script src="<?php bloginfo('template_url'); ?>/library/js/Sortable-master/Sortable.js"></script>
            <script type="text/javascript">
                // As an Admin, I can sort the media elements on a gallery when I am previewing the post
                // documentation here: https://github.com/RubaXa/Sortable
                var el = document.getElementById('gallery-<?php the_ID(); ?>');
                var sortable = Sortable.create(el, {
                    dataIdAttr: 'attachmentId',
                    onSort: console.log("Sorted happend."),
                    onMove: console.log("Move happend.")
                });

            </script>

            <!-- CUSTOM FUNCTIONS -->
            <script type="text/javascript">

            // Custom functions

            // Reorder Gallery Attachments via AJAX call
            function orderAttachmentesOnWpDb() {

                var menuOrderCount = 0;

                $('#gallery-<?php the_ID(); ?>').children('div').each(function () {

                    menuOrderCount++;

                    console.log(menuOrderCount);
                    console.log(this.getAttribute('attachmentid')); //log every element found to console output

                    var attachmentId = this.getAttribute('attachmentid');
                    var attachmentOrder = menuOrderCount;

                    // This does the ajax request to change the menu_order value on the wp_db
                    $.ajax({
                        url: example_ajax_obj.ajaxurl,
                        data: {
                            'action': 'gallery_media_order_change_request',
                            'attachmentId' : attachmentId,
                            'attachmentOrder' : attachmentOrder
                        },
                        success:function(data) {
                            // This outputs the result of the ajax request
                            console.log(data);
                        },
                        error: function(errorThrown){
                            console.log(errorThrown);
                        }
                    });

                }); // loop ends

                // Reload pages
                location.reload();

            }

            // Increase Attachment Grid Size
            function atachementGridSizeChange(attachmentID, changeSize) {

                    // This does the ajax request to change the menu_order value on the wp_db
                    $.ajax({
                        url: example_ajax_obj.ajaxurl,
                        data: {
                            'action': 'change_attachment_field_diferent_size_on_gallery',
                            'attachmentID' : attachmentID,
                            'changeSize' : changeSize
                        },
                        success:function(data) {
                            // This outputs the result of the ajax request
                            console.log(data);
                            // Reload Page
                            location.reload();
                        },
                        error: function(errorThrown){
                            console.log(errorThrown);
                            alert("Failed to change grid denominator!");
                        }
                    });

            }

            // Change Attachment Margin
            function atachementChangeMargin(attachmentID, marginName, incrementalValue) {

                if (incrementalValue === "clear") {
                    $( ".thumbnail[attachmentid='"+ attachmentID +"'] figure" ).css( marginName, 0 );
                    console.log('Cleared margins on browser.');
                } else {
                    // Get current Margin value
                    var marginValue = ( 100 * parseFloat($(".thumbnail[attachmentid='"+ attachmentID +"'] figure").css(marginName)) / parseFloat($(".thumbnail[attachmentid='"+ attachmentID +"'] figure").parent().css('width')) );

                    // Round it
                    marginValue = Math.round(marginValue);

                    // New margin %
                    var marginValueNEW =  marginValue + incrementalValue;

                    // Convert Margin % to px
                    var marginValueNEWpx = ( marginValueNEW * parseFloat($(".thumbnail[attachmentid='"+ attachmentID +"'] figure").parent().css('width'))) / 100;

                    // Update Element with the correct style
                    $( ".thumbnail[attachmentid='"+ attachmentID +"'] figure" ).css( marginName, marginValueNEWpx );
                }

                // Restart Masonry
                $('#gallery-<?php the_ID(); ?>').masonry({ })

                // This does the ajax request to change the menu_order value on the wp_db
                $.ajax({
                    url: example_ajax_obj.ajaxurl,
                    data: {
                        'action': 'change_attachment_margin',
                        'attachmentID' : attachmentID,
                        'marginName' : marginName,
                        'incrementalValue' : incrementalValue

                    },
                    success:function(data) {
                        // This outputs the result of the ajax request
                        console.log(data);
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                        alert("Failed to change margin!");
                    }
                });

            }

            </script>

        <?php }?>

    <?php } // if there are images

} // if gallery is not deactivated

// _______________________________
// NOTES:

// via http://code.tutsplus.com/articles/creating-your-own-image-gallery-page-template-in-wordpress--wp-23721
// more info with video integration an so on:  http://codex.wordpress.org/Function_Reference/get_children
// for video use MediaElement.js at http://mediaelementjs.com/
// probably alredy native: http://make.wordpress.org/core/2013/04/08/audio-video-support-in-core/

// this one makes the houver tittle appear:
// title="<?php echo $attachmen->post_title;
?>
