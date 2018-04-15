<?php
/*
Loop for inside Index of posts for Home and Archives
*/ ?>


    <?php
    //increment the variable by 1 each time the loop executes
    $count++;
    $hidden = "";

    // Hide more than 4 products on mobile devices
    if ($count > 4) {
        $hidden = " hidden-xs";
    }

    // Get Custom Post slug and Info
    $post_type = get_post_type( $post->ID );
    $obj = get_post_type_object( $post_type ); 

    // Get Image info
    $thumbnail_size         = "HD-3";
    $image_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $thumbnail_size);
    $image_thumb_url        = $image_thumb_attributes[0];

    ?>
    
    <li id="post-<?php the_ID(); ?>" class="col-xs-12 col-sm-6 col-md-4 col-lg-4<?php echo $post_type; ?>-thumb <?php //if ($count == 1) { echo "item-sizer";} else { echo "item";} echo " item".$count; ?> item blllllaaa item-post" <?php post_class('clearfix'); ?> role="article">
        
        <?php if (!($post_type == "log")) { ?>
        
        <figure>
            
            <a href="<?php echo get_permalink(); ?>" rel="bookmark">

                <img    src="<?php echo $image_thumb_url; ?>" 
                        alt="<?php the_title(); ?>"
                        class="lazy attachment-post-thumbnail imgcenter"/>
                
                <figcaption>booooo

                    <?php // If is featuring series
                    if ($post_type == "featuring") {
                        // Get the terms of with!
                        $withs = get_the_terms( $post->ID, 'with' );
                        $count = count( $withs );
                        $with_list = "";
                        if ( $withs && ! is_wp_error( $withs ) ) { 
                            $i = 0;
                            foreach ( $withs as $with ) {
                                $i++;
                                if ($i==1) {
                                    $separator = " ";
                                } elseif($count != $i) {
                                    $separator = ", ";
                                } else{
                                    $separator = " and ";
                                }
                                $with_list = $with_list.$separator.$with->name;
                            }?>
                        <p class="series">Featuring<span><?php echo $with_list;?></span></p>
                    <?php }

                    // If it is any other Series
                    } elseif (!($post_type == "post")) {?>
                        <div class="series">bbbbb<?php echo $obj->labels->name;?></div>
                    <?php } else {?>
                        <div class="series">undefined</div>
                    <?php }  ?>

                    <h2><?php the_title(); ?></h2>
                    
                    <p class="meta-info" ><?php taxonomy_list('cities', '&', 1); ?>boom</p>

                    <!-- <div class="btn btn-primary btn-lg" role="button">View more</div> -->

                </figcaption>

            </a>

        </figure>

        <?php } elseif ($post_type == "log") { ?>
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