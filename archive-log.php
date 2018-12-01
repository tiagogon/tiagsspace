<?php get_header(); ?>

	<div id="archive-log" class="archive-wrapper" role="main">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">

				<?PHP // Gallery
				get_template_part( 'template-parts/single', 'gallery' );
				?>

				<div class="container single-content">
			    	<div class="clearfix row">
			    		<div class="<?php content_wrap() ?> clearfix" role="main">
							<?PHP // Content
							get_template_part( 'template-parts/single', 'content' );?>
						</div>
					</div>
				</div>


				<section class="post">


			</article>

		<?php endwhile; ?>

		<?php // PAGINATION

		// Check if there are previouse or next pages
		$prev_link = get_previous_posts_link(__('&laquo; Older Entries'));
		$next_link = get_next_posts_link(__('Newer Entries &raquo;'));

		// if exists links
		if ($prev_link || $next_link) { ?>

		    <div class="container-fluid pagination-container">
		            <nav class="archive-navigation col-xs-12">
		                <span class="nav-next"><?php previous_posts_link( '< Future' ); ?></span> <span class="nav-previous"><?php next_posts_link( 'Past >' ); ?></span>
		            </nav>
		    </div>
		<?php } ?>


		<?php else : ?>

		<article id="post-not-found">
		    <header>
		    	<h1><?php _e("No Posts Yet", "wpbootstrap"); ?></h1>
		    </header>
		    <section class="post_content">
		    	<p><?php _e("Sorry, What you were looking for is not here.", "wpbootstrap"); ?></p>
		    </section>
		    <footer>
		    </footer>
		</article>

		<?php endif; ?>
	</div>

	<?php //get_template_part( 'template-parts/archive', 'header' ); ?>

<?php get_footer(); ?>
