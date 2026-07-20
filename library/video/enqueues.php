<?php
/**
 * Video Player Module — asset registration & conditional enqueue helpers
 *
 * Registers the module's scripts with correct deps and filemtime cache-busting
 * versions. Templates call the enqueue helper matching the mode they render, so
 * only the needed engine loads on a given page.
 *
 * @see library/video/player.php
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

add_action('wp_enqueue_scripts', 'tiagsspace_video_register_assets');
/**
 * Register (not enqueue) the module scripts. Templates enqueue by handle.
 */
function tiagsspace_video_register_assets() {
    $uri = get_template_directory_uri();

    // Progressive MP4 path: hand-rolled adaptive quality (legacy films only).
    wp_register_script(
        'plyr-adaptive-quality',
        $uri . '/library/js/plyr-adaptive-quality.js',
        ['plyr'],
        tiagsspace_asset_ver('/library/js/plyr-adaptive-quality.js'),
        true
    );

    // HLS path: vendored hls.js + our Plyr wiring.
    wp_register_script(
        'hls-js',
        $uri . '/library/js/hls/hls.min.js',
        [],
        tiagsspace_asset_ver('/library/js/hls/hls.min.js'),
        true
    );
    wp_register_script(
        'video-player-init-hls',
        $uri . '/library/js/video-player/init-hls.js',
        ['plyr', 'hls-js'],
        tiagsspace_asset_ver('/library/js/video-player/init-hls.js'),
        true
    );

    // Shared by both playback paths: fullscreen on play (see the file for why).
    wp_register_script(
        'video-player-fullscreen-on-play',
        $uri . '/library/js/video-player/fullscreen-on-play.js',
        ['plyr'],
        tiagsspace_asset_ver('/library/js/video-player/fullscreen-on-play.js'),
        true
    );

    // Shared by both playback paths: keep captions visible once iPhone hands
    // the video to Apple's native fullscreen player (see the file for why).
    wp_register_script(
        'video-player-ios-native-captions',
        $uri . '/library/js/video-player/ios-native-captions.js',
        ['plyr'],
        tiagsspace_asset_ver('/library/js/video-player/ios-native-captions.js'),
        true
    );
}

if (!function_exists('tiagsspace_video_enqueue_mp4')) {
    /** Enqueue the progressive MP4 adaptive-quality engine. */
    function tiagsspace_video_enqueue_mp4() {
        wp_enqueue_script('plyr-adaptive-quality');
        wp_enqueue_script('video-player-fullscreen-on-play');
        wp_enqueue_script('video-player-ios-native-captions');
    }
}

if (!function_exists('tiagsspace_video_enqueue_hls')) {
    /** Enqueue the HLS engine (hls.js + Plyr wiring). */
    function tiagsspace_video_enqueue_hls() {
        wp_enqueue_script('hls-js');
        wp_enqueue_script('video-player-init-hls');
        wp_enqueue_script('video-player-fullscreen-on-play');
        wp_enqueue_script('video-player-ios-native-captions');
    }
}
