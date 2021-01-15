<?php
/*
Template Name: feeds
*/
?>

<?php get_header(); ?>

	<div class="single-wrapper">

		<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">

			<?PHP // Gallery
			get_template_part( 'template-parts/single', 'gallery' );
			?>

			<?PHP // Map
			if (is_singular( 'sidewalk' ) && get_field('location')) {
				get_template_part( 'template-parts/single', 'map' );
			}?>

			<div class="container single-content">
			    
			    <div class="clearfix row">
			    
			        <div id="main" class="<?php content_wrap() ?> clearfix" role="main">

			            <?php if (have_posts()) : while (have_posts()) : the_post();?>

							<?PHP // Content
							get_template_part( 'template-parts/single', 'content' );
							?>

			            <?php endwhile; ?>          
			            
			            <?php else : ?>

			                <header>
			                    <h1><?php _e("Not Found", "wpbootstrap"); ?></h1>
			                </header>
			                <section class="post_content">
			                    <p><?php _e("Sorry, but the requested resource was not found on this site.", "wpbootstrap"); ?></p>
			                </section>
			                <footer>
			                </footer>
			            
			            <?php endif; ?>
			    
			        </div> <!-- end #main -->
			  
			        <?php // get_sidebar(); // sidebar 1 ?>
			  
			    </div> <!-- end #content -->

			</div> <!-- end #container -->

		</article>
	
	</div>
		
<?php get_footer(); ?>