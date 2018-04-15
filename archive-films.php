<?php get_header(); ?>
	
	<?php // SLIDER -- if it's 1st page:
		// if(!is_paged() ) {
		// 	get_template_part( 'template-parts/front', 'slider-films' );
		// } 
	?>

	<div class="archive-wrapper">

		<?php get_template_part( 'template-parts/content', 'index' ); ?>

	</div>

<?php get_footer(); ?>