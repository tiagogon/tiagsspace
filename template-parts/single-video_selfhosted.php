<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$self_host_film_id = get_field('self_host_film');
echo '<!-- Self-hosted film ID: ' . esc_html($self_host_film_id) . ' -->';

// Get poster if defined
$poster = get_field('video_poster');
$poster_url = $poster ? esc_url($poster['url']) : '';

// Start collecting sources
$video_sources = [];

// Add original video
$original_url = wp_get_attachment_url($self_host_film_id);
if ($original_url) {
    $video_sources[] = [
        'src'   => esc_url($original_url),
        'label' => 'original',
    ];
}

// Add encoded versions (child attachments)
$children = get_children([
    'post_parent'    => $self_host_film_id,
    'post_type'      => 'attachment',
    'post_mime_type' => 'video',
    'numberposts'    => -1,
]);

if (!empty($children)) {
    foreach ($children as $child) {
        $src = wp_get_attachment_url($child->ID);
        $filename = basename($src);

        // Extract resolution from filename (e.g. video-720.mp4)
        if (preg_match('/-(\d{3,4})\.mp4$/', $filename, $matches)) {
            $label = $matches[1] . 'p';
        } else {
            $label = 'default';
        }

        $video_sources[] = [
            'src'   => esc_url($src),
            'label' => esc_html($label),
        ];
    }
}
?>

<div class="container-fluid container-video">
    <div class="embed-container">
        <?php if (!empty($video_sources)) : ?>
            <video class="plyr selfhost-video" controls playsinline preload="auto" poster="<?php echo esc_url($poster_url); ?>">
                <?php foreach ($video_sources as $source) : ?>
                    <source src="<?php echo $source['src']; ?>" type="video/mp4"
                        <?php
                        // If it's a labeled resolution (e.g. 360p), provide `size`
                        if (preg_match('/^(\d{3,4})p$/', $source['label'], $m)) {
                            echo ' size="' . esc_attr($m[1]) . '"';
                        }
                        ?>
                    >
                <?php endforeach; ?>
                Your browser does not support the video tag.
            </video>
        <?php endif; ?>
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