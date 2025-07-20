<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$self_host_film_id = get_field('self_host_film');

if (!$self_host_film_id) {
    echo '<p>No video selected.</p>';
    return;
}

// Get poster if defined
$poster = get_field('video_poster');
$poster_url = $poster ? esc_url($poster['url']) : '';

// Get Videopack video sources
$video_data = get_post_meta($self_host_film_id, '_video_press_data', true);
$sources = [];

if (!empty($video_data['mp4'])) {
    foreach ($video_data['mp4'] as $label => $src) {
        $sources[] = [
            'label' => $label,
            'src'   => esc_url($src),
        ];
    }
} else {
    // Fallback: get original URL
    $fallback_url = wp_get_attachment_url($self_host_film_id);
    if ($fallback_url) {
        $sources[] = [
            'label' => 'default',
            'src'   => esc_url($fallback_url),
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