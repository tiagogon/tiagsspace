<?php
/*
Gallery template for single pages
*/



// get attachmens atached to the post
// On preview pages $post is a revision; resolve to the parent post so attachments are found
$gallery_post_id = wp_is_post_revision( $post->ID ) ? wp_get_post_parent_id( $post->ID ) : $post->ID;

// Exclude the film's own video source (the `self_host_film` attachment — an HLS
// .m3u8 or a progressive video). It's rendered by the player, not the gallery;
// dragging an HLS bundle in while editing parents it to the post, so without
// this it would render as a stray empty gallery item.
$self_host_film_id = intval( get_field( 'self_host_film', $gallery_post_id ) );

$args = array(
    'numberposts'       => -1, // Using -1 loads all posts
    'orderby'           => 'menu_order', // set in the page media manager
    'order'             => 'ASC',
    // 'post_mime_type'    => 'image', // Make sure it doesn't pull other resources, like videos
    'post_parent'       => $gallery_post_id, // Important part - ensures the associated images are loaded
    'exclude'           => $self_host_film_id ? array( $self_host_film_id ) : array(),
    'post_status'       => null,
    'post_type'         => 'attachment',
    'meta_query'        => array(
        'relation' => 'OR',
        array(
            'key'     => 'remove_from_default_gallery',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => 'remove_from_default_gallery',
            'value'   => '1',
            'compare' => '!=',
        ),
    ),
);
$attachmens = get_posts( $args );


if($attachmens){

    // -------------------------------
    // --- Sefault Galerry Settings ---
    // --------------------------------

    // Define Variables
    $item_max_heigh = '';
    $gallery_gap = 'default'; // 0, 2, 4, 8, 16, 20 or 'default' (the page gutter): see tiagsspace_gallery_gap()
    $deactivat_masonry = false;
    $there_is_video = '';
    $there_is_3d = '';
    $light_box = 'none';


    if ( !get_field('alternative_gallery')) {

        // Dusk posts
        if ( is_singular( 'dusk' )) {
            $class_container        = 'container-fluid';
            $number_of_columns_xs   = 1;
            $number_of_columns_sm   = 3;
            $number_of_columns_md   = 3;
            $number_of_columns_lg   = 3;
            $gallery_gap            = 0;
            $light_box              = 'magnific_popup';

        }

        // HYPER
        elseif ( is_singular( 'hyper' )) {
            $class_container        = 'container-fluid';
            $number_of_columns_xs   = 2;
            $number_of_columns_sm   = 4;
            $number_of_columns_md   = 4;
            $number_of_columns_lg   = 4;
            $gallery_gap               = 0;
            $light_box              = 'intense-images';
            $deactivat_masonry      = true;
        }

        // Cidade (City Series until Nov 2026)
        elseif ( is_singular( 'cidade' )) {
            $class_container        = 'container-fluid';
            $number_of_columns_xs   = 1;
            $number_of_columns_sm   = 1;
            $number_of_columns_md   = 3;
            $number_of_columns_lg   = 3;
            $gallery_gap            = 'default'; // the theme's own spacing, as between thumbnails
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
                $gallery_gap               = 0;
                $light_box              = 'none';
                $deactivat_masonry      = true;
            } elseif (has_term( 'hrzn', 'log-branch' ) ) {
                $class_container        = 'container-fluid';
                $number_of_columns_xs   = 1;
                $number_of_columns_sm   = 1;
                $number_of_columns_md   = 1;
                $number_of_columns_lg   = 1;
                $gallery_gap               = 0;
                $light_box              = 'none';
                $deactivat_masonry      = true;
            } elseif (has_term( 'still', 'log-branch' ) ) {
                  $class_container        = 'container-fluid';
                  $number_of_columns_xs   = 1;
                  $number_of_columns_sm   = 1;
                  $number_of_columns_md   = 1;
                  $number_of_columns_lg   = 1;
                  $gallery_gap               = 0;
                  $light_box              = 'none';
                  $deactivat_masonry      = true;
              } else {
                $class_container        = 'container';
                $number_of_columns_xs   = 1;
                $number_of_columns_sm   = 1;
                $number_of_columns_md   = 1;
                $number_of_columns_lg   = 1;
                $gallery_gap            = 'default';
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
        $item_max_heigh = get_field('item_max_heigh');
        $item_max_heigh = in_array( $item_max_heigh, array( 'max-heigh-90vh-left', 'max-heigh-90vh-right' ), true ) ? $item_max_heigh : '';

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

        // Space between images
        $gallery_gap = tiagsspace_gallery_gap();

        $deactivat_masonry = get_field('deactivat_masonry');

        // Image Box
        $light_box = get_field('light_box');
    }


    // -------------------------------
    // --- Final calculations
    // -------------------------------


    // Container: only the two values the grid knows
    $class_container = ( 'container' === $class_container ) ? 'container' : 'container-fluid';

    // Space between images. One number, the gap, drives everything (see
    // .gallery-gapped in _layout-grid-spacing.scss). No gap keeps the old
    // edge-to-edge path (.row.no-pad).
    $row            = 'row';
    $no_space       = '';
    $gapped_class   = '';
    $gap_style_attr = '';
    // Only a no-gap Container gallery is centred at a fixed max width; with a
    // gap both container types run between the theme's side lines, so the
    // image `sizes` in gallery-item.php must be viewport fractions.
    $gallery_fixed_width = ( 'container' === $class_container && 0 === $gallery_gap );
    // "Item max height" without Masonry: the row becomes a grid whose columns
    // shrink with their capped images, so the images still touch (see
    // .gallery-max-heigh-grid in _page-single-gallery.scss). The column counts
    // travel as custom properties; Masonry galleries keep the old behaviour,
    // because Masonry positions the items itself.
    $row_style_attr = '';
    $masonry_active = ( $number_of_columns_lg > 1 && $deactivat_masonry == false );
    if ( '' !== $item_max_heigh && ! $masonry_active ) {
        $row .= ' gallery-max-heigh-grid';
        if ( 'max-heigh-90vh-right' === $item_max_heigh ) {
            $row .= ' gallery-max-heigh-right';
        }
        $row_style_attr = sprintf(
            ' style="--cols-xs: %d; --cols-sm: %d; --cols-md: %d; --cols-lg: %d"',
            max( 1, (int) $number_of_columns_xs ),
            max( 1, (int) $number_of_columns_sm ),
            max( 1, (int) $number_of_columns_md ),
            max( 1, (int) $number_of_columns_lg )
        );
    }
    // Hidden images (edit mode only): rendered into the pool below, and they
    // count for the Masonry sizer, because N / U / Restore put them back into
    // the grid without a reload.
    $hidden_pool_items = array();
    if ( tiagsspace_gallery_edit_mode( $gallery_post_id ) ) {
        $hidden_pool_items = get_posts( array(
            'numberposts' => -1,
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
            'post_parent' => $gallery_post_id,
            'exclude'     => $self_host_film_id ? array( $self_host_film_id ) : array(),
            'post_status' => null,
            'post_type'   => 'attachment',
            'meta_query'  => array(
                array(
                    'key'   => 'remove_from_default_gallery',
                    'value' => '1',
                ),
            ),
        ) );
    }

    // Masonry column: on each breakpoint the GCD of every item's span, so no
    // item is rounded up to a wider slot (see tiagsspace_gallery_sizer_spans()).
    $masonry_sizer_class = '';
    if ( $masonry_active ) {
        $sizer_spans = tiagsspace_gallery_sizer_spans(
            array_merge( $attachmens, $hidden_pool_items ),
            array( $number_of_columns_xs, $number_of_columns_sm, $number_of_columns_md, $number_of_columns_lg )
        );
        $masonry_sizer_class = vsprintf( 'col-%d col-sm-%d col-md-%d col-lg-%d', $sizer_spans );
    }

    // Image loading, one decision per request: only the first gallery on the
    // page (not on Infinite Scroll pages) loads its first row at once, and its
    // first image gets fetchpriority="high" unless a film player sits above it.
    // Everything else is lazy: later rows, the galleries after the first (one
    // per entry on the Log listing), the hidden pool. Animation galleries show
    // their images on a timer, so they load them all at once.
    $gallery_is_animated   = ( get_field( 'animation' ) == 'swap-randomly-positions-constant-frequency' );
    $gallery_is_lead       = tiagsspace_once( 'lead-gallery' ) && ! is_paged();
    $gallery_eager_items   = $gallery_is_animated ? PHP_INT_MAX : ( $gallery_is_lead ? max( 1, (int) $number_of_columns_lg ) : 0 );
    $gallery_high_priority = $gallery_is_lead && ! $self_host_film_id;

    if ( 0 === $gallery_gap ) {
        $no_space = 'no-pad';
    } else {
        $gapped_class = 'gallery-gapped';
        if ( 'default' !== $gallery_gap ) {
            $gap_style_attr = ' style="--gallery-gap: ' . (int) $gallery_gap . 'px"';
        }
    }





    // Intense Images
    $intense = '';
    if ($light_box == 'intense-images') {
        $intense = 'intense';
        if ( tiagsspace_once( 'intense-js' ) ) { ?>
        <script src='<?php bloginfo('template_url'); ?>/library/js/intense-images/intense.min.js?v=<?php echo esc_attr( tiagsspace_asset_ver( '/library/js/intense-images/intense.min.js' ) ); ?>'></script><?php
        }
    }

    // Magnific Pop Up gallery
    $magnific_popup = "";
    if ($light_box == 'magnific_popup') {
        $magnific_popup = "magnific_popup-gallery-".$post->ID;
        // Magnific Popup core JS file, once per page
        if ( tiagsspace_once( 'magnific-js' ) ) {
        ?><script src="<?php bloginfo('template_url'); ?>/library/js/Magnific-Popup/jquery.magnific-popup.min.js?v=<?php echo esc_attr( tiagsspace_asset_ver( '/library/js/Magnific-Popup/jquery.magnific-popup.min.js' ) ); ?>"></script>
    <?php }
    } ?>

    <div class="<?php echo trim( $class_container . ' container-gallery ' . $gapped_class ); ?>"<?php echo $gap_style_attr; ?>>

        <?php if ( tiagsspace_gallery_edit_mode( $gallery_post_id ) ) { tiagsspace_gallery_edit_style(); } // before the grid: see the function ?>
        <div id="gallery-<?php the_ID(); ?>" class="gallery clearfix <?php echo $magnific_popup;?>  <?php echo $row;?> <?php echo $no_space;?>"<?php echo $row_style_attr; ?> itemscope itemtype="http://schema.org/ImageGallery">

            <?php
            $count_item = 0;

            // loop the atached images
            foreach($attachmens as $attachmen){

                // Per-item markup lives in the shared partial so the hidden
                // pool below can render identical items (see gallery-item.php).
                include( locate_template( 'template-parts/gallery-item.php' ) );

            } // end loop
            ?>

        </div>

    </div>

    <?php
    // ------------------------------------------------------------------
    // --- Hidden-attachment pool (preview edit mode only) ---
    // ------------------------------------------------------------------
    // Pre-render every attachment hidden via "remove_from_default_gallery"
    // into an off-grid, display:none container, using the SAME item markup as
    // the live grid (gallery-item.php). The "NEXT HIDDEN" button then moves the
    // next node from here into the gallery with no page reload — so items hidden
    // in any past session are recoverable, unlike the in-memory Undo (U) stack.
    if ( tiagsspace_gallery_edit_mode( $gallery_post_id ) ) {

        // $hidden_pool_items was fetched with the Masonry sizer, above.
        // The pool is always present in preview mode, even when empty: H parks
        // hidden nodes here and orderAttachmentesOnWpDb() reads it to keep
        // hidden items at their spot in menu_order.
        $rendering_hidden_pool = true;
        ?>
        <div id="gallery-hidden-pool-<?php the_ID(); ?>" class="gallery-hidden-pool" style="display:none" aria-hidden="true">
            <?php
            if ( $hidden_pool_items ) {
                // gallery-item.php increments $count_item itself.
                foreach ( $hidden_pool_items as $attachmen ) {
                    include( locate_template( 'template-parts/gallery-item.php' ) );
                }
                wp_reset_postdata();
            }
            ?>
        </div>
        <?php
        $rendering_hidden_pool = false;
    }
    ?>

    <?php
    // ---------------
    // --- Scripts ---
    // ---------------
    ?>

<?php
    // --- Masonry ----
        if ($number_of_columns_lg > 1 && $deactivat_masonry == false) { ?>
                <script type="text/javascript">
                        // Lays out at once: the items reserve their height (library/js/masonry-grid.js)
                        tiagsspaceMasonry(document.getElementById('gallery-<?php the_ID(); ?>'), {
                            itemSelector: '.item',
                            columnWidth: '.masonry-item-sizer',
                            percentPosition: true,
                            transitionDuration: <?php echo tiagsspace_gallery_edit_mode( $gallery_post_id ) ? "'0'" : "'0.6s'"; ?>,
                            gutter: 0
                        });
                </script>
        <?php }



    // --- Magnific Popup
    if ($light_box == 'magnific_popup') {?>

        <script type="text/javascript">
            // $(document).ready(function() {
                $('.magnific_popup-gallery-<?php the_ID(); ?>').magnificPopup({
                  delegate: 'a.magnific-popup-link',
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

    // VIDEO - Intersection Observer for smart autoplay
    if ($there_is_video == true && tiagsspace_once( 'gallery-video-observer' )) { ?>
        <script type="text/javascript">
            (function() {
                // Intersection Observer for viewport-based autoplay
                // Only plays videos when they're 50% visible, prevents scroll issues
                if (!('IntersectionObserver' in window)) {
                    return; // Graceful degradation for old browsers
                }

                // One observer per page: this script is printed with every gallery
                // that has a video (Log listings, Infinite Scroll pages).
                const videoObserver = window.tiagsspaceGalleryVideoObserver = window.tiagsspaceGalleryVideoObserver || new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        const container = entry.target;
                        const shouldAutoplay = container.getAttribute('data-should-autoplay') === 'true';
                        
                        if (!shouldAutoplay) return;

                        // Find Video.js player or native video element
                        const videoElement = container.querySelector('video');
                        if (!videoElement) return;

                        if (entry.isIntersecting) {
                            // Prevent browser scroll-to-video on autoplay
                            const scrollY = window.scrollY || window.pageYOffset;
                            
                            // Video entered viewport - play if muted
                            if (videoElement.muted || videoElement.volume === 0) {
                                videoElement.play().then(function() {
                                    // Restore scroll position immediately after play starts
                                    window.scrollTo({
                                        top: scrollY,
                                        behavior: 'instant'
                                    });
                                }).catch(function(err) {
                                    console.log('Autoplay prevented:', err);
                                });
                            }
                        } else {
                            // Video left viewport - pause to save resources
                            if (!videoElement.paused) {
                                videoElement.pause();
                            }
                        }
                    });
                }, {
                    threshold: 0.1, // Trigger when just 10% visible - earlier detection, smoother experience
                    rootMargin: '100px' // Start observing 100px before video enters viewport
                });

                // Wait for Video.js to initialize, then observe all video containers
                setTimeout(function() {
                    const videoContainers = document.querySelectorAll('.media-video[data-should-autoplay="true"]:not([data-video-observed])');
                    videoContainers.forEach(function(container) {
                        container.setAttribute('data-video-observed', '');
                        videoObserver.observe(container);
                    });
                }, 1000); // Give Video.js time to initialize
            })();
        </script><?php
    }
    // 3d
    if ($there_is_3d == true && tiagsspace_once( 'model-viewer-js' )) { ?>
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
    if (tiagsspace_gallery_edit_mode( $gallery_post_id ) ) {

        // Manual order images ?>
        <style>
            #gallery-<?php the_ID(); ?> .item { -webkit-user-select: none; -moz-user-select: none; user-select: none; }
            .sortable-fallback { transition: none !important; padding: 0 !important; opacity: 1 !important; }
        </style>
        <?php // Static files are served with a one-year cache: version both tags by filemtime. ?>
        <script src="<?php bloginfo('template_url'); ?>/library/js/Sortable-master/Sortable.js?v=<?php echo esc_attr( tiagsspace_asset_ver( '/library/js/Sortable-master/Sortable.js' ) ); ?>"></script>
        <script type="text/javascript">
            // As an Admin, I can sort the media elements on a gallery when I am previewing the post
            // documentation here: https://github.com/RubaXa/Sortable
            var el = document.getElementById('gallery-<?php the_ID(); ?>');

            // Prevent lightbox (Intense Images / Magnific Popup) from opening after a drag
            var wasDragging = false;
            el.addEventListener('click', function(e) {
                if (wasDragging) {
                    e.stopPropagation();
                    e.preventDefault();
                    wasDragging = false;
                }
            }, true);

            var sortable = Sortable.create(el, {
                dataIdAttr: 'attachmentId',
                forceFallback: true,
                fallbackOnBody: true,
                fallbackTolerance: 3,
                onStart: function(evt) {
                    wasDragging = true;
                    // Picking an image up makes it the keyboard-focused one,
                    // even if it is dropped back in the same slot (no onSort then).
                    if (typeof window.galleryKeyboardReorderFocus === 'function') {
                        window.galleryKeyboardReorderFocus(evt.item);
                    }
                },
                onUnchoose: function() {
                    // Reset flag after a short delay so the click event is caught first
                    setTimeout(function() { wasDragging = false; }, 50);
                },
                onSort: function(evt) {
                    console.log("Sorted happend.");
                    // Keep keyboard focus on the dragged item at its new index
                    // (DOM order is final when onSort fires).
                    if (typeof window.galleryKeyboardReorderFocus === 'function') {
                        window.galleryKeyboardReorderFocus(evt.item);
                    }
                    // Refresh Masonry layout after drag-reorder
                    var $grid = jQuery('#gallery-<?php the_ID(); ?>');
                    if ($grid.data('masonry')) {
                        $grid.masonry('reloadItems').masonry('layout');
                    }
                    // Auto-save the new order on every drop (debounced so a burst
                    // of drags coalesces into one save). Replaces the old
                    // "SAVE ORDER (S)" button.
                    if (typeof window.scheduleGalleryOrderSave === 'function') {
                        window.scheduleGalleryOrderSave();
                    }
                },
                onMove: function() { console.log("Move happend."); }
            });

        </script>

        <!-- Keyboard arrow reorder -->
        <script>
        window.galleryKeyboardReorderTarget = 'gallery-<?php the_ID(); ?>';
        window.galleryHideNonce = '<?php echo wp_create_nonce( 'gallery_hide_attachment' ); ?>';
        window.galleryAjaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
        // Lightbox mode, so a revealed hidden item can be re-bound to it.
        window.galleryLightbox = '<?php echo esc_js( $light_box ); ?>';
        </script>
        <script src="<?php bloginfo('template_url'); ?>/library/js/gallery-keyboard-reorder.js?v=<?php echo esc_attr( tiagsspace_asset_ver( '/library/js/gallery-keyboard-reorder.js' ) ); ?>"></script>

        <!-- Selection, toolbar (Delete / Select / Sort / Move to), keyboard selection -->
        <?php tiagsspace_gallery_edit_assets( $gallery_post_id, get_the_ID(), $light_box ); ?>

        <!-- CUSTOM FUNCTIONS -->
        <script type="text/javascript">

        // Custom functions

        // Save the gallery order (menu_order) via AJAX, one request per item.
        // Visible items are numbered in DOM order. Hidden items (off-grid pool)
        // are interleaved right after the visible item that preceded them at the
        // last save — the one with the largest previous attachmentOrder below
        // theirs — so a hidden image keeps its spot and U can put it back there
        // later. Hidden items with no visible predecessor go to the front.
        // Nothing is pushed to the end of the gallery anymore.
        function galleryItemsOf(parent) {
            return parent ? Array.prototype.filter.call(parent.children, function (c) {
                return c.classList && c.classList.contains('item');
            }) : [];
        }
        function galleryOrderOf(el) {
            return parseInt(el.getAttribute('attachmentOrder'), 10) || 0;
        }

        function orderAttachmentesOnWpDb() {
            var grid = document.getElementById('gallery-<?php the_ID(); ?>');
            var pool = document.getElementById('gallery-hidden-pool-<?php the_ID(); ?>');
            if (!grid) return;

            var visible = galleryItemsOf(grid);
            var hidden  = galleryItemsOf(pool).sort(function (a, b) {
                return galleryOrderOf(a) - galleryOrderOf(b);
            });

            // after[i] = hidden items anchored behind visible[i]; front = no anchor.
            var front = [];
            var after = visible.map(function () { return []; });
            hidden.forEach(function (h) {
                var ho = galleryOrderOf(h), anchor = -1, best = -Infinity;
                for (var i = 0; i < visible.length; i++) {
                    var vo = galleryOrderOf(visible[i]);
                    if (vo < ho && vo > best) { best = vo; anchor = i; }
                }
                (anchor < 0 ? front : after[anchor]).push(h);
            });

            var sequence = front.slice();
            visible.forEach(function (v, i) {
                sequence.push(v);
                sequence = sequence.concat(after[i]);
            });

            var requests = [];
            sequence.forEach(function (el, idx) {
                var attachmentId = el.getAttribute('attachmentId');
                var attachmentOrder = idx + 1;
                // The next save in this page anchors against these fresh numbers.
                el.setAttribute('attachmentOrder', attachmentOrder);
                if (!attachmentId) return;

                // This does the ajax request to change the menu_order value on the wp_db
                requests.push($.ajax({
                    url: example_ajax_obj.ajaxurl + '?t=' + new Date().getTime(), // Adds a timestamp to the request
                    data: {
                        'action': 'gallery_media_order_change_request',
                        'attachmentId' : attachmentId,
                        'attachmentOrder' : attachmentOrder,
                        '_nonce' : window.galleryHideNonce
                    },
                    cache: false,  // Disable caching explicitly
                    success:function(data) {
                        console.log(data);
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                    }
                }));
            });
            // Settles when every item's request has (a failed one resolves too).
            return $.when.apply($, requests.map(function (r) {
                return r.then(null, function () { return $.Deferred().resolve(); });
            }));
        }

        // Debounced wrapper around orderAttachmentesOnWpDb so drag auto-save and
        // reveal both coalesce rapid changes into a single save burst.
        var _galleryOrderSaveTimer = null;
        var _galleryOrderSaveRunning = null;
        window.scheduleGalleryOrderSave = function () {
            clearTimeout(_galleryOrderSaveTimer);
            _galleryOrderSaveTimer = setTimeout(function () {
                _galleryOrderSaveTimer = null;
                if (typeof window.orderAttachmentesOnWpDb === 'function') {
                    _galleryOrderSaveRunning = window.orderAttachmentesOnWpDb();
                }
            }, 400);
        };
        // Run a waiting save now and resolve once every save has landed (the
        // toolbar's Sort ▾ waits on it, so it reads the order the page shows).
        window.flushGalleryOrderSave = function () {
            if (_galleryOrderSaveTimer) {
                clearTimeout(_galleryOrderSaveTimer);
                _galleryOrderSaveTimer = null;
                _galleryOrderSaveRunning = window.orderAttachmentesOnWpDb();
            }
            return Promise.resolve(_galleryOrderSaveRunning);
        };

        // Shared tail of both reveal paths, once `node` sits in the grid:
        // persist the unhide, rebind Masonry / lightbox, focus it, save the order.
        function finishGalleryReveal(node) {
            var pool = document.getElementById('gallery-hidden-pool-<?php the_ID(); ?>');

            // Persist unhide: clear remove_from_default_gallery on the attachment.
            var attId = node.getAttribute('attachmentId');
            if (attId && window.galleryAjaxUrl && window.galleryHideNonce) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.galleryAjaxUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(
                    'action=gallery_toggle_hide_attachment' +
                    '&attachment_id=' + encodeURIComponent(attId) +
                    '&hide=0' +
                    '&_nonce=' + encodeURIComponent(window.galleryHideNonce)
                );
            }

            // Rebind Masonry + lightbox for the freshly inserted node.
            var $grid = jQuery('#gallery-<?php the_ID(); ?>');
            if ($grid.data('masonry')) {
                $grid.imagesLoaded(function () {
                    $grid.masonry('reloadItems').masonry('layout');
                });
            }
            // Magnific Popup is delegated on the container, so it needs no rebind.
            // Intense Images binds per element, so bind the new node's images.
            if (window.galleryLightbox === 'intense-images' && typeof Intense === 'function') {
                var intenseEls = node.querySelectorAll('.intense');
                if (intenseEls.length) { Intense(intenseEls); }
            }

            // The revealed item becomes the keyboard's focused + tracked item,
            // so a following arrow / H / N acts on it.
            if (typeof window.galleryKeyboardReorderFocus === 'function') {
                window.galleryKeyboardReorderFocus(node);
            } else if (typeof window.galleryKeyboardReorderReset === 'function') {
                window.galleryKeyboardReorderReset();
            }

            // Persist the new order so the revealed item keeps its position.
            if (typeof window.scheduleGalleryOrderSave === 'function') {
                window.scheduleGalleryOrderSave();
            }

            // Hide the reveal buttons once the pool is empty.
            if (!pool || !pool.querySelector('.item')) {
                document.querySelectorAll('.nexthidden').forEach(function (b) { b.style.display = 'none'; });
            }
        }

        // N: reveal the next hidden attachment (first in the off-grid pool) and
        // drop it into the grid right after `refItem` (or append). Works for
        // items hidden in ANY session, unlike the in-memory Undo (U) stack.
        window.revealGalleryHiddenAfter = function (refItem) {
            var grid = document.getElementById('gallery-<?php the_ID(); ?>');
            var pool = document.getElementById('gallery-hidden-pool-<?php the_ID(); ?>');
            if (!grid || !pool) return;

            var node = pool.querySelector('.item');
            if (!node) {
                // Nothing left to reveal.
                document.querySelectorAll('.nexthidden').forEach(function (b) { b.style.display = 'none'; });
                return;
            }

            if (refItem && refItem.parentNode === grid) {
                refItem.insertAdjacentElement('afterend', node);
            } else {
                grid.appendChild(node);
            }

            finishGalleryReveal(node);
        };

        // U with an empty undo stack: put the most recently hidden image (the
        // pool item with the highest saved menu_order) back where menu_order
        // says it was — right after the visible item with the largest
        // attachmentOrder below its own, or first in the grid when there is
        // none. Returns false when the pool is empty.
        window.revealGalleryHiddenRestore = function () {
            var grid = document.getElementById('gallery-<?php the_ID(); ?>');
            var pool = document.getElementById('gallery-hidden-pool-<?php the_ID(); ?>');
            if (!grid || !pool) return false;

            var node = null;
            galleryItemsOf(pool).forEach(function (h) {
                if (!node || galleryOrderOf(h) > galleryOrderOf(node)) node = h;
            });
            if (!node) return false;

            var order = galleryOrderOf(node), anchor = null, best = -Infinity;
            var visible = galleryItemsOf(grid);
            visible.forEach(function (v) {
                var vo = galleryOrderOf(v);
                if (vo < order && vo > best) { best = vo; anchor = v; }
            });

            if (anchor) {
                anchor.insertAdjacentElement('afterend', node);
            } else if (visible.length) {
                grid.insertBefore(node, visible[0]); // after the Masonry sizer
            } else {
                grid.appendChild(node);
            }

            finishGalleryReveal(node);
            return true;
        };

        // Per-image "NEXT HIDDEN" button handler.
        function revealNextHidden(btn) {
            var grid = document.getElementById('gallery-<?php the_ID(); ?>');
            var item = btn;
            while (item && item !== grid) {
                if (item.classList && item.classList.contains('item')) break;
                item = item.parentNode;
            }
            if (!item || item === grid) return;
            window.revealGalleryHiddenAfter(item);
        }

        // Increase Attachment Grid Size
        function atachementGridSizeChange(attachmentID, changeSize) {

                // This does the ajax request to change the menu_order value on the wp_db
                $.ajax({
                    url: example_ajax_obj.ajaxurl + '?t=' + new Date().getTime(), // Adds a timestamp to the request
                    data: {
                        'action': 'change_attachment_field_diferent_size_on_gallery',
                        'attachmentID' : attachmentID,
                        'changeSize' : changeSize,
                        '_nonce' : window.galleryHideNonce
                    },
                    cache: false,  // Disable caching explicitly
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
                    'incrementalValue' : incrementalValue,
                    '_nonce' : window.galleryHideNonce

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



// _______________________________
// NOTES:

// via http://code.tutsplus.com/articles/creating-your-own-image-gallery-page-template-in-wordpress--wp-23721
// more info with video integration an so on:  http://codex.wordpress.org/Function_Reference/get_children
// for video use MediaElement.js at http://mediaelementjs.com/
// probably alredy native: http://make.wordpress.org/core/2013/04/08/audio-video-support-in-core/

// this one makes the houver tittle appear:
// title="<?php echo $attachmen->post_title;
?>
