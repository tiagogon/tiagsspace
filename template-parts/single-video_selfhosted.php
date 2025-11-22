<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$self_host_film_id = get_field('self_host_film');

// Get poster if defined
$thumbnail_id = get_post_thumbnail_id(get_the_ID()); // or get_the_ID()
$full_image_url = wp_get_attachment_url($thumbnail_id);

$poster = $full_image_url ? esc_url($full_image_url) : '';

// Start collecting sources
$video_sources = [];

// Add original video
$original_url = wp_get_attachment_url($self_host_film_id);
$video_meta = wp_get_attachment_metadata($self_host_film_id);
echo '<!-- Video Meta: ' . esc_html(print_r($video_meta, true)) . ' -->';
if ($original_url) {
    $video_sources[] = [
        'src'   => esc_url($original_url),
        'label' => intval($video_meta['height']) . 'p', // Use height as label
    ];
}

// Add encoded versions (child attachments)
$children = get_children([
    'post_parent'    => $self_host_film_id,
    'post_type'      => 'attachment',
    'post_mime_type' => 'video',
    'numberposts'    => -1,
]);

if (!empty($children)) {
    foreach ($children as $child) {
        $src = wp_get_attachment_url($child->ID);
        $filename = basename($src);

        // Extract resolution from filename (e.g. video-720.mp4)
        if (preg_match('/-(\d{3,4})\.mp4$/', $filename, $matches)) {
            $label = $matches[1] . 'p';
        } else {
            $label = 'default';
        }

        $video_sources[] = [
            'src'   => esc_url($src),
            'label' => esc_html($label),
        ];
    }
}

// it does not seem to be picked up by the Plyr player, but it is needed for the HTML5 video tag
$film_player_options_html_string = "crossorigin ";

$film_player_options = get_field('film_player_options');
if( $film_player_options ) {
    foreach ($film_player_options as $film_player_option ) {
        $film_player_options_html_string .= $film_player_option . " ";
    }
}


// Build the Plyr config as a PHP array (no pre-built JSON strings)
$plyr_config = [
    // use the current WP post title for the player title
    'title'    => get_the_title(),
    //'autoplay' => true,
    'controls' => [
        'play-large', 'play', 'progress', 'current-time',
        'mute', 'captions', 'settings', 'fullscreen'
    ],
    // only include settings you want users to see (omit 'speed')
    'settings' => ['captions', 'quality'],
    'ratio'    => '16:9',
    'keyboard' => ['focused' => true, 'global' => false],
    'tooltips' => ['controls' => false, 'seek' => false],
    'seekTime' => 10,
    'quality'  => ['default' => 1080],
    'ads'      => ['enabled' => false],
    'previewThumbnails' => ['enabled' => false],
    // fullscreen can be a nested array; null will become JSON null
    'fullscreen' => ['enabled' => true, 'fallback' => true, 'iosNative' => true, 'container' => null],
];

// Encode once to JSON and escape for inclusion in an HTML attribute
$json = wp_json_encode($plyr_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
// echo into attribute with esc_attr to be safe


?>



<div class="container-fluid container-video">
    <div class="embed-container">
            <?php if (!empty($video_sources)) : ?>
                <video class="plyr film-player" 
                <?php echo $film_player_options_html_string; ?>     
                
                poster="<?php echo $poster; ?>" 
                data-plyr-config='<?php echo esc_attr( $json ); ?>'
                
                >
                    <?php foreach ($video_sources as $source) : ?>
                        <source src="<?php echo $source['src']; ?>" type="video/mp4"
                            <?php
                            // If it's a labeled resolution (e.g. 360p), provide `size`
                            if (preg_match('/^(\d{3,4})p$/', $source['label'], $m)) {
                                echo ' size="' . esc_attr($m[1]) . '"';
                            }
                            ?>
                        >
                    <?php endforeach; ?>
                    Your browser does not support the video tag.
                    <!-- Caption files -->
                    <!-- <track kind="captions" label="English" srclang="en" src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.en.vtt"
                            default>
                    <track kind="captions" label="Français" srclang="fr" src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.fr.vtt"> -->
                    <!-- Fallback for browsers that don't support the <video> element -->
                    <a href="<?php echo esc_url($original_url); ?>" download>Download</a>
                </video>
            <?php endif; ?>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Plyr for each element (if not already initialized)
        const plyrOptions = {
            // keep fullscreen options as before
            fullscreen: { enabled: true, fallback: true, iosNative: true, container: null }
        };

        const playerEls = Array.from(document.querySelectorAll('.film-player'));
        playerEls.forEach(el => {
            if (!el.plyr) {
                try {
                    el.plyr = new Plyr(el, plyrOptions);
                    // mark adaptive enabled by default; user selection will disable it
                    el._adaptiveEnabled = true;
                    el._userSelectedQuality = false;
                    // also attach flag to the Plyr instance for robustness
                    el.plyr._adaptiveEnabled = true;
                    el.plyr._userSelectedQuality = false;

                    // collect available sources once and keep them for later switches
                    try {
                        const videoEl = el;
                        const initialSourceEls = Array.from(videoEl.querySelectorAll('source'));
                        const available = initialSourceEls.map(s => ({
                            src: s.getAttribute('src') || s.src,
                            type: s.getAttribute('type') || s.type || 'video/mp4',
                            size: parseInt(s.getAttribute('size') || s.getAttribute('data-size') || (s.getAttribute('label')||'').match(/(\d{3,4})/)?.[1] || 0, 10)
                        })).filter(s => s.src);
                        videoEl._availableSources = available;
                        el.plyr._availableSources = available;
                    } catch (ee) {}

                    // add an "Auto" option at the top of the quality menu (if Plyr rendered quality buttons)
                    try {
                        const setupQualityAutoOption = function (player) {
                            if (!player || !player.elements || !player.elements.container) return;
                            const container = player.elements.container;
                            // find an existing quality button to get the parent element
                            const firstBtn = container.querySelector('button[data-plyr="quality"]');
                            if (!firstBtn) return;
                            const parent = firstBtn.parentElement;
                            if (!parent) return;
                            // don't add twice
                            if (parent.querySelector('button[data-value="auto"]')) return;
                            const autoBtn = document.createElement('button');
                            autoBtn.type = 'button';
                            autoBtn.className = firstBtn.className;
                            autoBtn.setAttribute('data-plyr', 'quality');
                            autoBtn.setAttribute('value', 'auto');
                            autoBtn.setAttribute('data-value', 'auto');
                            autoBtn.textContent = 'Auto';
                            parent.insertBefore(autoBtn, parent.firstChild);

                            // make Auto active by default (visual cue)
                            try {
                                // remove is-active from other buttons
                                Array.from(parent.querySelectorAll('button[data-plyr="quality"]')).forEach(b => b.classList.remove('is-active'));
                                autoBtn.classList.add('is-active');
                            } catch (e) {}
                        };
                        // run once after a small delay so Plyr has finished rendering controls
                        setTimeout(() => setupQualityAutoOption(el.plyr), 120);
                    } catch (ee) {
                        // ignore
                    }
                } catch (e) {
                    // ignore initialization errors
                    // console.warn('Plyr init failed', e);
                }
            }
        });

        // --- Adaptive quality logic ---
        function debounce(fn, wait) {
            let t;
            return function () {
                const args = arguments;
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        function chooseQuality(availableSizes, neededHeightPx) {
            for (let i = availableSizes.length - 1; i >= 0; i--) {
                if (availableSizes[i] <= neededHeightPx) return availableSizes[i];
            }
            return availableSizes[0] || null;
        }

        // helper: sync UI active state for quality buttons (auto or numeric)
        function syncQualityButtons(player, activeValue) {
            try {
                if (!player || !player.elements || !player.elements.container) return;
                const container = player.elements.container;
                // find any quality button and use its parent as the scope
                let anyBtn = container.querySelector('button[data-plyr="quality"]');
                const parentEl = anyBtn ? anyBtn.parentElement : null;
                const scope = parentEl || container;
                const buttons = Array.from(scope.querySelectorAll('button[data-plyr="quality"]'));
                buttons.forEach(b => {
                    try { b.classList.remove('is-active'); } catch (e) {}
                    // ensure the button has the role used by Plyr's CSS for radio items
                    try { if (!b.hasAttribute('role')) b.setAttribute('role', 'menuitemradio'); } catch (e) {}
                    const val = (b.getAttribute('value') || b.getAttribute('data-value') || '').toString();
                    const isActive = (activeValue === 'auto' && val === 'auto') || (val !== 'auto' && Number(val) === Number(activeValue));
                    try { if (isActive) { b.classList.add('is-active'); b.setAttribute('aria-checked', 'true'); } else { b.classList.remove('is-active'); b.setAttribute('aria-checked', 'false'); } } catch (e) {}
                });
            } catch (e) {}
        }

        // restore all available <source> elements from the stored list
        function rebuildSourcesFromAvailable(videoEl) {
            try {
                if (!videoEl || !videoEl._availableSources) return;
                const avail = Array.isArray(videoEl._availableSources) ? videoEl._availableSources : [];
                // remove existing <source> nodes
                try {
                    const existing = Array.from(videoEl.querySelectorAll('source'));
                    existing.forEach(s => s.parentNode && s.parentNode.removeChild(s));
                } catch (e) {}
                // append all available sources
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

            if (sizes.length === 0) return; // nothing to do

            sizes = sizes.sort((a, b) => a - b);

            let displayHeightCss = videoEl.clientHeight || videoEl.getBoundingClientRect().height || 0;
            if (!displayHeightCss) {
                const rect = player.elements && player.elements.container && player.elements.container.getBoundingClientRect();
                displayHeightCss = (rect && rect.height) || 0;
            }
            if (!displayHeightCss) return;

            const dpr = window.devicePixelRatio || 1;
            const neededHeightPx = Math.ceil(displayHeightCss * dpr);

            // if the user manually selected a quality, don't override their choice
            // also respect flags set on the Plyr instance
            if (el._userSelectedQuality || el._adaptiveEnabled === false || (player && (player._userSelectedQuality || player._adaptiveEnabled === false))) return;

            const desired = chooseQuality(sizes, neededHeightPx);
            if (!desired) return;

            try {
                const current = player.quality;
                if (current !== desired) {
                    player.quality = desired;
                    // record current quality and update UI to show manual/auto state
                    try { player._currentQuality = desired; } catch (e) {}
                    try { if (el) { el._currentQuality = desired; } } catch (e) {}
                    // mark Auto as active because this change was done by adaptive handler
                    syncQualityButtons(player, 'auto');
                }
            } catch (err) {
                // if quality setter isn't available, don't break the user experience
                // Optionally we could reload sources here as a fallback
                // console.warn('Could not set Plyr quality', err);
            }
        }

        // Change the player's source to the chosen quality using the native <video> element.
        // This approach replaces the src (or <source>) and uses the media 'loadedmetadata' event
        // to restore currentTime and playback state. It avoids relying on player.source which
        // can behave differently across Plyr builds.
        function changeQualityForPlayer(player, desiredSize, options) {
            if (!player || !player.elements) return;
            const videoEl = player.elements.media || player.elements.container && player.elements.container.querySelector('video');
            if (!videoEl) return;

            options = options || {};
            // prefer stored availableSources to allow switching back and forth
            const sources = (videoEl._availableSources && Array.isArray(videoEl._availableSources) && videoEl._availableSources.slice())
                || Array.from(videoEl.querySelectorAll('source')).map(s => ({
                    src: s.getAttribute('src') || s.src,
                    type: s.getAttribute('type') || s.type || 'video/mp4',
                    size: parseInt(s.getAttribute('size') || s.getAttribute('data-size') || (s.getAttribute('label')||'').match(/(\d{3,4})/)?.[1] || 0, 10)
                })).filter(s => s.src);

            const target = sources.find(s => s.size === Number(desiredSize));
            if (!target) return;

            // preserve state
            const wasPlaying = !videoEl.paused;
            const currentTime = videoEl.currentTime || 0;
            const wasMuted = videoEl.muted;
            const playbackRate = videoEl.playbackRate || 1;

            // mark adaptive disabled for now (unless this is an 'auto' triggered switch)
            if (!options.auto) {
                videoEl._adaptiveEnabled = false;
                if (player) {
                    player._adaptiveEnabled = false;
                    player._userSelectedQuality = true;
                }
            }

            // Replace the sources: set src to the target file and call load()
            try {
                // Pause to avoid auto-reset issues
                try { videoEl.pause(); } catch (e) {}

                if (options.keepAllSources) {
                    // rebuild full set of <source> elements from available list so future switches work
                    try { rebuildSourcesFromAvailable(videoEl); } catch (e) {}
                    // ensure target is present; if not, just set videoEl.src to target
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
                    // remove other sources and only keep the chosen one
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

                // set src and load
                videoEl.src = target.src;
                videoEl.load();

                // record current quality on element/player
                try { videoEl._currentQuality = target.size || desiredSize; } catch (e) {}
                try { if (player) player._currentQuality = target.size || desiredSize; } catch (e) {}

                // update UI active state for the selected quality (if not auto)
                try { if (!options.auto) syncQualityButtons(player, target.size || desiredSize); else syncQualityButtons(player, 'auto'); } catch (e) {}

                const onMeta = function () {
                    // restore time and playback state
                    try { videoEl.currentTime = Math.min(currentTime, videoEl.duration || currentTime); } catch (e) {}
                    try { videoEl.muted = wasMuted; } catch (e) {}
                    try { videoEl.playbackRate = playbackRate; } catch (e) {}
                    if (wasPlaying) {
                        const p = player.play && typeof player.play === 'function' ? player.play() : videoEl.play && videoEl.play();
                        // ignore promise
                        if (p && p.then) p.catch(() => {});
                    }
                    // cleanup
                    videoEl.removeEventListener('loadedmetadata', onMeta);
                };

                videoEl.addEventListener('loadedmetadata', onMeta);
            } catch (err) {
                // fallback: nothing
                // console.warn('changeQualityForPlayer native switch failed', err);
            }
        }

        // Listen for clicks on the Plyr quality menu items and perform an actual source switch.
        // Use the capture phase so we can intercept before Plyr's own handlers and stop them.
        document.addEventListener('click', function (ev) {
            const btn = ev.target.closest && ev.target.closest('[data-plyr="quality"]');
            if (!btn) return;
            // stop Plyr's internal handling so we can perform a robust native switch
            try {
                ev.preventDefault();
            } catch (e) {}
            try { ev.stopPropagation(); } catch (e) {}
            try { ev.stopImmediatePropagation(); } catch (e) {}

            // find the closest plyr container and its video element
            const container = btn.closest && btn.closest('.plyr');
            if (!container) return;
            const videoEl = container.querySelector && (container.querySelector('video') || container.querySelector('audio'));
            if (!videoEl) return;
            const player = videoEl.plyr || (window.Plyr && Plyr.get ? Plyr.get(videoEl) : null);
            if (!player) return;

            const raw = (btn.getAttribute('value') || btn.value || btn.getAttribute('data-value') || '').toString();
            if (!raw) return;

            // handle the special 'auto' option
            if (raw === 'auto') {
                // clear user selection and re-enable adaptive behavior
                videoEl._userSelectedQuality = false;
                videoEl._adaptiveEnabled = true;
                if (player) {
                    player._userSelectedQuality = false;
                    player._adaptiveEnabled = true;
                }

                // restore all known source elements so Plyr and our adaptive logic can see them
                try { rebuildSourcesFromAvailable(videoEl); } catch (e) {}

                // compute desiredSize from available sources and perform a native switch while keeping all sources
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
                            // perform the switch but mark it as 'auto' so flags aren't set to user-selected
                            changeQualityForPlayer(player, desiredAuto, { keepAllSources: true, auto: true });
                        }
                    }
                } catch (e) {}

                // update UI active state to Auto
                try { syncQualityButtons(player, 'auto'); } catch (e) {}

                return;
            }

            const desired = parseInt(raw, 10);
            if (Number.isNaN(desired)) return;

            // mark user selection so adaptive logic won't overwrite
            videoEl._userSelectedQuality = true;
            videoEl._adaptiveEnabled = false;
            if (player) {
                player._userSelectedQuality = true;
                player._adaptiveEnabled = false;
            }

            if (player) changeQualityForPlayer(player, desired);
        }, true);

        // When the user opens the settings menu, Plyr may rebuild the menu items.
        // Intercept settings button opens and re-sync the radio state after the menu is rendered.
        document.addEventListener('click', function (ev) {
            const settingsBtn = ev.target.closest && ev.target.closest('[data-plyr="settings"]');
            if (!settingsBtn) return;
            const container = settingsBtn.closest && settingsBtn.closest('.plyr');
            if (!container) return;
            const videoEl = container.querySelector && (container.querySelector('video') || container.querySelector('audio'));
            const player = videoEl && (videoEl.plyr || (window.Plyr && Plyr.get ? Plyr.get(videoEl) : null));
            if (!player) return;
            // wait a tick for Plyr to render the menu DOM, then sync the buttons
            setTimeout(() => {
                try {
                    if (player._adaptiveEnabled && !player._userSelectedQuality) {
                        syncQualityButtons(player, 'auto');
                    } else {
                        // prefer explicit currentQuality if recorded, otherwise fallback to player.quality
                        const cur = player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : 'auto');
                        syncQualityButtons(player, cur);
                    }
                } catch (e) {}
            }, 60);
        });

        function wireUpAdaptiveQuality(selector) {
            const els = Array.from(document.querySelectorAll(selector));
            if (!els.length) return;

            els.forEach(el => {
                const run = () => adaptQualityForElement(el);
                // run on player ready if possible
                const player = el.plyr || (window.Plyr && Plyr.get ? Plyr.get(el) : null);
                try {
                    player && player.on && player.on('ready', run);
                    player && player.on && player.on('loadedmetadata', run);
                } catch (e) {}
                // run now and after small delay to allow layout
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

                // also attach a buffering monitor to automatically step-down quality when in Auto
                try {
                    monitorBuffering(player);
                } catch (e) {}
            });
        }

        // Monitor the media element for prolonged buffering stalls and step-down quality when in Auto
        function monitorBuffering(player) {
            if (!player || !player.elements) return;
            const videoEl = player.elements.media || player.elements.container && player.elements.container.querySelector('video');
            if (!videoEl) return;

            const BUFFER_STALL_MS = 500; // how long waiting must persist before we act
            const COOLDOWN_MS = 2000; // minimum time between automatic downgrades
            let stallTimer = null;

            function clearStallTimer() {
                if (stallTimer) {
                    clearTimeout(stallTimer);
                    stallTimer = null;
                }
            }

            function onWaiting() {
                try {
                    // only act when adaptive is enabled and the user hasn't picked a manual quality
                    if (videoEl._userSelectedQuality) return;
                    if (videoEl._adaptiveEnabled === false || (player && player._adaptiveEnabled === false)) return;

                    clearStallTimer();
                    stallTimer = setTimeout(() => {
                        try {
                            // if readyState indicates not enough data (0-3), consider it stalled
                            if ((videoEl.readyState || 0) >= 3) return; // enough data, no stall

                            const last = videoEl._lastAutoDowngrade || 0;
                            if (Date.now() - last < COOLDOWN_MS) return; // respect cooldown

                            const avail = (videoEl._availableSources || []).map(s => Number(s.size)).filter(n => !!n).sort((a,b)=>a-b);
                            if (!avail.length) return;

                            const cur = Number(videoEl._currentQuality || player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : NaN));
                            // if we can't determine current, pick highest
                            const current = Number.isFinite(cur) ? cur : avail[avail.length-1];
                            // find the next lower available quality
                            const lower = avail.filter(s => s < current).pop();
                            if (!lower) return; // already at lowest

                            // perform a native switch but mark it as auto so flags remain adaptive
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
            videoEl.addEventListener('playing', onPlaying);
            videoEl.addEventListener('canplay', onCanPlay);
            // cleanup when player is destroyed (best-effort)
            try { player.on && player.on('destroy', () => { clearStallTimer(); videoEl.removeEventListener('waiting', onWaiting); videoEl.removeEventListener('stalled', onWaiting); videoEl.removeEventListener('playing', onPlaying); videoEl.removeEventListener('canplay', onCanPlay); }); } catch (e) {}
        }

        // start the adaptive quality wiring for film-player elements
        setTimeout(() => wireUpAdaptiveQuality('.film-player'), 150);
    });
</script>