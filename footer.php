

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

            const seamlessLoop = media.dataset.loopMode === 'seamless';

            media.setAttribute('preload', 'auto');
            media.setAttribute('playsinline', '');
            media.setAttribute('webkit-playsinline', '');
            media.setAttribute('muted', '');
            media.muted = true;
            media.defaultMuted = true;
            media.autoplay = true;
            media.loop = true;

            new Plyr(media, {
                controls: [],
            });

            const tryPlay = () => {
                if (media.paused) {
                    media.play().catch(err => {
                        if (window.console && console.debug) {
                            console.debug('Thumb video autoplay blocked', err);
                        }
                    });
                }
            };

            const handleTimeUpdate = () => {
                if (!seamlessLoop || !media.duration) {
                    return;
                }
                const threshold = Math.max(media.duration - 0.08, 0);
                if (media.currentTime >= threshold) {
                    media.currentTime = 0.01;
                    tryPlay();
                }
            };

            media.addEventListener('loadeddata', tryPlay, { once: true });
            media.addEventListener('canplay', tryPlay);
            media.addEventListener('timeupdate', handleTimeUpdate);
            media.addEventListener('ended', () => {
                media.currentTime = 0.01;
                tryPlay();
            });

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            tryPlay();
                        } else {
                            media.pause();
                        }
                    });
                }, { threshold: 0.15 });
                observer.observe(media);
            }

            media.dataset.plyrInitialized = 'true';
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
