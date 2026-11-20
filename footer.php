

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

    // Play / pause: ONE observer for every teaser on the page (it was one per
    // video, which added up over many Infinite Scroll pages).
    let thumbPlayObserver;
    function observeThumbPlayback(media) {
        if (!('IntersectionObserver' in window)) {
            media.dataset.inView = '1';
            return;
        }
        if (!thumbPlayObserver) {
            thumbPlayObserver = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    const video = entry.target;
                    video.dataset.inView = entry.isIntersecting ? '1' : '';
                    if (entry.isIntersecting) {
                        if (video.paused) {
                            video.play().catch(() => {});
                        }
                    } else if (!video.paused) {
                        video.pause();
                    }
                });
            }, { threshold: 0.15 });
        }
        thumbPlayObserver.observe(media);
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
            // No `autoplay`: it would start a teaser wherever it is once loaded
            // (600px ahead of the viewport). observeThumbPlayback() plays it in view.
            media.loop = true;

            new Plyr(media, {
                controls: [],
            });

            // Only while on screen: a teaser that finishes loading off screen
            // (canplay) must not start decoding there.
            const tryPlay = () => {
                if (media.paused && media.dataset.inView === '1') {
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

            observeThumbPlayback(media);

            media.dataset.plyrInitialized = 'true';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Defer heavy work; initialize when thumbnails are about to be visible
        queueInitializePlyrOnIntersect();
    });

    // Infinite Scroll pages: archive-grid.php calls queueInitializePlyrOnIntersect() on append.
</script>

</html>
