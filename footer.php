	<?php get_template_part( 'template-parts/front', 'social' ); ?>

 	<div id="footer-block">

 		<div class="container">
			
			<footer role="contentinfo">

				<div id="inner-footer" class="clearfix row">

					<div class="attribution <?php content_wrap() ?>">

						<p>
							 Hello, World! My name is <a xmlns:cc="http://creativecommons.org/ns#" href="https://tiags.space" property="cc:attributionName" rel="cc:attributionURL">Tiago</a> and this is my digital archive published under an <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/4.0/"> attribution-noncommercial-noderivatives</a> license. You can reach me at mail@tiags.space.
							<br>
							<br>
							<a href="https://trouble.place"><span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">https://tiags.space</span></a> © 2010-<?php echo date("Y"); ?>
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



	<?php // --- Menu --- Follow?>
	<div class="overlay overlay-follow overlay-hugeinc">
		<button type="button" class="overlay-close overlay-follow-close">
			<span class="close">close</span>
		</button>
		<nav>
			<ul>
				<li>
					<a href="https://www.instagram.com/tiagsssss/" target="_blank">
						Instagram
					</a>
				</li>
				<li>
					<a href="https://www.facebook.com/trouble.place" target="_blank">
						Facebook
					</a>
				</li>
				<li>
					<a href="https://twitter.com/troubleplace" target="_blank">
						Twitter
					</a>
				</li>
				<li>
					<a href="https://vimeo.com/troubleplace" target="_blank">
						Vimeo
					</a>
				</li>
				<li>
					<a href="https://troubleplace.tumblr.com" target="_blank">
						Tumblr
					</a>
				</li>
				<li>
					<a href="https://www.flickr.com/photos/cityburns/" target="_blank">
						Flickr
					</a>
				</li>
				<li>
					<a href="https://tinyletter.com/trouble-letter" target="_blank">
						mailing list
					</a>
				</li>
				<li>
					<a href="https://trouble.place/feed" target="_blank">
						<span class="fa fa-rss"></span> Feed
					</a>
				</li>
			</ul>
		</nav>
	</div>

	<script>
		// DEACTIVATED FOLLOW OVERLAY SCREEN

		// (function() {
		// 	var triggerBttnfollow = document.getElementById( 'trigger-overlay-follow' ),
		// 		overlayfollow = document.querySelector( 'div.overlay-follow' ),
		// 		closeBttnfollow = overlayfollow.querySelector( 'button.overlay-follow-close' );
		// 		transEndEventNamesfollow = {
		// 			'WebkitTransition': 'webkitTransitionEnd',
		// 			'MozTransition': 'transitionend',
		// 			'OTransition': 'oTransitionEnd',
		// 			'msTransition': 'MSTransitionEnd',
		// 			'transition': 'transitionend'
		// 		},
		// 		transEndEventNamefollow = transEndEventNamesfollow[ Modernizr.prefixed( 'transition' ) ],
		// 		supportfollow = { transitions : Modernizr.csstransitions };

		// 	function toggleOverlayfollow() {
		// 		if( classie.has( overlayfollow, 'open' ) ) {
		// 			classie.remove( overlayfollow, 'open' );
		// 			classie.add( overlayfollow, 'close' );
		// 			var onEndTransitionFnfollow = function( ev ) {
		// 				if( supportfollow.transitions ) {
		// 					if( ev.propertyName !== 'visibility' ) return;
		// 					this.removeEventListener( transEndEventNamefollow, onEndTransitionFnfollow );
		// 				}
		// 				classie.remove( overlayfollow, 'close' );
		// 			};
		// 			if( supportfollow.transitions ) {
		// 				overlayfollow.addEventListener( transEndEventNamefollow, onEndTransitionFnfollow );
		// 			}
		// 			else {
		// 				onEndTransitionFnfollow();
		// 			}
		// 		}
		// 		else if( !classie.has( overlayfollow, 'close' ) ) {
		// 			classie.add( overlayfollow, 'open' );
		// 		}
		// 	}

		// 	triggerBttnfollow.addEventListener( 'click', toggleOverlayfollow );
		// 	closeBttnfollow.addEventListener( 'click', toggleOverlayfollow );
		// })();
	</script>
	<style media="screen">

	</style>

	</body>

</html>
