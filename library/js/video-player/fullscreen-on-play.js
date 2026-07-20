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

    function enterFullscreen(player, video) {
        // Plyr handles vendor prefixes and routes iOS to webkitEnterFullscreen.
        try {
            if (player.fullscreen && typeof player.fullscreen.enter === 'function') {
                player.fullscreen.enter();
                return;
            }
        } catch (e) {}

        // Direct iOS fallback if Plyr's own path is unavailable. Needs metadata
        // loaded; with preload="metadata" that is normally true by click time.
        try {
            if (typeof video.webkitEnterFullscreen === 'function') {
                video.webkitEnterFullscreen();
            }
        } catch (e) {}
    }

    /**
     * Best-effort landscape lock. Android Chrome honours it inside fullscreen;
     * iOS Safari does not implement it at all (native fullscreen rotates anyway).
     * Must not throw or leave an unhandled rejection.
     */
    function lockLandscape() {
        try {
            var orientation = window.screen && window.screen.orientation;
            if (orientation && typeof orientation.lock === 'function') {
                var result = orientation.lock('landscape');
                if (result && typeof result.catch === 'function') {
                    result.catch(function () {});
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

        enterFullscreen(player, video);
        lockLandscape();
    }, true);
})();
