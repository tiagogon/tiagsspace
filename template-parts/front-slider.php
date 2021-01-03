<?php /*

	Front Slider

    hidden-sm hidden-xs

*/ ?>

    <div class="swiper-container hidden-xs">

        <div class="swiper-wrapper">

			<?php
            
            // posts set as to show on the slider
            $args = array(
                'post_type' => array('post','hyper','sidewalk','dusk', 'emulsion', 'featuring'),
                'posts_per_page' => 5,
                'meta_key' => 'slider_order',
                //'orderby' => 'meta_value_num',
                //'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'goes_to_the_front_slider',
                        'value' => true
                    )
                )
            );
            
            //set up the counter variable as 0
            $count = 0; 
            
            // start the ride
            $my_query = new WP_Query( $args );
            while ($my_query->have_posts()) : $my_query->the_post();

                //increment the variable by 1 each time the loop executes
                $count++; 


                // ---- image  ---- 

                // define a image for non valid pictures
                $back_up_image_url = "http://40.media.tumblr.com/a19fa5d288ab2046921a651cffa2a0b4/tumblr_n83weqTN181qawoi8o2_1280.jpg";

                // Check if there is and alternative image
                if( get_field('has_a_different_slider_image') ) {

                    // GET RECIPES IMAGE AND INFO FROM THE OBJECT
                    $image = get_field('different_slider_image');

                    if( !empty($image) ){

                        $image_id = $image['id'];

                    }

                // if not, use the featured image
                } else {

                   if ( has_post_thumbnail()) {

                        $image_id = get_post_thumbnail_id($post->ID);
             
                    } else {

                    	$image_id = 6091;

                    }

                }


                // --- SRCSET calculation ---
                    // Original image File
                    $image_thumb_attributes = wp_get_attachment_image_src($image_id, false);
                    $image_thumb_srcset = $image_thumb_attributes[0]." ".$image_thumb_attributes[1]."w";

                    // IF diferent sizes exist

                    // thumbnail
                    $image_thumb_thumbnail_srcset ="";
                    $image_thumb_thumbnail_attributes = wp_get_attachment_image_src($image_id, "thumbnail");
                    if ($image_thumb_thumbnail_attributes[3]) {
                        $image_thumb_thumbnail_srcset = $image_thumb_thumbnail_attributes[0]." ".$image_thumb_thumbnail_attributes[1]."w, ";
                    }

                    // small
                    $image_thumb_small_srcset ="";
                    $image_thumb_small_attributes = wp_get_attachment_image_src($image_id, "small");
                    if ($image_thumb_small_attributes[3]) {
                        $image_thumb_small_srcset = $image_thumb_small_attributes[0]." ".$image_thumb_small_attributes[1]."w, ";
                    }

                    // medium
                    $image_thumb_medium_srcset ="";
                    $image_thumb_medium_attributes = wp_get_attachment_image_src($image_id, "medium");
                    if ($image_thumb_medium_attributes[3]) {
                        $image_thumb_medium_srcset = $image_thumb_medium_attributes[0]." ".$image_thumb_medium_attributes[1]."w, ";
                    }

                    // large
                    $image_thumb_large_srcset ="";
                    $image_thumb_large_attributes = wp_get_attachment_image_src($image_id, "large");
                    if ($image_thumb_large_attributes[3]) {
                        $image_thumb_large_srcset = $image_thumb_large_attributes[0]." ".$image_thumb_large_attributes[1]."w, ";
                    }

                    // HD
                    $image_thumb_HD_srcset ="";
                    $image_thumb_HD_attributes = wp_get_attachment_image_src($image_id, "HD");
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
                
                $size_lg = "1200px";
                $size_md = "940px";
                $size_sm = "720px";
                $size_xs = "100vw";

                $image_sizes = "(min-width: 1240px) ".$size_lg.",
                                (min-width: 992px) ".$size_md.",
                                (min-width: 768px) ".$size_sm.",
                                 ".$size_xs;
        
                
                // sizes string
                $image_sizes = "(min-width: 1240px) ".$size_lg.",
                                    (min-width: 992px) ".$size_md.",
                                    (min-width: 768px) ".$size_sm.",
                                     ".$size_xs;



                // ---- title ---- 
                if (get_field('different_slider_title')) {
                    $title = get_field('different_slider_title');
                } else {
                    $title = get_the_title();
                }
                
                // ---- excerpt ---- 
                if (get_field('different_slider_excerpt')) {
                    $excerpt = get_field('different_slider_excerpt');
                } else {
                    $excerpt = get_the_excerpt( );
                }

                // ---- call to action ---- 
                $call = "View more";
                if (get_field('different_slider_call_to_action')) {
                    $call = get_field('different_slider_call_to_action');
                }

                // Get Custom Post slug and Info
                $post_type = get_post_type( $post->ID );
                $obj = get_post_type_object( $post_type ); 

                
            // start the output ?>
	
			<div class="swiper-slide">
            

                <a href="<?php echo get_permalink(); ?>">

                    <img    srcset="<?php echo $image_srcset; ?>"
                            sizes="<?php echo $image_sizes; ?>"
                            alt="<?php echo $alt; ?>"
                            class="max-height <?php /*swiper-lazy*/?>">

                    <?php /*
                    // Doesn't work with SRCSET
                    <div class="swiper-lazy-preloader">
                        <span class="fa fa-bolt fa-spin"></span>
                    </div>*/ ?>
                    

                    <div class="slider-cap">

                        <?php 
                        // If is Hyper series
                        if ($post_type == "hyper") {
                            $series_number = number_of_the_post($post->ID);?>
                            <p class="series">#<?php echo $series_number;?> // <?php the_time('M Y') ?></p>
                        <?php 

                        // If it is any other Series
                        } else {?>
                            <div class="series"><?php the_time('M j') ?><?php if (!($post_type == "post")) { echo ' // '.$obj->labels->name;}?></div>
                        <?php }  ?>

                        <h2><?php echo $title; ?></h2>
						
						<?php if (!get_field('remove_slider_excerpt')) { ?>

                        	<p class="hidden-xs"> <?php echo $excerpt; ?></p>

                        <?php } ?>
    
                        <?php // Deactivate call to action
                        /*<div class="btn btn-primary btn-lg" role="button">

                            <?php echo $call;?>
                        </div>*/ ?>

                    </div>

                </a>

            </div>

		    <?php 
		    endwhile; // finish the ride
		    ?>

		</div>

	<div class="swiper-pagination swiper-pagination-clickable">

	<span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span>
	</div>
	    <div class="swiper-button-prev">
	           <span class="arrowicon fa fa-angle-left"></span>
	    </div>
	    <div class="swiper-button-next">
	           <span class="arrowicon fa fa-angle-right"></span>
	    </div>
	</div>


	<script>

		var mySwiper = new Swiper ('.swiper-container', {

            // mode:'horizontal',
            // //mousewheelControl: true,
            // mousewheelControlForceToAxis: true,
            // freeMode: true,

			pagination: '.swiper-pagination',
			paginationClickable: true,

			nextButton: '.swiper-button-next',
			prevButton: '.swiper-button-prev',

			// AutoPlay
			autoplay: 6000, // it will be actived "On Lazy Image ready"
			speed: 1500,
			

			// Loop
			loop: true,

    		// Keyboard and Mousewheel
    		keyboardControl: true,
    		//mousewheelControl: true,
    		//mousewheelForceToAxis: true, // just use the horizontal axis to 

    		// Lazy Loading 
    		watchSlidesVisibility: true,
    		
    		preloadImages: true, // turn false if active lazyLoading
    			// lazyLoading: true,-- doesnt work with srcset
    			// lazyLoadingInPrevNext: true, -- doesnt work with srcset
            // lazyLoadingOnTransitionStart: true,
			//updateOnImagesReady: false,



    			// // it will start the slider just after images are loaded
    			// onLazyImageReady: function (s) {
    			//   if (!s.params.autoplay) {
    			//     s.params.autoplay = 5500; // set time of autoplay
    			//     s.startAutoplay();
    			//   }
    			// },

			// if user change slide, tehn it stops autoplay
			onSlideChangeStart: function (s) {
			  if (!s.params.autoplay) {
			  	s.params.autoplay = false;
			    s.stopAutoplay();
			  }
			}

		})

		// mySwiper.stopAutoplay(); // booo play around
 	 	
 	 	// mySwiper.startAutoplay();

		
		
	</script>