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

// Caption tracks (ACF repeater). Rows with no file are skipped; the module
// normalises the rest and falls back to Videopack's _kgvid-meta when this
// is empty, so MP4 films using the old workflow keep working untouched.
$caption_tracks = [];
$caption_rows = get_field('film_caption_tracks');
if (is_array($caption_rows)) {
    foreach ($caption_rows as $row) {
        if (empty($row['caption_file'])) {
            continue;
        }
        $srclang = !empty($row['caption_srclang']) ? strtolower($row['caption_srclang']) : 'en';
        $caption_tracks[] = [
            'src'     => $row['caption_file'],
            'srclang' => $srclang,
            'label'   => !empty($row['caption_label']) ? $row['caption_label'] : strtoupper($srclang),
            'kind'    => 'subtitles',
            'default' => !empty($row['caption_default']),
        ];
    }
}

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
