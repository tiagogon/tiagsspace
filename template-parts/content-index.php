<?php
/*

Index of posts for Home and Archives

*/ ?>

    <div class="first-block index-block container-fluid side-paddin" id="index">

      <ul id="masonry-container"
          class="loadcontainer clearfix row">

          <?php


          // Is sugested posts loop
          $is_sugested_posts = 0;

          if (is_singular() ) {
              $is_sugested_posts = 1;
          }

          // set up the counter variable as 0
          $count = 0;

          //current year
          $year = date("Y");

          if (have_posts()) : while (have_posts()) : the_post();


              //increment the variable by 1 each time the loop executes
              $count++;

              // Get Custom Post slug
              $post_type = get_post_type( $post->ID );

              if ($count == 1) {
                  $post_id_before = "";
              }

              // Declare empety variable
              $next_post = "";
              $next_post_type = "";
              $next_post_year = "";
              $hide_figcaption = "hide-figcaption";

              if ($post_type == "films"){
                  //$hide_figcaption = "";
              }


              // GRID Mansonary

              // NOTES
              // The values for the number of collumns need to be always a multiple of 2. E.g. 2,4,6,8, etc...

              // grid on archive
              $grid_sizer = 'col-2 col-sm-2 col-md-2 col-lg-1';

              $grid_year_separator = 'col-48 col-sm-8 col-md-6';

              // Default umber of collumns
              $grid_array = array(48, 12, 12, 8);

              if ($post_type == "films" ) {
                  $grid_array = array(48, 14, 14, 12);
              }
              if ($post_type == "dusk" or $post_type == "emulsion") {
                  $grid_array = array(48, 12, 12, 10);
              }
              if ($post_type == "hyper") {
                  $grid_array = array(48, 12, 18, 8);
              }
              if ($post_type == "4k-lento" ) {
                  $grid_array = array(48, 12, 12, 6);
              }
              if ($post_type == "log" ) {
                  $grid_array = array(48, 24, 16, 5);
              }

                  //
                  // Get coolumns size in proportion to image area
                  //
                  //collumns size in percentage of width
                  $grid_array_width_prc[0] = $grid_array[0] * 100/48;
                  $grid_array_width_prc[1] = $grid_array[1] * 100/48;
                  $grid_array_width_prc[2] = $grid_array[2] * 100/48;
                  $grid_array_width_prc[3] = $grid_array[3] * 100/48;

                  //Area for 1x1 thumbnail percentage
                  $grid_array_area1x1_prc[0] = $grid_array_width_prc[0] * $grid_array_width_prc[0];
                  $grid_array_area1x1_prc[1] = $grid_array_width_prc[1] * $grid_array_width_prc[1];
                  $grid_array_area1x1_prc[2] = $grid_array_width_prc[2] * $grid_array_width_prc[2];
                  $grid_array_area1x1_prc[3] = $grid_array_width_prc[3] * $grid_array_width_prc[3];

                  // Get thumbanisl width and heigh
                  $image_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), false);

                  // Image thumbnail ratio -- with/height
                  $image_thumb_ratio = $image_thumb_attributes[1] / $image_thumb_attributes[2] ;

                  //collumns size proportional to image area in percentage of width
                  $grid_array_width_prop_to_area_prc[0] = sqrt($grid_array_area1x1_prc[0] * $image_thumb_ratio);
                  $grid_array_width_prop_to_area_prc[1] = sqrt($grid_array_area1x1_prc[1] * $image_thumb_ratio);
                  $grid_array_width_prop_to_area_prc[2] = sqrt($grid_array_area1x1_prc[2] * $image_thumb_ratio);
                  $grid_array_width_prop_to_area_prc[3] = sqrt($grid_array_area1x1_prc[3] * $image_thumb_ratio);

                  // Number of collumns proportional to area of images
                  $grid_array_prop_to_area[0] = round(
                                                  ($grid_array_width_prop_to_area_prc[0] / (100/48)) /2
                                                )*2;
                  $grid_array_prop_to_area[1] = round(($grid_array_width_prop_to_area_prc[1] / (100/48))/2)*2;
                  $grid_array_prop_to_area[2] = round(($grid_array_width_prop_to_area_prc[2] / (100/48))/2)*2;
                  $grid_array_prop_to_area[3] = round($grid_array_width_prop_to_area_prc[3] / (100/48));

                  // Increase 1, keep or decrease 1 the collumns
                  if (get_field('column_size_increment')) {
                    $increment = get_field('column_size_increment');
                    //$grid_array_prop_to_area[0] = $grid_array_prop_to_area[0] + $increment;
                    $grid_array_prop_to_area[1] = $grid_array_prop_to_area[1] + $increment;
                    $grid_array_prop_to_area[2] = $grid_array_prop_to_area[2] + $increment;
                    $grid_array_prop_to_area[3] = $grid_array_prop_to_area[3] + $increment;
                  } else {
                    $random_increment = rand(-1, 1);
                    //$grid_array_prop_to_area[0] = $grid_array_prop_to_area[0] + $random_increment;
                    $grid_array_prop_to_area[1] = $grid_array_prop_to_area[1] + $random_increment;
                    $grid_array_prop_to_area[2] = $grid_array_prop_to_area[2] + $random_increment;
                    $grid_array_prop_to_area[3] = $grid_array_prop_to_area[3] + $random_increment;
                  }

                  // Cap to max of number of collumn and minimun to havoid sizer-bug
                  if ($grid_array_prop_to_area[0] < 48) { $grid_array_prop_to_area[0] = 48;}
                  if ($grid_array_prop_to_area[0] > 48) { $grid_array_prop_to_area[0] = 48;}
                  if ($grid_array_prop_to_area[1] < 2) { $grid_array_prop_to_area[1] = 2;}
                  if ($grid_array_prop_to_area[1] > 48) { $grid_array_prop_to_area[1] = 48;}
                  if ($grid_array_prop_to_area[2] < 2) { $grid_array_prop_to_area[2] = 2;}
                  if ($grid_array_prop_to_area[2] > 48) { $grid_array_prop_to_area[2] = 48;}
                  if ($grid_array_prop_to_area[3] < 1) { $grid_array_prop_to_area[3] = 1;}
                  if ($grid_array_prop_to_area[3] > 48) { $grid_array_prop_to_area[3] = 48;}

                  // Get the number of collumns porportional to Area
                  $grid_array = $grid_array_prop_to_area;

              $grid = 'col-'.$grid_array[0].' col-sm-'.$grid_array[1].' col-md-'.$grid_array[2].' col-lg-'.$grid_array[3].'';


                  // Item Sizer for masonry

                  if ($count == 1) {
                      // echo '<div class="masonry-item-sizer '.$grid.'"></div>';
                      echo '<div class="masonry-item-sizer '.$grid_sizer.'"></div>';

                  }

                  // Print Year Separator if the
                  if ($year == get_the_time("Y")) {

                      $year = get_the_time("Y");

                  // Year is diferent
                  } else {

                      $year = get_the_time("Y");

                      ?>

                      <li class="masonry-item year-separator <?php echo $grid_year_separator; ?> " <?php post_class('clearfix'); ?> >
                          <div class="separator-wrapper text-sm-left text-md-center"><?php echo $year; ?></div>
                      </li>

                  <?php }

                  // Get Custom Post Info
                  $obj = get_post_type_object( $post_type );

                  // Get Image info
                  if ( has_post_thumbnail()) {


                      // Imgcontainer calculations -- intrinsic ratio

                          // Get Video
                          $video_thumbnail_id = "";
                          $video_thumbnail_id = get_field('video_thumbnail');

                          //get animated image
                          $animated_thumbnail_array = array();
                          $animated_thumbnail_array = get_field('animated_thumbnail');

                          if ($video_thumbnail_id) {

                              // THERE IS A BUG: on related posts get_filed retrives ID insteas of the Arrat. THis is a work around. if it's array and if it nos (it will be a ID)
                              if (is_array($video_thumbnail_id)) {

                                  $intrinsic_ratio = $video_thumbnail_id['height'] * 100 / $video_thumbnail_id['width'];
                                  $video_poster = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full')[0];
                                  // $video_mp4 = $video_thumbnail_id['url'];
                                  // $video_webmvp8 = str_replace(".mp4","-vp8.webm",$video_thumbnail_id['url']);
                                  // $video_webmvp9 = str_replace(".mp4","-vp9.webm",$video_thumbnail_id['url']);

                              // Else, it must be an ID:
                              } else {

                                  // Make sure is a number inteiro
                                  $video_thumbnail_id = (int)$video_thumbnail_id;
                                  // https://codex.wordpress.org/Function_Reference/wp_get_attachment_metadata
                                  $video_attachment_metadata = wp_get_attachment_metadata($video_thumbnail_id);

                                  //Final variables
                                  $intrinsic_ratio = $video_attachment_metadata['height'] * 100 / $video_attachment_metadata['width'];
                                  $video_poster = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full')[0];
                                  // $video_mp4 = wp_get_attachment_url( $video_thumbnail_id );
                                  // $video_webmvp8 = str_replace(".mp4","-vp8.webm",$video_mp4);
                                  // $video_webmvp9 = str_replace(".mp4","-vp9.webm",$video_mp4);
                              }


                          } else {
                              // If there is an animated thumbail

                              if ($animated_thumbnail_array) {

                                  $image_animate_thumb_attributes = wp_get_attachment_image_src($animated_thumbnail_array['ID'], 'full');

                                  $intrinsic_ratio = $image_animate_thumb_attributes[2] * 100 / $image_animate_thumb_attributes[1];

                              // If it's a normal thumbnail
                              }  else {
                                  $image_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
                                  $intrinsic_ratio = $image_thumb_attributes[2] * 100 / $image_thumb_attributes[1];
                              }
                          }


                  // --- SRCSET calculation ---
                      // Original image File
                      $image_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), false);
                      $image_thumb_srcset = $image_thumb_attributes[0]." ".$image_thumb_attributes[1]."w";

                      // IF diferent sizes exist

                      // thumbnail
                      $image_thumb_thumbnail_srcset ="";
                      $image_thumb_thumbnail_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "thumbnail");
                      if ($image_thumb_thumbnail_attributes[3]) {
                          $image_thumb_thumbnail_srcset = $image_thumb_thumbnail_attributes[0]." ".$image_thumb_thumbnail_attributes[1]."w, ";
                      }

                      // small
                      $image_thumb_small_srcset ="";
                      $image_thumb_small_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "small");
                      if ($image_thumb_small_attributes[3]) {
                          $image_thumb_small_srcset = $image_thumb_small_attributes[0]." ".$image_thumb_small_attributes[1]."w, ";
                      }

                      // medium
                      $image_thumb_medium_srcset ="";
                      $image_thumb_medium_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "medium");
                      if ($image_thumb_medium_attributes[3]) {
                          $image_thumb_medium_srcset = $image_thumb_medium_attributes[0]." ".$image_thumb_medium_attributes[1]."w, ";
                      }

                      // large
                      $image_thumb_large_srcset ="";
                      $image_thumb_large_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "large");
                      if ($image_thumb_large_attributes[3]) {
                          $image_thumb_large_srcset = $image_thumb_large_attributes[0]." ".$image_thumb_large_attributes[1]."w, ";
                      }

                      // HD
                      $image_thumb_HD_srcset ="";
                      $image_thumb_HD_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID));
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

                      $size_lg = $grid_array_width_prc[3]."vw";
                      $size_md = $grid_array_width_prc[2]."vw";
                      $size_sm = $grid_array_width_prc[1]."vw";
                      $size_xs = $grid_array_width_prc[0]."vw";

                      $image_sizes = "(min-width: 1280px) ".$size_lg.",(min-width: 1302px) ".$size_md.",(min-width: 808px) ".$size_sm.",".$size_xs;

                  // Thumbnail image source code

                      if ($animated_thumbnail_array and $video_thumbnail_id == array()) {


                          $source = 'src="'.$animated_thumbnail_array['url'].'" style="width: 100%;"';

                      } else {
                          $source = 'srcset="'.$image_srcset.'" sizes="'.$image_sizes.'" ';
                      }
                  } ?>

                  <li id="post-<?php the_ID(); ?>" class="masonry-item <?php echo $grid; ?> <?php echo $post_type; ?>-item <?php if ($intrinsic_ratio >= 56.25) { /* 16:9 percentage ratio (9/16*100)*/ echo "wide-ratio"; }  ?> item-numb-<?php echo $count; ?> " <?php post_class('clearfix'); ?> role="article">

                      <figure>

                          <a href="<?php echo get_permalink(); ?>">

                              <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">

                                  <?php // IF IS VIDEO -- via: https://codepen.io/dudleystorey/pen/knqyK
                                  if (!empty($video_thumbnail_id)) {

                                    // Use videoplayer from VideoPack
                                    // Use of videopack shortcode https://www.wordpressvideopack.com/docs/shortcode-reference/
                                    // Use of shortcode via PHP https://developer.wordpress.org/reference/functions/do_shortcode/
                                    // Parameters

                                    echo do_shortcode( '[KGVID id="'.$video_thumbnail_id.'" muted="true" controls="false" loop="true" autoplay="true" pauseothervideos="false" pixel_ratio="true" schema="false" poster="'.$video_poster.'"]' );

                                  } else { ?>

                                    <img <?php echo $source; ?> alt="<?php the_title(); ?>" />

                                  <?php } ?>

                              </div>


                              <figcaption  class="<?php echo $hide_figcaption;?>">


                                  <?php if ( $post_type == "hyper") {?>
                                      <h2>
                                          <?php echo "H".sprintf("%02d", number_of_the_post($post->ID))." ";?><?php the_title(); ?>
                                      </h2>
                                  <?php }elseif (
                                      $post_type == "4k-lento") {?>
                                      <h2>
                                          <?php echo "4KL".sprintf("%02d", number_of_the_post($post->ID))." ";?><?php the_title(); ?>
                                      </h2>
                                  <?php }elseif (
                                      $post_type == "log") {?>
                                      <h2>
                                          <?php echo taxonomy_list_w_numbers($post->ID,'log-branch','',' ',', ', ' & ', 'no-link');?><span class="branch"> <?php the_title(); ?>
                                      </h2>
                                  <?php } else { ?>
                                      <h2>
                                          <?php the_title();?>
                                      </h2>
                                  <?php  } ?>



                              </figcaption>
                          </a>
                      </figure>

                  </li>

              <?php // } // Else Comente from old log blocks

              // Send this post type for the next loop iteration
              $post_id_before = $post->ID;

          endwhile; ?>

      </ul>

      <?php else : ?>

      <article id="post-not-found">
          <header>
              <h1><?php _e("Not Found", "wpbootstrap"); ?></h1>
          </header>
          <section class="post_content">
              <p><?php _e("Sorry, but the requested resource was not found on this site.", "wpbootstrap"); ?></p>
          </section>
          <footer>
          </footer>
      </article>

      <?php endif; ?>



    </div> <!-- end #container -->

    <?php // PAGINATION

    // Check if there are previouse or next pages
    $prev_link = get_previous_posts_link(__('&laquo; Older Entries'));
    $next_link = get_next_posts_link(__('Newer Entries &raquo;'));

    // if exists links
    if (($prev_link || $next_link) AND !is_singular()) { ?>

        <div class="container-fluid pagination-container">
            <div class="row justify-content-between">
                <nav class="archive-navigation col-24">
                    <span class="nav-next"><?php previous_posts_link( '/Future' ); ?></span>
                </nav>
                <nav class="archive-navigation col-24">
                    <span class="nav-previous"><?php next_posts_link( '/Past' ); ?></span>
                </nav>
            </div>

        </div>
    <?php } ?>


    <?php  // --- Masory  ?>
    <script>
        // $('#masonry-container').masonry({
        //   itemSelector: '.item',
        //   columnWidth: '.masonry-item-sizer',
        //   percentPosition: true,
        //   transitionDuration: '0.6s'
        // })


        $('#masonry-container').masonry({
          // set itemSelector so .grid-sizer is not used in layout
          itemSelector: '.masonry-item',
          // use element for option
          columnWidth: '.masonry-item-sizer',
          percentPosition: true,
          transitionDuration: '0.6s',
          gutter: 0,
          percentPosition: true,
        })
    </script>
    <?php

    // Play videos just on the view port and fix of iOS
    // VIA: https://stackoverflow.com/questions/15395920/play-html5-video-when-scrolled-to
    if (is_post_type_archive( "films" )
       // Acitvate it for Log series with video autoplay -- NEEDS TO BE TESTED
       // OR is_post_type_archive( "log" ) OR is_tax( 'log-branch' )
    ) { ?>

        <script>
            $(document).ready(function() {
                // Get media - with autoplay disabled (audio or video)
                var media = $('video').not("[autoplay='autoplay']");
                var tolerancePixel = 40;

                function checkMedia(){
                    // Get current browser top and bottom
                    var scrollTop = $(window).scrollTop() + tolerancePixel;
                    var scrollBottom = $(window).scrollTop() + $(window).height() - tolerancePixel;

                    media.each(function(index, el) {
                        var yTopMedia = $(this).offset().top;
                        var yBottomMedia = $(this).height() + yTopMedia;

                        if(scrollTop < yBottomMedia && scrollBottom > yTopMedia){ //view explaination in `In brief` section above
                            $(this).get(0).play();
                        } else {
                            $(this).get(0).pause();
                        }
                    });

                    //}
                }
                $(document).on('scroll', checkMedia);
            });
        </script>

    <?php } ?>




    <?php  // --- Infinite Scrool

    if (!is_single() ) { ?>

        <!-- OLD <script src="https://unpkg.com/infinite-scroll@3/dist/infinite-scroll.pkgd.min.js"></script> -->

        <script type="text/javascript"  src="<?php bloginfo('template_url'); ?>/library/js/infinite-scroll/infinite-scroll.pkgd.min.js"></script>
        <script type="text/javascript"  src="https://tiags.space/wp-content/plugins/video-embed-thumbnail-generator/video-js/v7/video.min.js?x33528"></script>

        <script type="text/javascript">

            // get Masonry instance
            var msnry = $('#masonry-container').data('masonry');

            var $container = $('.loadcontainer').infiniteScroll({
              // options
              path: 'page/{{#}}/',
              append: '.masonry-item',
              outlayer: msnry,
              history: false,
              button: '.view-more-button',
            });

            // -- Scroll 2 pages, then load with button -- https://infinite-scroll.com/extras.html#scroll-2-pages-then-load-with-button
            var $viewMoreButton = $('.view-more-button');

            // get Infinite Scroll instance
            var infScroll = $container.data('infiniteScroll');

            $container.on( 'load.infiniteScroll', onPageLoad );

            function onPageLoad() {
              if ( infScroll.loadCount == 2 ) {
                // after 2nd page loaded
                // disable loading on scroll
                $container.infiniteScroll( 'option', {
                  loadOnScroll: false,
                });
                // show button
                $viewMoreButton.show();
                // remove event listener
                $container.off( 'load.infiniteScroll', onPageLoad );
              }
            }
            $container.on( 'append.infiniteScroll', function( event, body, path, items, response ) {
              //DEbug
              console.log(`Appended ${items.length} items on ${path}`);

                picturefill();

                // VIDEO ISSUE -- Code

                //VideoJS trigers
                videojs(document.querySelector('.video-js'));

                // Videopack functions
                jQuery(document).ready(kgvid_document_ready());
                jQuery(window).on("load", kgvid_window_load);

            });

        </script>

        <?php
    } ?>
