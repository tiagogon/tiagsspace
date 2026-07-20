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

    function initNative(video, debug) {
        // The <source type="application/vnd.apple.mpegurl"> is already present;
        // Safari/iOS play it natively and handle adaptation. No quality menu.
        if (debug) log('engine: native HLS (Safari/iOS)');
        if (window.Plyr) {
            var player = new Plyr(video);
            video.plyr = player;
        }
    }

    function initHlsJs(video, src, debug) {
        if (debug) log('engine: hls.js', src);

        var hls = new Hls({
            capLevelToPlayerSize: true, // don't fetch levels larger than the display
            startLevel: -1              // let ABR pick the start level
        });

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
