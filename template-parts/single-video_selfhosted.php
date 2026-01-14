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
    'numberposts'    => -1,
]);

// Separate video files from caption/subtitle files
$caption_tracks = [];

if (!empty($children)) {
    foreach ($children as $child) {
        $src = wp_get_attachment_url($child->ID);
        $filename = basename($src);
        
        // Check if it's a caption/subtitle file (VTT or SRT)
        if (preg_match('/\.(vtt|srt)$/i', $filename)) {
            // Extract language code from filename (e.g. video-en.vtt, video-fr_FR.vtt)
            $lang_match = preg_match('/(?:captions?|subtitles?)?-?([a-z]{2}(?:_[A-Z]{2})?)\.(vtt|srt)$/i', $filename, $matches);
            
            if ($lang_match) {
                $lang_code = $matches[1]; // e.g. 'en', 'fr_FR'
                $file_type = strtolower($matches[2]); // 'vtt' or 'srt'
                
                // Convert language code to label
                $lang_label = $lang_code;
                $lang_map = [
                    'en' => 'English',
                    'fr' => 'Français',
                    'de' => 'Deutsch',
                    'es' => 'Español',
                    'it' => 'Italiano',
                    'pt' => 'Português',
                    'pt_BR' => 'Português (Brasil)',
                    'pt_PT' => 'Português (Portugal)',
                    'nl' => 'Nederlands',
                    'sv' => 'Svenska',
                ];
                
                if (isset($lang_map[$lang_code])) {
                    $lang_label = $lang_map[$lang_code];
                }
                
                $caption_tracks[] = [
                    'src'     => esc_url($src),
                    'srclang' => esc_attr($lang_code),
                    'label'   => esc_html($lang_label),
                    'kind'    => 'captions',
                ];
            } else {
                // Fallback for files named just "captions.vtt" or "subtitles.vtt"
                $caption_tracks[] = [
                    'src'     => esc_url($src),
                    'srclang' => 'en',
                    'label'   => esc_html(ucfirst(preg_replace('/\.[^.]*$/', '', $filename))),
                    'kind'    => 'captions',
                ];
            }
        } elseif (preg_match('/\.mp4$|\.webm$|\.ogv$/i', $filename)) {
            // It's a video file - add to sources
            // Extract resolution from filename (e.g. video-720.mp4)
            if (preg_match('/-(\d{3,4})\.mp4$/i', $filename, $matches)) {
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
                <video class="plyr film-player" data-debug="0"
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
                    
                    <?php // Output caption tracks if available ?>
                    <?php if (!empty($caption_tracks)) : ?>
                        <?php foreach ($caption_tracks as $index => $track) : ?>
                            <track 
                                kind="<?php echo $track['kind']; ?>" 
                                src="<?php echo $track['src']; ?>" 
                                srclang="<?php echo $track['srclang']; ?>" 
                                label="<?php echo $track['label']; ?>"
                                <?php echo ($index === 0) ? 'default' : ''; // Mark first track as default
                                ?>
                            >
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    Your browser does not support the video tag.
                    <!-- Fallback for browsers that don't support the <video> element -->
                    <a href="<?php echo esc_url($original_url); ?>" download>Download</a>
                </video>
            <?php endif; ?>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Lightweight adaptive quality system for self-hosted videos
        // ---------------------------------------------
        // This script augments Plyr with:
        //  - adaptive selection based on displayed element height × DPR
        //  - a visible "Auto" quality option which re-enables adaptive mode
        //  - robust native <video> source swaps to preserve playback position
        //  - a conservative auto-downgrade when Auto stalls on slow networks
        //
        // Important runtime assumptions:
        //  - The server supports range requests (206) so seeking can work.
        //  - Sources include a `size` attribute (or parsable filename/label)
        //    indicating vertical resolution (e.g. 2160, 1080, 720).
        //  - Plyr is available globally and attaches instances to the element
        //    via `el.plyr` or `Plyr.get(el)`.
        //
        // The rest of the code initializes players, collects available
        // sources, and wires adaptive/manual switching. See individual
        // functions for more detail.
        // Initialize Plyr for each element (if not already initialized)
        const plyrOptions = {
            // keep fullscreen options as before
            fullscreen: { enabled: true, fallback: true, iosNative: true, container: null }
        };

        const playerEls = Array.from(document.querySelectorAll('.film-player'));

        // Small debug helper: enable by adding `data-debug="1"` to the <video>
        // element. We also set `el._debug = true` during initialization so other
        // routines can check the flag cheaply.
        function isDebugEnabled(videoEl) {
            try {
                if (!videoEl) return false;
                if (videoEl._debug) return true;
                const a = videoEl.getAttribute && videoEl.getAttribute('data-debug');
                return a === '1' || a === 'true';
            } catch (e) { return false; }
        }

        function debugLog(videoEl /* optional */, ...args) {
            try {
                // allow calling debugLog(player, ...) by passing player.elements.media
                const v = videoEl && videoEl.elements ? (videoEl.elements.media || videoEl.elements.container && videoEl.elements.container.querySelector('video')) : videoEl;
                if (isDebugEnabled(v)) console.debug('[plyr-adaptive]', ...args);
            } catch (e) {}
        }
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
                        // honor data-debug attribute on the <video> so debug helpers
                        // can be used throughout the script (e.g. debugLog(videoEl,...))
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

                    // Wrap the native play() (and Plyr.play if present) so any
                    // returned promise rejection is logged. This ensures we
                    // capture autoplay policy rejections even when Plyr triggers
                    // playback internally.
                    try {
                        const videoEl = el;
                        if (videoEl && videoEl.play && !videoEl._playWrapped) {
                            try {
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
                            } catch (e) {}
                        }

                        // also wrap Plyr's play if present
                        if (el.plyr && el.plyr.play && !el.plyr._playWrapped) {
                            try {
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
                            } catch (e) {}
                        }
                    } catch (e) {}

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
                    // attach lightweight media event logging to capture MediaError and lifecycle events
                    try {
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
                    } catch (ee) {}
                    // attach lightweight interaction logging to help debug user clicks vs. progress
                    try { attachInteractionLogging(el.plyr); } catch (e) {}
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
    //
    // Overview:
    // This block implements a light-weight "adaptive" quality system that
    // chooses an appropriate video resolution based on the displayed
    // element height and the devicePixelRatio (DPR). It coexists with
    // Plyr's quality controls and provides:
    //  - An "Auto" UI option (inserted into Plyr controls) which re-enables
    //    adaptive behavior.
    //  - Manual quality switching handled by performing native <video>
    //    source swaps (preserving currentTime, playback state, muted, rate),
    //    because relying on `player.source` proved unreliable across
    //    different Plyr builds/versions.
    //  - Flags stored on both the <video> element and the Plyr instance to
    //    avoid adaptive logic overriding explicit user choices.
    //  - A buffering monitor that steps down one quality when Auto stalls
    //    for a short period to improve playback on slow networks.
        function debounce(fn, wait) {
            let t;
            return function () {
                const args = arguments;
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        // Choose the best quality (height in pixels) from a sorted list.
        // The algorithm picks the largest available size that is <= neededHeightPx.
        // If none match (needed is smaller than the smallest), return the smallest
        // available as a fallback.
        function chooseQuality(availableSizes, neededHeightPx) {
            for (let i = availableSizes.length - 1; i >= 0; i--) {
                if (availableSizes[i] <= neededHeightPx) return availableSizes[i];
            }
            return availableSizes[0] || null;
        }

    // helper: sync UI active state for quality buttons (auto or numeric)
    //
    // Plyr's controls are re-built by Plyr at times (when opening settings, etc.).
    // We maintain a visual radio-like state by ensuring the proper button has
    // the `is-active` class and aria attributes. This makes the custom "Auto"
    // button show correctly and keeps the native Plyr buttons in sync.
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
    //
    // When we perform native source swaps we sometimes remove existing
    // <source> elements (to replace with a single source). This helper
    // rebuilds the full set of <source> nodes from the cached
    // `videoEl._availableSources` so future switches (and Plyr's UI) can
    // see all alternatives again.
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

        // Attach user interaction logging to a Plyr instance.
        // Logs clicks on controls and detects when a user-initiated seek/click
        // does not result in progress (no timeupdate/currentTime change)
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
                        // Log any click inside the player container
                        try { debugLog(videoEl, 'userClick', { tag: tag, classes: classes }); } catch (e) {}

                        // Detect clicks on the progress/seeker area. Plyr markup uses
                        // `.plyr__progress` for the wrapper and an <input type="range"> for the seek bar.
                        const progressEl = target.closest && target.closest('.plyr__progress');
                        const rangeEl = (target && target.closest) ? target.closest('input[type="range"]') : null;
                        if (progressEl || rangeEl) {
                                    try { videoEl._suppressAutoAfterUserSeek = Date.now(); } catch (e) {}
                                    try { debugLog(videoEl, 'userClick:progress', { classes: classes }); } catch (e) {}
                            // Monitor for progress: if currentTime or buffer increases within a short window
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

                            // After this timeout, if we haven't seen progress, log it
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
                        // Additionally log explicit clicks on the play control
                        const playBtn = target.closest && target.closest('[data-plyr="play"]');
                        if (playBtn || (target && (target.className || '').indexOf('plyr__control--play') !== -1)) {
                            try { debugLog(videoEl, 'userClick:playbutton', { classes: classes }); } catch (e) {}
                        }
                    } catch (e) {}
                }, true);
            } catch (e) {}
        }

    // Compute and apply an adaptive quality for the given video element.
    // Steps:
    //  - Gather available sizes from <source> tags or stored list.
    //  - Compute displayed height * DPR to get the target pixel height.
    //  - If the user explicitly selected a quality (or adaptive is disabled)
    //    do nothing; otherwise pick and set Plyr's `quality`.
    // Notes:
    //  - We check both flags on the raw element (`el._userSelectedQuality`)
    //    and the Plyr instance (`player._userSelectedQuality`) for robustness.
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

            // Debug: report sizes and computed need (guarded to avoid allocations when disabled)
            try { if (isDebugEnabled(videoEl)) debugLog(videoEl, 'adaptQualityForElement', { displayHeightCss, dpr, neededHeightPx, sizes }); } catch (e) {}

            // if the user manually selected a quality, don't override their choice
            // also respect flags set on the Plyr instance. These flags are set
            // when the user clicks a quality button (we set `_userSelectedQuality`)
            // or when the adaptive logic explicitly disables itself.
            if (el._userSelectedQuality || el._adaptiveEnabled === false || (player && (player._userSelectedQuality || player._adaptiveEnabled === false))) return;

            // If we recently performed an automatic downgrade due to starvation,
            // suppress adaptive changes for a short window. This avoids the
            // adaptive handler immediately trying to re-upgrade the quality
            // based on the element size and causing oscillation/hiccups.
            try {
                const lastAuto = (videoEl && (videoEl._lastAutoDowngrade || 0)) || (player && (player._lastAutoDowngrade || 0)) || 0;
                const ADAPT_SUPPRESS_AFTER_AUTO_MS = 15000; // suppress adaptive adjustments for 30s after an auto-downgrade
                if (lastAuto && (Date.now() - lastAuto) < ADAPT_SUPPRESS_AFTER_AUTO_MS) {
                    try { debugLog(videoEl, 'adaptQualityForElement suppressed after recent auto downgrade', { lastAutoAge: Date.now() - lastAuto }); } catch (e) {}
                    return;
                }
            } catch (e) {}

            const desired = chooseQuality(sizes, neededHeightPx);
            if (!desired) return;

            try {
                // If we recently auto-downgraded due to starvation, avoid
                // immediately increasing quality back to the element-size target.
                // Strategy:
                //  - Check when the last automatic downgrade happened (videoEl._lastAutoDowngrade)
                //  - If a recent downgrade exists, only allow adaptive *upgrades*
                //    when there is a sustained buffered headroom (stable buffer).
                // This prevents flip-flopping where adaptive sees the large player
                // size and immediately returns to a too-high quality.
                try {
                    const lastAuto = (videoEl && (videoEl._lastAutoDowngrade || 0)) || (player && (player._lastAutoDowngrade || 0)) || 0;
                    const AUTO_UPGRADE_COOLDOWN_MS = 5000; // during this window require stable buffer before upscaling
                    const AUTO_UPGRADE_STABLE_BUFFER_SEC = 3; // seconds of buffered content required to allow upgrade
                    const recordedCur = Number(videoEl._currentQuality || player._currentQuality || (typeof player.quality !== 'undefined' ? player.quality : NaN));

                    if (lastAuto && (Date.now() - lastAuto) < AUTO_UPGRADE_COOLDOWN_MS && Number.isFinite(recordedCur) && desired > recordedCur) {
                        // compute buffer ahead now to see if we have sustained headroom
                        let bufEnd = 0;
                        try {
                            if (videoEl.buffered && videoEl.buffered.length) bufEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
                        } catch (e) { bufEnd = 0; }
                        const now = (videoEl.currentTime || 0);
                        const bufferAhead = (bufEnd - now);

                        if (bufferAhead < AUTO_UPGRADE_STABLE_BUFFER_SEC) {
                            try { debugLog(videoEl, 'suppress upupgrade until stable buffer', { desired, recordedCur, lastAutoAge: Date.now() - lastAuto, bufferAhead }); } catch (e) {}
                            // don't upgrade now — let the buffer grow or more downgrades occur
                            return;
                        }
                    }
                } catch (e) {}

                const current = player.quality;
                if (current !== desired) {
                    // Set Plyr's quality (when supported). We also record the chosen
                    // value on both the player and the element so other parts of the
                    // code (buffer monitor, UI sync) can inspect it.
                    player.quality = desired;
                    try { player._currentQuality = desired; } catch (e) {}
                    try { if (el) { el._currentQuality = desired; } } catch (e) {}
                    // Since this change originated from adaptive logic, make sure
                    // the UI shows the "Auto" option as active.
                    syncQualityButtons(player, 'auto');
                    try { debugLog(videoEl, 'adaptive applied', { desired, current }); } catch (e) {}
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

            try { debugLog(player, 'changeQualityForPlayer:start', { desiredSize: desiredSize, options: options, currentTime: videoEl.currentTime, paused: videoEl.paused }); } catch (e) {}

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

            // NOTE: keep logic minimal — avoid temporary muting workarounds here.

            // Prevent external/play attempts from racing during the native
            // source swap. Some browsers or integrations may trigger playback
            // as soon as load() is called which can occur before we restore
            // currentTime. To avoid a visible jump to 0 we temporarily block
            // 'play' attempts and remember that a play was requested.
            let playRequestedDuringRestore = false;
            const onPlayAttempt = function () {
                try {
                    if (videoEl._isRestoring) {
                        // immediately pause to prevent playback before we restore time
                        try { videoEl.pause(); } catch (e) {}
                        playRequestedDuringRestore = true;
                    }
                } catch (e) {}
            };
            try { videoEl._isRestoring = true; videoEl.addEventListener('play', onPlayAttempt, true); } catch (e) {}

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
                // Pause to avoid auto-reset issues (also pause any racing play)
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
                // no temporary muting — leave mute state as-is and attempt play later
                videoEl.load();

                // record current quality on element/player
                try { videoEl._currentQuality = target.size || desiredSize; } catch (e) {}
                try { if (player) player._currentQuality = target.size || desiredSize; } catch (e) {}

                // update UI active state for the selected quality (if not auto)
                try { if (!options.auto) syncQualityButtons(player, target.size || desiredSize); else syncQualityButtons(player, 'auto'); } catch (e) {}

                // Handler run after the element emits loadedmetadata.
                // The goal is to restore playback position and state after we
                // swapped the underlying src/source. Seeking immediately after
                // load() often fails because the browser hasn't populated
                // `seekable` or `buffered` ranges yet. To improve reliability we:
                //  - compute the desiredTime we want to restore to
                //  - attempt to set `currentTime` immediately and then retry
                //    periodically until the media indicates the time is seekable
                //    or we've exhausted retry attempts
                //  - listen to additional events (progress, canplaythrough,
                //    canplay, seeked) to get more opportunities to seek
                //  - make Auto-triggered switches more patient (longer retry window)
                const onMeta = function () {
                    // desiredTime: clamp to duration when available
                    const desiredTime = Math.min(currentTime || 0, (videoEl.duration && isFinite(videoEl.duration)) ? videoEl.duration : currentTime || 0);
                    let attempts = 0;
                    // allow much longer attempts especially for Auto-driven switches
                    // Auto: try for up to ~20s (maxAttempts * retryDelay)
                    const maxAttempts = options && options.auto ? 80 : 24;
                    const retryDelay = 250; // ms

                    // Once we've successfully restored time and playback, cleanup.
                    // To avoid brief play-then-jump behaviour we resume playback only
                    // when there's a small amount of buffered content ahead. If the
                    // buffer is small we wait for 'progress'/'canplay'/'canplaythrough'
                    // or a timeout before calling play. This prevents the UI from
                    // showing a few seconds of play and then jumping to 0 repeatedly.
                        function finishRestore() {
                        // restore original mute state (this will unmute if we had
                        // forced a temporary mute for autoplay)
                        try { videoEl.muted = wasMuted; } catch (e) {}
                        try { videoEl.playbackRate = playbackRate; } catch (e) {}

                        // Helper: compute buffered ahead seconds
                        function getBufferAhead() {
                            try {
                                if (videoEl.buffered && videoEl.buffered.length) {
                                    const end = videoEl.buffered.end(videoEl.buffered.length - 1);
                                    return Math.max(0, end - (videoEl.currentTime || 0));
                                }
                            } catch (e) {}
                            return 0;
                        }

                        // How much buffer ahead we require before resuming playback.
                        // Keep this modest; we only need a short cushion to avoid
                        // immediate rebuffering and visible jumps.
                        const RESUME_BUFFER_SEC = 1.2;

                        // Simplified resume logic: attempt a single programmatic play
                        // when there's enough buffer ahead, otherwise watch for
                        // progress/canplay and try once when buffer grows or after a timeout.
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
                                    // watch buffer growth and fallback to a timeout (5s)
                                    resumeCleanupTimer = setTimeout(() => { tryPlayOnce(); cleanupResumeWatchers(); }, 5000);
                                    try { videoEl.addEventListener('progress', resumeOnBuffer); } catch (e) {}
                                    try { videoEl.addEventListener('canplay', resumeOnBuffer); } catch (e) {}
                                }
                            } catch (e) {
                                // fallback: try to resume immediately
                                tryPlayOnce();
                            }
                        }
                            
                        // If a user attempted to play while we were restoring the
                        // source (playRequestedDuringRestore was set by onPlayAttempt),
                        // honor that request now by attempting to play once more.
                        try {
                            if (playRequestedDuringRestore) {
                                try { debugLog(videoEl, 'honoring playRequestedDuringRestore'); } catch (e) {}
                                tryPlayOnce();
                                playRequestedDuringRestore = false;
                            }
                        } catch (e) {}

                        try { debugLog(videoEl, 'finishRestore', { attempts: attempts, desiredTime: desiredTime, currentTime: videoEl.currentTime }); } catch (e) {}

                        // cleanup listeners we attached while trying to restore
                        try { videoEl.removeEventListener('loadedmetadata', onMeta); } catch (e) {}
                        try { videoEl.removeEventListener('canplay', onCanPlay); } catch (e) {}
                        try { videoEl.removeEventListener('seeked', onSeeked); } catch (e) {}
                        // also remove progress / canplaythrough handlers which were added earlier
                        try { videoEl.removeEventListener('progress', onProgress); } catch (e) {}
                        try { videoEl.removeEventListener('canplaythrough', onCanPlayThrough); } catch (e) {}
                        // Ensure we always clear the restoring flag and remove the capture
                        // play listener so subsequent user-initiated plays are allowed.
                        try { videoEl._isRestoring = false; } catch (e) {}
                        try { videoEl.removeEventListener('play', onPlayAttempt, true); } catch (e) {}
                    }

                    // Helper to determine if seeking to `time` is likely to succeed.
                    // We check `seekable` ranges first (most reliable). If unavailable,
                    // fall back to checking `buffered` ranges as a hint the browser
                    // has some bytes for the desired position.
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

                    // Try to set currentTime, but only force it when it appears
                    // seekable or on the very first attempt. Otherwise keep retrying
                    // because setting currentTime too early can be ignored by the
                    // browser and lead to an apparent "restart" at 0.
                    function trySetTime() {
                        attempts++;
                        try {
                            const canSeek = canSeekTo(desiredTime);
                            try { if (isDebugEnabled(videoEl)) debugLog(videoEl, 'trySetTime', { attempts: attempts, canSeek: canSeek, desiredTime: desiredTime, currentTime: videoEl.currentTime }); } catch (e) {}
                            if (canSeek || attempts === 1) {
                                try { videoEl.currentTime = desiredTime; } catch (err) {}
                            }
                        } catch (err) {}

                        // If the currentTime is close enough to desiredTime we're done
                        const diff = Math.abs((videoEl.currentTime || 0) - desiredTime);
                        if (diff <= 0.5 || attempts >= maxAttempts) {
                            finishRestore();
                        } else {
                            // schedule another attempt
                            setTimeout(trySetTime, retryDelay);
                        }
                    }

                    // Additional opportunities to try seeking: progress/canplaythrough
                    // indicate the browser has buffered more data and may allow seeks.
                    const onProgress = function () {
                        trySetTime();
                    };
                    const onCanPlayThrough = function () {
                        trySetTime();
                    };

                    videoEl.addEventListener('progress', onProgress);
                    videoEl.addEventListener('canplaythrough', onCanPlayThrough);

                    const onSeeked = function () {
                        // If the element emits seeked, we're finished successfully.
                        finishRestore();
                    };

                    const onCanPlay = function () {
                        // ensure we try to set time once canplay occurs
                        trySetTime();
                    };

                    videoEl.addEventListener('seeked', onSeeked);
                    videoEl.addEventListener('canplay', onCanPlay);

                    // first attempt immediately
                    trySetTime();
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
        //
        // Rationale:
        // On slow networks an adaptive "Auto" decision may pick a source that later
        // stalls the player. Rather than leaving the user stuck, we detect a short
        // persistent stall and step down one quality level. We intentionally keep
        // this conservative (one-step down + cooldown) to avoid flapping.
        function monitorBuffering(player) {
            if (!player || !player.elements) return;
            const videoEl = player.elements.media || player.elements.container && player.elements.container.querySelector('video');
            if (!videoEl) return;

            // Tunable values (could be exposed via data-attributes later)
            const BUFFER_STALL_MS = 800; // how long waiting must persist before we act
            const COOLDOWN_MS = 3000; // minimum time between automatic downgrades
            // When the user explicitly seeks far ahead we suppress automatic
            // downgrades for a short grace period so the player has time to
            // fetch the requested segments. This prevents the adaptive logic
            // from immediately stepping the quality down while the seek is
            // still completing.
            const SEEK_SUPPRESS_MS = 10000; // ms to suppress auto-downgrade after user seek
            // When determining if we should downgrade, check how many seconds are
            // buffered ahead of the currentTime. If less than this threshold we
            // consider the playback starved and will downgrade.
            const MIN_BUFFER_AHEAD = 0.9; // seconds
            let stallTimer = null;

            function clearStallTimer() {
                if (stallTimer) {
                    clearTimeout(stallTimer);
                    stallTimer = null;
                }
            }

            // Additional safeguard: some browsers may not reliably fire 'waiting'
            // when buffer ahead runs out, especially during continuous playback.
            // To catch these cases we also monitor `timeupdate` and count
            // consecutive occurrences where buffered-ahead is below threshold.
            // When the low-buffer counter reaches a small threshold we trigger
            // the same conservative one-step downgrade used by the waiting handler.
            let lowBufferCount = 0;
            const LOW_BUFFER_COUNT_THRESHOLD = 1; // number of consecutive checks

            function onTimeUpdate() {
                try {
                    // Respect recent user-initiated seeks: don't auto-downgrade if within suppress window
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
                        // guard with cooldown and available qualities check
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

            // Called when the element reports waiting/stalled. We start a timer and
            // only perform an automatic downgrade if the waiting condition persists
            // past BUFFER_STALL_MS and adaptive mode is still active.
            function onWaiting() {
                try {
                    // If the user recently sought, avoid aggressive auto-downgrade
                    try { if (videoEl._suppressAutoAfterUserSeek && (Date.now() - videoEl._suppressAutoAfterUserSeek) < SEEK_SUPPRESS_MS) { try { debugLog(player, 'onWaiting: suppress downgrade due to recent user seek', { age: Date.now() - videoEl._suppressAutoAfterUserSeek }); } catch (e) {} return; } } catch (e) {}
                    // only act when adaptive is enabled and the user hasn't picked a manual quality
                    if (videoEl._userSelectedQuality) return;
                    if (videoEl._adaptiveEnabled === false || (player && player._adaptiveEnabled === false)) return;

                    clearStallTimer();
                    stallTimer = setTimeout(() => {
                        try {
                            // Compute how many seconds are buffered ahead of the currentTime.
                            let bufEnd = 0;
                            try {
                                if (videoEl.buffered && videoEl.buffered.length) {
                                    bufEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
                                }
                            } catch (e) { bufEnd = 0; }

                            const now = (videoEl.currentTime || 0);
                            const bufferAhead = (bufEnd - now);
                            try { debugLog(player, 'onWaiting bufferAhead', { bufferAhead: bufferAhead, now: now, bufEnd: bufEnd }); } catch (e) {}

                            // If we have more than MIN_BUFFER_AHEAD seconds buffered ahead,
                            // prefer to wait for the download to catch up instead of
                            // immediately downgrading.
                            if (bufferAhead > MIN_BUFFER_AHEAD) return;

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
                            // (this avoids marking the switch as a user preference)
                            try { debugLog(player, 'onWaiting downgrading', { current: current, lower: lower }); } catch (e) {}
                            changeQualityForPlayer(player, lower, { keepAllSources: true, auto: true });
                            videoEl._lastAutoDowngrade = Date.now();
                        } catch (e) {}
                    }, BUFFER_STALL_MS);
                } catch (e) {}
            }

            function onPlaying() {
                // clear pending stall timers when playback resumes
                clearStallTimer();
            }

            function onCanPlay() {
                // clear pending stall timers when browser reports canplay
                clearStallTimer();
            }

            videoEl.addEventListener('waiting', onWaiting);
            videoEl.addEventListener('stalled', onWaiting);
            // Mark suppress flag when a seek starts so we can avoid downgrading
            try { videoEl.addEventListener('seeking', function() { try { videoEl._suppressAutoAfterUserSeek = Date.now(); } catch (e) {} }); } catch (e) {}
            // Monitor timeupdate to detect sustained low-buffer states that
            // might not emit a 'waiting' event in some browsers.
            videoEl.addEventListener('timeupdate', onTimeUpdate);
            videoEl.addEventListener('playing', onPlaying);
            videoEl.addEventListener('canplay', onCanPlay);
            // cleanup when player is destroyed (best-effort)
            try { player.on && player.on('destroy', () => { clearStallTimer(); videoEl.removeEventListener('waiting', onWaiting); videoEl.removeEventListener('stalled', onWaiting); videoEl.removeEventListener('timeupdate', onTimeUpdate); videoEl.removeEventListener('playing', onPlaying); videoEl.removeEventListener('canplay', onCanPlay); }); } catch (e) {}
        }

        // start the adaptive quality wiring for film-player elements
        setTimeout(() => wireUpAdaptiveQuality('.film-player'), 150);
    });
</script>