<?php
/*
Template Name: 3D
*/
?>

<?php get_header(); ?>

	<div class="single-wrapper first-block">

		<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">

			<?PHP // Gallery
			get_template_part( 'template-parts/single', 'gallery' );
			?>

			<?PHP // Map
			if (is_singular( 'sidewalk' ) && get_field('location')) {
				get_template_part( 'template-parts/single', 'map' );
			}?>


			<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>


			<div id="canvas-container" class="container-fuild single-content">
				<model-viewer src="https://modelviewer.dev/shared-assets/models/NeilArmstrong.glb" ar ar-modes="webxr scene-viewer quick-look" camera-controls poster="poster.webp" shadow-intensity="1" exposure="0.94" auto-rotate>
				    <div class="progress-bar hide" slot="progress-bar">
				        <div class="update-bar"></div>
				    </div>
				    <button slot="ar-button" id="ar-button">
				        View in your space
				    </button>
				    <div id="ar-prompt">
				        <img src="https://modelviewer.dev/shared-assets/icons/hand.png">
				    </div>
				</model-viewer>

			</div>



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
