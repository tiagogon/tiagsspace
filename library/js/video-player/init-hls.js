/**
 * HLS player init — Plyr + hls.js / native HLS.
 *
 * Initialises every `.film-player--hls` <video> on the page:
 *   - MSE available (Chrome/Edge/Firefox…) → hls.js drives playback and its ABR
 *     picks the rung. hls.js is preferred even though modern Chrome reports
 *     native HLS support, because native ignores capLevelToPlayerSize.
 *   - Safari / iOS (native `application/vnd.apple.mpegurl`, no MSE for this) →
 *     let the OS play the .m3u8 and adapt quality.
 *
 * Neither path offers a quality menu: choosing a rung is the player's job, not
 * the viewer's, and this way every browser behaves the same. Use ?video_debug=1
 * to see which rung ABR actually settled on.
 *
 * The rest of the player config (controls, captions, ratio, fullscreen) comes
 * from the element's data-plyr-config, emitted by library/video/player-hls.php.
 *
 * Debug: add ?video_debug=1 to the URL (or data-debug="1" on the <video>) to log
 * the chosen engine, level switches, bandwidth estimates and errors.
 *
 * @see library/video/player-hls.php
 * @see library/video/enqueues.php  (enqueued only for HLS films)
 */
(function () {
    'use strict';

    /**
     * Debug label for an hls.js level, as a 16:9-equivalent height.
     *
     * Rungs are encoded at the master's own aspect ratio (bin/hls-package.sh
     * fits the picture into a 16:9 box without padding), so a 2.39:1 film's
     * levels are 3840x1608, 1920x804… Labelling those by raw height would show
     * "1608p / 804p". Taking the taller of the real height and the height the
     * level's width implies at 16:9 gives back the familiar 2160 / 1080, and is
     * a no-op for 16:9 films.
     */
    function nominalHeight(level) {
        return Math.max(level.height || 0, Math.round((level.width || 0) * 9 / 16));
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
            args.unshift('[hls]');
            console.log.apply(console, args);
        } catch (e) {}
    }

    /**
     * On films with in-manifest subtitle renditions (data-manifest-subs="1"),
     * the server-emitted data-plyr-config ships with Plyr captions fully
     * disabled: on the native HLS path (iPhone) AVPlayer must own caption
     * selection — a bare <video> with this manifest renders subtitles in native
     * fullscreen, and every Plyr-managed variant we tried did not, because
     * Plyr's captions.update() demotes manifest tracks to hidden.
     *
     * On the hls.js path, Plyr's caption manager is proven to work against
     * manifest renditions, so this helper re-enables it. It must rewrite the
     * data-plyr-config ATTRIBUTE (not pass constructor options): in the
     * vendored Plyr build the merge order is defaults → options → attribute,
     * so the attribute always wins.
     */
    function reenablePlyrCaptions(video) {
        if (video.getAttribute('data-manifest-subs') !== '1') return;
        try {
            var config = JSON.parse(video.getAttribute('data-plyr-config') || '{}');
            config.captions = { active: true, language: 'auto', update: true };
            config.settings = ['captions'];
            if (Array.isArray(config.controls) && config.controls.indexOf('captions') === -1) {
                var at = config.controls.indexOf('fullscreen');
                if (at === -1) at = config.controls.length;
                config.controls.splice(at, 0, 'captions', 'settings');
            }
            video.setAttribute('data-plyr-config', JSON.stringify(config));
        } catch (e) {}
    }

    function initNative(video, debug) {
        // The <source type="application/vnd.apple.mpegurl"> is already present;
        // Safari/iOS play it natively and handle adaptation. No quality menu.
        //
        // Manifest-subs films skip Plyr COMPLETELY on this path and use Apple's
        // player, inline included. This is a deliberate trade, decided after a
        // controlled test: a bare <video> with the same manifest renders
        // subtitles in native fullscreen, and every Plyr-wrapped variant —
        // captions managed, supervised, or fully disabled — did not. Captions
        // for iPhone viewers beat the Plyr skin. iPhones reach here via the
        // explicit override in initOne() (modern iOS has ManagedMediaSource, so
        // Hls.isSupported() is true there and would otherwise steal this path);
        // iPad reports as Macintosh and keeps hls.js + the Plyr UI.
        if (video.getAttribute('data-manifest-subs') === '1') {
            if (debug) log('engine: native HLS, native player (manifest subs)');
            video.classList.add('film-player--native');
            video.controls = true;

            // Fullscreen on play, natively. fullscreen-on-play.js cannot help
            // here: it delegates on [data-plyr="play"] inside a .plyr container,
            // and neither exists once Plyr is skipped. Dropping playsinline is
            // the native equivalent — iOS then enters fullscreen by itself when
            // playback starts, with no script and so no user-activation problem.
            // Ambient films keep playing inline: seizing the screen is exactly
            // what fullscreen-on-play.js excludes them from.
            if (!video.hasAttribute('loop') && !video.hasAttribute('autoplay')) {
                video.removeAttribute('playsinline');
                video.removeAttribute('webkit-playsinline');
                if (debug) log('playsinline removed — iOS will fullscreen on play');
            }
            return;
        }

        if (debug) log('engine: native HLS (Safari/iOS)');
        if (window.Plyr) {
            var player = new Plyr(video);
            video.plyr = player;
        }
    }

    /**
     * Keep hls.js's selected subtitle rendition equal to Plyr's caption choice.
     *
     * Plyr and hls.js disagree about what text-track modes mean. Plyr never
     * writes "showing": it sets the track it renders to "hidden" and paints
     * the cues itself. hls.js listens to textTracks "change", re-derives its
     * selection from the modes (first "showing" wins, else the LAST "hidden"),
     * then writes modes back: its pick becomes "showing"/"hidden" and every
     * other track "disabled". Left to guess, it can pick a different rendition
     * than Plyr did — the browser then paints one language natively while
     * Plyr's overlay freezes on a track that no longer receives cues. Telling
     * hls.js explicitly which rendition Plyr wants makes its mode writes agree
     * with Plyr's, and the feedback loop settles at once.
     */
    function bindSubtitleSync(player, hls, debug) {
        function findRendition(node) {
            var tracks = hls.subtitleTracks || [];
            var lang = (node.language || '').toLowerCase();
            var label = node.label || '';
            var byLang = [];
            for (var i = 0; i < tracks.length; i++) {
                if ((tracks[i].lang || '').toLowerCase() === lang) byLang.push(i);
            }
            if (byLang.length > 1) {
                for (var j = 0; j < byLang.length; j++) {
                    if ((tracks[byLang[j]].name || '') === label) return byLang[j];
                }
            }
            return byLang.length ? byLang[0] : -1;
        }

        function sync() {
            if (!hls.subtitleTracks || !hls.subtitleTracks.length) return;
            var captions = player.captions || {};
            var on = captions.active && captions.toggled !== false;
            var node = on ? captions.currentTrackNode : null;
            var wanted = node ? findRendition(node) : -1;
            if (node && wanted === -1) {
                if (debug) log('subtitle sync: no hls.js rendition for', node.language, node.label);
                return;
            }
            if (hls.subtitleTrack !== wanted) {
                hls.subtitleTrack = wanted;
                if (debug) log('subtitle sync →', node ? node.language : 'off', 'hls.js track', wanted);
            }
        }

        player.on('languagechange', sync);
        player.on('captionsenabled', sync);
        player.on('captionsdisabled', sync);
        hls.on(Hls.Events.SUBTITLE_TRACKS_UPDATED, sync);
        sync();
    }

    function initHlsJs(video, src, debug) {
        if (debug) log('engine: hls.js', src);

        reenablePlyrCaptions(video);

        var hls = new Hls({
            capLevelToPlayerSize: true, // don't fetch levels larger than the display
            startLevel: -1              // let ABR pick the start level
        });

        // Plyr paints cues in its own overlay; hls.js must only feed them.
        // This is an instance property, not a constructor option. Left at its
        // default (true), hls.js sets its selected subtitle track to mode
        // "showing" and the browser's native cue renderer paints it as well —
        // invisible in WebKit/Blink (Plyr hides ::-webkit-media-text-track-
        // container) but drawn on top of Plyr's overlay in Firefox.
        hls.subtitleDisplay = false;

        hls.loadSource(src);

        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            if (debug) log('levels', hls.levels.map(function (level) {
                return nominalHeight(level) + 'p (' + level.width + 'x' + level.height + ')';
            }));

            // No quality options: ABR owns the choice. Controls come from
            // data-plyr-config.
            var player = new Plyr(video);

            player.hls = hls;
            video.plyr = player;

            bindSubtitleSync(player, hls, debug);

            // ABR's switches are invisible in the UI by design — this log is the
            // only way to see them.
            hls.on(Hls.Events.LEVEL_SWITCHED, function (event, data) {
                if (debug) {
                    var lvl = hls.levels[data.level];
                    log('level switched →',
                        lvl ? nominalHeight(lvl) + 'p (' + lvl.width + 'x' + lvl.height + ')' : data.level,
                        'bw≈', Math.round(hls.bandwidthEstimate / 1000) + 'kbps');
                }
            });
        });

        hls.on(Hls.Events.ERROR, function (event, data) {
            if (debug) log('error', data.type, data.details, data.fatal ? '(fatal)' : '');
            if (data.fatal) {
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        hls.startLoad(); // retry
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hls.recoverMediaError();
                        break;
                    default:
                        hls.destroy();
                        break;
                }
            }
        });

        hls.attachMedia(video);

        // Tear down hls.js when Plyr is destroyed.
        video.addEventListener('plyr:destroy', function () {
            try { hls.destroy(); } catch (e) {}
        });
    }

    function initOne(video) {
        if (video._hlsInited) return;
        video._hlsInited = true;

        var debug = debugEnabled(video);
        var src = video.getAttribute('data-hls-src');
        if (!src) {
            var source = video.querySelector('source[type="application/vnd.apple.mpegurl"]');
            src = source ? source.getAttribute('src') : '';
        }
        if (!src) {
            if (debug) log('no HLS source found on element');
            return;
        }

        // iPhone + manifest subtitles: native HLS MUST win over hls.js.
        // iOS 17.1+ ships ManagedMediaSource, so Hls.isSupported() is true on
        // modern iPhones and the MSE branch below would grab them — but
        // subtitle tracks created in JS (hls.js's route) are listed yet never
        // painted by AVPlayer in native fullscreen. Only true native playback
        // (src=m3u8, AVPlayer reads the manifest itself) renders the in-manifest
        // renditions — proven with a bare <video> on device. iPad reports as
        // Macintosh and stays on hls.js + Plyr, where captions work.
        if (video.getAttribute('data-manifest-subs') === '1'
            && /iPhone|iPod/i.test(navigator.userAgent)
            && video.canPlayType('application/vnd.apple.mpegurl')) {
            initNative(video, debug);
            return;
        }

        // Prefer hls.js wherever MSE is available. Modern Chrome also reports
        // native HLS support ("maybe"), but its native path ignores
        // capLevelToPlayerSize, so it will happily pull a 4K rung into a small
        // player. hls.js caps to the display size instead.
        if (window.Hls && Hls.isSupported()) {
            initHlsJs(video, src, debug);
            return;
        }

        // Safari/iOS: hls.js's MSE path is unavailable/restricted there, and
        // native HLS is genuinely better (battery, OS-level ABR).
        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            initNative(video, debug);
            return;
        }

        // No HLS support at all — fall back to whatever the browser can do with
        // the <source>, wrapped in Plyr so the UI is consistent.
        if (debug) log('engine: none (no native, no hls.js) — falling back');
        if (window.Plyr) {
            video.plyr = new Plyr(video);
        }
    }

    function initAll() {
        var players = document.querySelectorAll('video.film-player--hls');
        Array.prototype.forEach.call(players, initOne);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
