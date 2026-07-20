<?php
/**
 * Video Player Module — HLS bundle import
 *
 * Lets an admin drag a `<slug>.hlspack.zip` (produced by bin/hls-package.sh)
 * into the Media Library. On upload the zip is unpacked into
 * uploads/hls/<attachment-id>/, the attachment is re-pointed at the extracted
 * master.m3u8 (MIME set to application/vnd.apple.mpegurl), and the zip is
 * deleted. The attachment then behaves like any media item and can be selected
 * in the film's "Self Host Film" field — where the player detects .m3u8 and
 * plays via HLS.
 *
 * On failure nothing is half-done: the extracted folder is removed, the zip
 * attachment is left intact, and an admin notice + import.log entry explain why.
 *
 * @see bin/hls-package.sh       produces the bundle
 * @see library/video/player.php consumes the .m3u8 attachment
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * Pure helpers (no WP globals) — unit-testable in isolation.
 * ---------------------------------------------------------------------- */

if (!function_exists('tiagsspace_hls_is_bundle')) {
    /**
     * Is this filename one of our HLS bundle zips?
     *
     * @param string $filename basename
     * @return bool
     */
    function tiagsspace_hls_is_bundle($filename) {
        return (bool) preg_match('/\.hlspack\.zip$/i', (string) $filename);
    }
}

if (!function_exists('tiagsspace_hls_playlist_name')) {
    /**
     * Derive a film-specific playlist filename from the uploaded bundle path,
     * e.g. ".../project-forest.hlspack.zip" -> "project-forest.m3u8".
     * Falls back to "master.m3u8" if nothing usable remains after sanitising.
     *
     * @param string $zip_path
     * @return string
     */
    function tiagsspace_hls_playlist_name($zip_path) {
        $base = basename((string) $zip_path);
        $base = preg_replace('/\.hlspack\.zip$/i', '', $base);
        $base = preg_replace('/\.zip$/i', '', $base);
        $base = sanitize_file_name($base);
        return ($base !== '') ? $base . '.m3u8' : 'master.m3u8';
    }
}

if (!function_exists('tiagsspace_hls_rrmdir')) {
    /**
     * Recursively delete a directory, but only if it lives under $guard_base.
     *
     * @param string $dir        Directory to remove.
     * @param string $guard_base Only proceed if $dir resolves under here.
     * @return bool
     */
    function tiagsspace_hls_rrmdir($dir, $guard_base) {
        $real      = realpath($dir);
        $real_base = realpath($guard_base);
        if ($real === false || $real_base === false) {
            return false;
        }
        // Safety: never delete outside the guard base.
        if (strpos($real, $real_base) !== 0 || $real === $real_base) {
            return false;
        }

        $items = scandir($real);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $real . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path) && !is_link($path)) {
                tiagsspace_hls_rrmdir($path, $guard_base);
            } else {
                @unlink($path);
            }
        }
        return @rmdir($real);
    }
}

if (!function_exists('tiagsspace_hls_extract_bundle')) {
    /**
     * Unzip a bundle into $dest_dir and verify it contains master.m3u8.
     * On failure the (partial) $dest_dir is removed.
     *
     * @param string $zip_path
     * @param string $dest_dir
     * @param string $guard_base Base under which $dest_dir must live (cleanup safety).
     * @return array{ok:bool,message:string}
     */
    function tiagsspace_hls_extract_bundle($zip_path, $dest_dir, $guard_base) {
        if (!file_exists($zip_path)) {
            return ['ok' => false, 'message' => 'Bundle file not found: ' . $zip_path];
        }

        if (!function_exists('unzip_file')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        // unzip_file needs an initialised filesystem.
        if (function_exists('WP_Filesystem')) {
            WP_Filesystem();
        }

        if (!wp_mkdir_p($dest_dir)) {
            return ['ok' => false, 'message' => 'Could not create destination: ' . $dest_dir];
        }

        $result = unzip_file($zip_path, $dest_dir);
        if (is_wp_error($result)) {
            tiagsspace_hls_rrmdir($dest_dir, $guard_base);
            return ['ok' => false, 'message' => 'Unzip failed: ' . $result->get_error_message()];
        }

        if (!file_exists(trailingslashit($dest_dir) . 'master.m3u8')) {
            tiagsspace_hls_rrmdir($dest_dir, $guard_base);
            return ['ok' => false, 'message' => 'master.m3u8 not found in bundle (was it built with bin/hls-package.sh?)'];
        }

        return ['ok' => true, 'message' => 'Extracted to ' . $dest_dir];
    }
}

if (!function_exists('tiagsspace_hls_log')) {
    /**
     * Append a timestamped line to uploads/hls/import.log.
     *
     * @param string $line
     */
    function tiagsspace_hls_log($line) {
        $uploads = wp_upload_dir();
        $dir = trailingslashit($uploads['basedir']) . 'hls';
        wp_mkdir_p($dir);
        $stamp = gmdate('Y-m-d H:i:s');
        @file_put_contents($dir . '/import.log', "[$stamp] $line\n", FILE_APPEND | LOCK_EX);
    }
}

/* -------------------------------------------------------------------------
 * WP glue.
 * ---------------------------------------------------------------------- */

// Allow the bundle zip and playlist types to be uploaded.
add_filter('upload_mimes', 'tiagsspace_hls_upload_mimes');
function tiagsspace_hls_upload_mimes($mimes) {
    $mimes['zip']  = 'application/zip'; // .hlspack.zip resolves to the zip extension
    $mimes['m3u8'] = 'application/vnd.apple.mpegurl';
    return $mimes;
}

// On upload: if it's a bundle, unpack and re-point the attachment.
add_action('add_attachment', 'tiagsspace_hls_on_add_attachment');
function tiagsspace_hls_on_add_attachment($post_id) {
    $file = get_attached_file($post_id);
    if (!$file || !tiagsspace_hls_is_bundle(basename($file))) {
        return; // not our bundle — no-op for every other upload
    }

    $uploads    = wp_upload_dir();
    $guard_base = trailingslashit($uploads['basedir']) . 'hls';
    $dest_dir   = trailingslashit($guard_base) . $post_id;

    $res = tiagsspace_hls_extract_bundle($file, $dest_dir, $guard_base);
    if (!$res['ok']) {
        tiagsspace_hls_log("FAIL attachment $post_id: " . $res['message']);
        tiagsspace_hls_set_notice('error', 'HLS import failed: ' . $res['message'] . ' See wp-content/uploads/hls/import.log');
        return; // leave the zip attachment intact
    }
    tiagsspace_hls_log("OK   attachment $post_id: extracted");

    // Give the top playlist a film-specific name (derived from the uploaded zip,
    // e.g. project-forest.hlspack.zip -> project-forest.m3u8) so the Media
    // Library shows something meaningful instead of every film's file being
    // "master.m3u8". The bundle's internal segment/variant references are
    // relative, so renaming only the entry playlist is safe.
    $playlist = tiagsspace_hls_playlist_name($file); // e.g. "project-forest.m3u8"
    $src      = trailingslashit($dest_dir) . 'master.m3u8';
    $master_path = trailingslashit($dest_dir) . $playlist;
    if ($playlist !== 'master.m3u8' && file_exists($src) && @rename($src, $master_path)) {
        tiagsspace_hls_log("OK   attachment $post_id: playlist renamed to $playlist");
    } else {
        $master_path = $src; // fall back to master.m3u8 if rename didn't happen
        $playlist    = 'master.m3u8';
    }

    // Re-point the attachment at the (possibly renamed) master playlist.
    update_attached_file($post_id, $master_path);
    tiagsspace_hls_log("OK   attachment $post_id: re-pointed to $playlist");

    $master_url = trailingslashit($uploads['baseurl']) . 'hls/' . $post_id . '/' . $playlist;
    wp_update_post([
        'ID'             => $post_id,
        'post_mime_type' => 'application/vnd.apple.mpegurl',
        'guid'           => $master_url,
    ]);
    update_post_meta($post_id, '_tiagsspace_hls_dir', 'hls/' . $post_id);

    // Remove the now-redundant zip.
    if (@unlink($file)) {
        tiagsspace_hls_log("OK   attachment $post_id: zip deleted");
    }

    tiagsspace_hls_set_notice('success', 'HLS bundle imported — select this attachment in the film\'s "Self Host Film" field to play via HLS.');
}

// On deletion: remove the extracted HLS folder.
add_action('delete_attachment', 'tiagsspace_hls_on_delete_attachment');
function tiagsspace_hls_on_delete_attachment($post_id) {
    $uploads    = wp_upload_dir();
    $guard_base = trailingslashit($uploads['basedir']) . 'hls';
    $dir        = trailingslashit($guard_base) . $post_id;
    if (is_dir($dir)) {
        tiagsspace_hls_rrmdir($dir, $guard_base);
        tiagsspace_hls_log("OK   attachment $post_id: HLS folder removed on delete");
    }
}

/* -------------------------------------------------------------------------
 * Admin notice (transient, per-user).
 * ---------------------------------------------------------------------- */

if (!function_exists('tiagsspace_hls_set_notice')) {
    function tiagsspace_hls_set_notice($type, $message) {
        $uid = get_current_user_id();
        if (!$uid) {
            return;
        }
        set_transient('tiagsspace_hls_notice_' . $uid, ['type' => $type, 'message' => $message], 60);
    }
}

add_action('admin_notices', 'tiagsspace_hls_show_notice');
function tiagsspace_hls_show_notice() {
    $uid = get_current_user_id();
    if (!$uid) {
        return;
    }
    $notice = get_transient('tiagsspace_hls_notice_' . $uid);
    if (!$notice) {
        return;
    }
    delete_transient('tiagsspace_hls_notice_' . $uid);
    $class = ($notice['type'] === 'error') ? 'notice-error' : 'notice-success';
    printf('<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr($class), esc_html($notice['message']));
}
