<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$self_host_film_id = get_field('self_host_film');

// Get poster if defined
$thumbnail_id = get_post_thumbnail_id(get_the_ID()); // or get_the_ID()
$full_image_url = wp_get_attachment_url($thumbnail_id);

$poster = $full_image_url ? esc_url($full_image_url) : '';

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
        <video class="test-video" controls crossorigin playsinline poster="<?php echo $poster; ?>">
            <?php if (!empty($video_sources)) : ?>
                    <video class="plyr selfhost-video" controls playsinline preload="auto" poster="<?php echo $poster; ?>">
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

            <!-- Caption files -->
            <track kind="captions" label="English" srclang="en" src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.en.vtt"
                    default>
            <track kind="captions" label="Français" srclang="fr" src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.fr.vtt">
            <!-- Fallback for browsers that don't support the <video> element -->
            <a href="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-576p.mp4" download>Download</a>
        </video>
    </div>
</div>


<script>
     document.addEventListener('DOMContentLoaded', () => {
         const player = new Plyr('.test-video');
    });
</script>