<?php
/*

Front posts for Hyper Series

*/

//define variables
$block_type = 'hyper';

?>
	<div class="container-fluid series-block front-block front-hyper" id="index">

        <div id="main" role="main" >

            <ul id="thumb-container" class="clearfix row no-pad">

                <?php

                $grid = 'col-xs-48 col-sm-24 col-md-16 col-lg-16';

                $args = array(
                    'post_type' => 'hyper',
                    'posts_per_page' => 3,
                    // 'meta_query' => array(
                    // 	'relation' => 'OR',
                    //     array(
                    //         'key' => 'product_featured_on_the_front_page',
                    //         'value' => 1
                    //     ),
                    //     array(
                    //     	'key' => 'product_featured_on_the_top_of_the_front_page',
                    //     	'value' => 0
                    //     )
                    // )
                );

                // start the ride
                $my_query = new WP_Query( $args );
			    while ($my_query->have_posts()) : $my_query->the_post();

                    // Get Custom Post slug and Info
                    $post_type = get_post_type( $post->ID );
                    $obj = get_post_type_object( $post_type );

                    // Get Image info
                    if ( has_post_thumbnail() ) {
                        $thumbnail_size         = "small";
                        $image_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $thumbnail_size);
                        $image_thumb_url        = $image_thumb_attributes[0];

                        // Imgcontainer calculations -- intrinsic ratio
                        $intrinsic_ratio = $image_thumb_attributes[2] * 100 / $image_thumb_attributes[1];
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
                        $image_thumb_HD_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "HD");
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

                        $image_sizes = "(min-width: 1240px) ".$size_lg.",(min-width: 992px) ".$size_md.",(min-width: 768px) ".$size_sm.",".$size_xs;
                    ?>

                    <li id="post-<?php the_ID(); ?>" class="<?php echo $grid; ?> <?php echo $post_type; ?>-thumb item item<?php echo $count; ?> item-post" <?php post_class('clearfix'); ?> role="article">

                        <?php if (!($post_type == "log")) { ?>

                        <figure>

                            <a href="<?php echo get_permalink(); ?>" rel="bookmark">

                                <div class="imgcontainer" style="position: relative; padding-bottom: <?php echo $intrinsic_ratio; ?>%; height: 0; overflow: hidden; max-width: 100%;">

                                    <img    srcset="<?php echo $image_srcset; ?>"
                                                sizes="<?php echo $image_sizes; ?>"
                                                alt="<<?php the_title(); ?>"
                                                class=" <?php echo $max_height; ?>        <?php echo $intense; ?>"/>

                                </div>

                                <figcaption>

                                    <?php
                                    // If is Hyper series
                                    if ($post_type == "hyper") {
                                        $series_number = number_of_the_post($post->ID);?>
                                        <p class="series">#<?php echo $series_number;?> // <?php the_time('M Y') ?></p>
                                    <?php

                                    // If it is any other Series
                                    } elseif (!($post_type == "post")) {?>
                                        <div class="series"><?php echo $obj->labels->name;?></div>
                                    <?php } else {?>
                                        <div class="series"><?php the_time('M Y') ?></div>
                                    <?php }  ?>

                                    <h2><?php the_title();?></h2>
                                </figcaption>
                            </a>
                        </figure>

                        <?php } else { ?>
                        <figure>

                            <a href="<?php echo get_permalink(); ?>" rel="bookmark">

                                <figcaption>

                                    <p class="series"><?php echo $obj->labels->name;?></p>

                                    <h2><?php the_title(); ?></h2>

                                </figcaption>

                            </a>

                        </figure>
                        <?php } ?>

                    </li>

                <?php endwhile;
                // restores the $wp_query and global post data to the original main query.
		        wp_reset_query();
                ?>

            </ul>


        </div> <!-- end #main -->

        <nav class="archive-navigation col-xs-48">
            <span class="more-sets"> <a href="<?php echo get_post_type_archive_link( 'hyper'); ?>">Hyper Series ></a> </span>
        </nav>
	</div>
