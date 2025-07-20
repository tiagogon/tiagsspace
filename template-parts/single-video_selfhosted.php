<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$self_host_film_id = get_field('self_host_film');
echo '<!-- Self-hosted film ID: ' . esc_html($self_host_film_id) . ' -->';


// Get poster if defined
$poster = get_field('video_poster');
$poster_url = $poster ? esc_url($poster['url']) : '';

// Get Videopack video sources
$video_data = get_post_meta($self_host_film_id, '_video_press_data', true);
$sources = [];

echo '<pre>';
var_dump( $video_data );
echo '</pre>';

echo '<pre>';
var_dump( get_post_meta($self_host_film_id) );
echo '</pre>';


if (!empty($video_data['mp4']) && is_array($video_data['mp4'])) {
    echo '<!-- MP4 sources found for this video. -->';

    foreach ($video_data['mp4'] as $quality => $url) {
        $sources[] = [
            'src'   => esc_url($url),
            'label' => esc_attr($quality), // e.g. sd, hd, original
        ];
    }
} else {
    echo '<!-- No MP4 sources found for this video. -->';
    // Fallback to single file
    $url = wp_get_attachment_url($self_host_film_id);
    if ($url) {
        $sources[] = [
            'src'   => esc_url($url),
            'label' => 'default',
        ];
    }
}
?>

<div class="container-fluid container-video">
    <div class="embed-container">
        <video
            class="plyr selfhost-video"
            playsinline
            autoplay
            muted
            loop
            controls
            preload="auto"
            <?php if ($poster_url): ?>poster="<?php echo $poster_url; ?>"<?php endif; ?>
        >
            <?php foreach ($sources as $source): ?>
                <source src="<?php echo $source['src']; ?>" type="video/mp4" size="<?php echo esc_attr($source['label']); ?>">
            <?php endforeach; ?>
            Your browser does not support the video tag.
        </video>
    </div>
</div>

<style>
    .embed-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        max-width: 100%;
        margin-bottom: 20px;
    }

    .embed-container video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<script>
// document.addEventListener('DOMContentLoaded', function () {
//     document.querySelectorAll('.selfhost-video').forEach(video => {
//         if (video.dataset.plyrInitialized === 'true') return;

//         const player = new Plyr(video, {
//             controls: ['play', 'progress', 'mute', 'fullscreen'],
//         });

//         video.dataset.plyrInitialized = 'true';

//         // Attempt autoplay if attribute present
//         if (video.hasAttribute('autoplay')) {
//             video.muted = true; // Required for autoplay in Chrome
//             video.play().catch(err => {
//                 console.warn('Autoplay failed (selfhost):', err);
//             });
//         }
//     });
// });
</script>