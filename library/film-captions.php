<?php
/**
 * Film captions — ACF glue for HLS subtitle renditions.
 *
 * Deliberately OUTSIDE library/video/: that module may not call get_field() or
 * any ACF helper (portability contract, library/video/player.php:11-14). This
 * file reads ACF and hands plain arrays to the generator.
 *
 * Flow: caption rows live on the .m3u8 attachment (ACF group_69a2f1a4c0e11).
 * Saving the attachment regenerates uploads/hls/<id>/subs/ and rewrites the
 * playlist, which is what lets AVPlayer show subtitles in the iPhone's
 * fullscreen player. Nothing re-encodes video.
 *
 * @see library/video/hls-subtitles.php  the generator
 * @see library/video/player-hls.php     reads _tiagsspace_hls_subs
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tiagsspace_film_caption_tracks')) {
    /**
     * Read an attachment's caption rows into the generator's array shape.
     *
     * @param int $attachment_id
     * @return array[] Each: src, srclang, label, kind, default.
     */
    function tiagsspace_film_caption_tracks($attachment_id) {
        if (!function_exists('get_field')) {
            return [];
        }

        $rows = get_field('caption_tracks', $attachment_id);
        if (!is_array($rows)) {
            return [];
        }

        $tracks = [];
        foreach ($rows as $row) {
            $src = isset($row['caption_file']) ? $row['caption_file'] : '';
            // ACF returns a URL string here, but tolerate an array/ID if the
            // field's return format is ever changed.
            if (is_array($src)) {
                $src = isset($src['url']) ? $src['url'] : '';
            } elseif (is_numeric($src)) {
                $src = wp_get_attachment_url((int) $src);
            }
            if (!$src) {
                continue;
            }

            $lang = isset($row['caption_srclang']) ? strtolower(trim($row['caption_srclang'])) : '';
            if (!$lang) {
                continue;
            }

            $label = isset($row['caption_label']) && $row['caption_label'] !== ''
                ? $row['caption_label']
                : strtoupper($lang);

            $tracks[] = [
                'src'     => $src,
                'srclang' => $lang,
                'label'   => $label,
                'kind'    => 'subtitles',
                'default' => !empty($row['caption_default']),
            ];
        }

        return $tracks;
    }
}

if (!function_exists('tiagsspace_film_is_hls_attachment')) {
    /**
     * Is this post ID an .m3u8 attachment?
     *
     * @param int $post_id
     * @return bool
     */
    function tiagsspace_film_is_hls_attachment($post_id) {
        $post_id = intval($post_id);
        if (!$post_id || get_post_type($post_id) !== 'attachment') {
            return false;
        }
        return function_exists('tiagsspace_video_is_hls') && tiagsspace_video_is_hls($post_id);
    }
}

if (!function_exists('tiagsspace_film_regenerate_subtitles')) {
    /**
     * Regenerate an attachment's subtitle renditions from its ACF rows.
     *
     * @param int $attachment_id
     * @return array Languages generated (empty when cleared or on failure).
     */
    function tiagsspace_film_regenerate_subtitles($attachment_id) {
        if (!tiagsspace_film_is_hls_attachment($attachment_id)) {
            return [];
        }
        if (!function_exists('tiagsspace_hls_generate_subtitles')) {
            return [];
        }

        $tracks = tiagsspace_film_caption_tracks($attachment_id);
        $langs  = tiagsspace_hls_generate_subtitles($attachment_id, $tracks);

        // The film page markup depends on _tiagsspace_hls_subs, so the page
        // cache has to go. W3TC only — no-op elsewhere.
        if (function_exists('w3tc_flush_posts')) {
            w3tc_flush_posts();
        }

        return $langs;
    }
}

/**
 * Regenerate whenever the attachment's caption rows are saved.
 *
 * Priority 20 so ACF has written the meta before we read it back.
 */
add_action('acf/save_post', 'tiagsspace_film_on_acf_save', 20);
function tiagsspace_film_on_acf_save($post_id) {
    if (!is_numeric($post_id)) {
        return; // options page, taxonomy term, user — not ours
    }
    if (!tiagsspace_film_is_hls_attachment($post_id)) {
        return;
    }
    tiagsspace_film_regenerate_subtitles($post_id);
}

/**
 * Re-run after a bundle is (re-)imported: unzipping replaces the directory, so
 * any previously generated subs/ and the patched playlist are gone. Late
 * priority so hls-import.php has finished re-pointing the attachment.
 */
add_action('add_attachment', 'tiagsspace_film_on_bundle_import', 99);
function tiagsspace_film_on_bundle_import($post_id) {
    if (!tiagsspace_film_is_hls_attachment($post_id)) {
        return;
    }
    tiagsspace_film_regenerate_subtitles($post_id);
}

/**
 * WP-CLI: regenerate subtitle renditions.
 *
 *   wp tiagsspace hls-subs             # every .m3u8 attachment
 *   wp tiagsspace hls-subs 39581       # one attachment
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('tiagsspace hls-subs', function ($args) {
        $ids = !empty($args)
            ? array_map('intval', $args)
            : get_posts([
                'post_type'      => 'attachment',
                'post_mime_type' => 'application/vnd.apple.mpegurl',
                'post_status'    => 'inherit',
                'numberposts'    => -1,
                'fields'         => 'ids',
            ]);

        foreach ($ids as $id) {
            if (!tiagsspace_film_is_hls_attachment($id)) {
                WP_CLI::warning("$id: not an .m3u8 attachment, skipped");
                continue;
            }
            $langs = tiagsspace_film_regenerate_subtitles($id);
            if ($langs) {
                WP_CLI::success("$id: generated " . implode(', ', $langs));
            } else {
                WP_CLI::log("$id: no caption tracks — cleared");
            }
        }
    });
}
