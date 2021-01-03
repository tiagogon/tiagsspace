<?php get_header(); ?>
	
	
	<?php
	// If is home first page, get slider! 
	// GOODBYE! I MIGHT USE YOU LATER
		// if(is_front_page() && !is_paged() ) {

		// 	get_template_part( 'template-parts/front', 'slider' );

		// } 
	?>

	<?php get_template_part( 'template-parts/content', 'index' ); ?>

<?php get_footer(); ?>