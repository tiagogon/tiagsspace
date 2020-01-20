<?php
/*

Front posts for Emulsion Series

*/ ?>

	<div id="emulsion-block" class="front-block series-block">

		<div class="block-header">
			<h1>Emulsion Series</h1>
		</div>

		<?php // Get Dusk Series posts ?>
		<div class="container-fluid block-content">

			<div class="clearfix row no-pad">

				<?php
                $args = array(
                    'post_type' => 'emulsion',
                    'posts_per_page' => 4,
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

                // set up the counter variable as 0
                $count = 0;

                // start the ride
                $my_query = new WP_Query( $args );
			    while ($my_query->have_posts()) : $my_query->the_post();

			        //increment the variable by 1 each time the loop executes
			    	$count++;
			    	$hidden = "";

			    	// Hide more than 4 products on mobile devices
			    	if ($count > 4) {
			    		$hidden = " hidden-xs";
			    	}  ?>

			        <figure class="col-sm-24 col-md-24 thumb-series thumb-emulsion <?php echo "item".$count.$hidden; ?>">

						<a href="<?php echo get_permalink(); ?>">

			        	<?php
			        	$thumbnail_size 		= "HD-2";
						$image_thumb_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $thumbnail_size);
						$image_thumb_url 		= $image_thumb_attributes[0];?>

						<img 	data-original="<?php echo $image_thumb_url; ?>"
				            	alt="<?php the_title(); ?>"
				            	class="lazy attachment-post-thumbnail"/>

			        	<figcaption>
				        	<h2>
				        		<?php the_title(); ?>
				        	</h2>
							<p>
								Lily likes to play with crayons and pencils
							</p>
						</figcaption>
			            </a>
			        </figure>

		        <?php
		        // finish the ride
		        endwhile;

		        // restores the $wp_query and global post data to the original main query.
		        wp_reset_query();
		        ?>
			</div>
		</div><?php // container-fluid ?>

		<div class="block-footer">

			  <button type="button" class="btn btn-primary btn-lg">View more from Hyper Series</button>
			  <button type="button" class="btn btn-default btn-lg">Whatever</button>

		</div>

	</div>
