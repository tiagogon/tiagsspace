<?php
/**
 * HLS subtitle renditions — generate EXT-X-MEDIA:TYPE=SUBTITLES into a bundle.
 *
 * WHY THIS EXISTS: sidecar <track> elements are a DOM concept with no AVPlayer
 * media-selection group behind them, so iOS lists nothing and draws nothing for
 * them in native fullscreen. The only form iOS renders is subtitle renditions
 * declared inside the playlist. This writes them, without re-encoding a single
 * frame of video — the ~12 GB of segments are never touched.
 *
 * THE FORMAT HERE IS PROVEN ON DEVICE, not inferred. Do not "tidy" it without
 * re-testing on a real iPhone:
 *   - 6s segments, matching the video cadence (SEG in bin/hls-package.sh)
 *   - cue timestamps stay ABSOLUTE (not per-segment local)
 *   - every segment carries X-TIMESTAMP-MAP=LOCAL:00:00:00.000,MPEGTS:0, i.e.
 *     an identity map, which is correct here: ffprobe measures this ladder's
 *     media timeline starting at 0.040s, and RFC 8216 makes 0→0 the default
 *     assumption anyway. Authoring spec rule 5.3 requires the header present.
 *   - a cue spanning a boundary is repeated in both segments (RFC 8216 §3.5:
 *     a segment must contain all cues displayed during its EXTINF)
 *
 * PORTABILITY CONTRACT (library/video/player.php:11-14): nothing here may call
 * get_field() or any ACF/theme helper. Callers pass $tracks in. The ACF glue
 * lives in library/film-captions.php.
 *
 * @see library/film-captions.php  ACF glue + WP-CLI
 * @see library/video/hls-import.php  bundle layout, path guard, rrmdir
 * @see library/video/player-hls.php  reads _tiagsspace_hls_subs to drop sidecar tracks
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Segment length in seconds. Matches SEG in bin/hls-package.sh. */
if (!defined('TIAGSSPACE_HLS_SUB_SEG')) {
    define('TIAGSSPACE_HLS_SUB_SEG', 6);
}

/** Meta key listing the languages a bundle has renditions for, e.g. "pt,en". */
if (!defined('TIAGSSPACE_HLS_SUBS_META')) {
    define('TIAGSSPACE_HLS_SUBS_META', '_tiagsspace_hls_subs');
}

if (!function_exists('tiagsspace_hls_guard_base')) {
    /** Absolute path of uploads/hls — nothing outside this is ever written or removed. */
    function tiagsspace_hls_guard_base() {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . 'hls';
    }
}

if (!function_exists('tiagsspace_hls_bundle_dir')) {
    /**
     * Absolute path of an attachment's bundle directory, or '' if there isn't one.
     *
     * Prefers the _tiagsspace_hls_dir meta written at import
     * (hls-import.php:210) and falls back to the hls/<id> convention.
     *
     * @param int $attachment_id
     * @return string
     */
    function tiagsspace_hls_bundle_dir($attachment_id) {
        $attachment_id = intval($attachment_id);
        if (!$attachment_id) {
            return '';
        }

        $uploads = wp_upload_dir();
        $rel     = get_post_meta($attachment_id, '_tiagsspace_hls_dir', true);
        $dir     = $rel
            ? trailingslashit($uploads['basedir']) . ltrim($rel, '/')
            : trailingslashit(tiagsspace_hls_guard_base()) . $attachment_id;

        return is_dir($dir) ? untrailingslashit($dir) : '';
    }
}

if (!function_exists('tiagsspace_hls_playlist_path')) {
    /**
     * The bundle's master playlist on disk.
     *
     * NOT master.m3u8: the importer renames it to <slug>.m3u8
     * (hls-import.php:190-198), and that renamed file is what the player loads.
     *
     * @param int $attachment_id
     * @return string Absolute path, or '' if not found.
     */
    function tiagsspace_hls_playlist_path($attachment_id) {
        $file = get_attached_file($attachment_id);
        if ($file && preg_match('/\.m3u8$/i', $file) && file_exists($file)) {
            return $file;
        }

        // Fall back to whatever .m3u8 sits at the bundle root.
        $dir = tiagsspace_hls_bundle_dir($attachment_id);
        if (!$dir) {
            return '';
        }
        foreach ((array) glob($dir . '/*.m3u8') as $candidate) {
            return $candidate;
        }
        return '';
    }
}

if (!function_exists('tiagsspace_hls_bundle_duration')) {
    /**
     * Content duration in seconds, summed from a variant playlist's #EXTINF.
     *
     * Read from the bundle itself rather than re-probing video: it is exact,
     * costs one small file read, and needs no ffmpeg on the server.
     *
     * @param int $attachment_id
     * @return float 0.0 when it cannot be determined.
     */
    function tiagsspace_hls_bundle_duration($attachment_id) {
        $dir = tiagsspace_hls_bundle_dir($attachment_id);
        if (!$dir) {
            return 0.0;
        }

        $variants = glob($dir . '/stream_*/playlist.m3u8');
        if (empty($variants)) {
            return 0.0;
        }

        $contents = @file_get_contents($variants[0]);
        if ($contents === false) {
            return 0.0;
        }

        $total = 0.0;
        if (preg_match_all('/^#EXTINF:\s*([0-9.]+)/mi', $contents, $m)) {
            foreach ($m[1] as $seconds) {
                $total += (float) $seconds;
            }
        }
        return $total;
    }
}

if (!function_exists('tiagsspace_hls_parse_vtt')) {
    /**
     * Parse a WebVTT file into [start, end, timing_line, body] cues.
     *
     * @param string $path
     * @return array[]
     */
    function tiagsspace_hls_parse_vtt($path) {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        // Normalise line endings and strip a UTF-8 BOM.
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        $cues = [];
        foreach (preg_split('/\n\s*\n/', $raw) as $block) {
            if (strpos($block, '-->') === false) {
                continue; // header, NOTE, STYLE or blank
            }
            $lines = array_values(array_filter(explode("\n", trim($block)), 'strlen'));
            $index = -1;
            foreach ($lines as $i => $line) {
                if (strpos($line, '-->') !== false) {
                    $index = $i;
                    break;
                }
            }
            if ($index === -1) {
                continue;
            }
            $timing = $lines[$index];
            if (!preg_match('/^\s*([0-9:.]+)\s*-->\s*([0-9:.]+)/', $timing, $m)) {
                continue;
            }
            $body = implode("\n", array_slice($lines, $index + 1));
            if ($body === '') {
                continue;
            }
            $cues[] = [
                tiagsspace_hls_vtt_seconds($m[1]),
                tiagsspace_hls_vtt_seconds($m[2]),
                trim($timing),
                $body,
            ];
        }
        return $cues;
    }
}

if (!function_exists('tiagsspace_hls_vtt_seconds')) {
    /**
     * "00:05:11.400" or "05:11.400" → float seconds.
     *
     * @param string $stamp
     * @return float
     */
    function tiagsspace_hls_vtt_seconds($stamp) {
        $parts = explode(':', trim($stamp));
        $seconds = 0.0;
        foreach ($parts as $part) {
            $seconds = $seconds * 60 + (float) $part;
        }
        return $seconds;
    }
}

if (!function_exists('tiagsspace_hls_write_subtitles')) {
    /**
     * Write subs/<lang>/ for every track: segmented WebVTT + its media playlist.
     *
     * @param int   $attachment_id
     * @param array $tracks Each: src (URL), srclang, label, default.
     * @return array Languages written, in order.
     */
    function tiagsspace_hls_write_subtitles($attachment_id, $tracks) {
        $dir = tiagsspace_hls_bundle_dir($attachment_id);
        if (!$dir || empty($tracks)) {
            return [];
        }

        $duration = tiagsspace_hls_bundle_duration($attachment_id);
        if ($duration <= 0) {
            return [];
        }

        $seg     = (float) TIAGSSPACE_HLS_SUB_SEG;
        $count   = (int) ceil($duration / $seg);
        $written = [];

        foreach ($tracks as $track) {
            $lang = sanitize_key(isset($track['srclang']) ? $track['srclang'] : '');
            $path = tiagsspace_hls_local_path_for_url(isset($track['src']) ? $track['src'] : '');
            if (!$lang || !$path) {
                continue;
            }

            $cues = tiagsspace_hls_parse_vtt($path);
            if (empty($cues)) {
                continue;
            }

            $lang_dir = $dir . '/subs/' . $lang;
            if (!wp_mkdir_p($lang_dir)) {
                continue;
            }

            $playlist = [
                '#EXTM3U',
                '#EXT-X-VERSION:7',
                '#EXT-X-TARGETDURATION:' . (int) ceil($seg),
                '#EXT-X-MEDIA-SEQUENCE:0',
                '#EXT-X-PLAYLIST-TYPE:VOD',
            ];

            for ($i = 0; $i < $count; $i++) {
                $from = $i * $seg;
                $to   = min(($i + 1) * $seg, $duration);

                // Every cue overlapping this window, timestamps left absolute.
                // Overlapping cues appear in each window they touch (RFC 8216 §3.5).
                $lines = ['WEBVTT', 'X-TIMESTAMP-MAP=LOCAL:00:00:00.000,MPEGTS:0', ''];
                foreach ($cues as $cue) {
                    if ($cue[0] < $to && $cue[1] > $from) {
                        $lines[] = $cue[2];
                        $lines[] = $cue[3];
                        $lines[] = '';
                    }
                }

                $name = sprintf('seg_%04d.vtt', $i);
                file_put_contents($lang_dir . '/' . $name, implode("\n", $lines) . "\n");

                $playlist[] = sprintf('#EXTINF:%.3f,', $to - $from);
                $playlist[] = $name;
            }

            $playlist[] = '#EXT-X-ENDLIST';
            file_put_contents($lang_dir . '/playlist.m3u8', implode("\n", $playlist) . "\n");

            $written[] = $lang;
        }

        return $written;
    }
}

if (!function_exists('tiagsspace_hls_local_path_for_url')) {
    /**
     * Resolve an uploads URL to its path on disk.
     *
     * Deliberately never fetches over HTTP: the file is local, and a request
     * would be slower and could fail behind auth or a cache layer.
     *
     * @param string $url
     * @return string '' when it cannot be resolved to an existing file.
     */
    function tiagsspace_hls_local_path_for_url($url) {
        if (!$url) {
            return '';
        }

        $id = attachment_url_to_postid($url);
        if ($id) {
            $file = get_attached_file($id);
            if ($file && file_exists($file)) {
                return $file;
            }
        }

        $uploads = wp_upload_dir();
        if (strpos($url, $uploads['baseurl']) === 0) {
            $file = $uploads['basedir'] . substr($url, strlen($uploads['baseurl']));
            if (file_exists($file)) {
                return $file;
            }
        }

        return '';
    }
}

if (!function_exists('tiagsspace_hls_rewrite_playlist')) {
    /**
     * Add the EXT-X-MEDIA subtitle lines and SUBTITLES= to every variant.
     *
     * Idempotent: the pristine playlist is kept as <slug>.orig.m3u8 on first
     * run and every rewrite regenerates from it, so repeated saves cannot
     * double-inject.
     *
     * @param int   $attachment_id
     * @param array $tracks
     * @param array $langs Languages actually written by tiagsspace_hls_write_subtitles().
     * @return bool
     */
    function tiagsspace_hls_rewrite_playlist($attachment_id, $tracks, $langs) {
        $playlist = tiagsspace_hls_playlist_path($attachment_id);
        if (!$playlist || empty($langs)) {
            return false;
        }

        $pristine = preg_replace('/\.m3u8$/i', '.orig.m3u8', $playlist);
        if (!file_exists($pristine) && !@copy($playlist, $pristine)) {
            return false;
        }

        $source = @file_get_contents($pristine);
        if ($source === false) {
            return false;
        }

        // Build one EXT-X-MEDIA per language, in $tracks order so the flagged
        // default keeps its position. Only one may carry DEFAULT=YES.
        $media       = [];
        $has_default = false;
        foreach ($tracks as $track) {
            $lang = sanitize_key(isset($track['srclang']) ? $track['srclang'] : '');
            if (!$lang || !in_array($lang, $langs, true)) {
                continue;
            }
            $label      = isset($track['label']) && $track['label'] !== '' ? $track['label'] : strtoupper($lang);
            $is_default = !$has_default && !empty($track['default']);
            if ($is_default) {
                $has_default = true;
            }
            $media[] = sprintf(
                '#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID="subs",NAME="%s",LANGUAGE="%s",DEFAULT=%s,AUTOSELECT=YES,FORCED=NO,URI="subs/%s/playlist.m3u8"',
                str_replace('"', '', $label),
                $lang,
                $is_default ? 'YES' : 'NO',
                $lang
            );
        }

        if (empty($media)) {
            return false;
        }

        // Nothing was flagged: make the first one default so iOS auto-selects.
        if (!$has_default) {
            $media[0] = str_replace('DEFAULT=NO', 'DEFAULT=YES', $media[0]);
        }

        $out      = [];
        $injected = false;
        foreach (explode("\n", $source) as $line) {
            if (strpos($line, '#EXT-X-STREAM-INF:') === 0) {
                if (!$injected) {
                    foreach ($media as $tag) {
                        $out[] = $tag;
                    }
                    $injected = true;
                }
                if (strpos($line, 'SUBTITLES=') === false) {
                    $line .= ',SUBTITLES="subs"';
                }
            }
            $out[] = $line;
        }

        if (!$injected) {
            return false;
        }

        return (bool) file_put_contents($playlist, implode("\n", $out));
    }
}

if (!function_exists('tiagsspace_hls_generate_subtitles')) {
    /**
     * Write renditions and wire them into the playlist.
     *
     * @param int   $attachment_id
     * @param array $tracks
     * @return array Languages generated.
     */
    function tiagsspace_hls_generate_subtitles($attachment_id, $tracks) {
        if (empty($tracks)) {
            tiagsspace_hls_clear_subtitles($attachment_id);
            return [];
        }

        // Start from pristine so removed languages leave no orphans behind.
        tiagsspace_hls_clear_subtitles($attachment_id);

        $langs = tiagsspace_hls_write_subtitles($attachment_id, $tracks);
        if (empty($langs) || !tiagsspace_hls_rewrite_playlist($attachment_id, $tracks, $langs)) {
            tiagsspace_hls_clear_subtitles($attachment_id);
            return [];
        }

        update_post_meta($attachment_id, TIAGSSPACE_HLS_SUBS_META, implode(',', $langs));
        return $langs;
    }
}

if (!function_exists('tiagsspace_hls_clear_subtitles')) {
    /**
     * Restore the pristine playlist and remove subs/.
     *
     * @param int $attachment_id
     * @return void
     */
    function tiagsspace_hls_clear_subtitles($attachment_id) {
        $playlist = tiagsspace_hls_playlist_path($attachment_id);
        if ($playlist) {
            $pristine = preg_replace('/\.m3u8$/i', '.orig.m3u8', $playlist);
            if (file_exists($pristine)) {
                @copy($pristine, $playlist);
            }
        }

        $dir = tiagsspace_hls_bundle_dir($attachment_id);
        if ($dir && is_dir($dir . '/subs')) {
            tiagsspace_hls_rrmdir($dir . '/subs', tiagsspace_hls_guard_base());
        }

        delete_post_meta($attachment_id, TIAGSSPACE_HLS_SUBS_META);
    }
}
