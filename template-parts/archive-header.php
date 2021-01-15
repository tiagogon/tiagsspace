<?php 
/*

Archive Header for archive templates

*/ ?>

	<div id="page-header">

		<div class="container">
			
			<div class="clearfix row">
			
				<div class="col-sm-12 clearfix">
				
					<?php if (is_category()) { ?>
						<h1 class="archive_title">
							<?php single_cat_title(); ?>
						</h1>
					<?php } elseif (is_post_type_archive( "featuring" )) { ?> 
						<h1 class="archive_title">
							<?php  the_archive_title(); ?>
						</h1>
						<span class="description">
						</span>
					<?php }  elseif (is_post_type_archive( "emulsion" )) { ?> 
						<h1 class="archive_title">
							<?php  the_archive_title(); ?>
						</h1>
						<span class="description">
							a suspension of solid particles – 35mm analog photography.
						</span>
					<?php }  elseif (is_post_type_archive( "sidewalk" )) { ?> 
						<h1 class="archive_title">
							<?php  the_archive_title(); ?>
						</h1>
						<span class="description">
							People and their streerts.
						</span>
					<?php }  elseif (is_post_type_archive( "dusk" )) { ?> 
						<h1 class="archive_title">
							<?php  the_archive_title(); ?>
						</h1>
						<span class="description">
						</span>
						
					<?php }  elseif (is_post_type_archive( "hyper" )) { ?> 
						<h1 class="archive_title">
							<?php  the_archive_title(); ?>
						</h1>
						<span class="description">
							<?php echo number_of_days_in_the_post_type("hyper")." days in ".number_of_images_in_the_post_type("hyper")." frames"; ?>
						</span>
						
					<?php } elseif (is_post_type_archive( "log" )) { ?> 
						<h1 class="archive_title">
							<?php  the_archive_title(); ?>
						</h1>
						<span class="description">
							tiago's visual diary.
						</span>
					<?php } elseif (is_tax( 'with' ) ) { ?> 
						<h1 class="archive_title">
							With <?php single_tag_title(); ?>
						</h1>
					<?php }  elseif (is_tag()) { ?> 
						<h1 class="archive_title">
							<?php single_tag_title(); ?> // tag
						</h1>
						<span class="description">
							<?php  the_archive_description(); ?> 
						</span>
						


					<?php } elseif (is_author()) { ?>
						<h1 class="archive_title">
							Posts By: <?php get_the_author_meta('display_name'); ?>
						</h1>
					<?php } elseif (is_day()) { ?>
						<h1 class="archive_title">
							Daily Archives: <?php the_time('l, F j, Y'); ?>
						</h1>
					<?php } elseif (is_month()) { ?>
					    <h1 class="archive_title">
					    	Monthly Archives: <?php the_time('F Y'); ?>
					    </h1>
					<?php } elseif (is_year()) { ?>
					    <h1 class="archive_title">
					    	Yearly Archives: <?php the_time('Y'); ?>
					    </h1>
					<?php } ?>
		
				</div>
		
			</div>
		
		</div>

	</div>