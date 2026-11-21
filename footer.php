

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
                // Nothing kept in the visitor's browser, no icon sprite (no controls):
                // see library/video/player.php.
                storage: { enabled: false },
                loadSprite: false,
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

            // Cross-fade over the poster <img> (_component-image-fade.scss); the
            // Firefox parking below takes the class off again.
            media.addEventListener('playing', () => media.classList.add('is-playing'));

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

    // Back/forward cache, Firefox on computers only (Nov 2026). Firefox will not keep a
    // page in its back/forward cache while a request is still open when the visitor
    // leaves, and a playing teaser nearly always has one (a looping .mov asks for byte
    // ranges again and again), so Back from a set reloaded the archive. Teasers whose
    // file is not fully downloaded are parked before leaving: sources taken out and
    // load() called, which cancels the download (the poster stays). Firefox checks the
    // moment the navigation starts and the cancel lands a few ms later, so parking
    // starts on the press of a link (mouse/pen pointerdown, Enter), with beforeunload
    // as the backstop (touch: no pointerdown, every scroll gesture starts with one on
    // a thumbnail). On return from the cache, or when no page change follows, they are
    // put back and the play/pause observer starts the ones on screen.
    // Limit: once Firefox has buffered enough of a BIG teaser (Project Forest is 38 MB)
    // it pauses that download, and a paused connection is not closed by load(); then
    // the page is reloaded anyway and library/js/infinite-scroll-setup.js rebuilds the
    // scrolled pages instead. Chrome and Safari never need this: not loaded for them.
    // No unload listener: that would keep every browser from caching the page.
    (function () {
        const ua = navigator.userAgent;
        if (!/Firefox\//.test(ua) || /Android|Mobile|Tablet/.test(ua)) return;
        let restoreTimer = 0;

        function leavingLink(target) {
            const link = target && target.closest ? target.closest('a[href]') : null;
            if (!link || link.hasAttribute('download')) return false;
            if (link.target && link.target !== '_self') return false;
            const href = link.getAttribute('href');
            return !!href && href.charAt(0) !== '#' && !/^(javascript|mailto|tel):/i.test(href);
        }

        function parkSoon(delay) {
            parkTeasers();
            clearTimeout(restoreTimer);
            // Frozen while the page sits in the cache; runs if the visitor never left.
            restoreTimer = setTimeout(restoreTeasers, delay);
        }

        // A finished download has no open connection: that teaser keeps playing.
        function fullyBuffered(media) {
            const b = media.buffered;
            return media.duration > 0 && isFinite(media.duration) && b.length === 1 &&
                b.start(0) <= 0.1 && b.end(0) >= media.duration - 0.1;
        }

        function parkTeasers() {
            document.querySelectorAll('.plyr-thumbnail-front[data-plyr-initialized="true"]').forEach(media => {
                if (media.dataset.teaserParked === '1' || fullyBuffered(media)) return;
                const sources = Array.from(media.querySelectorAll('source'));
                media._parked = { sources: sources, time: media.currentTime || 0 };
                media.pause();
                media.classList.remove('is-playing');
                sources.forEach(source => source.remove());
                media.removeAttribute('src');
                media.load();
                media.dataset.teaserParked = '1';
            });
        }

        function restoreTeasers() {
            clearTimeout(restoreTimer);
            document.querySelectorAll('.plyr-thumbnail-front[data-teaser-parked="1"]').forEach(media => {
                const parked = media._parked;
                delete media.dataset.teaserParked;
                if (!parked) return;
                parked.sources.forEach(source => media.appendChild(source));
                media.load();
                if (parked.time) {
                    media.addEventListener('loadedmetadata', () => {
                        try { media.currentTime = parked.time; } catch (e) {}
                    }, { once: true });
                }
                media._parked = null;
                // canplay -> tryPlay() starts it if it is on screen
            });
        }

        document.addEventListener('pointerdown', event => {
            if (event.pointerType === 'touch' || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (leavingLink(event.target)) parkSoon(1500);
        }, true);
        document.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.metaKey && !event.ctrlKey && !event.shiftKey && leavingLink(event.target)) parkSoon(1500);
        }, true);
        // A slow server can hold the old page longer than 1.5s: from here, wait longer.
        window.addEventListener('beforeunload', () => parkSoon(8000));
        window.addEventListener('pageshow', event => {
            if (event.persisted) restoreTeasers();
        });
    })();
</script>

</html>
