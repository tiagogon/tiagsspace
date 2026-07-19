<?php
/**
 * Video Player Module — entry point & shared helpers
 *
 * Portable player module (Plyr + optional HLS). Renders a self-hosted video
 * player from a single WordPress attachment. The attachment's type decides the
 * playback tech:
 *   - `.m3u8` attachment  → HLS  (native on Safari/iOS, hls.js elsewhere)
 *   - regular video file  → progressive MP4 ladder (Plyr + adaptive-quality JS)
 *
 * PORTABILITY CONTRACT: nothing in library/video/* may call theme-specific
 * helpers (get_field, ACF, custom post types). Callers gather those values and
 * pass them in via $args. This keeps the module copy-pasteable into other
 * themes (e.g. lento.world) with only a require + enqueue.
 *
 * @see library/video/player-mp4.php  progressive MP4 renderer
 * @see library/video/player-hls.php  HLS renderer
 * @see library/video/enqueues.php    conditional asset loading
 * @see library/video/hls-import.php  Media Library .hlspack.zip import
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/player-mp4.php';
require_once __DIR__ . '/player-hls.php';

if (!function_exists('render_video_player')) {
    /**
     * Render a video player for an attachment, dispatching HLS vs MP4.
     *
     * @param array $args {
     *     @type int    $attachment_id  Required. The video/playlist attachment ID.
     *     @type string $poster_url     Optional. Poster image URL. Falls back to
     *                                  the current post's featured image (xlarge).
     *     @type string $title          Optional. Player title. Falls back to
     *                                  get_the_title().
     *     @type array  $player_options Optional. Flags from ACF film_player_options
     *                                  (controls/keyboard/loop/autoplay/muted).
     *     @type array  $extra_attrs    Optional. Extra HTML attributes for <video>.
     * }
     * @return string HTML markup, or '' on invalid input.
     */
    function render_video_player($args) {
        if (!is_array($args)) {
            return '';
        }

        $attachment_id = isset($args['attachment_id']) ? intval($args['attachment_id']) : 0;
        if (!$attachment_id) {
            return '';
        }

        if (tiagsspace_video_is_hls($attachment_id)) {
            return tiagsspace_render_video_hls($attachment_id, $args);
        }

        return tiagsspace_render_video_mp4($attachment_id, $args);
    }
}

if (!function_exists('tiagsspace_video_is_hls')) {
    /**
     * Is this attachment an HLS playlist (.m3u8)?
     *
     * Checks MIME first, then the attached file path, then the URL — the import
     * flow re-points the attachment at master.m3u8 and sets the MIME, but we stay
     * robust if the MIME wasn't updated.
     *
     * @param int $attachment_id
     * @return bool
     */
    function tiagsspace_video_is_hls($attachment_id) {
        $mime = get_post_mime_type($attachment_id);
        if (in_array($mime, ['application/vnd.apple.mpegurl', 'application/x-mpegurl', 'vnd.apple.mpegurl'], true)) {
            return true;
        }

        $file = get_attached_file($attachment_id);
        if ($file && preg_match('/\.m3u8$/i', $file)) {
            return true;
        }

        $url = wp_get_attachment_url($attachment_id);
        if ($url && preg_match('/\.m3u8(\?|$)/i', $url)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('tiagsspace_video_poster')) {
    /**
     * Resolve the poster URL: explicit arg, else the current post's featured
     * image at the largest registered size (avoids shipping the full original).
     *
     * @param array $args
     * @return string Escaped poster URL, or '' if none.
     */
    function tiagsspace_video_poster($args) {
        if (!empty($args['poster_url'])) {
            return esc_url($args['poster_url']);
        }

        $thumbnail_id = get_post_thumbnail_id(get_the_ID());
        if (!$thumbnail_id) {
            return '';
        }

        // Prefer a large-but-not-original size; fall back to full.
        $url = wp_get_attachment_image_url($thumbnail_id, 'xlarge');
        if (!$url) {
            $url = wp_get_attachment_url($thumbnail_id);
        }

        return $url ? esc_url($url) : '';
    }
}

if (!function_exists('tiagsspace_video_caption_tracks')) {
    /**
     * Build WebVTT caption <track> data from Videopack (_kgvid-meta).
     *
     * @param int $attachment_id
     * @return array[] Each: src, srclang, label, kind (all pre-escaped).
     */
    function tiagsspace_video_caption_tracks($attachment_id) {
        $tracks = [];
        $kgvid_meta = get_post_meta($attachment_id, '_kgvid-meta', true);

        if ($kgvid_meta && isset($kgvid_meta['track']) && is_array($kgvid_meta['track'])) {
            foreach ($kgvid_meta['track'] as $track) {
                if (is_array($track) && isset($track['src'])) {
                    $tracks[] = [
                        'src'     => esc_url($track['src']),
                        'srclang' => esc_attr(strtolower($track['srclang'] ?? 'en')),
                        'label'   => esc_html($track['label'] ?? 'Captions'),
                        'kind'    => esc_attr($track['kind'] ?? 'captions'),
                    ];
                }
            }
        }

        return $tracks;
    }
}

if (!function_exists('tiagsspace_video_build_config')) {
    /**
     * Build the Plyr JSON config shared by both playback paths.
     *
     * @param array $args      Renderer args (title, player_options).
     * @param array $overrides Path-specific overrides merged last (e.g. quality).
     * @return array Plyr config, ready for wp_json_encode.
     */
    function tiagsspace_video_build_config($args, $overrides = []) {
        $player_options = isset($args['player_options']) && is_array($args['player_options'])
            ? $args['player_options']
            : [];

        $title = isset($args['title']) && $args['title'] !== ''
            ? $args['title']
            : get_the_title();

        $config = [
            'title'    => $title,
            'settings' => ['captions', 'quality'],
            'captions' => ['active' => true, 'language' => 'auto', 'update' => true],
            'ratio'    => '16:9',
            'tooltips' => ['controls' => false, 'seek' => false],
            'seekTime' => 10,
            'ads'      => ['enabled' => false],
            'previewThumbnails' => ['enabled' => false],
            'fullscreen' => ['enabled' => true, 'fallback' => true, 'iosNative' => true, 'container' => null],
            'pip'      => ['enabled' => false],
        ];

        // Controls: a fixed set when enabled, otherwise none (background videos).
        if (in_array('controls', $player_options, true)) {
            $config['controls'] = ['play-large', 'play', 'progress', 'current-time', 'mute', 'captions', 'settings', 'fullscreen'];
        } else {
            $config['controls'] = false;
        }

        if (in_array('keyboard', $player_options, true)) {
            $config['keyboard'] = ['focused' => true, 'global' => false];
        }
        if (in_array('loop', $player_options, true)) {
            $config['loop'] = ['active' => true];
        }
        if (in_array('autoplay', $player_options, true)) {
            $config['autoplay'] = true;
        }
        if (in_array('muted', $player_options, true)) {
            $config['muted'] = true;
        }

        return array_merge($config, $overrides);
    }
}

if (!function_exists('tiagsspace_video_boolean_attrs')) {
    /**
     * Boolean HTML attributes for the <video> element from player options.
     *
     * @param array $player_options
     * @return string e.g. "muted autoplay "
     */
    function tiagsspace_video_boolean_attrs($player_options) {
        if (!is_array($player_options)) {
            return '';
        }
        $out = '';
        foreach (['muted', 'autoplay', 'loop', 'controls'] as $flag) {
            if (in_array($flag, $player_options, true)) {
                $out .= $flag . ' ';
            }
        }
        return $out;
    }
}

if (!function_exists('tiagsspace_video_extra_attrs')) {
    /**
     * Serialize caller-provided extra HTML attributes.
     *
     * @param array $args
     * @return string
     */
    function tiagsspace_video_extra_attrs($args) {
        if (empty($args['extra_attrs']) || !is_array($args['extra_attrs'])) {
            return '';
        }
        $out = '';
        foreach ($args['extra_attrs'] as $attr => $value) {
            $out .= esc_attr($attr) . '="' . esc_attr($value) . '" ';
        }
        return $out;
    }
}
