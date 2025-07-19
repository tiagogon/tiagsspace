

		<?php wp_footer(); // js scripts are inserted using this function ?>

	</body>
	<script>
            // Initialize Plyr elements with thumbnails on front page or recomended 
			function initializePlyrElementsThumnails(scope = document) {
				scope.querySelectorAll('.plyr-thumbnail-front').forEach(media => {
				if (media.dataset.plyrInitialized === 'true') return;

				// Hard-set autoplay conditions for Chrome
				media.setAttribute('preload', 'auto');
				media.setAttribute('playsinline', '');
				media.setAttribute('muted', '');
				media.muted = true; // JavaScript-mandated muted
				media.autoplay = true; // Ensure it's active
				media.loop = true;

				new Plyr(media, {
					controls: [], // No controls at all
				});
				media.dataset.plyrInitialized = 'true';

				// Play after ready
				media.addEventListener('loadeddata', () => {
					media.play().catch(err => {
					console.warn('Autoplay failed (direct load):', err);
					});
				}, { once: true });
				});
			}

			document.addEventListener('DOMContentLoaded', () => {
				initializePlyrElementsThumnails();
			});

			// Infinite Plyr elements on infinite Scroll loading
			if (typeof $container !== 'undefined') {
				$container.on('append.infiniteScroll', function(event, response, path, items) {
				items.forEach(item => {
					item.querySelectorAll('img[srcset]').forEach(img => {
					img.outerHTML = img.outerHTML;
					});
					initializePlyrElementsThumnails(item);
				});
				});
			}
		</script>

</html>
