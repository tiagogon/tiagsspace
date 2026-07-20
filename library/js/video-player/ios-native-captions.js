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
     * Plyr's index filtering. It can lag behind — Plyr assigns it from deferred
     * callbacks, and on manifest-subs films the tracks themselves can arrive
     * after the play-click that triggered fullscreen — so fall back to matching
     * Plyr's language, then to the first caption track. There are no sidecar
     * <track default> elements to fall back to on manifest-subs films.
     */
    function trackToShow(video, player) {
        var tracks = captionTracks(video);

        if (player) {
            try {
                if (!player.captions || !player.captions.toggled) return null;
                if (player.captions.currentTrackNode) return player.captions.currentTrackNode;
                var lang = (player.language || '').toLowerCase();
                if (lang) {
                    var match = tracks.filter(function (t) {
                        return (t.language || '').toLowerCase().indexOf(lang) === 0;
                    })[0];
                    if (match) return match;
                }
            } catch (e) {}
        }

        try {
            var el = video.querySelector('track[default]');
            if (el && el.track) return el.track;
        } catch (e) {}

        return tracks[0] || null;
    }

    function onBeginFullscreen(video) {
        var debug = debugEnabled(video);

        // A single promotion at entry is not enough. Verified in Plyr's source:
        // captions.toggle() re-hides the current track from a setTimeout, and
        // captions.update() — bound to addtrack — demotes any "showing" track,
        // including manifest renditions that arrive AFTER fullscreen began. So
        // supervise the whole fullscreen window instead of firing once:
        //
        //   - if the wanted track drifted back to "hidden", that was Plyr
        //     (Apple's Off sets "disabled", not "hidden") → re-promote;
        //   - if the viewer picked another language in Apple's menu, adopt it;
        //   - if the viewer chose Off ("disabled"), respect it and stand down;
        //   - if captions are off in Plyr, do nothing at all.
        //
        // The interval dies on webkitendfullscreen.
        var supervise = function () {
            var player = video.plyr || null;
            var wanted = video._tiagsCaptionTrack || trackToShow(video, player);
            if (!wanted) return;

            var tracks = captionTracks(video);

            var chosen = tracks.filter(function (t) { return t.mode === 'showing'; })[0];
            if (chosen && chosen !== wanted) {
                // Apple's menu is live during fullscreen — the viewer switched.
                video._tiagsCaptionTrack = wanted = chosen;
                if (debug) log('adopted', wanted.language || wanted.label || '?');
            }

            if (wanted.mode === 'disabled') {
                // Off via Apple's menu. Their call; stop pushing.
                video._tiagsCaptionUserOff = true;
                return;
            }
            if (video._tiagsCaptionUserOff) return;

            if (wanted.mode !== 'showing') {
                video._tiagsCaptionTrack = wanted;
                tracks.forEach(function (track) {
                    track.mode = (track === wanted) ? 'showing' : 'hidden';
                });
                if (debug) log('promoted', wanted.language || wanted.label || '?');
            }
        };

        if (video._tiagsCaptionInterval) clearInterval(video._tiagsCaptionInterval);
        video._tiagsCaptionUserOff = false;
        supervise();
        video._tiagsCaptionInterval = setInterval(supervise, 300);

        if (debug) log('fullscreen in — supervising captions');
    }

    function onEndFullscreen(video) {
        var debug = debugEnabled(video);
        var player = video.plyr || null;
        var tracks = captionTracks(video);
        var previous = video._tiagsCaptionTrack || null;

        // Stop the fullscreen supervisor before touching modes, or it would
        // fight the restore below on its next tick.
        if (video._tiagsCaptionInterval) {
            clearInterval(video._tiagsCaptionInterval);
            video._tiagsCaptionInterval = null;
        }
        delete video._tiagsCaptionUserOff;

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
        // Films with in-manifest subtitle renditions are handled by the native
        // layer end to end: Plyr captions are disabled in their config and
        // AVPlayer applies DEFAULT/AUTOSELECT itself (proven with a bare
        // <video>). Touching track modes here would only reintroduce the fight
        // this script keeps losing. Sidecar-track films (the MP4/Videopack
        // path) still need the supervisor below.
        if (video.getAttribute('data-manifest-subs') === '1') return;

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
