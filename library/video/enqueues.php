<?php
/**
 * tiagsspace_asset_ver(): the filemtime version every theme asset URL carries (?ver=), so a
 * deploy changes the URL of whatever file changed (the server caches static files for a year).
 *
 * This file used to register the old film scripts (init-hls.js, plyr-adaptive-quality.js,
 * fullscreen-on-play.js, ios-native-captions.js); they were removed in Nov 2026 with the old
 * film player. The media players load their own modules (library/media/enqueue.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tiagsspace_asset_ver')) {
    /**
     * filemtime-based asset version for cache busting.
     *
     * @param string $relative_path Theme-relative path (leading slash).
     * @return int|null
     */
    function tiagsspace_asset_ver($relative_path) {
        $abs = get_template_directory() . $relative_path;
        return file_exists($abs) ? filemtime($abs) : null;
    }
}
