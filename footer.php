

		<?php wp_footer(); // js scripts are inserted using this function ?>


		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/assets/vendor/plyr/dist/plyr.min.js" ></script>
		
		<script>
			
		function initializePlyrElements(scope = document) {
			scope.querySelectorAll('.plyr').forEach(media => {
				// Prevent double-init
				if (media.dataset.plyrInitialized === 'true') return;

				// Set required attributes before initializing
				media.setAttribute('preload', 'auto');
				media.setAttribute('playsinline', '');

				if (media.hasAttribute('autoplay')) {
				media.muted = true; // Required for autoplay to work
				}

				// Initialize Plyr
				const player = new Plyr(media);
				media.dataset.plyrInitialized = 'true';

				// Attempt to autoplay if requested
				if (media.hasAttribute('autoplay')) {
				media.addEventListener('loadedmetadata', () => {
					media.play().catch(err => {
					console.warn('Autoplay failed:', err);
					});
				}, { once: true });
				}
			});
			}

			// Run on DOM ready
			document.addEventListener('DOMContentLoaded', () => {
			initializePlyrElements();
			});
		</script>

	</body>

</html>
