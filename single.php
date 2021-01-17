 <?php get_header(); ?>

<div class="single-wrapper">

	<article    id="?p=<?php the_ID(); ?>"
                <?php post_class( array('clearfix', 'first-block')); ?>
                role="article" itemscope itemtype="http://schema.org/BlogPosting"

                <?php
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
                }?> >

		<?PHP
		// Video
		if (get_field('video_embed')) {
			get_template_part( 'template-parts/single', 'video' );
		}

		// Gallery
		if (!get_field('horizontal_gallery')) {
			get_template_part( 'template-parts/single', 'gallery' );
		} else {
			get_template_part( 'template-parts/single', 'gallery-horizontal' );
		}
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

<?php // PAGINATION links

$post_type = get_post_type($post->ID);

if ($post_type == "hyper" OR $post_type == "4k-lento") {

    //number
	$next_number = number_of_the_post($post->ID) + 1;
	$previous_number = number_of_the_post($post->ID) - 1;

    //prefix
    if ($post_type == "hyper") {
        $prefix = "H";
    }
    if ($post_type == "4k-lento") {
        $prefix = "4KL";
    }

    // final sring
    $next_string = $prefix.sprintf("%02d", $next_number);
	$previous_string = $prefix.sprintf("%02d", $previous_number);
	?>

	<div class="single-navigation container-fluid side-padding">
		<div class="row justify-content-between">
	        <nav class="nav-next col-24">
	            <span><?php next_post_link('%link', '< '.$next_string); ?></span>
	        </nav>
	        <nav class="nav-previous col-24">
	        	<span><?php previous_post_link('%link', $previous_string.' >'); ?></span>
	        </nav>
        </div>
	</div>

<?php } else { ?>

	<div class="single-navigation container-fluid side-padding">
		<div class="row justify-content-between">
	        <nav class="nav-next col-24">
	            <span><?php next_post_link('%link', '< Future'); ?></span>
	        </nav>
	        <nav class="nav-previous col-24">
	        	<span><?php previous_post_link('%link', 'Past >'); ?></span>
	        </nav>
		</div>
	</div>

<?php } ?>

<?php // get related posts

if( !(is_singular( 'log' ))) {
	related_posts();
} ?>

<?php // get Footer

get_footer(); ?>
