/**
 * Captions in iPhone native fullscreen — shared by both film playback paths.
 *
 * THE PROBLEM: Plyr never lets the browser render captions. It force-demotes
 * every text track to mode="hidden" and paints the cues itself into its own
 * .plyr__captions div — see captions.update() ("showing" === mode && (mode =
 * "hidden")) and captions.toggle(), which ends with a deferred re-hide of
 * currentTrackNode even when captions are being turned ON.
 *
 * On iPhone, Plyr's `iosNative: true` sends fullscreen to
 * video.webkitEnterFullscreen(), handing the element to Apple's own player. At
 * that moment BOTH renderers are dead: .plyr__captions is no longer on screen
 * (the native player draws outside the DOM, so no CSS of ours can reach it),
 * and the track is still "hidden" so the native player has nothing to show.
 * Plyr has no webkitbeginfullscreen/webkitendfullscreen handling at all, so
 * nothing ever flips the track back. Result: subtitles work inline and vanish
 * in fullscreen.
 *
 * THE FIX: promote the active track to "showing" for exactly the duration of
 * native fullscreen, and put it back to "hidden" on exit so Plyr resumes its
 * own rendering. Inside fullscreen the cues are drawn by iOS, which means they
 * pick up the viewer's system caption styling (Settings > Accessibility >
 * Subtitles & Captioning) rather than ours — the correct trade on iPhone.
 *
 * WHY NOT LEAVE THE TRACK "showing" PERMANENTLY: inline, Safari would draw the
 * native cues AND Plyr would draw its overlay — subtitles twice over. The mode
 * change has to be scoped to the fullscreen window.
 *
 * WHY PER-ELEMENT LISTENERS, not the delegation used by fullscreen-on-play.js:
 * webkitbeginfullscreen/webkitendfullscreen do not bubble. The Plyr instance is
 * still looked up at event time rather than bind time, which keeps this
 * independent of when each engine constructs its player
 * (plyr-adaptive-quality.js at DOMContentLoaded, init-hls.js inside
 * MANIFEST_PARSED).
 *
 * THREE PLYR QUIRKS THIS CODE IS BUILT AROUND, all verified in plyr.js 3.8.4:
 *   1. `player.currentTrack = player.currentTrack` is a no-op — captions.set()
 *      early-returns on an unchanged value, so it cannot be used to force a
 *      repaint. We dispatch a synthetic `cuechange` at the track instead, which
 *      is exactly what Plyr listens to in order to call updateCues().
 *   2. Plyr's getTracks() filters on `captions.meta.has(track)` as well as kind,
 *      so its indices need not match a plain textTracks scan. We select by
 *      language via the public `player.language` setter and never touch indices.
 *   3. `player.currentTrack` returns -1 whenever captions are toggled off, so it
 *      cannot double as "which track is loaded".
 *
 * Debug: ?video_debug=1 in the URL, or data-debug="1" on the <video>. On device,
 * read the log with Safari Web Inspector over USB.
 *
 * @see library/video/enqueues.php (enqueued for both MP4 and HLS films)
 * @see library/js/video-player/fullscreen-on-play.js (what puts us in fullscreen)
 */
(function () {
    'use strict';

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
            args.unshift('[captions]');
            console.log.apply(console, args);
        } catch (e) {}
    }

    /** Subtitle/caption tracks only — ignore chapters, metadata and the like. */
    function captionTracks(video) {
        try {
            return Array.prototype.slice.call(video.textTracks || []).filter(function (track) {
                return track.kind === 'subtitles' || track.kind === 'captions';
            });
        } catch (e) {
            return [];
        }
    }

    /**
     * The track to show in fullscreen, or null when captions are off.
     *
     * captions.currentTrackNode is the real TextTrack object, which sidesteps
     * Plyr's index filtering. Without a Plyr instance — or with captions on but
     * nothing selected yet — we fall back to the <track default> the renderers
     * emit.
     */
    function trackToShow(video, player) {
        if (player) {
            try {
                if (!player.captions || !player.captions.toggled) return null;
                if (player.captions.currentTrackNode) return player.captions.currentTrackNode;
            } catch (e) {}
        }

        try {
            var el = video.querySelector('track[default]');
            if (el && el.track) return el.track;
        } catch (e) {}

        return null;
    }

    function onBeginFullscreen(video) {
        var debug = debugEnabled(video);
        var player = video.plyr || null;
        var wanted = trackToShow(video, player);

        if (!wanted) {
            if (debug) log('fullscreen in — captions off, nothing to show');
            return;
        }

        // Remember what we promoted so exit can tell "unchanged" from "the viewer
        // picked something else in Apple's menu".
        video._tiagsCaptionTrack = wanted;

        // Others go to "hidden", never "disabled" — a disabled track risks
        // disappearing from Apple's subtitle menu, and the viewer should still be
        // able to switch language while fullscreen.
        var promote = function () {
            captionTracks(video).forEach(function (track) {
                track.mode = (track === wanted) ? 'showing' : 'hidden';
            });
        };
        promote();

        // Plyr re-hides the active track from a setTimeout inside captions.toggle(),
        // and captions.update() demotes anything "showing" whenever a track is
        // added. Either can land just after us and undo the promotion, so re-assert
        // once the current task queue has drained.
        setTimeout(promote, 0);

        if (debug) log('fullscreen in — showing', wanted.language || wanted.label || '?');
    }

    function onEndFullscreen(video) {
        var debug = debugEnabled(video);
        var player = video.plyr || null;
        var tracks = captionTracks(video);
        var previous = video._tiagsCaptionTrack || null;

        // What is showing now may not be what we promoted: Apple's own subtitle
        // menu is live during fullscreen. Adopt the viewer's choice so Plyr's
        // menu agrees with what they just watched.
        var chosen = tracks.filter(function (track) {
            return track.mode === 'showing';
        })[0] || null;

        if (player) {
            try {
                if (chosen && chosen !== previous && chosen.language) {
                    // -1 means Plyr currently has captions off; turn them on first.
                    if (player.currentTrack === -1) player.toggleCaptions(true);
                    player.language = chosen.language;
                    if (debug) log('adopted native choice', chosen.language);
                } else if (!chosen && previous) {
                    player.toggleCaptions(false);
                    if (debug) log('viewer turned captions off natively');
                }
            } catch (e) {}
        }

        // Back to hidden so Plyr owns the rendering again. Anything left on
        // "showing" would double up with the .plyr__captions overlay.
        tracks.forEach(function (track) {
            track.mode = 'hidden';
        });

        // Plyr repaints on cuechange, which on a sparse film can be many seconds
        // away — long enough to look broken if a cue is on screen right now.
        // Dispatching cuechange at the track calls Plyr's own updateCues handler.
        if (player) {
            try {
                var active = player.captions && player.captions.currentTrackNode;
                if (active && typeof window.Event === 'function') {
                    active.dispatchEvent(new Event('cuechange'));
                }
            } catch (e) {}
        }

        delete video._tiagsCaptionTrack;

        if (debug) log('fullscreen out — restored to hidden');
    }

    function bind(video) {
        // Native handoff only happens where webkitEnterFullscreen exists, i.e.
        // iPhone/iPod. iPad reports as desktop Safari and keeps the Plyr overlay.
        if (typeof video.webkitEnterFullscreen !== 'function') return;
        if (video._tiagsCaptionsBound) return;
        video._tiagsCaptionsBound = true;

        video.addEventListener('webkitbeginfullscreen', function () {
            onBeginFullscreen(video);
        });
        video.addEventListener('webkitendfullscreen', function () {
            onEndFullscreen(video);
        });

        if (debugEnabled(video)) log('bound native fullscreen caption handling');
    }

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll('video.film-player'), bind);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
