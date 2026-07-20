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

        // Captions come from one of two places, and they are mutually exclusive.
        //
        // 1. IN-MANIFEST renditions (`EXT-X-MEDIA:TYPE=SUBTITLES` in the playlist,
        //    generated into uploads/hls/<id>/subs/). This is the only form iOS
        //    renders in native fullscreen: sidecar <track> elements are a DOM
        //    concept with no AVPlayer media-selection group behind them, so
        //    Apple's player lists nothing and draws nothing.
        // 2. SIDECAR <track> elements, from $args['caption_tracks'].
        //
        // When the bundle has manifest renditions we must NOT also emit sidecar
        // tracks. Both would load into the same <video>, and two things break:
        // hls.js merges them and every cue renders twice (verified), and Plyr's
        // captions.update() — bound to `addtrack` — force-demotes whatever is
        // `showing` to `hidden`, so the promote-on-fullscreen handler in
        // ios-native-captions.js would target a sidecar track that iOS cannot
        // draw while hiding the manifest track that it can.
        //
        // Unrelated to CLOSED-CAPTIONS=NONE in the playlist, which only declares
        // that no embedded CEA-608 captions exist (without it Safari synthesizes
        // a phantom CC track).
        $has_manifest_subs = (bool) get_post_meta($attachment_id, '_tiagsspace_hls_subs', true);
        $caption_tracks    = $has_manifest_subs ? [] : tiagsspace_video_caption_tracks($attachment_id, $args);
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
            <?php if ($has_manifest_subs) : ?>data-manifest-subs="1"<?php endif; ?>
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

            <?php
            // Only one <track> may carry `default`: whichever row was flagged,
            // or the first row when none was.
            $default_index = 0;
            foreach ($caption_tracks as $index => $track) {
                if (!empty($track['default'])) {
                    $default_index = $index;
                    break;
                }
            }
            ?>
            <?php foreach ($caption_tracks as $index => $track) : ?>
                <track kind="<?php echo $track['kind']; ?>" src="<?php echo $track['src']; ?>" srclang="<?php echo $track['srclang']; ?>" label="<?php echo $track['label']; ?>"<?php echo ($index === $default_index) ? ' default' : ''; ?>>
            <?php endforeach; ?>

            <?php esc_html_e('Your browser does not support the video tag.', 'tiagsspace'); ?>
        </video>
        <?php
        return ob_get_clean();
    }
}
