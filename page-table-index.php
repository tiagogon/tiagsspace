<?php
/*
Template Name: Table Index
*/
?>

<?php get_header(); ?>

	<div class="first-block container-fluid side-padding">

		<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">

			<?php
			// Page title / intro content (from the WP page editor)
			if (have_posts()) : while (have_posts()) : the_post();
				if (get_the_content()) : ?>
					<div class="table-index-intro">
						<?php the_content(); ?>
					</div>
				<?php endif;
			endwhile; endif;

			// Load the table
			get_template_part( 'template-parts/archive', 'table' );
			?>

		</article>

	</div>

<?php get_footer(); ?>
