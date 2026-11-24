<?php
/*
Template: Self-hosted film (the Edge player — HLS or the MP4 ladder)

Gathers the film's ACF values and hands them to the film renderer
(library/video/player-film.php). The selected attachment decides the playback
tech: an `.m3u8` attachment plays via HLS, any other video plays the progressive
MP4 ladder.
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

// Caption styling (ACF, group "Video Player Options"): the text colour (white / yellow /
// custom) and box or shadow, drawn by the film's own caption layer. iPhone's native player
// ignores both by design (system caption style).
$caption_color = get_field('caption_color');
$caption_hex   = '';
if ($caption_color === 'yellow') {
    $caption_hex = '#ffe100';
} elseif ($caption_color === 'custom') {
    $caption_hex = (string) sanitize_hex_color((string) get_field('caption_color_custom'));
}
$caption_contrast = get_field('caption_contrast') === 'shadow' ? 'shadow' : 'box';

// Caption tracks live on the .m3u8 ATTACHMENT, not this post — the HLS bundle
// they generate into is keyed by attachment ID. These sidecar <track> elements
// are only a fallback: once renditions exist in the playlist, the renderer
// drops them (both would load and every cue would render twice). MP4 films fall
// back to Videopack's _kgvid-meta inside the module, untouched.
$caption_tracks = function_exists('tiagsspace_film_caption_tracks')
    ? tiagsspace_film_caption_tracks($attachment_id)
    : [];

// The Edge player (library/video/player-film.php, library/js/media/film.js): it brings its
// own engine (hls.js / native HLS / the MP4 ladder) through the media loader.
?>

<div class="container-fluid container-video">
    <div class="embed-container">
        <?php
        echo tiagsspace_render_film($attachment_id, [
            'player_options'   => $player_options,
            'caption_tracks'   => $caption_tracks,
            'caption_color'    => $caption_hex,
            'caption_contrast' => $caption_contrast,
            'title'            => get_the_title(),
        ]);
        ?>
    </div>
</div>
