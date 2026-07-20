<?php
/**
 * Video Player Module — HLS renderer
 *
 * Renders the Plyr <video> pointed at an HLS master playlist (.m3u8). Playback
 * engine is chosen client-side by library/js/video-player/init-hls.js:
 *   - Safari / iOS  → native HLS (the OS handles adaptive switching)
 *   - everything else → hls.js
 *
 * The <video> carries a single <source type="application/vnd.apple.mpegurl">.
 * Captions (if any) remain WebVTT <track> elements, which work with both the
 * native player and hls.js.
 *
 * @see library/video/player.php  shared helpers & dispatcher
 * @see library/js/video-player/init-hls.js  playback wiring
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tiagsspace_render_video_hls')) {
    /**
     * @param int   $attachment_id .m3u8 playlist attachment ID.
     * @param array $args          Renderer args (see render_video_player()).
     * @return string
     */
    function tiagsspace_render_video_hls($attachment_id, $args) {
        $playlist_url = wp_get_attachment_url($attachment_id);
        if (!$playlist_url) {
            return '';
        }

        $poster = tiagsspace_video_poster($args);

        // Captions for HLS films are sidecar WebVTT <track>s read from
        // Videopack's _kgvid-meta ON THE .M3U8 ATTACHMENT (same workflow as MP4
        // films: upload the .vtt separately). They are unrelated to the
        // CLOSED-CAPTIONS=NONE attribute in master.m3u8, which only declares
        // that no embedded CEA-608 captions exist in the video stream (without
        // it Safari synthesizes a phantom CC track). CAVEAT for the first
        // captioned HLS migration: Videopack's captions UI may not appear on an
        // attachment with MIME application/vnd.apple.mpegurl — if so, the
        // _kgvid-meta 'track' array needs a filter or manual meta entry.
        $caption_tracks = tiagsspace_video_caption_tracks($attachment_id);
        $player_options = isset($args['player_options']) && is_array($args['player_options']) ? $args['player_options'] : [];

        // Quality for HLS is driven by the JS (hls.levels / native), not by
        // <source> sizes — so no quality.default here. And no quality pane at
        // all: adapting to the connection is the player's job, and Safari's
        // native path never had one, so this keeps every browser consistent.
        // (The MP4 renderer keeps its menu — it has no ABR to fall back on.)
        $config = tiagsspace_video_build_config($args, ['settings' => ['captions']]);
        $json = wp_json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $extra_attrs   = tiagsspace_video_extra_attrs($args);
        $boolean_attrs = tiagsspace_video_boolean_attrs($player_options);

        ob_start();
        ?>
        <video class="plyr film-player film-player--hls" data-debug="0"
            data-hls-src="<?php echo esc_url($playlist_url); ?>"
            <?php echo $extra_attrs; ?>
            <?php if ($poster) : ?>poster="<?php echo $poster; ?>"<?php endif; ?>
            data-plyr-config='<?php echo esc_attr($json); ?>'
            preload="metadata"
            webkit-playsinline
            playsinline
            disablePictureInPicture
            <?php echo $boolean_attrs; ?>
        >
            <source src="<?php echo esc_url($playlist_url); ?>" type="application/vnd.apple.mpegurl">

            <?php foreach ($caption_tracks as $index => $track) : ?>
                <track kind="<?php echo $track['kind']; ?>" src="<?php echo $track['src']; ?>" srclang="<?php echo $track['srclang']; ?>" label="<?php echo $track['label']; ?>"<?php echo ($index === 0) ? ' default' : ''; ?>>
            <?php endforeach; ?>

            <?php esc_html_e('Your browser does not support the video tag.', 'tiagsspace'); ?>
        </video>
        <?php
        return ob_get_clean();
    }
}
