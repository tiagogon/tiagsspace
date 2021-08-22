		<?php get_template_part( 'template-parts/front', 'social' ); ?>

	 	<div id="footer-block">

	 		<div class="container">

				<footer role="contentinfo">

					<div id="inner-footer" class="clearfix row">

						<div class="attribution <?php content_wrap() ?>">

							<p>
								 Hello, World! My name is <a xmlns:cc="http://creativecommons.org/ns#" href="https://tiags.space" property="cc:attributionName" rel="cc:attributionURL">Tiago</a> and this is my digital archive published under an <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/4.0/"> attribution-noncommercial-noderivatives</a> license. You can reach me via mail@tiags.space.
								<br>
								<br>
								<a href="https://trouble.place"><span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">https://tiags.space</span></a>/#<?php echo count_media_files_in_published_posts(); ?> © 2010-<?php echo date("Y"); ?>
							</p>

						</div>

					</div> <!-- end #inner-footer -->

				</footer> <!-- end footer -->

			</div>

		</div>

		<!--[if lt IE 7 ]>
				<script src="//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js"></script>
				<script>window.attachEvent('onload',function(){CFInstall.check({mode:'overlay'})})</script>
		<![endif]-->

		<?php wp_footer(); // js scripts are inserted using this function ?>

		<nav id="my-menu">
			<ul>

				<li class="">
					<a title="<?php echo get_bloginfo('description'); ?>" href="<?php echo home_url(); ?>" class="<?php if (is_home()) { echo "active";} ?>">All</a>
				</li>

				<li>
					<span>Series</span>
					<ul>

						<li>
							<a href="<?php echo get_post_type_archive_link( 'hyper'); ?>" class="<?php if ( is_post_type_archive('hyper')) { echo "active";} if (is_singular( 'hyper' ) ) { echo " belongs";} ?>">Hyper</a>
						</li>

					   <li>
						   <a href="<?php echo get_post_type_archive_link( 'dusk'); ?>" class="<?php if ( is_post_type_archive('dusk')) { echo "active";} if (is_singular( 'dusk' )) { echo " belongs";} ?>">Dusk</a>
					   </li>
		   				<li>
		   					<a href="<?php echo get_post_type_archive_link( 'films'); ?>" class="<?php if (is_singular( 'films' ) OR is_post_type_archive('films')) { echo "active";} if (is_singular( 'films' )) { echo "active";} if (is_singular( 'films' )) { echo " belongs";} ?>">Films</a>
		   				</li>

					   <li>
						   <span>Discontinued</span>
						   <ul>
							   <li>
								   <a href="<?php echo get_post_type_archive_link( 'emulsion'); ?>" class="<?php if (is_post_type_archive('emulsion')) { echo "active";} if (is_singular( 'emulsion' )) { echo " belongs";} ?>">Emulsion 2011-2018</a>
							   </li>
							   <li>
								   <a href="<?php echo get_post_type_archive_link( 'cityburns'); ?>" class="<?php if (is_post_type_archive('cityburns')) { echo "active";} if (is_singular( 'cityburns' )) { echo " belongs";} ?>">CityBurns 2010-2014</a>
							   </li>
						   </ul>
					   </li>


					</ul>
				</li>
 			   <li>
				   <span>4K Lento</span>
 					<ul>
 						<li><a href="<?php echo get_post_type_archive_link( '4k-lento'); ?>" class="<?php if ( is_post_type_archive('4k-lento')) { echo "active";} if (is_singular( '4k-lento' ) ) { echo " belongs";} ?>">Mixfiles</a></li>
 						<li>
 							<a href="https://soundcloud.com/tiagsssss" target="_blank">Soundcloud</a>
 						</li>
 						<li>
 							<a href="https://podcasts.apple.com/ca/podcast/4k-lento/id1445312236" target="_blank">Podcast</a>
 						</li>
 					</ul>
 				</li>


			   <li>
				  <span>Log</span>
				   <ul>
					   <!-- <li>
						   <a href="<?php echo get_post_type_archive_link( 'log'); ?>"  class="<?php
		 				  if (is_singular( 'log' ) OR is_post_type_archive('log') or is_tax('log-branch'))
		 					   { echo " belongs";}
		 				  if (is_post_type_archive('emulsion'))
		 					   { echo "active";}  ?>
		 					   ">All</a>
					   </li> -->
					   <li>
						   <a href="<?php echo get_term_link( 'blwww', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','blwww')) { echo "active";} if ((is_single() and has_term( 'blwww', 'log-branch' ))) { echo " belongs";} ?>">blwww</a>
					   </li>

					   <li>
						   <a href="<?php echo get_term_link( 'hrzn', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','hrzn')) { echo "active";} if ((is_single() and has_term( 'hrzn', 'log-branch' ))) { echo " belongs";} ?>">hrzn</a>
					   </li>

					   <li>
						   <span>Discontinued</span>
						   <ul>
							   <li>
								   <a href="<?php echo get_term_link( 'frntr', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','frntr')) { echo "active";} if ((is_single() and has_term( 'frntr', 'log-branch' ))) { echo " belongs";} ?>">frntr</a>
							   </li>
							   <li>
								   <a href="<?php echo get_term_link( 'plnts', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','plnts')) { echo "active";} if ((is_single() and has_term( 'plnts', 'log-branch' ))) { echo " belongs";} ?>">plnts</a>
							   </li>
							   <li>
								   <a href="<?php echo get_term_link( 'sdwlk', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','sdwlk')) { echo "active";} if ((is_single() and has_term( 'sdwlk', 'log-branch' ))) { echo " belongs";} ?>">sdwlk</a>
							   </li>

							   <li>
								   <a href="<?php echo get_term_link( 'rchv', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','rchv') or (is_single() and has_term( 'rchv', 'log-branch' ))) { echo "active";} ?>">rchv</a>
							   </li>
						   </ul>
					   </li>

				   </ul>
			   </li>

				<li><span>Filter</span>
					<ul>
						<li><span>Medium</span>
							<?php
							$args = array(
							  'taxonomy'     => 'medium',
							  'orderby'      => 'name',
							  'hide_empty'   => 1,
							  'title_li'     => '',
							  'hierarchical' => 1,
							  'walker'       => null,
							);
							?>
							<ul>
								<?php wp_list_categories( $args ); ?>
							</ul>
						</li>
						<li><span>Year</span>
							<?php
							$args = array(
							  'taxonomy'     => 'from',
							  'orderby'      => 'name',
							  'hide_empty'   => 1,
							  'title_li'     => '',
							  'hierarchical' => 1,
							  'walker'       => null,
							);
							?>
							<ul>
								<?php wp_list_categories( $args ); ?>
							</ul>
						</li>
						<li><span>Places</span>

							<?php
							$args = array(
							  'taxonomy'     => 'places',
							  'orderby'      => 'name',
							  'hide_empty'   => 1,
							  'title_li'     => '',
							  'hierarchical' => 1,
							  'walker'       => null,
							);
							?>
							<ul>
								<?php wp_list_categories( $args ); ?>
							</ul>

						</li>
					</ul>
				</li>

				<li><span>Metadata</a></span>
					<ul>

						<li>
							<a href="https://tiags.tumblr.com/" target="_blank">About</a>
						</li>
						<li>
							<a href="mailto:mail@tiags.space" target="_blank">Contact</a>
						</li>
						<li>
							<span>Follow</span>
							<ul>
								<li>
									<a href="https://www.instagram.com/tiagsssss/" target="_blank">Instagram</a>
								</li>
								<li>
									<a href="https://soundcloud.com/tiagsssss"  target="_blank">Soundcloud</a>
								</li>
								<li>
									<a href="https://vimeo.com/tiags" target="_blank">Vimeo</a>
								</li>
								<li>
									<a href="https://tiagssssspace.tumblr.com/"  target="_blank">Tumblr</a>
								</li>
								<li>
									<a href="https://twitter.com/tiagsssss" target="_blank">Twitter</a>
								</li>
							</ul>
						</li>

					</ul>

			</ul>

		</nav>



		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/dist/plyr.min.js" ></script>
		<script>
			//const players = Plyr.setup('audio'); //can be audioTag, .someClass, #someID

			const players = Array.from(document.querySelectorAll('audio')).map(p => new Plyr(p));
		</script>



		<?php
		// Suport Log hover image preview
		// -- https://github.com/zpalffy/preview-image-jquery ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/log-preview-image/preview-images.js" crossorigin="anonymous"></script>
		<script type="text/javascript">
			$.previewImage({
			   'xOffset': 30,  // x-offset from cursor
			   'yOffset': -300,  // y-offset from cursor
			   'fadeIn': 700, // delay in ms. to display the preview
			   'css': {        // the following css will be used when rendering the preview image.
			      'padding': '0px',
				  'box-shadow': ''
			   }
			});
		</script>


	</body>

</html>
