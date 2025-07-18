

		<?php wp_footer(); // js scripts are inserted using this function ?>


		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/src/js/plyr.js"></script>
		
		<script>
			function initializePlyrElements(scope = document) {
				scope.querySelectorAll('.plyr').forEach(media => {
					// Prevent double init
					if (media.dataset.plyrInitialized === 'true') return;

					// Set necessary attributes
					media.setAttribute('preload', 'auto');
					media.setAttribute('playsinline', '');

					// For autoplay support in Chrome: muted must be set via JS
					if (media.hasAttribute('autoplay')) {
						media.muted = true;
					}

					// Init Plyr
					const player = new Plyr(media);
					media.dataset.plyrInitialized = 'true';

					// Attempt autoplay after media is ready
					if (media.hasAttribute('autoplay')) {
						media.addEventListener('loadeddata', () => {
							media.play().catch(err => {
								console.warn('Autoplay failed:', err);
							});
						}, { once: true });
					}
				});
			}

			document.addEventListener('DOMContentLoaded', () => {
				initializePlyrElements();
			});

			// Infinite Scroll support
			if (typeof $container !== 'undefined') {
				$container.on('append.infiniteScroll', function(event, response, path, items) {
					items.forEach(item => {
						// Safari bug fix for srcset
						item.querySelectorAll('img[srcset]').forEach(img => {
							img.outerHTML = img.outerHTML;
						});

						// Init Plyr for new elements
						initializePlyrElements(item);
					});
				});
			}
		</script>

	</body>

</html>
