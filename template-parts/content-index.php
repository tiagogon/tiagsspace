<?php
/*

Index of posts for Home and Archives

*/ ?>


    <div class="container-fluid side-padding series-block front-block " id="index">

        <div id="main" role="main" class="">

            <ul id="thumb-container"
                class="loadcontainer clearfix row <?php if (is_post_type_archive( "hyper" ) OR is_post_type_archive( "films" )) {
                    echo " no-pad";
            } ?>">

                <?php
                // grid on archive
                $grid_base = 'col-12 col-sm-6 col-md-4 col-lg-4';

                if (is_post_type_archive( "hyper" )) {
                    $grid_base = 'col-12 col-sm-6 col-md-4 col-lg-4';
                }
                if (is_post_type_archive( "films" )) {
                    $grid_base = 'col-12 col-sm-6 col-md-6 col-lg-6';
                }

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



                    // Diferent Grid size for this item -- Ex: FILMS

                        //reset
                        $grid_multiplayer = 0;

                        if ($post_type == "films" && !is_post_type_archive( "films" )) {
                            $grid_multiplayer = 2;
                        }

                        // If its sugested posts
                        if ($is_sugested_posts == 1) {
                            $grid_multiplayer = 0;
                        }

                        // If defined on post edit
                        if ( get_field('thumbnail_size')) {
                            $grid_multiplayer = get_field('thumbnail_size');
                        }

                        //reset
                        $grid = $grid_base;

                        if ($grid_multiplayer == 2) {
                            $grid = 'col-12 col-sm-12 col-md-8 col-lg-8';
                        }

                        if ($grid_multiplayer == 3) {
                            $grid = 'col-12 col-sm-12 col-md-12 col-lg-12';
                        }



                    // Grouping Log posts on the front and taxonomies archives
                    if ($post_type == "log" && (is_front_page() OR is_tag() OR is_tax() )  ) { //&& !is_paged()


                        // Get next post_types and Year
                        if (!empty( $post_id_before )){
                            $next_post_type = get_post_type( $post_id_before );
                            $next_post_year = get_the_time( "Y", $post_id_before );
                        }


                        // HTML - Year Separator
                        // Print Year Separator if the
                        if ($year == get_the_time("Y") OR $count == 1) {
                            $year = get_the_time("Y");
                        // Year is diferent
                        } else {
                            $year = get_the_time("Y");?>
                                        </figcaption>
                                </figure>
                            </li>
                            <li class="loadpost year-separator item col-12  item-post" <?php post_class('clearfix'); ?> >
                                <div class="separator-wrapper"><?php echo $year; ?></div>
                            </li>
                        <?php }

                        // Item sizer for masory
                        if ($count == 1 ) {
                            echo '<div class="item-sizer '.$grid.'"></div>';
                        }

                        // HTML - Open Log Posts container
                        if ($count == 1 OR !($next_post_type == "log") OR !($next_post_year == $year)) { ?>
                            <li id="post-group-<?php echo $count; ?>" class="loadpost post-group <?php echo $grid; ?> <?php echo $post_type; ?>-thumb item item<?php echo $count; ?> item-post" <?php post_class('clearfix'); ?> role="article">
                                <figure>
                                    <figcaption>
                                        <p class="series">log</p>
                        <?php }

                        // HTML - Log post title and link ?>


                        <a href="<?php echo get_permalink(); ?>" rel="bookmark">
                            <h2><?php echo taxonomy_list_w_numbers($post->ID,'log-branch','',' ',', ', ' & ', 'no-link');?><span class="branch"> <?php the_title();?> ></span></h2>
                        </a>

                        <?php
                        // HTML - Close Log Posts container
                        // If is last post of the page
                        if ($wp_query->current_post +1 == $wp_query->post_count) { ?>

                                        </figcaption>
                                </figure>
                            </li>

                        <?php }

                    } else { // ... do output for other postypes */


                        // If the Post before was log, close log group

                        // HTML - Close Log Posts container
                        if ( !($next_post_type == "log") ){ ?>

                                        </figcaption>
                                </figure>
                            </li>

                        <?php }


                        // Item Sizer for masonry

                        if ($count == 1 && !is_post_type_archive( "hyper" )) {
                            echo '<div class="item-sizer '.$grid.'"></div>';

                        }

                        // Print Year Separator if the
                        if ($year == get_the_time("Y") OR ($count == 1 AND !is_post_type_archive( "cityburns" )) OR (is_front_page() && !is_paged())) {

                            $year = get_the_time("Y");

                        // Year is diferent
                        } elseif (!is_post_type_archive( "hyper" ) OR (is_front_page() && !is_paged())) {

                            $year = get_the_time("Y");

                            ?>

                            <li class="item loadpost year-separator col-12 item-post" <?php post_class('clearfix'); ?> >
                                <div class="separator-wrapper"><?php echo $year; ?></div>
                            </li>

                        <?php }

                        // Get Custom Post Info
                        $obj = get_post_type_object( $post_type );

                        // Get Image info
                        if ( has_post_thumbnail() and (!($post_type == "log")) ) {


                            // Imgcontainer calculations -- intrinsic ratio

                                // Get Video
                                $video_array = array();
                                $video_array = get_field('video_thumbnail');

                                //get animated image
                                $animated_thumbnail_array = array();
                                $animated_thumbnail_array = get_field('animated_thumbnail');

                                if ($video_array) {

                                    // THERE IS A BUG: on related posts get_filed retrives ID insteas of the Arrat. THis is a work around. if it's array and if it nos (it will be a ID)
                                    if (is_array($video_array)) {

                                        $intrinsic_ratio = $video_array['height'] * 100 / $video_array['width'];
                                        $video_poster = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full')[0];
                                        $video_mp4 = $video_array['url'];
                                        $video_webm = str_replace(".mp4","-vp9.webm",$video_array['url']);

                                    // Else, it must be an ID:
                                    } else {

                                        // Make sure is a number inteiro
                                        $video_array = (int)$video_array;
                                        // https://codex.wordpress.org/Function_Reference/wp_get_attachment_metadata
                                        $video_attachment_metadata = wp_get_attachment_metadata($video_array);

                                        //Final variables
                                        $intrinsic_ratio = $video_attachment_metadata['height'] * 100 / $video_attachment_metadata['width'];
                                        $video_poster = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full')[0];
                                        $video_mp4 = wp_get_attachment_url( $video_array );
                                        $video_webm = str_replace(".mp4","-vp9.webm",$video_mp4);
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

                            $size_lg = (100 / 3)."vw";
                            $size_md = (100 / 3)."vw";
                            $size_sm = (100 / 2)."vw";
                            $size_xs = (100 / 1)."vw";

                            if ($grid_multiplayer == 2) {
                                $size_lg = ((100 / 3)*2)."vw";
                                $size_md = ((100 / 3)*2)."vw";
                                $size_sm = (100 / 1)."vw";
                                $size_xs = (100 / 1)."vw";
                            }

                            if ($grid_multiplayer == 3) {
                                $size_lg = (100 / 1)."vw";
                                $size_md = (100 / 1)."vw";
                                $size_sm = (100 / 1)."vw";
                                $size_xs = (100 / 1)."vw";
                            }

                            if (is_post_type_archive( "films" )) {
                                $size_lg = (100 / 2)."vw";
                                $size_md = (100 / 2)."vw";
                                $size_sm = (100 / 2)."vw";
                                $size_xs = (100 / 1)."vw";
                            }

                            $image_sizes = "(min-width: 1280px) ".$size_lg.",(min-width: 1302px) ".$size_md.",(min-width: 808px) ".$size_sm.",".$size_xs;

                        // Thumbnail image source code

                            if ($animated_thumbnail_array and $video_array == array()) {


                                $source = 'src="'.$animated_thumbnail_array['url'].'" style="width: 100%;"';

                            } else {
                                $source = 'srcset="'.$image_srcset.'" sizes="'.$image_sizes.'" ';
                            }
                        } ?>

                        <li id="post-<?php the_ID(); ?>" class="loadpost item <?php echo $grid; ?> <?php echo $post_type; ?>-thumb item<?php echo $count; ?> item-post" <?php post_class('clearfix'); ?> role="article">

                            <?php if (!($post_type == "log")) { ?>

                            <figure>

                                <a href="<?php echo get_permalink(); ?>" rel="bookmark">

                                    <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">

                                        <?php // IF IS VIDEO -- via: https://codepen.io/dudleystorey/pen/knqyK
                                        if (!empty($video_array)) { ?>

                                            <video poster="<?php echo $video_poster; ?>" id="bgvid" playsinline autoplay muted loop>
                                                <source src="<?php echo $video_webm; ?>" type="video/webm">
                                                <source src="<?php echo $video_mp4; ?>" type="video/mp4">
                                            </video>

                                        <?php //IS not video
                                        } else { ?>

                                            <img <?php echo $source; ?> alt="<?php the_title(); ?>" />

                                        <?php } ?>


                                    </div>


                                    <figcaption>

                                        <?php
                                        // Is series archive
                                        /* if( is_post_type_archive() ) {

                                            if ($post_type == "hyper") {
                                                $series_number = number_of_the_post($post->ID);?>

                                                    <p class="series">hyper#<?php echo $series_number;?><?php //the_time('Y'); ?></p>

                                            <?php } elseif ($post_type == "emulsion") { ?>

                                                <p class="series"><?php the_time('M d'); ?></p>

                                            <?php } elseif ($post_type == "dusk") { ?>


                                                <p class="series"><?php the_time('M d'); ?></p>

                                            <?php } elseif ($post_type == "films") { ?>


                                                <p class="series"><?php echo taxonomy_list($post->ID,'from', '', '', ', ', ' & ', ''); if (get_field('film_length')) { echo " // ".get_field('film_length')."′"; } ?></p>

                                            <?php } else { ?>


                                                <div class="series"><?php if (!($post_type == "post")) { echo $obj->labels->name;}?></div>

                                            <?php }

                                        // Is HOME or taxonomies
                                        } else {

                                            if ($post_type == "hyper") {
                                                $series_number = number_of_the_post($post->ID);?>

                                                    <p class="series">hyper#<?php echo $series_number;?></p>

                                            <?php } elseif ($post_type == "emulsion") { ?>

                                                <p class="series">emulsion</p>

                                            <?php } elseif ($post_type == "dusk") { ?>


                                                <p class="series">dusk</p>

                                            <?php } elseif ($post_type == "films") { ?>


                                                <p class="series"><?php
                                                    if (get_field('film_length')) {
                                                        echo get_field('film_length')."′"." // ";
                                                    }
                                                    echo taxonomy_list($post->ID,'from', '', '', ', ', ' & ', '');
                                                    ?></p>

                                            <?php } elseif ($post_type == "post") { ?>

                                                <div class="series">undefined</div>

                                            <?php } else { ?>

                                                <div class="series"><?php echo $obj->labels->name;?></div>

                                            <?php }  ?>
                                        <?php }*/ ?>

                                        <h2><?php the_title();?></h2>


                                    </figcaption>
                                </a>
                            </figure>

                            <?php } else { ?>
                                <figure>

                                    <a href="<?php echo get_permalink(); ?>" rel="bookmark">

                                        <figcaption>

                                            <p class="series"><?php echo $obj->labels->name;?></p>

                                            <h2><span class="branch"><?php echo taxonomy_list_w_numbers($post->ID,'log-branch','',', ',', ', ' & ', 'no-link');?></span><?php the_title(); ?></h2>

                                        </figcaption>

                                    </a>

                                </figure>
                            <?php } ?>

                        </li>

                    <?php } // Else

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

        </div> <!-- end #main -->

    </div> <!-- end #container -->

    <?php // PAGINATION

    // Check if there are previouse or next pages
    $prev_link = get_previous_posts_link(__('&laquo; Older Entries'));
    $next_link = get_next_posts_link(__('Newer Entries &raquo;'));

    // if exists links
    if (($prev_link || $next_link) AND !is_singular()) { ?>

        <div class="container-fluid pagination-container">
            <div class="row justify-content-between">
                <nav class="archive-navigation col-6">
                    <span class="nav-next"><?php previous_posts_link( '< Future' ); ?></span>
                </nav>
                <nav class="archive-navigation col-6">
                    <span class="nav-previous"><?php next_posts_link( 'Past >' ); ?></span>
                </nav>
            </div>

        </div>
    <?php } ?>


    <?php  // --- Masory  ?>

    <?php

    if (!is_post_type_archive( "hyper" ) AND !is_post_type_archive( "films" )) { ?>
        <script>
            // $('#thumb-container').masonry({
            //   itemSelector: '.item',
            //   columnWidth: '.item-sizer',
            //   percentPosition: true,
            //   transitionDuration: '0.6s'
            // })


            $('#thumb-container').masonry({
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

    if (is_tax( 'medium', 'photography') ) { ?>

        <script src="https://unpkg.com/infinite-scroll@3/dist/infinite-scroll.pkgd.min.js"></script>

        <script type="text/javascript">

            // get Masonry instance
            var msnry = $('#thumb-container').data('masonry');

            var $container = $('.loadcontainer').infiniteScroll({
              // options
              path: 'page/{{#}}/',
              append: '.loadpost',
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

            $container.on( 'append.infiniteScroll', function(){
              picturefill();
            });

        </script>

    <?php } ?>
