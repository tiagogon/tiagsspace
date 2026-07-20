<?php
/**
 * Video Player Module — progressive MP4 renderer
 *
 * Renders the Plyr <video> with a de-duplicated, correctly-typed quality ladder
 * built from the original attachment plus its encoded child attachments
 * (Videopack resolutions). Adaptive quality is handled client-side by
 * library/js/plyr-adaptive-quality.js.
 *
 * Corrections over the old library/plyr-player.php:
 *   - real MIME per <source> (was hardcoded video/mp4) — a .mov master is now
 *     a selectable rung tagged video/quicktime (plays in Safari/Chrome/iOS;
 *     Firefox skips it and falls through to the MP4 rungs), no longer served
 *     first-and-mislabeled the way it used to be
 *   - sources de-duplicated by size and URL (killed the triple-1080p menu)
 *   - default quality first in DOM (browsers load the first source) and
 *     quality.default computed from the ladder instead of a hardcoded 1080
 *   - preload="metadata"; dropped invalid crossorigin on <track>/<video>
 *   - no debug echo side-effects
 *
 * @see library/video/player.php  shared helpers & dispatcher
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tiagsspace_render_video_mp4')) {
    /**
     * @param int   $attachment_id Video attachment ID.
     * @param array $args          Renderer args (see render_video_player()).
     * @return string
     */
    function tiagsspace_render_video_mp4($attachment_id, $args) {
        $original_url  = wp_get_attachment_url($attachment_id);

        // Collect ladder sources, de-duplicated by pixel height and URL. Each
        // source carries its REAL MIME type. A .mov/quicktime master is included
        // as a selectable rung tagged video/quicktime: Safari/Chrome/iOS can play
        // it, browsers that can't (Firefox) skip that <source> and fall through to
        // the MP4 rungs. The earlier bug — the .mov served FIRST and mislabeled as
        // MP4, so every browser greedily loaded 400+ MB — is prevented by the
        // ordering below (the default rung, always <=1080, is emitted first).
        $sources   = [];
        $seen_size = [];
        $seen_url  = [];

        $add_source = function ($id, $url) use (&$sources, &$seen_size, &$seen_url) {
            if (!$url) {
                return;
            }
            $mime = get_post_mime_type($id);
            if (strpos((string) $mime, 'video/') !== 0) {
                return; // videos only
            }
            $meta = wp_get_attachment_metadata($id);
            $size = isset($meta['height']) ? intval($meta['height']) : 0;
            if (!$size) {
                return;
            }
            if (isset($seen_size[$size]) || isset($seen_url[$url])) {
                return;
            }
            $seen_size[$size] = true;
            $seen_url[$url]   = true;
            $sources[] = [
                'src'  => $url,
                'type' => $mime,
                'size' => $size,
            ];
        };

        // Original (master, any video container) + encoded children.
        $add_source($attachment_id, $original_url);

        $children = get_children([
            'post_parent'    => $attachment_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'video',
            'numberposts'    => -1,
        ]);
        if ($children) {
            foreach ($children as $child) {
                $add_source($child->ID, wp_get_attachment_url($child->ID));
            }
        }

        if (empty($sources)) {
            // No usable video source — offer the original as a download only.
            if ($original_url) {
                return '<div class="video-player-fallback"><a href="' . esc_url($original_url) . '" download>' . esc_html__('Download video', 'tiagsspace') . '</a></div>';
            }
            return '';
        }

        // Default quality: largest rung <= 1080; else the smallest available.
        $all_sizes = array_column($sources, 'size');
        $capped    = array_filter($all_sizes, function ($s) { return $s <= 1080; });
        $default_size = !empty($capped) ? max($capped) : min($all_sizes);

        // DOM order: default source first (browsers load the first <source>),
        // remaining sources after, largest→smallest. Plyr builds its own menu
        // order from the size attributes regardless of DOM order.
        usort($sources, function ($a, $b) use ($default_size) {
            if ($a['size'] === $default_size) return -1;
            if ($b['size'] === $default_size) return 1;
            return $b['size'] - $a['size'];
        });

        $poster         = tiagsspace_video_poster($args);
        $caption_tracks = tiagsspace_video_caption_tracks($attachment_id, $args);
        $player_options = isset($args['player_options']) && is_array($args['player_options']) ? $args['player_options'] : [];

        $config = tiagsspace_video_build_config($args, [
            'quality' => ['default' => $default_size],
        ]);
        $json = wp_json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $extra_attrs   = tiagsspace_video_extra_attrs($args);
        $boolean_attrs = tiagsspace_video_boolean_attrs($player_options);

        ob_start();
        ?>
        <video class="plyr film-player" data-debug="0"
            <?php echo $extra_attrs; ?>
            <?php if ($poster) : ?>poster="<?php echo $poster; ?>"<?php endif; ?>
            data-plyr-config='<?php echo esc_attr($json); ?>'
            preload="metadata"
            webkit-playsinline
            playsinline
            disablePictureInPicture
            <?php echo $boolean_attrs; ?>
        >
            <?php foreach ($sources as $source) : ?>
                <source src="<?php echo esc_url($source['src']); ?>" type="<?php echo esc_attr($source['type']); ?>" size="<?php echo esc_attr($source['size']); ?>" label="<?php echo esc_attr($source['size'] . 'p'); ?>">
            <?php endforeach; ?>

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
            <?php if ($original_url) : ?><a href="<?php echo esc_url($original_url); ?>" download><?php esc_html_e('Download', 'tiagsspace'); ?></a><?php endif; ?>
        </video>
        <?php
        return ob_get_clean();
    }
}
