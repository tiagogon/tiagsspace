

		<?php wp_footer(); // js scripts are inserted using this function ?>

	</body>

<script>
    // Lazily initialize Plyr when thumbnails enter viewport
    let plyrInitObserver;
    function queueInitializePlyrOnIntersect(scope = document) {
        if (!('IntersectionObserver' in window)) {
            // Fallback: initialize when browser is idle or after a short delay
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => initializePlyrElementsThumnails(scope), { timeout: 300 });
            } else {
                setTimeout(() => initializePlyrElementsThumnails(scope), 100);
            }
            return;
        }

        if (!plyrInitObserver) {
            plyrInitObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        initializePlyrElementsThumnails(entry.target.parentElement || document);
                        plyrInitObserver.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '600px 0px', threshold: 0.01 });
        }

        // Batch observation to avoid layout thrash
        const toObserve = [];
        scope.querySelectorAll('.plyr-thumbnail-front').forEach(el => {
            if (el.dataset.plyrInitialized === 'true') return;
            toObserve.push(el);
        });
        if (toObserve.length) {
            (window.requestAnimationFrame || setTimeout)(() => {
                toObserve.forEach(el => plyrInitObserver.observe(el));
            });
        }
    }

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
        // Defer heavy work; initialize when thumbnails are about to be visible
        queueInitializePlyrOnIntersect();
    });

    // Infinite scroll integration
    if (typeof $container !== 'undefined') {
        $container.on('append.infiniteScroll', function(event, response, path, items) {
            const work = () => {
                items.forEach(item => {
                    // Hint browser to decode images off the main thread when possible
                    item.querySelectorAll('img').forEach(img => {
                        try { if ('decode' in img) img.decode().catch(() => {}); } catch(e) {}
                        img.loading = img.loading || 'lazy';
                    });

                    // Fix for Safari srcset bug (avoid full outerHTML rewrite)
                    item.querySelectorAll('img[srcset]').forEach(img => {
                        const current = img.getAttribute('srcset');
                        if (current) img.setAttribute('srcset', current);
                    });

                    // Lazily initialize Plyr only when elements approach viewport
                    queueInitializePlyrOnIntersect(item);
                });
            };
            if (window.requestAnimationFrame) {
                requestAnimationFrame(work);
            } else {
                setTimeout(work, 0);
            }
        });
    }
</script>

</html>
