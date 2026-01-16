/**
 * Plyr Adaptive Quality System
 * 
 * This script augments Plyr with:
 *  - Adaptive selection based on displayed element height × device pixel ratio
 *  - A visible "Auto" quality option which re-enables adaptive mode
 *  - Robust native <video> source swaps to preserve playback position
 *  - Conservative auto-downgrade when Auto stalls on slow networks
 *
 * Runtime assumptions:
 *  - Server supports range requests (206) for seeking
 *  - Sources include a `size` attribute indicating vertical resolution (e.g., 2160, 1080, 720)
 *  - Plyr is available globally and attaches instances via `el.plyr` or `Plyr.get(el)`
 */

document.addEventListener('DOMContentLoaded', () => {
    // Plyr configuration for self-hosted videos
    const plyrOptions = {
        fullscreen: { enabled: true, fallback: true, iosNative: true, container: null },
        captions: {
            active: true,
            language: 'auto',
            update: true
        }
    };

    const playerEls = Array.from(document.querySelectorAll('.film-player'));

    // Debug helper: enable by adding `data-debug="1"` to the <video> element
    function isDebugEnabled(videoEl) {
        try {
            if (!videoEl) return false;
            if (videoEl._debug) return true;
            const a = videoEl.getAttribute && videoEl.getAttribute('data-debug');
            return a === '1' || a === 'true';
        } catch (e) { return false; }
    }

    function debugLog(videoEl, ...args) {
        try {
            const v = videoEl && videoEl.elements ? (videoEl.elements.media || videoEl.elements.container && videoEl.elements.container.querySelector('video')) : videoEl;
            if (isDebugEnabled(v)) console.debug('[plyr-adaptive]', ...args);
        } catch (e) {}
    }

    // Initialize Plyr for each element
    playerEls.forEach(el => {
        if (!el.plyr) {
            try {
                el.plyr = new Plyr(el, plyrOptions);
                el._adaptiveEnabled = true;
                el._userSelectedQuality = false;
                el.plyr._adaptiveEnabled = true;
                el.plyr._userSelectedQuality = false;

                // Collect available sources once
                try {
                    const videoEl = el;
                    try { el._debug = (videoEl.getAttribute && (videoEl.getAttribute('data-debug') === '1' || videoEl.getAttribute('data-debug') === 'true')); } catch (e) {}
                    const initialSourceEls = Array.from(videoEl.querySelectorAll('source'));
                    const available = initialSourceEls.map(s => ({
                        src: s.getAttribute('src') || s.src,
                        type: s.getAttribute('type') || s.type || 'video/mp4',
                        size: parseInt(s.getAttribute('size') || s.getAttribute('data-size') || (s.getAttribute('label')||'').match(/(\d{3,4})/)?.[1] || 0, 10)
                    })).filter(s => s.src);
                    videoEl._availableSources = available;
                    el.plyr._availableSources = available;
                } catch (ee) {}

                // Wrap native play() for autoplay policy rejection logging
                try {
                    const videoEl = el;
                    if (videoEl && videoEl.play && !videoEl._playWrapped) {
                        const _origPlay = videoEl.play.bind(videoEl);
                        videoEl.play = function () {
                            try {
                                const p = _origPlay();
                                if (p && p.then) p.then(() => {}).catch(err => {
                                    try { debugLog(videoEl, 'play() rejected (wrapped)', err); } catch (e) {}
                                    try { console.warn('[plyr-adaptive] play() rejected (wrapped)', err); } catch (e) {}
                                });
                                return p;
                            } catch (err) {
                                try { debugLog(videoEl, 'play() threw', err); } catch (e) {}
                                throw err;
                            }
                        };
                        videoEl._playWrapped = true;
                    }

                    if (el.plyr && el.plyr.play && !el.plyr._playWrapped) {
                        const _origPlyrPlay = el.plyr.play.bind(el.plyr);
                        el.plyr.play = function () {
                            try {
                                const p = _origPlyrPlay();
                                if (p && p.then) p.then(() => {}).catch(err => {
                                    try { debugLog(videoEl, 'plyr.play() rejected (wrapped)', err); } catch (e) {}
                                    try { console.warn('[plyr-adaptive] plyr.play() rejected (wrapped)', err); } catch (e) {}
                                });
                                return p;
                            } catch (err) {
                                try { debugLog(videoEl, 'plyr.play() threw', err); } catch (e) {}
                                throw err;
                            }
                        };
                        el.plyr._playWrapped = true;
                    }
                } catch (e) {}

                // Add "Auto" option to quality menu
                try {
                    const setupQualityAutoOption = function (player) {
                        if (!player || !player.elements || !player.elements.container) return;
                        const container = player.elements.container;
                        const firstBtn = container.querySelector('button[data-plyr="quality"]');
                        if (!firstBtn) return;
                        const parent = firstBtn.parentElement;
                        if (!parent) return;
                        if (parent.querySelector('button[data-value="auto"]')) return;
                        const autoBtn = document.createElement('button');
                        autoBtn.type = 'button';
                        autoBtn.className = firstBtn.className;
                        autoBtn.setAttribute('data-plyr', 'quality');
                        autoBtn.setAttribute('value', 'auto');
                        autoBtn.setAttribute('data-value', 'auto');
                        autoBtn.textContent = 'Auto';
                        parent.insertBefore(autoBtn, parent.firstChild);

                        try {
                            Array.from(parent.querySelectorAll('button[data-plyr="quality"]')).forEach(b => b.classList.remove('is-active'));
                            autoBtn.classList.add('is-active');
                        } catch (e) {}
                    };
                    setTimeout(() => setupQualityAutoOption(el.plyr), 120);
                } catch (e) {}

                // Attach media event logging
                try {
                    const videoEl = el;
                    const mediaLog = function (name, info) {
                        try {
                            if (isDebugEnabled(videoEl)) {
                                try { debugLog(videoEl, 'media:' + name, info); } catch (e) {}
                                try { console.debug('[plyr-adaptive] media:' + name, info); } catch (e) {}
                            }
                        } catch (e) {}
                    };

                    try { videoEl.addEventListener('play', () => mediaLog('play', { currentTime: videoEl.currentTime })); } catch (e) {}
                    try { videoEl.addEventListener('playing', () => mediaLog('playing', { currentTime: videoEl.currentTime })); } catch (e) {}
                    try { videoEl.addEventListener('pause', () => mediaLog('pause', { currentTime: videoEl.currentTime })); } catch (e) {}
                    try { videoEl.addEventListener('loadedmetadata', () => mediaLog('loadedmetadata', { duration: videoEl.duration })); } catch (e) {}
                    try { videoEl.addEventListener('canplay', () => mediaLog('canplay', { readyState: videoEl.readyState })); } catch (e) {}
                    try { videoEl.addEventListener('waiting', () => mediaLog('waiting', { currentTime: videoEl.currentTime })); } catch (e) {}
                    try { videoEl.addEventListener('error', () => { const err = videoEl.error || {}; mediaLog('error', { code: err.code, message: err && err.message ? err.message : String(err) }); console.warn('[plyr-adaptive] media error', err); }); } catch (e) {}
                    try { mediaLog('init', { src: (videoEl.currentSrc || videoEl.src) }); } catch (e) {}
                } catch (e) {}

                try { attachInteractionLogging(el.plyr); } catch (e) {}
            } catch (e) {}
        }
    });

    // --- Adaptive Quality Logic ---

    function debounce(fn, wait) {
        let t;
        return function () {
            const args = arguments;
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    // Choose best quality from available sizes
    function chooseQuality(availableSizes, neededHeightPx) {
        for (let i = availableSizes.length - 1; i >= 0; i--) {
            if (availableSizes[i] <= neededHeightPx) return availableSizes[i];
        }
        return availableSizes[0] || null;
    }

    // Sync UI active state for quality buttons
    function syncQualityButtons(player, activeValue) {
        try {
            if (!player || !player.elements || !player.elements.container) return;
            const container = player.elements.container;
            let anyBtn = container.querySelector('button[data-plyr="quality"]');
            const parentEl = anyBtn ? anyBtn.parentElement : null;
            const scope = parentEl || container;
            const buttons = Array.from(scope.querySelectorAll('button[data-plyr="quality"]'));
            buttons.forEach(b => {
                try { b.classList.remove('is-active'); } catch (e) {}
                try { if (!b.hasAttribute('role')) b.setAttribute('role', 'menuitemradio'); } catch (e) {}
                const val = (b.getAttribute('value') || b.getAttribute('data-value') || '').toString();
                const isActive = (activeValue === 'auto' && val === 'auto') || (val !== 'auto' && Number(val) === Number(activeValue));
                try { if (isActive) { b.classList.add('is-active'); b.setAttribute('aria-checked', 'true'); } else { b.classList.remove('is-active'); b.setAttribute('aria-checked', 'false'); } } catch (e) {}
            });
        } catch (e) {}
    }

    // Rebuild source elements from cache
    function rebuildSourcesFromAvailable(videoEl) {
        try {
            if (!videoEl || !videoEl._availableSources) return;
            const avail = Array.isArray(videoEl._availableSources) ? videoEl._availableSources : [];
            try {
                const existing = Array.from(videoEl.querySelectorAll('source'));
                existing.forEach(s => s.parentNode && s.parentNode.removeChild(s));
            } catch (e) {}
            avail.forEach(src => {
                try {
                    const s = document.createElement('source');
                    s.setAttribute('src', src.src);
                    if (src.type) s.setAttribute('type', src.type);
                    if (src.size) s.setAttribute('size', src.size);
                    videoEl.appendChild(s);
                } catch (e) {}
            });
        } catch (e) {}
    }

    // Attach user interaction logging
    function attachInteractionLogging(player) {
        try {
            if (!player || !player.elements) return;
            const container = player.elements.container;
            const videoEl = player.elements.media || container && container.querySelector('video');
            if (!container || !videoEl) return;

            container.addEventListener('click', function (ev) {
                try {
                    const target = ev.target;
                    const tag = (target && target.tagName) ? target.tagName.toLowerCase() : '';
                    const classes = target && target.className ? ('' + target.className) : '';
                    try { debugLog(videoEl, 'userClick', { tag: tag, classes: classes }); } catch (e) {}

                    const progressEl = target.closest && target.closest('.plyr__progress');
                    const rangeEl = (target && target.closest) ? target.closest('input[type="range"]') : null;
                    if (progressEl || rangeEl) {
                        try { videoEl._suppressAutoAfterUserSeek = Date.now(); } catch (e) {}
                        try { debugLog(videoEl, 'userClick:progress', { classes: classes }); } catch (e) {}
                        const startTime = videoEl.currentTime || 0;
                        let sawProgress = false;
                        const startBufEnd = (videoEl.buffered && videoEl.buffered.length) ? videoEl.buffered.end(videoEl.buffered.length - 1) : 0;

                        const onTempTime = function () {
                            try {
                                const now = videoEl.currentTime || 0;
                                const bufEnd = (videoEl.buffered && videoEl.buffered.length) ? videoEl.buffered.end(videoEl.buffered.length - 1) : 0;
                                if (Math.abs(now - startTime) > 0.09 || (bufEnd - startBufEnd) > 0.05) {
                                    sawProgress = true;
                                    try { videoEl._suppressAutoAfterUserSeek = Date.now(); } catch (e) {}
                                    try { debugLog(videoEl, 'userClick:progress detected', { startTime: startTime, now: now, bufDiff: (bufEnd - startBufEnd) }); } catch (e) {}
                                    cleanup();
                                }
                            } catch (e) {}
                        };

                        const cleanup = function () {
                            try { videoEl.removeEventListener('timeupdate', onTempTime); } catch (e) {}
                            try { videoEl.removeEventListener('progress', onTempTime); } catch (e) {}
                            try { clearTimeout(tmpTimer); } catch (e) {}
                        };

                        const tmpTimer = setTimeout(() => {
                            try {
                                if (!sawProgress) {
                                    const now = videoEl.currentTime || 0;
                                    const bufEnd = (videoEl.buffered && videoEl.buffered.length) ? videoEl.buffered.end(videoEl.buffered.length - 1) : 0;
                                    try { videoEl._suppressAutoAfterUserSeek = Date.now(); } catch (e) {}
                                    try { console.warn('[plyr-adaptive] user click produced no progress', { startTime: startTime, now: now, bufferAhead: Math.max(0, bufEnd - now) }); } catch (e) {}
                                    try { debugLog(videoEl, 'userClick:no-progress', { startTime: startTime, now: now, bufferAhead: Math.max(0, bufEnd - now) }); } catch (e) {}
                                }
                            } catch (e) {}
                            cleanup();
                        }, 900);

                        try { videoEl.addEventListener('timeupdate', onTempTime); } catch (e) {}
                        try { videoEl.addEventListener('progress', onTempTime); } catch (e) {}
                    }
                    const playBtn = target.closest && target.closest('[data-plyr="play"]');
                    if (playBtn || (target && (target.className || '').indexOf('plyr__control--play') !== -1)) {
                        try { debugLog(videoEl, 'userClick:playbutton', { classes: classes }); } catch (e) {}
                    }
                } catch (e) {}
            }, true);
        } catch (e) {}
    }

    // Compute and apply adaptive quality
    function adaptQualityForElement(el) {
        if (!el) return;
        const player = el.plyr || (window.Plyr && Plyr.get ? Plyr.get(el) : null);
        if (!player) return;

        const videoEl = player.elements && player.elements.media ? player.elements.media : el;
        if (!videoEl) return;

        const sourceEls = Array.from(videoEl.querySelectorAll('source[size]'));
        let sizes = sourceEls.map(s => parseInt(s.getAttribute('size'), 10)).filter(n => !Number.isNaN(n));

        if (sizes.length === 0) {
            sizes = Array.from(videoEl.querySelectorAll('source')).map(s => {
                const ds = s.getAttribute('data-size') || s.getAttribute('data-label') || s.getAttribute('label') || '';
                const m = (ds + '').match(/(\d{3,4})/);
                return m ? parseInt(m[1], 10) : null;
            }).filter(n => n);
        }

        if (sizes.length === 0) return;
        sizes = sizes.sort((a, b) => a - b);

        let displayHeightCss = videoEl.clientHeight || videoEl.getBoundingClientRect().height || 0;
        if (!displayHeightCss) {
            const rect = player.elements && player.elements.container && player.elements.container.getBoundingClientRect();
            displayHeightCss = (rect && rect.height) || 0;
        }
        if (!displayHeightCss) return;

        const dpr = window.devicePixelRatio || 1;
        const neededHeightPx = Math.ceil(displayHeightCss * dpr);

        try { if (isDebugEnabled(videoEl)) debugLog(videoEl, 'adaptQualityForElement', { displayHeightCss, dpr, neededHeightPx, sizes }); } catch (e) {}

        if (el._userSelectedQuality || el._adaptiveEnabled === false || (player && (player._userSelectedQuality || player._adaptiveEnabled === false))) return;

        try {
            const lastAuto = (videoEl && (videoEl._lastAutoDowngrade || 0)) || (player && (player._lastAutoDowngrade || 0)) || 0;
            const ADAPT_SUPPRESS_AFTER_AUTO_MS = 15000;
            if (lastAuto && (Date.now() - lastAuto) < ADAPT_SUPPRESS_AFTER_AUTO_MS) {
                try { debugLog(videoEl, 'adaptQualityForElement suppressed after recent auto downgrade', { lastAutoAge: Date.now() - lastAuto }); } catch (e) {}
                return;
            }
        } catch (e) {}

        const desired = chooseQuality(sizes, neededHeightPx);
        if (!desired) return;

        try {
            try {
                const lastAuto = (videoEl && (videoEl._lastAutoDowngrade || 0)) || (player && (player._lastAutoDowngrade || 0)) || 0;
                const AUTO_UPGRADE_COOLDOWN_MS = 5000;
                const AUTO_UPGRADE_STABLE_BUFFER_SEC = 3;
                const recordedCur = Number(videoEl._currentQuality || player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : NaN));

                if (lastAuto && (Date.now() - lastAuto) < AUTO_UPGRADE_COOLDOWN_MS && Number.isFinite(recordedCur) && desired > recordedCur) {
                    let bufEnd = 0;
                    try {
                        if (videoEl.buffered && videoEl.buffered.length) bufEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
                    } catch (e) { bufEnd = 0; }
                    const now = (videoEl.currentTime || 0);
                    const bufferAhead = (bufEnd - now);

                    if (bufferAhead < AUTO_UPGRADE_STABLE_BUFFER_SEC) {
                        try { debugLog(videoEl, 'suppress upgrade until stable buffer', { desired, recordedCur, lastAutoAge: Date.now() - lastAuto, bufferAhead }); } catch (e) {}
                        return;
                    }
                }
            } catch (e) {}

            const current = player.quality;
            if (current !== desired) {
                player.quality = desired;
                try { player._currentQuality = desired; } catch (e) {}
                try { if (el) { el._currentQuality = desired; } } catch (e) {}
                syncQualityButtons(player, 'auto');
                try { debugLog(videoEl, 'adaptive applied', { desired, current }); } catch (e) {}
            }
        } catch (err) {}
    }

    // Change player source to desired quality
    function changeQualityForPlayer(player, desiredSize, options) {
        if (!player || !player.elements) return;
        const videoEl = player.elements.media || player.elements.container && player.elements.container.querySelector('video');
        if (!videoEl) return;

        try { debugLog(player, 'changeQualityForPlayer:start', { desiredSize: desiredSize, options: options, currentTime: videoEl.currentTime, paused: videoEl.paused }); } catch (e) {}

        options = options || {};
        const sources = (videoEl._availableSources && Array.isArray(videoEl._availableSources) && videoEl._availableSources.slice())
            || Array.from(videoEl.querySelectorAll('source')).map(s => ({
                src: s.getAttribute('src') || s.src,
                type: s.getAttribute('type') || s.type || 'video/mp4',
                size: parseInt(s.getAttribute('size') || s.getAttribute('data-size') || (s.getAttribute('label')||'').match(/(\d{3,4})/)?.[1] || 0, 10)
            })).filter(s => s.src);

        const target = sources.find(s => s.size === Number(desiredSize));
        if (!target) return;

        const wasPlaying = !videoEl.paused;
        const currentTime = videoEl.currentTime || 0;
        const wasMuted = videoEl.muted;
        const playbackRate = videoEl.playbackRate || 1;

        let playRequestedDuringRestore = false;
        const onPlayAttempt = function () {
            try {
                if (videoEl._isRestoring) {
                    try { videoEl.pause(); } catch (e) {}
                    playRequestedDuringRestore = true;
                }
            } catch (e) {}
        };
        try { videoEl._isRestoring = true; videoEl.addEventListener('play', onPlayAttempt, true); } catch (e) {}

        if (!options.auto) {
            videoEl._adaptiveEnabled = false;
            if (player) {
                player._adaptiveEnabled = false;
                player._userSelectedQuality = true;
            }
        }

        try {
            try { videoEl.pause(); } catch (e) {}

            if (options.keepAllSources) {
                try { rebuildSourcesFromAvailable(videoEl); } catch (e) {}
                try {
                    const found = Array.from(videoEl.querySelectorAll('source')).some(s => (s.getAttribute('src')||s.src) === target.src);
                    if (!found) {
                        const ns = document.createElement('source');
                        ns.setAttribute('src', target.src);
                        if (target.type) ns.setAttribute('type', target.type);
                        if (target.size) ns.setAttribute('size', target.size);
                        videoEl.appendChild(ns);
                    }
                } catch (e) {}
            } else {
                try {
                    const existing = Array.from(videoEl.querySelectorAll('source'));
                    existing.forEach(s => s.parentNode && s.parentNode.removeChild(s));
                } catch (e) {}
                try {
                    const newSource = document.createElement('source');
                    newSource.setAttribute('src', target.src);
                    if (target.type) newSource.setAttribute('type', target.type);
                    if (target.size) newSource.setAttribute('size', target.size);
                    videoEl.appendChild(newSource);
                } catch (e) {}
            }

            videoEl.src = target.src;
            videoEl.load();

            try { videoEl._currentQuality = target.size || desiredSize; } catch (e) {}
            try { if (player) player._currentQuality = target.size || desiredSize; } catch (e) {}

            try { if (!options.auto) syncQualityButtons(player, target.size || desiredSize); else syncQualityButtons(player, 'auto'); } catch (e) {}

            const onMeta = function () {
                const desiredTime = Math.min(currentTime || 0, (videoEl.duration && isFinite(videoEl.duration)) ? videoEl.duration : currentTime || 0);
                let attempts = 0;
                const maxAttempts = options && options.auto ? 80 : 24;
                const retryDelay = 250;

                function finishRestore() {
                    try { videoEl.muted = wasMuted; } catch (e) {}
                    try { videoEl.playbackRate = playbackRate; } catch (e) {}

                    function getBufferAhead() {
                        try {
                            if (videoEl.buffered && videoEl.buffered.length) {
                                const end = videoEl.buffered.end(videoEl.buffered.length - 1);
                                return Math.max(0, end - (videoEl.currentTime || 0));
                            }
                        } catch (e) {}
                        return 0;
                    }

                    const RESUME_BUFFER_SEC = 1.2;
                    let resumeCleanupTimer = null;

                    function cleanupResumeWatchers() {
                        try { videoEl.removeEventListener('progress', resumeOnBuffer); } catch (e) {}
                        try { videoEl.removeEventListener('canplay', resumeOnBuffer); } catch (e) {}
                        if (resumeCleanupTimer) { clearTimeout(resumeCleanupTimer); resumeCleanupTimer = null; }
                        try { videoEl.removeEventListener('play', onPlayAttempt, true); } catch (e) {}
                        try { videoEl._isRestoring = false; } catch (e) {}
                    }

                    function tryPlayOnce() {
                        try {
                            const p = player.play && typeof player.play === 'function' ? player.play() : (videoEl.play && videoEl.play());
                            if (p && p.then) p.then(() => {}).catch(err => {
                                try { debugLog(videoEl, 'play() rejected', err); } catch (e) {}
                                try { console.warn('[plyr-adaptive] play() rejected', err); } catch (e) {}
                            });
                        } catch (e) {}
                    }

                    function resumeOnBuffer() {
                        try {
                            const bufferAhead = getBufferAhead();
                            try { debugLog(videoEl, 'resumeOnBuffer', { bufferAhead: bufferAhead }); } catch (e) {}
                            if (bufferAhead >= RESUME_BUFFER_SEC) {
                                tryPlayOnce();
                                cleanupResumeWatchers();
                            }
                        } catch (e) {}
                    }

                    if (wasPlaying) {
                        try {
                            const bufferAhead = getBufferAhead();
                            try { if (isDebugEnabled(videoEl)) debugLog(videoEl, 'finishRestore bufferAhead', { bufferAhead: bufferAhead }); } catch (e) {}
                            if (bufferAhead >= RESUME_BUFFER_SEC) {
                                tryPlayOnce();
                            } else {
                                resumeCleanupTimer = setTimeout(() => { tryPlayOnce(); cleanupResumeWatchers(); }, 5000);
                                try { videoEl.addEventListener('progress', resumeOnBuffer); } catch (e) {}
                                try { videoEl.addEventListener('canplay', resumeOnBuffer); } catch (e) {}
                            }
                        } catch (e) {
                            tryPlayOnce();
                        }
                    }
                        
                    try {
                        if (playRequestedDuringRestore) {
                            try { debugLog(videoEl, 'honoring playRequestedDuringRestore'); } catch (e) {}
                            tryPlayOnce();
                            playRequestedDuringRestore = false;
                        }
                    } catch (e) {}

                    try { debugLog(videoEl, 'finishRestore', { attempts: attempts, desiredTime: desiredTime, currentTime: videoEl.currentTime }); } catch (e) {}

                    try { videoEl.removeEventListener('loadedmetadata', onMeta); } catch (e) {}
                    try { videoEl.removeEventListener('canplay', onCanPlay); } catch (e) {}
                    try { videoEl.removeEventListener('seeked', onSeeked); } catch (e) {}
                    try { videoEl.removeEventListener('progress', onProgress); } catch (e) {}
                    try { videoEl.removeEventListener('canplaythrough', onCanPlayThrough); } catch (e) {}
                    try { videoEl._isRestoring = false; } catch (e) {}
                    try { videoEl.removeEventListener('play', onPlayAttempt, true); } catch (e) {}
                }

                function canSeekTo(time) {
                    try {
                        if (videoEl.seekable && videoEl.seekable.length) {
                            const end = videoEl.seekable.end(videoEl.seekable.length - 1);
                            if (time <= end + 0.5) return true;
                        }
                        if (videoEl.buffered && videoEl.buffered.length) {
                            const bufEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
                            if (time <= bufEnd + 0.5) return true;
                        }
                    } catch (e) {}
                    return false;
                }

                function trySetTime() {
                    attempts++;
                    try {
                        const canSeek = canSeekTo(desiredTime);
                        try { if (isDebugEnabled(videoEl)) debugLog(videoEl, 'trySetTime', { attempts: attempts, canSeek: canSeek, desiredTime: desiredTime, currentTime: videoEl.currentTime }); } catch (e) {}
                        if (canSeek || attempts === 1) {
                            try { videoEl.currentTime = desiredTime; } catch (err) {}
                        }
                    } catch (err) {}

                    const diff = Math.abs((videoEl.currentTime || 0) - desiredTime);
                    if (diff <= 0.5 || attempts >= maxAttempts) {
                        finishRestore();
                    } else {
                        setTimeout(trySetTime, retryDelay);
                    }
                }

                const onProgress = function () {
                    trySetTime();
                };
                const onCanPlayThrough = function () {
                    trySetTime();
                };

                videoEl.addEventListener('progress', onProgress);
                videoEl.addEventListener('canplaythrough', onCanPlayThrough);

                const onSeeked = function () {
                    finishRestore();
                };

                const onCanPlay = function () {
                    trySetTime();
                };

                videoEl.addEventListener('seeked', onSeeked);
                videoEl.addEventListener('canplay', onCanPlay);

                trySetTime();
            };

            videoEl.addEventListener('loadedmetadata', onMeta);
        } catch (err) {}
    }

    // Listen for quality button clicks
    document.addEventListener('click', function (ev) {
        const btn = ev.target.closest && ev.target.closest('[data-plyr="quality"]');
        if (!btn) return;
        try {
            ev.preventDefault();
        } catch (e) {}
        try { ev.stopPropagation(); } catch (e) {}
        try { ev.stopImmediatePropagation(); } catch (e) {}

        const container = btn.closest && btn.closest('.plyr');
        if (!container) return;
        const videoEl = container.querySelector && (container.querySelector('video') || container.querySelector('audio'));
        if (!videoEl) return;
        const player = videoEl.plyr || (window.Plyr && Plyr.get ? Plyr.get(videoEl) : null);
        if (!player) return;

        const raw = (btn.getAttribute('value') || btn.value || btn.getAttribute('data-value') || '').toString();
        if (!raw) return;

        if (raw === 'auto') {
            videoEl._userSelectedQuality = false;
            videoEl._adaptiveEnabled = true;
            if (player) {
                player._userSelectedQuality = false;
                player._adaptiveEnabled = true;
            }

            try { rebuildSourcesFromAvailable(videoEl); } catch (e) {}

            try {
                const avail = (videoEl._availableSources || []).map(s => Number(s.size)).filter(n => !!n).sort((a,b)=>a-b);
                if (avail.length) {
                    let displayHeightCss = videoEl.clientHeight || videoEl.getBoundingClientRect().height || 0;
                    if (!displayHeightCss) {
                        const rect = player.elements && player.elements.container && player.elements.container.getBoundingClientRect();
                        displayHeightCss = (rect && rect.height) || 0;
                    }
                    const needed = Math.ceil((window.devicePixelRatio || 1) * (displayHeightCss || 0));
                    const desiredAuto = chooseQuality(avail, needed);
                    if (desiredAuto) {
                        changeQualityForPlayer(player, desiredAuto, { keepAllSources: true, auto: true });
                    }
                }
            } catch (e) {}

            try { syncQualityButtons(player, 'auto'); } catch (e) {}

            return;
        }

        const desired = parseInt(raw, 10);
        if (Number.isNaN(desired)) return;

        videoEl._userSelectedQuality = true;
        videoEl._adaptiveEnabled = false;
        if (player) {
            player._userSelectedQuality = true;
            player._adaptiveEnabled = false;
        }

        if (player) changeQualityForPlayer(player, desired);
    }, true);

    // Handle settings menu
    document.addEventListener('click', function (ev) {
        const settingsBtn = ev.target.closest && ev.target.closest('[data-plyr="settings"]');
        if (!settingsBtn) return;
        const container = settingsBtn.closest && settingsBtn.closest('.plyr');
        if (!container) return;
        const videoEl = container.querySelector && (container.querySelector('video') || container.querySelector('audio'));
        const player = videoEl && (videoEl.plyr || (window.Plyr && Plyr.get ? Plyr.get(videoEl) : null));
        if (!player) return;
        setTimeout(() => {
            try {
                if (player._adaptiveEnabled && !player._userSelectedQuality) {
                    syncQualityButtons(player, 'auto');
                } else {
                    const cur = player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : 'auto');
                    syncQualityButtons(player, cur);
                }
            } catch (e) {}
        }, 60);
    });

    // Setup adaptive quality
    function wireUpAdaptiveQuality(selector) {
        const els = Array.from(document.querySelectorAll(selector));
        if (!els.length) return;

        els.forEach(el => {
            const run = () => adaptQualityForElement(el);
            const player = el.plyr || (window.Plyr && Plyr.get ? Plyr.get(el) : null);
            try {
                player && player.on && player.on('ready', run);
                player && player.on && player.on('loadedmetadata', run);
            } catch (e) {}
            run();
            setTimeout(run, 200);

            const deb = debounce(run, 150);
            window.addEventListener('resize', deb);
            document.addEventListener('fullscreenchange', deb);
            document.addEventListener('webkitfullscreenchange', deb);
            document.addEventListener('msfullscreenchange', deb);
            document.addEventListener('mozfullscreenchange', deb);
            if (player && player.on) {
                player.on('enterfullscreen', deb);
                player.on('exitfullscreen', deb);
            }

            try {
                monitorBuffering(player);
            } catch (e) {}
        });
    }

    // Monitor buffering and auto-downgrade
    function monitorBuffering(player) {
        if (!player || !player.elements) return;
        const videoEl = player.elements.media || player.elements.container && player.elements.container.querySelector('video');
        if (!videoEl) return;

        const BUFFER_STALL_MS = 800;
        const COOLDOWN_MS = 3000;
        const SEEK_SUPPRESS_MS = 10000;
        const MIN_BUFFER_AHEAD = 0.9;
        let stallTimer = null;

        function clearStallTimer() {
            if (stallTimer) {
                clearTimeout(stallTimer);
                stallTimer = null;
            }
        }

        let lowBufferCount = 0;
        const LOW_BUFFER_COUNT_THRESHOLD = 1;

        function onTimeUpdate() {
            try {
                try { if (videoEl._suppressAutoAfterUserSeek && (Date.now() - videoEl._suppressAutoAfterUserSeek) < SEEK_SUPPRESS_MS) { if (isDebugEnabled(videoEl)) debugLog(player, 'onTimeUpdate: suppressed due to recent user seek', { age: Date.now() - videoEl._suppressAutoAfterUserSeek }); lowBufferCount = 0; return; } } catch (e) {}
                if (videoEl._userSelectedQuality) { lowBufferCount = 0; return; }
                if (videoEl._adaptiveEnabled === false || (player && player._adaptiveEnabled === false)) { lowBufferCount = 0; return; }

                let bufEnd = 0;
                try {
                    if (videoEl.buffered && videoEl.buffered.length) bufEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
                } catch (e) { bufEnd = 0; }
                const now = (videoEl.currentTime || 0);
                const bufferAhead = (bufEnd - now);

                if (bufferAhead <= MIN_BUFFER_AHEAD) {
                    lowBufferCount++;
                } else {
                    lowBufferCount = 0;
                }
                try { if (isDebugEnabled(videoEl)) debugLog(player, 'onTimeUpdate', { bufferAhead: bufferAhead, lowBufferCount: lowBufferCount }); } catch (e) {}

                if (lowBufferCount >= LOW_BUFFER_COUNT_THRESHOLD) {
                    try {
                        const last = videoEl._lastAutoDowngrade || 0;
                        if (Date.now() - last < COOLDOWN_MS) return;
                        const avail = (videoEl._availableSources || []).map(s => Number(s.size)).filter(n => !!n).sort((a,b)=>a-b);
                        if (!avail.length) return;
                        const cur = Number(videoEl._currentQuality || player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : NaN));
                        const current = Number.isFinite(cur) ? cur : avail[avail.length-1];
                        const lower = avail.filter(s => s < current).pop();
                        if (!lower) return;
                        changeQualityForPlayer(player, lower, { keepAllSources: true, auto: true });
                        videoEl._lastAutoDowngrade = Date.now();
                        lowBufferCount = 0;
                    } catch (e) {}
                }
            } catch (e) { lowBufferCount = 0; }
        }

        function onWaiting() {
            try {
                try { if (videoEl._suppressAutoAfterUserSeek && (Date.now() - videoEl._suppressAutoAfterUserSeek) < SEEK_SUPPRESS_MS) { try { debugLog(player, 'onWaiting: suppress downgrade due to recent user seek', { age: Date.now() - videoEl._suppressAutoAfterUserSeek }); } catch (e) {} return; } } catch (e) {}
                if (videoEl._userSelectedQuality) return;
                if (videoEl._adaptiveEnabled === false || (player && player._adaptiveEnabled === false)) return;

                clearStallTimer();
                stallTimer = setTimeout(() => {
                    try {
                        let bufEnd = 0;
                        try {
                            if (videoEl.buffered && videoEl.buffered.length) {
                                bufEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
                            }
                        } catch (e) { bufEnd = 0; }

                        const now = (videoEl.currentTime || 0);
                        const bufferAhead = (bufEnd - now);
                        try { debugLog(player, 'onWaiting bufferAhead', { bufferAhead: bufferAhead, now: now, bufEnd: bufEnd }); } catch (e) {}

                        if (bufferAhead > MIN_BUFFER_AHEAD) return;

                        const last = videoEl._lastAutoDowngrade || 0;
                        if (Date.now() - last < COOLDOWN_MS) return;

                        const avail = (videoEl._availableSources || []).map(s => Number(s.size)).filter(n => !!n).sort((a,b)=>a-b);
                        if (!avail.length) return;

                        const cur = Number(videoEl._currentQuality || player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : NaN));
                        const current = Number.isFinite(cur) ? cur : avail[avail.length-1];
                        const lower = avail.filter(s => s < current).pop();
                        if (!lower) return;

                        try { debugLog(player, 'onWaiting downgrading', { current: current, lower: lower }); } catch (e) {}
                        changeQualityForPlayer(player, lower, { keepAllSources: true, auto: true });
                        videoEl._lastAutoDowngrade = Date.now();
                    } catch (e) {}
                }, BUFFER_STALL_MS);
            } catch (e) {}
        }

        function onPlaying() {
            clearStallTimer();
        }

        function onCanPlay() {
            clearStallTimer();
        }

        videoEl.addEventListener('waiting', onWaiting);
        videoEl.addEventListener('stalled', onWaiting);
        try { videoEl.addEventListener('seeking', function() { try { videoEl._suppressAutoAfterUserSeek = Date.now(); } catch (e) {} }); } catch (e) {}
        videoEl.addEventListener('timeupdate', onTimeUpdate);
        videoEl.addEventListener('playing', onPlaying);
        videoEl.addEventListener('canplay', onCanPlay);
        try { player.on && player.on('destroy', () => { clearStallTimer(); videoEl.removeEventListener('waiting', onWaiting); videoEl.removeEventListener('stalled', onWaiting); videoEl.removeEventListener('timeupdate', onTimeUpdate); videoEl.removeEventListener('playing', onPlaying); videoEl.removeEventListener('canplay', onCanPlay); }); } catch (e) {}
    }

    // Start adaptive quality
    setTimeout(() => wireUpAdaptiveQuality('.film-player'), 150);
});
