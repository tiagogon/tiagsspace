<?php get_header(); ?>

	<div id="archive-log" class="archive-wrapper first-block" role="main">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">

				<?PHP // Gallery
				if (!get_field('horizontal_gallery')) {
					get_template_part( 'template-parts/single', 'gallery' );
				} else {
					get_template_part( 'template-parts/single', 'gallery-horizontal' );
				}
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

		?>
	</div>

	<?php
	// if exists links
	if ($prev_link || $next_link) { ?>

			<div class="container-fluid pagination-container">
							<nav class="archive-navigation col-xs-48">
									<span class="nav-next"><?php previous_posts_link( 'Future' ); ?></span> <span class="nav-previous"><?php next_posts_link( 'Past' ); ?></span>
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

	<script type="text/javascript"  src="<?php bloginfo('template_url'); ?>/library/js/infinite-scroll/infinite-scroll.pkgd.min.js"></script>

	<script type="text/javascript">

			let $container = $('.archive-wrapper').infiniteScroll({
			  // options...
				path: 'page/{{#}}/',
				append: '.type-log',
				//outlayer: msnry,
				history: false,
			  button: '.view-more-button',
			});

			let $viewMoreButton = $('.view-more-button');

			// get Infinite Scroll instance
			let infScroll = $container.data('infiniteScroll');

			$container.on( 'load.infiniteScroll', onPageLoad );

			function onPageLoad() {
			  if ( infScroll.loadCount == 2 ) {
			    // after 2nd page loaded
			    // disable loading on scroll
			    $container.infiniteScroll( 'option', {
			      loadOnScroll: false,
			    });
			    // show button
			    $viewMoreButton.show();
			    // remove event listener
			    $container.off( 'load.infiniteScroll', onPageLoad );
			  }
			}

			// Safari not loading srset issue
			// https://github.com/metafizzy/infinite-scroll/issues/770
			$container.on( 'append.infiniteScroll', function( event, response, path, items ) {
				$( items ).find('img[srcset]').each( function( i, img ) {
					img.outerHTML = img.outerHTML;
				});
			});

			// jQuery VIDEO fix
			// https://github.com/metafizzy/infinite-scroll/issues/926
			$container.on( 'append.infiniteScroll', function( event, response, path, items ) {
				$(items).find('video').each((i, video) => video.play())
			});


	</script>

<?php get_footer(); ?>
