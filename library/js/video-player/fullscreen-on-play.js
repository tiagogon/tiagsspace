/**
 * Fullscreen on play — shared by both film playback paths.
 *
 * Pressing play on a film is the "I want to watch this" gesture, so it puts the
 * work on the whole screen instead of leaving it in the inline 16:9 box.
 *
 * WHY A DELEGATED CAPTURE-PHASE CLICK LISTENER, and not Plyr's `play` event:
 * requestFullscreen() needs transient user activation. Calling it from a `play`
 * handler only works by accident — Chrome's activation window happens to still be
 * open — and fails outright when play is programmatic. Handling the click itself,
 * in the capture phase, keeps us squarely inside the gesture. Delegation also
 * sidesteps init order: the two engines build their Plyr instances at different
 * moments (plyr-adaptive-quality.js at DOMContentLoaded, init-hls.js inside
 * MANIFEST_PARSED), and this needs no instance at bind time.
 *
 * SCOPE: films only, via `video.film-player` — gallery/Videopack [KGVID] videos
 * never carry that class. Films marked `loop` or `autoplay` opt out: those are
 * ambient by intent and must not seize the screen.
 *
 * iOS: Plyr's `iosNative: true` routes iPhone to video.webkitEnterFullscreen(),
 * i.e. Apple's own player. True fullscreen with correct landscape rotation, at
 * the cost of our Plyr skin for the duration. iPadOS 13+ and everything else get
 * real fullscreen with the skin intact.
 *
 * That handoff also breaks captions outright — not merely their styling — because
 * Plyr keeps every text track "hidden" and renders cues into its own overlay,
 * which the native player never shows. ios-native-captions.js exists to fix that;
 * see its header for the full explanation.
 *
 * @see library/video/enqueues.php (enqueued for both MP4 and HLS films)
 * @see library/js/video-player/ios-native-captions.js (captions during that handoff)
 */
(function () {
    'use strict';

    /**
     * Ambient films opt out — a looping or autoplaying piece is wallpaper, not a
     * screening. Both are emitted as real HTML attributes by
     * tiagsspace_video_boolean_attrs() in library/video/player.php.
     */
    function optsOut(video) {
        return video.hasAttribute('loop') || video.hasAttribute('autoplay');
    }

    function debugEnabled(video) {
        try {
            if (video && video.getAttribute('data-debug') === '1') return true;
            return /[?&]video_debug=1\b/.test(window.location.search);
        } catch (e) {
            return false;
        }
    }

    function log() {
        try {
            if (!window.console || !console.log) return;
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[fullscreen]');
            console.log.apply(console, args);
        } catch (e) {}
    }

    /**
     * Enter fullscreen, handling the outcome.
     *
     * WHY NOT JUST player.fullscreen.enter(): Plyr calls
     * `target.requestFullscreen({navigationUI:"hide"})` and never captures the
     * returned promise (verified in the vendored source — enter() returns
     * undefined). A rejected request therefore fails completely silently: no
     * error, no retry, and no fallback, because Plyr's `fallback: true` only
     * engages when the API is ABSENT, never when a request is refused. Calling
     * the API ourselves lets us see the rejection and still fill the screen via
     * Plyr's CSS fallback.
     */
    function enterFullscreen(player, video) {
        var debug = debugEnabled(video);

        // iOS native player: no Plyr, no container — go straight to the element.
        // (Manifest-subs films drop playsinline in init-hls.js and fullscreen on
        // their own, so this is only for sidecar-track films on iPhone.)
        if (!player) {
            try {
                if (typeof video.webkitEnterFullscreen === 'function') {
                    video.webkitEnterFullscreen();
                    if (debug) log('webkitEnterFullscreen (no Plyr)');
                }
            } catch (e) {}
            return;
        }

        var container = player.elements && player.elements.container;

        // iOS via Plyr's iosNative path: element-level, returns no promise.
        if (typeof video.webkitEnterFullscreen === 'function'
            && player.config && player.config.fullscreen && player.config.fullscreen.iosNative) {
            try {
                video.webkitEnterFullscreen();
                if (debug) log('webkitEnterFullscreen (iosNative)');
                return;
            } catch (e) {}
        }

        if (container && typeof container.requestFullscreen === 'function') {
            try {
                var result = container.requestFullscreen({ navigationUI: 'hide' });
                if (result && typeof result.then === 'function') {
                    result.then(function () {
                        if (debug) log('entered');
                        lockLandscape(debug);
                    })['catch'](function (err) {
                        if (debug) log('REJECTED:', err && err.name, err && err.message, '— using CSS fallback');
                        cssFallback(player);
                    });
                } else {
                    // Older/prefixed implementations return nothing; assume it worked.
                    if (debug) log('requested (no promise returned)');
                    lockLandscape(debug);
                }
                return;
            } catch (err) {
                if (debug) log('THREW:', err && err.name, err && err.message, '— using CSS fallback');
                cssFallback(player);
                return;
            }
        }

        // No native API at all: Plyr's own path (which will use its fallback).
        try {
            if (player.fullscreen && typeof player.fullscreen.enter === 'function') {
                player.fullscreen.enter();
                if (debug) log('delegated to plyr.fullscreen.enter()');
            }
        } catch (e) {}
    }

    /**
     * Plyr's CSS "fullscreen" — the viewport-filling mode its `fallback: true`
     * config already intends. Used when a native request is refused, so the
     * viewer gets a filled screen instead of nothing happening.
     */
    function cssFallback(player) {
        try {
            if (player.fullscreen && typeof player.fullscreen.toggleFallback === 'function') {
                player.fullscreen.toggleFallback(true);
            }
        } catch (e) {}
    }

    /**
     * Best-effort landscape lock, only AFTER fullscreen is confirmed.
     *
     * Ordering matters: screen.orientation.lock() requires active fullscreen, so
     * calling it synchronously alongside the request guarantees a rejection mid
     * transition — and on some engines a failed lock aborts the transition
     * itself. Touch devices only; on a desktop it can only ever reject.
     */
    function lockLandscape(debug) {
        try {
            var isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            if (!isTouch) return;

            var orientation = window.screen && window.screen.orientation;
            if (orientation && typeof orientation.lock === 'function') {
                var result = orientation.lock('landscape');
                if (result && typeof result.catch === 'function') {
                    result['catch'](function (err) {
                        if (debug) log('orientation lock refused:', err && err.name);
                    });
                }
            }
        } catch (e) {}
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || typeof target.closest !== 'function') return;

        var button = target.closest('[data-plyr="play"]');
        if (!button) return;

        var container = button.closest('.plyr');
        var video = container && container.querySelector('video.film-player');
        if (!video) return;

        if (optsOut(video)) return;

        var player = video.plyr;
        if (!player) return;

        // Only when this click is about to START playback. In the capture phase
        // Plyr has not handled the click yet, so `paused` is still the pre-click
        // state — this is what keeps the pause button from triggering fullscreen.
        if (!player.paused) return;

        // Already fullscreen: nothing to do, and this guard stops any re-entry loop.
        try {
            if (player.fullscreen && player.fullscreen.active) return;
        } catch (e) {}

        // lockLandscape() is deliberately NOT called here — it now runs only once
        // fullscreen is confirmed, inside enterFullscreen().
        enterFullscreen(player, video);
    }, true);
})();
