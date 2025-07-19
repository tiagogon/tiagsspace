

		<?php wp_footer(); // js scripts are inserted using this function ?>

	</body>

<script>
    function initializePlyrElementsThumnails(scope = document) {
        scope.querySelectorAll('.plyr-thumbnail-front').forEach(media => {
            if (media.dataset.plyrInitialized === 'true') return;

            media.setAttribute('preload', 'auto');
            media.setAttribute('playsinline', '');
            media.setAttribute('muted', '');
            media.muted = true;
            media.autoplay = true;
            media.loop = true;

            new Plyr(media, {
                controls: [],
            });

            media.dataset.plyrInitialized = 'true';

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

    // Infinite scroll integration
    if (typeof $container !== 'undefined') {
        $container.on('append.infiniteScroll', function(event, response, path, items) {
            items.forEach(item => {
                // Fix for Safari srcset bug
                item.querySelectorAll('img[srcset]').forEach(img => {
                    img.outerHTML = img.outerHTML;
                });

                // ✅ Pass actual DOM element
                initializePlyrElementsThumnails(item);
            });
        });
    }
</script>

</html>
