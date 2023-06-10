<?php
/*
Gallery template for single pages
*/


// if Gallery is Activated
if (!get_field('deactivate_gallery')) {

    // get images atached to the post
    $args = array(
        'numberposts'       => -1, // Using -1 loads all posts
        'orderby'           => 'menu_order', // set in the page media manager
        'order'             => 'ASC',
        'post_mime_type'    => 'image', // Make sure it doesn't pull other resources, like videos
        'post_parent'       => $post->ID, // Important part - ensures the associated images are loaded
        'post_status'       => null,
        'post_type'         => 'attachment'
    );
    $images = get_children( $args );

    // if there are images atached to the post
    if($images){

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

         $class_container  = 'container-fluid';

        if ( !get_field('alternative_gallery')) {


            $no_space               = '';
            $spacement              = 'spacement-20';
            $light_box              = 'magnific_popup';
        }



        // -------------------------------
        // --- Alternative Settings gallery checked
        // -------------------------------
        if ( get_field('alternative_gallery')) {

            // Space betwene images
            $no_space           = '';
            if ( get_field('no_space')) {
                $no_space           = 'no-pad';
            }

            if (get_field('spacement')) {
                $spacement = get_field('spacement');
            }

        }


        // -------------------------------
        // --- Final calculations
        // -------------------------------


        // Row -- add margin on the sides
            // $row = 'row';
            // if (($class_container == 'container-fluid') AND ($no_space == '')) {
            //     $row = 'row row-inverted'; // add margin on the sides
            // }

        ?>
        <?php
        // Swipper style
        $horizontal_gallery_style = "horizontal-gallery-style-".get_field( 'horizontal_gallery_style' ); ?>

        <div class="<?php echo $class_container; ?> container-gallery swiper swiper-container swiper-container-<?php the_ID(); ?> <?php echo $horizontal_gallery_style; ?>">

            <div id="gallery-<?php the_ID(); ?>" class="gallery gallery-horizontal swiper-wrapper" itemscope itemtype="http://schema.org/ImageGallery">

                <?php
                $count_item = 0;

                // loop the atached images
                foreach($images as $image){

                	//Check IF image is not to be removed
                	if (!get_field('remove_from_default_gallery',$image->ID)) {

                        $count_item++;


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

                            // HD
                            $image_thumb_HD_srcset ="";
                            $image_thumb_HD_attributes = wp_get_attachment_image_src($image->ID, "HD");
                            if ($image_thumb_HD_attributes[3]) {
                                $image_thumb_HD_srcset = $image_thumb_HD_attributes[0]." ".$image_thumb_HD_attributes[1]."w, ";
                            }

                            // FINAL SRCSET
                            $image_srcset = $image_thumb_thumbnail_srcset.
                                            $image_thumb_small_srcset.
                                            $image_thumb_medium_srcset.
                                            $image_thumb_large_srcset.
                                            $image_thumb_HD_srcset.
                                            $image_thumb_srcset;
                            // --- Sizes ---
                            //Container or Full width?
                            // if ($class_container == 'container') {
                            //     $container_size_lg = 1200;
                            //     $container_size_md = 940;
                            //     $container_size_sm = 720;

                            //     $size_lg = $container_size_lg."px";
                            //     $size_md = $container_size_md."px";
                            //     $size_sm = $container_size_sm."px";
                            //     $size_xs = "100vw";

                            //     $image_sizes = "(min-width: 1240px) ".$size_lg.",
                            //                     (min-width: 992px) ".$size_md.",
                            //                     (min-width: 768px) ".$size_sm.",
                            //                      ".$size_xs;
                            // } else {
                                // $size_lg = "100vw";
                                // $size_md = "100vw";
                                // $size_sm = "100vw";
                                // $size_xs = "100vw";

                                // $image_sizes = "(min-width: 1240px) ".$size_lg.",
                                //                 (min-width: 992px) ".$size_md.",
                                //                 (min-width: 768px) ".$size_sm.",
                                //                  ".$size_xs;
                                $image_sizes = "130vh";
                            // }

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

                        // Imgcontainer-gallery< calculations -- intrinsic ratio
                            $intrinsic_ratio = $image_thumb_attributes[2] * 100 / $image_thumb_attributes[1];

                        ?>

                        <div class="thumbnail item swiper-slide swiper-slide-<?php the_ID(); ?>" >

                            <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject" >

	                            <img    <?php echo $source; ?>
	                                    alt="<?php echo $image_alt; ?>"
                                        class=""/>

                                <?php //Caption
                                if ($caption) { ?>
                                <figcaption itemprop="caption description">
                                    <?php echo $caption; ?>
                                </figcaption>
                                <?php } ?>

                            </figure>
                        <div class="swiper-lazy-preloader"></div>
                        </div>

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

	                    		<div id="x" class="thumbnail item">

	                    			<div class="embed-container">


										<iframe class="vimdeoiframe" src='https://player.vimeo.com/video/<?php echo $v[1]; ?>?api=1;title=0&byline=0&portrait=0&color=666666&autoplay=1&loop=1&badge=0' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>



									</div>

	                    		</div>


	                    	<?php } // end if
	                    } ?>

                    <?php } // end IF image is not to be removed ?>

                <?php } // end loop ?>

            </div>

            <!-- Add Scrollbar -->

        <div class="swiper-pagination"></div>

        </div>

        <?php
        // ---------------
        // --- Scripts ---
        // ---------------
        ?>



        <!-- Slider main container -->

        <?php if (get_field('horizontal_gallery_style') == 'classic' or get_field('horizontal_gallery_style') == ''): ?>

          <script type="text/javascript">

            var swiper<?php the_ID(); ?> = new Swiper('.swiper-container-<?php the_ID(); ?>', {
                  freeMode: {
                    enabled: true,
                    sticky: true,
                  },

                  speed: 800,

                  grabCursor: true,

                  mousewheel: {
                    //invert: true,
                    forceToAxis: true,
                  },

                  keyboard: {
                    enabled: true,
                    onlyInViewport: false,
                  },

                  scrollbar: {
                   el: '.swiper-scrollbar',
                   draggable: true,
                   hide: false,
                 },

                 slidesPerView: 'auto',
                 spaceBetween: 0,

                //updateTranslate: true,

            });

          </script>

        <?php endif; ?>


        <?php if (get_field('horizontal_gallery_style') == 'still'): ?>

          <script type="text/javascript">

            var swiper<?php the_ID(); ?> = new Swiper('.swiper-container-<?php the_ID(); ?>', {
              keyboard: {
                enabled: true,
                onlyInViewport: false,
              },
              navigation: {
                nextEl: '.swiper-slide-<?php the_ID(); ?>'
              },
             slidesPerView: 'auto',
             lazy: true,
             spaceBetween: 0,
             effect: "fade",
             fadeEffect: {
              crossFade: false
             },
             speed: 600, //needs to be >1
             loop: true,
             autoplay: {
               delay: <?php
                    if (get_field('still_duration')) {
                        echo get_field('still_duration')+rand(-1000, 1000);
                   } else {
                     echo 1800+rand(-100, 100);
                   }
                ?>, // defaul is 3000ms = 4 heart beats
               disableOnInteraction: false,
             },

            });

          </script>

        <?php endif; ?>

        <?php if (get_field('horizontal_gallery_style') == 'insta'): ?>

          <script type="text/javascript">

            var swiper<?php the_ID(); ?> = new Swiper('.swiper-container-<?php the_ID(); ?>', {

              keyboard: {
                enabled: true,
                onlyInViewport: false,
              },
              navigation: {
                nextEl: '.swiper-slide-<?php the_ID(); ?>'
              },

             spaceBetween: 0,
             lazy: true,

             loop: true,
             speed: 1200,

             autoplay: {
               delay: <?php
                    if (get_field('still_duration')) {
                        echo get_field('still_duration')+rand(-1000, 1000);
                   } else {
                     echo 1800+rand(-100, 100);
                   }
                ?>, // defaul is 3000ms = 4 heart beats
               disableOnInteraction: false,
             },

            });

          </script>

        <?php endif; ?>



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
