		<!-- <?php get_template_part( 'template-parts/front', 'social' ); ?>

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

					</div>

				</footer>

			</div>

		</div> -->

		<!--[if lt IE 7 ]>
				<script src="//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js"></script>
				<script>window.attachEvent('onload',function(){CFInstall.check({mode:'overlay'})})</script>
		<![endif]-->

		<?php wp_footer(); // js scripts are inserted using this function ?>

		
		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/assets/vendor/plyr/dist/plyr.min.js" ></script>
		<script>
			//const players = Plyr.setup('audio'); //can be audioTag, .someClass, #someID

			const players = Array.from(document.querySelectorAll('audio')).map(p => new Plyr(p));
		</script>



		<?php
		// Suport Log hover image preview
		// -- https://github.com/zpalffy/preview-image-jquery ?>

		<script src="<?php bloginfo('template_url'); ?>/library/js/preview-image-jquery-master/preview-image.js"></script>

		<!-- <script type="text/javascript">
		// Does not work on chrome private mode.
		//CSS edited with !imporant on ID #preview-image-plugin-overlay on front-main-index.scss
			$.previewImage({
			   'xOffset': 30,  // x-offset from cursor
			   'yOffset': -300,  // y-offset from cursor
			   'fadeIn': 700, // delay in ms. to display the preview
			   'css': {        // the following css will be used when rendering the preview image.
			      'padding': '0px',
				  'box-shadow': '',
					'z-index': '10000'
			   }
			});
		</script> -->


	</body>

</html>
