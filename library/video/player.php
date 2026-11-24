<?php
/**
 * Video Player Module — entry point & shared helpers
 *
 * Self-hosted video from a WordPress attachment, drawn by the media players
 * (library/media/README.md): films on the Edge player (player-film.php), gallery
 * videos (player-gallery.php). The attachment's type decides the playback tech:
 *   - `.m3u8` attachment  → HLS  (hls.js where MediaSource exists, native elsewhere)
 *   - regular video file  → progressive MP4 ladder (player-mp4.php)
 * The old Plyr-skinned film renderers (render_video_player(), player-hls.php) and
 * their scripts were removed in Nov 2026, after the Edge player passed on iPhone.
 *
 * PORTABILITY CONTRACT: nothing in library/video/* may call theme-specific
 * helpers (get_field, ACF, custom post types). Callers gather those values and
 * pass them in via $args. This keeps the module copy-pasteable into other
 * themes (e.g. lento.world) with only a require + enqueue.
 *
 * @see library/video/player-film.php    films (the Edge player)
 * @see library/video/player-gallery.php gallery videos
 * @see library/video/player-mp4.php     the MP4 ladder (tiagsspace_video_mp4_sources())
 * @see library/video/hls-import.php  Media Library .hlspack.zip import
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/player-mp4.php';
require_once __DIR__ . '/player-gallery.php';   // gallery videos (the media players' markup, library/media/)
require_once __DIR__ . '/player-film.php';      // films: the Edge player (the media players' markup)

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
     * Build WebVTT caption <track> data for an attachment.
     *
     * Prefers the theme-owned ACF repeater (passed in via $args['caption_tracks'],
     * gathered by the caller — see the portability contract at the top of this
     * file) so HLS films get a working admin UI (Videopack's captions panel never
     * renders for an .m3u8 attachment). Falls back to Videopack's own
     * `_kgvid-meta` when the caller has none, so MP4 films using the old
     * workflow are unaffected.
     *
     * @param int   $attachment_id
     * @param array $args Renderer args; reads $args['caption_tracks'] if present.
     * @return array[] Each: src, srclang, label, kind, default (all pre-escaped
     *                 except default, which is a plain bool).
     */
    function tiagsspace_video_caption_tracks($attachment_id, $args = []) {
        $tracks = [];

        if (!empty($args['caption_tracks']) && is_array($args['caption_tracks'])) {
            foreach ($args['caption_tracks'] as $track) {
                if (is_array($track) && !empty($track['src'])) {
                    $tracks[] = [
                        'src'     => esc_url($track['src']),
                        'srclang' => esc_attr(strtolower($track['srclang'] ?? 'en')),
                        'label'   => esc_html($track['label'] ?? 'Captions'),
                        'kind'    => esc_attr($track['kind'] ?? 'subtitles'),
                        'default' => !empty($track['default']),
                    ];
                }
            }
            return $tracks;
        }

        $kgvid_meta = get_post_meta($attachment_id, '_kgvid-meta', true);

        if ($kgvid_meta && isset($kgvid_meta['track']) && is_array($kgvid_meta['track'])) {
            foreach ($kgvid_meta['track'] as $track) {
                if (is_array($track) && isset($track['src'])) {
                    $tracks[] = [
                        'src'     => esc_url($track['src']),
                        'srclang' => esc_attr(strtolower($track['srclang'] ?? 'en')),
                        'label'   => esc_html($track['label'] ?? 'Captions'),
                        'kind'    => esc_attr($track['kind'] ?? 'captions'),
                        'default' => !empty($track['default']),
                    ];
                }
            }
        }

        return $tracks;
    }
}

