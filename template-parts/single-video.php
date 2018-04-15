<?php
/*
Gallery template for single pages
*/

?>

<div class="container-fluid container-video">
    
    <div id="video-player" class="embed-container" itemprop="video" itemscope itemtype="http://schema.org/VideoObject">

        <?php 

        // Set autoplay
        // via https://www.advancedcustomfields.com/resources/oembed/

        // get iframe HTML
        $iframe = get_field('video_embed');


        // use preg_match to find iframe src
        preg_match('/src="(.+?)"/', $iframe, $matches);
        $src = $matches[1];


        // add extra params to iframe src
        $params = array(
            'autoplay'    => 1,
        );

        $new_src = add_query_arg($params, $src);

        $iframe = str_replace($src, $new_src, $iframe);


        // add extra attributes to iframe html
            // $attributes = 'frameborder="0"';

            // $iframe = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $iframe);


        // echo $iframe
        echo $iframe;

        the_field(''); ?>

    </div>
    
    <?php // Make it resposive ?>
    <style>
        .embed-container { 
            position: relative; 
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
            height: auto;
            margin-bottom: 20px;
        } 

        .embed-container iframe,
        .embed-container object,
        .embed-container embed { 
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>


    <!-- change captions style  -->

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('iframe').contents().find('.player .captions span').css({'font-size: 0.9em !important;
                  color: #ffeb3b!important;
                  font-family: "Raleway","Helvetica Neue", Helvetica, Arial, sans-serif;
                  line-height: 1.3em;
                  margin-bottom: 0.8em;
                  background: none;
                  border-radius: 0px;
                  padding: 0.2em .3em .3em;'});
        });
    </script>

</div>

                
