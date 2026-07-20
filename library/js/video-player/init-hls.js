/**
 * HLS player init — Plyr + hls.js / native HLS.
 *
 * Initialises every `.film-player--hls` <video> on the page:
 *   - MSE available (Chrome/Edge/Firefox…) → hls.js drives playback; Plyr's
 *     quality menu is wired to hls.js levels with an "Auto" default that
 *     follows the network. hls.js is preferred even though modern Chrome
 *     reports native HLS support — native gives no quality menu and ignores
 *     capLevelToPlayerSize.
 *   - Safari / iOS (native `application/vnd.apple.mpegurl`, no MSE for this) →
 *     let the OS play the .m3u8 and adapt quality. No quality menu (the OS
 *     decides).
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

    var AUTO = 0; // Plyr quality value we reserve for "Auto"

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
            // Build Plyr quality options from the ladder heights, tallest first,
            // with Auto (0) at the front as the default.
            var heights = hls.levels
                .map(function (l) { return l.height; })
                .filter(function (h, i, a) { return h && a.indexOf(h) === i; })
                .sort(function (a, b) { return b - a; });

            var options = [AUTO].concat(heights);

            if (debug) log('levels', heights);

            var player = new Plyr(video, {
                quality: {
                    default: AUTO,
                    options: options,
                    forced: true,
                    onChange: function (quality) {
                        setQuality(hls, quality, debug);
                    }
                },
                i18n: { qualityLabel: { 0: 'Auto' } }
            });

            player.hls = hls;
            video.plyr = player;

            // Reflect ABR's automatic switches in the menu label while on Auto.
            hls.on(Hls.Events.LEVEL_SWITCHED, function (event, data) {
                if (debug) {
                    var lvl = hls.levels[data.level];
                    log('level switched →', lvl ? lvl.height + 'p' : data.level,
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

    function setQuality(hls, quality, debug) {
        if (quality === AUTO) {
            hls.currentLevel = -1; // back to ABR
            if (debug) log('quality → Auto');
            return;
        }
        for (var i = 0; i < hls.levels.length; i++) {
            if (hls.levels[i].height === quality) {
                hls.currentLevel = i;
                if (debug) log('quality → ' + quality + 'p (manual)');
                return;
            }
        }
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
        // native HLS support ("maybe"), but its native path gives Plyr no
        // quality menu and ignores capLevelToPlayerSize — hls.js keeps the UI
        // consistent across Chrome/Edge/Firefox.
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
