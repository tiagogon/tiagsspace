<?php get_header(); ?>

	<div id="archive-log" class="archive-wrapper first-block" role="main">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">

				<?PHP // Gallery
				if (!get_field('horizontal_gallery')) {
					get_template_part( 'template-parts/entry', 'gallery' );
				} else {
					get_template_part( 'template-parts/entry', 'gallery-horizontal' );
				}
				?>



				<div class="container single-content">
			    	<div class="clearfix row">
			    		<div class="<?php content_wrap() ?> clearfix" role="main">
							<?PHP // Content
						get_template_part( 'template-parts/entry', 'body' ); ?>
						</div>
					</div>
				</div>


			</article>

		<?php endwhile; ?>

		<?php // PAGINATION

		// Check if there are previouse or next pages
		$prev_link = get_previous_posts_link(__('&laquo; Older Entries'));
		$next_link = get_next_posts_link(__('Newer Entries &raquo;'));

		?>
	</div>

	<?php
	// if exists links
	if ($prev_link || $next_link) { ?>

			<div class="container-fluid pagination-container">
							<nav class="archive-navigation col-xs-48">
									<span class="nav-next"><?php previous_posts_link( 'Previous' ); ?></span> <span class="nav-previous"><?php next_posts_link( 'More' ); ?></span>
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

	<?php endif;  ?>

	<script type="text/javascript" src="<?php echo esc_url( get_template_directory_uri() . '/library/js/infinite-scroll/infinite-scroll.pkgd.min.js?v=' . tiagsspace_asset_ver( '/library/js/infinite-scroll/infinite-scroll.pkgd.min.js' ) ); ?>"></script>

	<script type="text/javascript">
		(function ($) {
			// Each appended entry carries its own gallery scripts, which Infinite Scroll
			// runs: Masonry lays the gallery out at once (library/js/masonry-grid.js) and
			// the gallery video observer picks up its autoplay videos (entry-gallery.php).
			// No `outlayer`, so no imagesLoaded wait before a page is inserted.
			var $container = $('.archive-wrapper').infiniteScroll({
				path: 'page/{{#}}/',
				append: '.type-log',
				history: false,
				button: '.view-more-button'
			});

			// Safari srcset (metafizzy/infinite-scroll#770): re-parse once
			$container.on('append.infiniteScroll', function (event, body, path, items) {
				$(items).find('img[srcset]').each(function (i, img) {
					img.outerHTML = img.outerHTML;
				});
			});
		})(jQuery);
	</script>

<?php get_footer(); ?>
