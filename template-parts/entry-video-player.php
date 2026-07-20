<?php
/*
Template: Self-hosted video (Plyr — MP4 ladder or HLS)

Gathers the film's ACF values and hands them to the portable video module
(library/video/*). The selected attachment decides the playback tech: an
`.m3u8` attachment plays via HLS, any other video plays the progressive MP4
ladder. See library/video/player.php.
*/

$video_source = get_field('self_host_film');
if (!$video_source) {
    return;
}
$attachment_id = intval($video_source);

$player_options = get_field('film_player_options');
if (!is_array($player_options)) {
    $player_options = [];
}

// Caption tracks live on the .m3u8 ATTACHMENT, not this post — the HLS bundle
// they generate into is keyed by attachment ID. These sidecar <track> elements
// are only a fallback: once renditions exist in the playlist, player-hls.php
// drops them (both would load and every cue would render twice). MP4 films fall
// back to Videopack's _kgvid-meta inside the module, untouched.
$caption_tracks = function_exists('tiagsspace_film_caption_tracks')
    ? tiagsspace_film_caption_tracks($attachment_id)
    : [];

// Enqueue the matching playback engine (footer scripts, so late enqueue is fine).
if (function_exists('tiagsspace_video_is_hls') && tiagsspace_video_is_hls($attachment_id)) {
    tiagsspace_video_enqueue_hls();
} else {
    tiagsspace_video_enqueue_mp4();
}
?>

<div class="container-fluid container-video">
    <div class="embed-container">
        <?php
        echo render_video_player([
            'attachment_id'  => $attachment_id,
            'player_options' => $player_options,
            'caption_tracks' => $caption_tracks,
        ]);
        ?>
    </div>
</div>
