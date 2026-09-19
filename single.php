 <?php get_header(); ?>

<div class="single-wrapper first-block post-block"
<?php
// Background Image for 4k Lento et all
$background_image = get_field('background_image');
if ($background_image) {
    echo 'style="
    /* Location of the image */
      background-image: url('.esc_url($background_image['url']).');

      /* Background image is centered vertically and horizontally at all times */
      background-position: center center;

      /* Background image doesnt tile */
      background-repeat: no-repeat;

      /* Background image is fixed in the viewport so that it doesnt move when
         the contents height is greater than the images height
      background-attachment: fixed; */

      /* This is what makes the background image rescale based
         on the containers size */
      background-size: cover;"';
}?>>

<article    id="?p=<?php the_ID(); ?>"
			<?php post_class( array('clearfix', '')); ?>
			role="article" itemscope itemtype="http://schema.org/BlogPosting">

	<?PHP
	// Video
	if (get_field('self_host_film')) {
		get_template_part( 'template-parts/entry', 'video-player' );
	}

	// if Gallery is Activated
	if (get_field('deactivate_gallery') == false) {
		// Gallery
		if (get_field('horizontal_gallery')) {
		get_template_part( 'template-parts/entry', 'gallery-horizontal' );
		} else {
			get_template_part( 'template-parts/entry', 'gallery' );
		}
	} // if gallery is not deactivated

	?>

	<div class="container single-content">

		<div class="clearfix row">

			<div id="main" class="<?php content_wrap() ?> clearfix" role="main">

				<?php if (have_posts()) : while (have_posts()) : the_post();?>

					<?PHP // Content
					get_template_part( 'template-parts/entry', 'body' );
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


<?php 

// Do not show the next & previous posts & related posts if disabled in ACF - to share films and content for festival submissions
if (! get_field('disable_previouse_next_&_related_posts')) { ?>

    <div class="single-navigation container-fluid side-padding d-none d-sm-block">
        <div class="row justify-content-between">

            <?php // PAGINATION links — plain "Next" / "Previous" labels (not the adjacent post's title) ?>

    	        <nav class="nav-previous col-24">
    	        	<span><?php previous_post_link('%link', 'Previous'); ?></span>
    	        </nav>
    	        <nav class="nav-next col-24">
    	            <span><?php next_post_link('%link', 'Next'); ?></span>
    	        </nav>

        </div>
    </div>
</div>

<!-- Related Posts -->

    <div class="container-fluid side-padding yarpp-related-header">
        <div class="row justify-content-between">
            <nav class="nav-next col-24">	
                <h3>Re(ve)lations</h3>
            </nav>
        </div>
    </div>
    <?php  yarpp_related(); ?>
<?php } ?>



<?php // get Footer

get_footer(); ?>
