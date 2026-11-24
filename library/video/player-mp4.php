<?php
/**
 * Video Player Module — the progressive MP4 ladder
 *
 * tiagsspace_video_mp4_sources( $attachment_id ): the original plus its encoded
 * child attachments (Videopack resolutions), de-duplicated by height and URL, each
 * with its REAL MIME (a .mov master is a video/quicktime rung: Safari / Chrome / iOS
 * play it, Firefox skips that <source>), the default rung (largest <= 1080) first in
 * the DOM so browsers never auto-load the big master. Used by the film
 * (player-film.php) and gallery video (player-gallery.php) renderers.
 *
 * @see library/video/player.php
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tiagsspace_video_mp4_sources')) {
    /**
     * The progressive MP4 ladder of a video attachment: the original plus its encoded children
     * (Videopack resolutions), de-duplicated, each with its real MIME, the default rung first.
     * Shared by the film renderer below and the gallery video renderer.
     *
     * @param int $attachment_id Video attachment ID.
     * @return array{sources: array<int, array{src: string, type: string, size: int}>, default: int, original: string|false}
     *               `default` is the default rung's height (0 when there is no usable source).
     */
    function tiagsspace_video_mp4_sources($attachment_id) {
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
            return ['sources' => [], 'default' => 0, 'original' => $original_url];
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

        return ['sources' => $sources, 'default' => $default_size, 'original' => $original_url];
    }
}
