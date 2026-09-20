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

// Caption styling (ACF, group "Video Player Options"). Only non-default values
// reach the markup, so an untouched film renders exactly as before. Colour goes
// out as Plyr's own custom property (plyr.css reads --plyr-captions-text-color
// on .plyr__caption); contrast is a modifier class styled in _component-plyr.scss.
// Both sit on .embed-container, an ancestor of everything Plyr builds, so they
// survive fullscreen. iPhone's native player ignores both by design.
$caption_color = get_field('caption_color');
$caption_hex   = '';
if ($caption_color === 'yellow') {
    $caption_hex = '#ffe100';
} elseif ($caption_color === 'custom') {
    $caption_hex = (string) sanitize_hex_color((string) get_field('caption_color_custom'));
}
$caption_contrast = get_field('caption_contrast') === 'shadow' ? 'shadow' : 'box';

$embed_classes = 'embed-container' . ($caption_contrast === 'shadow' ? ' captions-contrast--shadow' : '');
$embed_style   = $caption_hex ? ' style="--plyr-captions-text-color: ' . esc_attr($caption_hex) . '"' : '';

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
    <div class="<?php echo esc_attr($embed_classes); ?>"<?php echo $embed_style; ?>>
        <?php
        echo render_video_player([
            'attachment_id'  => $attachment_id,
            'player_options' => $player_options,
            'caption_tracks' => $caption_tracks,
        ]);
        ?>
    </div>
</div>
