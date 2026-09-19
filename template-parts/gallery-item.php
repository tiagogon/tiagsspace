<?php
/**
 * Single gallery item renderer.
 *
 * Shared by the visible grid loop and the hidden-attachment pool in
 * template-parts/entry-gallery.php. Included (not called as a function) so it
 * inherits the enclosing scope: it expects $attachmen and $count_item plus the
 * gallery-level variables computed above the loop in entry-gallery.php
 * ($number_of_columns_*, $deactivat_masonry, $item_max_heigh, $light_box,
 * $intense, $class_container, the $post_video_* option vars, etc.).
 *
 * Set $rendering_hidden_pool = true before including to suppress the Masonry
 * column sizer, which belongs only to the live grid.
 *
 * @package tiagsspace
 */
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
	                    $attachmen_thumb_srcset = "";
	                    $attachmen_thumb_attributes = wp_get_attachment_image_src($attachmen->ID, false);
                      if ($attachmen_thumb_attributes) {
                        $attachmen_thumb_srcset = $attachmen_thumb_attributes[0]." ".$attachmen_thumb_attributes[1]."w";
                      }


                        // IF diferent sizes exist

                                                // thumbnail
                                                $attachmen_thumb_thumbnail_srcset ="";
                                                $attachmen_thumb_thumbnail_attributes = wp_get_attachment_image_src($attachmen->ID, "thumbnail");
                                                if ($attachmen_thumb_thumbnail_attributes && !empty($attachmen_thumb_thumbnail_attributes[0]) && !empty($attachmen_thumb_thumbnail_attributes[1])) {
                                                        $attachmen_thumb_thumbnail_srcset = $attachmen_thumb_thumbnail_attributes[0]." ".$attachmen_thumb_thumbnail_attributes[1]."w, ";
                                                }


                                                // small
                                                $attachmen_thumb_small_srcset ="";
                                                $attachmen_thumb_small_attributes = wp_get_attachment_image_src($attachmen->ID, "small");
                                                if ($attachmen_thumb_small_attributes && !empty($attachmen_thumb_small_attributes[0]) && !empty($attachmen_thumb_small_attributes[1])) {
                                                        $attachmen_thumb_small_srcset = $attachmen_thumb_small_attributes[0]." ".$attachmen_thumb_small_attributes[1]."w, ";
                                                }


                                                // medium
                                                $attachmen_thumb_medium_srcset ="";
                                                $attachmen_thumb_medium_attributes = wp_get_attachment_image_src($attachmen->ID, "medium");
                                                if ($attachmen_thumb_medium_attributes && !empty($attachmen_thumb_medium_attributes[0]) && !empty($attachmen_thumb_medium_attributes[1])) {
                                                        $attachmen_thumb_medium_srcset = $attachmen_thumb_medium_attributes[0]." ".$attachmen_thumb_medium_attributes[1]."w, ";
                                                }


                                                // large
                                                $attachmen_thumb_large_srcset ="";
                                                $attachmen_thumb_large_attributes = wp_get_attachment_image_src($attachmen->ID, "large");
                                                if ($attachmen_thumb_large_attributes && !empty($attachmen_thumb_large_attributes[0]) && !empty($attachmen_thumb_large_attributes[1])) {
                                                        $attachmen_thumb_large_srcset = $attachmen_thumb_large_attributes[0]." ".$attachmen_thumb_large_attributes[1]."w, ";
                                                }

                        // FINAL SRCSET
                        $attachmen_srcset = $attachmen_thumb_thumbnail_srcset.
                                        $attachmen_thumb_small_srcset.
                                        $attachmen_thumb_medium_srcset.
                                        $attachmen_thumb_large_srcset.
                                        $attachmen_thumb_srcset;
                        // --- Sizes ---
                        // Compute sizes based on actual bootstrap grid fractions so browsers pick correct sources
                        if ($class_container == 'container') {
                            $container_size_lg = 1200;
                            // Use grid numbers WITHOUT factor to represent actual column widths at breakpoints
                            $lg_frac = isset($grid_number_lg_without_factor) ? ($grid_number_lg_without_factor / 48) : 1;
                            $md_frac = isset($grid_number_md_without_factor) ? ($grid_number_md_without_factor / 48) : 1;
                            $sm_frac = isset($grid_number_sm_without_factor) ? ($grid_number_sm_without_factor / 48) : 1;
                            $xs_frac = isset($grid_number_xs_without_factor) ? ($grid_number_xs_without_factor / 48) : 1;

                            // Large breakpoint in px (container has fixed max width)
                            $size_lg = ($container_size_lg * $lg_frac)."px";
                            // For smaller breakpoints, vw reflects viewport fraction
                            $size_md = (100 * $md_frac)."vw";
                            $size_sm = (100 * $sm_frac)."vw";
                            $size_xs = (100 * $xs_frac)."vw";

                            $attachmen_sizes = "(min-width: 1240px) ".$size_lg.",
                                                (min-width: 992px) ".$size_md.",
                                                (min-width: 768px) ".$size_sm.",
                                                 ".$size_xs;
                        } else {
                            // container-fluid: widths are fractions of viewport
                            // Prefer item grid numbers if available, else fallback to gallery grid numbers
                            $lg_frac = isset($grid_number_lg) ? ($grid_number_lg / 48) : (isset($grid_number_lg_without_factor) ? ($grid_number_lg_without_factor / 48) : 1);
                            $md_frac = isset($grid_number_md) ? ($grid_number_md / 48) : (isset($grid_number_md_without_factor) ? ($grid_number_md_without_factor / 48) : 1);
                            $sm_frac = isset($grid_number_sm) ? ($grid_number_sm / 48) : (isset($grid_number_sm_without_factor) ? ($grid_number_sm_without_factor / 48) : 1);
                            $xs_frac = isset($grid_number_xs) ? ($grid_number_xs / 48) : (isset($grid_number_xs_without_factor) ? ($grid_number_xs_without_factor / 48) : 1);

                            $size_lg = (100 * $lg_frac)."vw";
                            $size_md = (100 * $md_frac)."vw";
                            $size_sm = (100 * $sm_frac)."vw";
                            $size_xs = (100 * $xs_frac)."vw";

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
                          if ($attachmen_thumb_attributes && isset($attachmen_thumb_attributes[1]) && isset($attachmen_thumb_attributes[2]) && $attachmen_thumb_attributes[1] > 0) {
                              $intrinsic_ratio = $attachmen_thumb_attributes[2] * 100 / $attachmen_thumb_attributes[1];
                          } else {
                              $intrinsic_ratio = 100; // Default to square ratio
                          }

                        // 3D
                        } elseif ( $attachmen->post_mime_type == "application/octet-stream" ) {
                          $intrinsic_ratio = 1*100/1;

                        }else {

                            if ($attachmen_thumb_attributes && isset($attachmen_thumb_attributes[1]) && isset($attachmen_thumb_attributes[2]) && $attachmen_thumb_attributes[1] > 0) {
                                $intrinsic_ratio = $attachmen_thumb_attributes[2] * 100 / $attachmen_thumb_attributes[1];
                            } else {
                                $intrinsic_ratio = 100; // Default to square ratio
                            }

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
                        // Disable autoplay initially - Intersection Observer will handle it
                        $video_otions = '
                            controls="'.$video_controls.'"
                            mute="'.$video_mute.'"
                            loop="'.$video_loop.'"
                            autoplay="false"
                            playsinline="true"
                            pauseothervideos="'.$video_pauseothervideos.'"
                            preload="'.$video_preload.'"
                            Schema="'.$video_schema.'"';
                        
                        // Store original autoplay setting for Intersection Observer
                        $should_autoplay = $video_autoplay;


                    // add masonry-item-sizer for Masonry responsive calculations
                    if ($count_item == 1 AND $number_of_columns_lg > 1 AND $deactivat_masonry == false AND empty($rendering_hidden_pool)) { ?>

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

                        <div class="thumbnail item <?php echo $class_thumbnail;?> media-video video-id-<?php echo $attachmen->ID;?> attachmen-<?php echo $count_item;?>" data-should-autoplay="<?php echo $should_autoplay; ?>" attachmentId="<?php echo $attachmen->ID;?>" attachmentOrder="<?php echo $attachmen->menu_order;?>">

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

                        <div class="thumbnail item
                          <?php echo $class_thumbnail;?>
                          attachmen-<?php echo $count_item;?>
                          <?php if($item_max_heigh!=""){echo "max-heigh-90vh";} ?>"
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
                                        <?php
                                        // Build sizes map for picture sources (largest -> smallest)
                                        $sizes_map = array();
                                        if ($class_container == 'container') {
                                            $sizes_map = array(
                                                array('size' => 'large', 'media' => '(min-width: 1240px)'),
                                                array('size' => 'medium', 'media' => '(min-width: 992px)'),
                                                array('size' => 'small', 'media' => '(min-width: 768px)'),
                                                array('size' => 'thumbnail', 'media' => '(min-width: 576px)'),
                                            );
                                            $fallback_sizes_attr = $attachmen_sizes; // already computed above
                                        } else {
                                            $sizes_map = array(
                                                array('size' => 'large', 'media' => '(min-width: 992px)'),
                                                array('size' => 'medium', 'media' => '(min-width: 768px)'),
                                                array('size' => 'small', 'media' => '(min-width: 576px)'),
                                                array('size' => 'thumbnail', 'media' => '(min-width: 0px)'),
                                            );
                                            $fallback_sizes_attr = $attachmen_sizes;
                                        }

                                        // Compose class list for <img> fallback
                                        $img_class = trim($intense . ' ' . esc_html(get_field('item_max_heigh')));
                                        $picture_html = tiagsspace_render_picture_from_attachment($attachmen->ID, $sizes_map, $fallback_sizes_attr, $img_class, $attachmen_alt);
                                        // Ensure Intense Images uses the full-resolution image by setting data-image on the <img>
                                        if ($light_box == 'intense-images' && !empty($attachmen_thumb_attributes[0])) {
                                            $picture_html = str_replace('<img ', '<img data-image="'.$attachmen_thumb_attributes[0].'" ', $picture_html);
                                        }
                                        echo $picture_html;

                                        // data-image for Intense Images Gallery
                                        ?>
                                        <span style="display:none" itemprop="http://schema.org/image"></span>

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

                <?php wp_reset_postdata(); ?>

