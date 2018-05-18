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
                // LOG > Blowww brunch
                if (has_term( 'blwww', 'log-branch' ) ) {
                    $class_container        = 'container-fluid';
                    $number_of_columns_xs   = 2;
                    $number_of_columns_sm   = 4;
                    $number_of_columns_md   = 4;
                    $number_of_columns_lg   = 4;
                    $no_space               = 'no-pad';
                    $light_box              = 'intense-images';
                    $deactivat_masonry      = true;

                // othes
                } else {
                    $class_container        = 'container';
                    $number_of_columns_xs   = 1;
                    $number_of_columns_sm   = 1;
                    $number_of_columns_md   = 1;
                    $number_of_columns_lg   = 1;
                    $no_space               = '';
                    $light_box              = 'magnific_popup';
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
                $post_video_schema = "false";
            }

            // Emulsion
            elseif ( is_singular( 'emulsion' )) {

            }

            // Others
            else {
                // LOG > blwww brunch
                if (has_term( 'blwww', 'log-branch' ) ) {
                    $post_video_mute = true;
                    $post_video_loop = true;
                    $post_video_autoplay = true;
                    $post_video_pauseothervideos = false;
                    $post_video_schema = "false";
                }
            }


        // DEFAULT VIDEO PLAYER OPTIONS PER POST TYPE
            if ( get_field('alternative_video_player_options_on_post')) {
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
                foreach($attachmens as $image){

                	//Check IF atachment is not to be removed
                	if (!get_field('remove_from_default_gallery',$image->ID)) {

                        $count_item++;

                        // Gallery item size factor
                        $size_on_gallery_factor = 1;

                        // Diferent image size factor
                        if (get_field('diferent_size_on_gallery',$image->ID)) {
                            $size_on_gallery_factor = get_field('diferent_size_on_gallery',$image->ID);
                        }

                        // If Gallery item size factor is making item smaller, deactivate it on mobile
                        if ($size_on_gallery_factor > 1) {
                            $number_of_columns_item_xs   = $number_of_columns_xs; // deactiave on mobile //* $size_on_gallery_factor;
                        } else {
                            $number_of_columns_item_xs   = $number_of_columns_xs * $size_on_gallery_factor;
                        }

                            if ($number_of_columns_item_xs < 1) { $number_of_columns_item_xs = 1;
                            } elseif($number_of_columns_item_xs > 6) { $number_of_columns_item_xs = 6;}

                        $number_of_columns_item_sm   = $number_of_columns_sm * $size_on_gallery_factor;

                            if ($number_of_columns_item_sm < 1) { $number_of_columns_item_sm = 1;
                            } elseif($number_of_columns_item_sm > 6) { $number_of_columns_item_sm = 6;}

                        $number_of_columns_item_md   = $number_of_columns_md * $size_on_gallery_factor;

                            if ($number_of_columns_item_md < 1) { $number_of_columns_item_md = 1;
                            } elseif($number_of_columns_item_md > 6) { $number_of_columns_item_md = 6;}

                        $number_of_columns_item_lg   = $number_of_columns_lg * $size_on_gallery_factor;

                            if ($number_of_columns_item_lg < 1) { $number_of_columns_item_lg = 1;
                            } elseif($number_of_columns_item_lg > 6) { $number_of_columns_item_lg = 6;}



                        // calculate bootstrap responsive classes from the number of columns WITH FACTOR
                        $class_thumbnail_xs = "";
                        $class_thumbnail_sm = "";
                        $class_thumbnail_md = "";
                        $class_thumbnail_lg = "";


                        if ($number_of_columns_item_xs) {
                            $grid_number_xs        = (int) (12 / $number_of_columns_item_xs);
                            // If its 12, so its just one colum and thats not necessary
                                $class_thumbnail_xs    = "col-".$grid_number_xs." ";
                        }

                        if ($number_of_columns_item_sm) {
                            $grid_number_sm        = (int) (12 / $number_of_columns_item_sm);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_sm != $grid_number_xs) {
                                $class_thumbnail_sm    = "col-sm-".$grid_number_sm." ";
                            }
                        }

                        if ($number_of_columns_item_md) {
                            $grid_number_md        = (int) (12 / $number_of_columns_item_md);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_md != $grid_number_sm) {
                                $class_thumbnail_md    = "col-md-".$grid_number_md." ";
                            }
                        }

                        if ($number_of_columns_item_lg) {
                            $grid_number_lg        = (int) (12 / $number_of_columns_item_lg);
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
                            $grid_number_xs_without_factor        = (int) (12 / $number_of_columns_xs);
                            // If its 12, so its just one colum and thats not necessary
                                $class_thumbnail_xs_without_factor    = "col-".$grid_number_xs_without_factor." ";
                        }

                        if ($number_of_columns_sm) {
                            $grid_number_sm_without_factor        = (int) (12 / $number_of_columns_sm);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_sm_without_factor != $grid_number_xs_without_factor) {
                                $class_thumbnail_sm_without_factor    = "col-sm-".$grid_number_sm_without_factor." ";
                            }
                        }

                        if ($number_of_columns_md) {
                            $grid_number_md_without_factor        = (int) (12 / $number_of_columns_md);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_md_without_factor != $grid_number_sm_without_factor) {
                                $class_thumbnail_md_without_factor    = "col-md-".$grid_number_md_without_factor." ";
                            }
                        }

                        if ($number_of_columns_lg) {
                            $grid_number_lg_without_factor        = (int) (12 / $number_of_columns_lg);
                            // If is not the same columns for the smaller size before
                            if ($grid_number_lg_without_factor != $grid_number_md_without_factor) {
                                $class_thumbnail_lg_without_factor    = "col-lg-".$grid_number_lg_without_factor." ";
                            }
                        }

                        $class_thumbnail_without_factor = "";
                        $class_thumbnail_without_factor = $class_thumbnail_xs_without_factor.$class_thumbnail_sm_without_factor.$class_thumbnail_md_without_factor.$class_thumbnail_lg_without_factor;




                        // --- SRC calculation ---
                            // Original image File
    	                    $image_thumb_attributes = wp_get_attachment_image_src($image->ID, false);
    	                    $image_thumb_srcset = $image_thumb_attributes[0]." ".$image_thumb_attributes[1]."w";

                            // IF diferent sizes exist

                            // thumbnail
                            $image_thumb_thumbnail_srcset ="";
                            $image_thumb_thumbnail_attributes = wp_get_attachment_image_src($image->ID, "thumbnail");
                            if ($image_thumb_thumbnail_attributes[3]) {
                                $image_thumb_thumbnail_srcset = $image_thumb_thumbnail_attributes[0]." ".$image_thumb_thumbnail_attributes[1]."w, ";
                            }

                            // small
                            $image_thumb_small_srcset ="";
                            $image_thumb_small_attributes = wp_get_attachment_image_src($image->ID, "small");
                            if ($image_thumb_small_attributes[3]) {
                                $image_thumb_small_srcset = $image_thumb_small_attributes[0]." ".$image_thumb_small_attributes[1]."w, ";
                            }

                            // medium
                            $image_thumb_medium_srcset ="";
                            $image_thumb_medium_attributes = wp_get_attachment_image_src($image->ID, "medium");
                            if ($image_thumb_medium_attributes[3]) {
                                $image_thumb_medium_srcset = $image_thumb_medium_attributes[0]." ".$image_thumb_medium_attributes[1]."w, ";
                            }

                            // large
                            $image_thumb_large_srcset ="";
                            $image_thumb_large_attributes = wp_get_attachment_image_src($image->ID, "large");
                            if ($image_thumb_large_attributes[3]) {
                                $image_thumb_large_srcset = $image_thumb_large_attributes[0]." ".$image_thumb_large_attributes[1]."w, ";
                            }


                            // FINAL SRCSET
                            $image_srcset = $image_thumb_thumbnail_srcset.
                                            $image_thumb_small_srcset.
                                            $image_thumb_medium_srcset.
                                            $image_thumb_large_srcset.
                                            $image_thumb_srcset;
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

                                $image_sizes = "(min-width: 1240px) ".$size_lg.",
                                                 ".$size_xs;
                            } else {
                                $size_lg = (100 / $number_of_columns_item_lg)."vw";
                                $size_md = (100 / $number_of_columns_item_md)."vw";
                                $size_sm = (100 / $number_of_columns_item_sm)."vw";
                                $size_xs = (100 / $number_of_columns_item_xs)."vw";

                                $image_sizes = "(min-width: 992px) ".$size_lg.",
                                                (min-width: 768px) ".$size_md.",
                                                (min-width: 576px) ".$size_sm.",
                                                 ".$size_xs;
                            }

                            // Source code
                            if (!get_field('insert_src_of_higher_resolution',$image->ID)) {
                                $source = 'srcset="'.$image_srcset.'" sizes="'.$image_sizes.'" ';
                            } else {
                                $source = 'src="'.$image_thumb_attributes[0].'"';
                            }

                        // --- AlT ---
                            $alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                            $caption = $image->post_excerpt;
                            $image_post_title = get_the_title();
                            if ($alt) {
                                $image_alt = $alt;
                            } elseif($caption) {
                                $image_alt = $caption." – ".$image_post_title;
                            }else{
                                $image_alt = $image_post_title." – ".$count_item;
                            }


                        // INTRINSTIC RATIO

                            // AUDIO
                            if ( $image->post_mime_type == "audio/wav" OR $image->post_mime_type == "audio/mpeg" ) {

                                // TO CODE
                                // $intrinsic_ratio of audio container

                            // VIDEO
                            } elseif ( $image->post_mime_type == "video/mpeg" OR $image->post_mime_type == "video/mp4" OR $image->post_mime_type == "video/quicktime" ) {

                            	$video_metadata = wp_get_attachment_metadata( $image->ID );

                            	$intrinsic_ratio = $video_metadata['height'] * 100 / $video_metadata['width'];

                            }else {

                                $intrinsic_ratio = $image_thumb_attributes[2] * 100 / $image_thumb_attributes[1];
                            }


                        // COMPILE VIDEO OPTIONS

                            // IF there are DEFAULT VIDEO PLAYER OPTIONS of the ATTACHEMENT
                            if (get_field('alternative_video_player_options',$image->ID)) {
                                // need to convert boleans into true/false strings >> https://stackoverflow.com/questions/2795177/how-to-convert-boolean-to-string
                                $video_mute = (get_field('video_mute',$image->ID)) ? 'true' : 'false';
                                $video_loop = (get_field('video_loop',$image->ID)) ? 'true' : 'false';
                                $video_autoplay = (get_field('video_autoplay',$image->ID)) ? 'true' : 'false';
                                $video_pauseothervideos = (get_field('video_pauseothervideos',$image->ID)) ? 'true' : 'false';
                                $video_preload = get_field('video_preload',$image->ID); // NOT A BOLEAN - string
                                $post_video_schema = get_field('video_schema',$image->ID);

                            // Else use them on the post level
                            } else {
                                $video_mute = ($post_video_mute) ? 'true' : 'false';
                                $video_loop = ($post_video_loop) ? 'true' : 'false';
                                $video_autoplay = ($post_video_autoplay) ? 'true' : 'false';
                                $video_pauseothervideos = ($post_video_pauseothervideos) ? 'true' : 'false';
                                $video_preload = $post_video_preload; // NOT A BOLEAN - string
                                $video_schema = ($post_video_schema) ? 'true' : 'false';
                            }

                            // COMPILE VIDEO OPTIONS STRING

                                $video_otions = '
                                    mute="'.$video_mute.'"
                                    loop="'.$video_loop.'"
                                    autoplay="'.$video_autoplay.'"
                                    pauseothervideos="'.$video_pauseothervideos.'"
                                    preload="'.$video_preload.'"
                                    Schema="'.$video_schema.'"';


                        // add item-sizer for Masonry responsive calculations
                        if ($count_item == 1 AND $number_of_columns_lg > 1 AND $deactivat_masonry == false) { ?>

                            <div class="item-sizer <?php echo $class_thumbnail_without_factor;?>"></div>
                        <?php }?>


                        <?php

                        // ----------------------------------------
                        // --- Images and AUDIO from Media library
                        // ----------------------------------------

                        // AUDIO
                        if ( $image->post_mime_type == "audio/wav" OR $image->post_mime_type == "audio/mpeg" ) { ?>

                            <div class="thumbnail item <?php echo $class_thumbnail;?> media-audio attachmen-<?php echo $count_item;?>" >
                                <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
                                    <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">

                                            <?php

                                            $audio_url = wp_get_attachment_url( $image->ID );
                                            $attr = array(
                                                'src'      => $audio_url,
                                                'loop'     => '', // true
                                                'autoplay' => '',
                                                'preload'  => 'none'
                                            );
                                            echo wp_audio_shortcode( $attr );

                                             ?>
                                    </div>
                                </figure>
                            </div><?php

                        // VIDEO
                        } elseif ( $image->post_mime_type == "video/mpeg" OR $image->post_mime_type == "video/mp4" OR $image->post_mime_type == "video/quicktime" ) { ?>

                            <div class="thumbnail item <?php echo $class_thumbnail;?> media-video video-id-<?php echo $image->ID;?> attachmen-<?php echo $count_item;?>" >
                                <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/VideoObject">
                                    <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>100%; height: 0; overflow: hidden; max-width: 100%;">

                                            <?php

                                            echo do_shortcode('[KGVID id="'.$image->ID.'" '.$video_otions.' ]');

                                            echo "<style>
                                                        .video-id-".$image->ID." .vjs-fluid {padding-top: ".$intrinsic_ratio."%!important;
                                                        }
                                                    </style>";
                                            if ($video_mute == "true") {
                                                echo "<style>
                                                    .video-id-".$image->ID." .vjs-volume-menu-button {display: none!important;}
                                                </style>";
                                            }

                                            // To activate scripts on the bootom
                                            $there_is_video = true;
                                             ?>
                                    </div>
                                </figure>
                            </div><?php

                        // IMAGE
                        } else { ?>

                            <div class="thumbnail item <?php echo $class_thumbnail;?> attachmen-<?php echo $count_item;?>" >

                            <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">

                                <?php // IMAGE CONTAINER
                                //if ($number_of_columns_lg > 1 AND $deactivat_masonry == false) { ?>
                                <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">
                                <?php //} ?>
                                    <?php
                                    // Image Link
                                    if (($light_box != 'intense-images')) { // print thumbnails with a link ?>
                                    <a href="<?php echo wp_get_attachment_url($image->ID); ?>"
                                            class="magnific-popup-link" caption="<?php if ($caption) { echo " – <i>".$caption."</i>";} ?>"
                                    itemprop="contentUrl">
                                    <?php }?>
                                        <img    <?php echo $source; ?>
                                                alt="<?php echo $image_alt; ?>"
                                                class=" <?php echo $max_height; ?>        <?php echo $intense; ?>"
                                                itemprop="http://schema.org/image"
                                                data-image="
                                                <?php
                                                // data-image ofr Intense Images Gallery
                                                if ($light_box == 'intense-images') {
                                                    echo $image_thumb_attributes[0];
                                                } ?>"/>

                                    <?php
                                    // Image Link
                                    if (($light_box != 'intense-images')) {?>
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
	                    	if ($v[0] == $image->ID ) {

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
                  columnWidth: '.item-sizer',
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


        //////// GALERY ANIMATION //////////////

        // Flash images by changing divs postion
        // via :

        if( get_field('animation') == 'swap-randomly-positions-constant-frequency' ) {

            $animation_frequency = 135; // in Beats per minute
            $animation_frequency = get_field('animation_bpm');
            $animation_period = 1 / ( $animation_frequency / 60000 ); // miliseconds
        ?>

            <script type="text/javascript">

                // Randam Swap function
                function randomDivsPosition<?php the_ID(); ?>() {
                    $("#gallery-<?php the_ID(); ?>").html($("#gallery-<?php the_ID(); ?>").children().sort(function() { return 0.5 - Math.random() }));
                }

                // Repeat functions every X miliseconds
                var myVar<?php the_ID(); ?> = setInterval(randomDivsPosition<?php the_ID(); ?>, <?php echo $animation_period; ?>);

                // // call this line to stop the loop:
                // clearInterval(myVar<?php the_ID(); ?>);

            </script>

        <?php }

        // When previeweing posts
        if (is_user_logged_in() && is_preview()) { ?>
            <!-- <script src="<?php bloginfo('template_url'); ?>/library/js/packery/packery.pkgd.min.js"></script> -->
            <script type="text/javascript">

                // // jQuery
                // $grid.packery( 'bindUIDraggableEvents', $items )

                // // initialize Packery
                // var $grid = $('.grid').packery({
                //   itemSelector: '.grid-item',
                //   // columnWidth helps with drop positioning
                //   columnWidth: 100
                // });

                // // make all items draggable
                // var $items = $grid.find('.grid-item').draggable();
                // // bind drag events to Packery
                // $grid.packery( 'bindUIDraggableEvents', $items );
            </script>

            // As an Admin, I can sort the media elements on a gallery when I am previewing the post
            // -- does not work with masonry!!
            // documentation here: https://github.com/RubaXa/Sortable
            <script src="<?php bloginfo('template_url'); ?>/library/js/Sortable-master/Sortable.js"></script>
            <script type="text/javascript">
                // Simple list
                var el = document.getElementById('gallery-<?php the_ID(); ?>');
                var sortable = Sortable.create(el, { /* options */ });
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
// title="<?php echo $image->post_title;
?>
