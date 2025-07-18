

		<?php wp_footer(); // js scripts are inserted using this function ?>


		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/src/js/plyr.js"></script>
		
		<script>
		function initializePlyrElements(scope = document) {
			scope.querySelectorAll('.plyr').forEach(media => {
			if (media.dataset.plyrInitialized === 'true') return;

			media.setAttribute('preload', 'auto');
			media.setAttribute('playsinline', '');
			media.setAttribute('muted', '');      // HTML attribute
			media.muted = true;                   // JavaScript property

			const player = new Plyr(media);
			media.dataset.plyrInitialized = 'true';

			media.addEventListener('loadeddata', () => {
				media.play().catch(err => {
				console.warn('Autoplay failed:', err);
				});
			}, { once: true });
			});
		}

		document.addEventListener('DOMContentLoaded', () => {
			initializePlyrElements();
		});

		if (typeof $container !== 'undefined') {
			$container.on('append.infiniteScroll', function(event, response, path, items) {
			items.forEach(item => {
				item.querySelectorAll('img[srcset]').forEach(img => {
				img.outerHTML = img.outerHTML; // Safari fix
				});

				initializePlyrElements(item);
			});
			});
		}
		</script>

	</body>

</html>
