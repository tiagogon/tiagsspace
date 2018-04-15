<?php 
/*

Front posts for No Destruction Nots

*/
//define variables
$block_type = 'log';
 ?>

	<div id="log-block" class="front-block series-block">
		
		<div class="block-header">
			<h1>// No Destruction</h1>
		</div>

		<?php // Get No Destruction Nots ?>
		<div class="container-fluid block-content">


			<div class="clearfix row no-pad">

				<?php 
                $args = array(
                    'post_type' => 'log',
                    'posts_per_page' =>6,
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



                // MUCH MORE FUN HERE!



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

			        <div class="col-sm-6 col-md-3 <?php echo "item".$count.$hidden; ?>">

			        	<a href="<?php echo get_permalink(); ?>">

			        			<div class="thumbnail">

			        		        <?php the_post_thumbnail( 'HD-4' )?>

			        		        <div class="caption">
			        		            <h3><?php the_title(); ?></h3>
			        		        </div>

			        		    </div>

			        	    </a>
			        </div>

		        <?php 
		        // finish the ride
		        endwhile;

		        // restores the $wp_query and global post data to the original main query.
		        wp_reset_query();
		        ?>
			</div>

		</div><?php // container-fluid ?>

		<div class="block-footer">
			
			<div class="block-footer">
			  	<a role="button" class="btn btn-default btn-lg" href="<?php echo get_post_type_archive_link( $block_type ); ?>">View more...</a>
			</div>
		
		</div>

	</div>