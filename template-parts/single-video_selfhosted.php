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
$video_meta = wp_get_attachment_metadata($self_host_film_id);
echo '<!-- Video Meta: ' . esc_html(print_r($video_meta, true)) . ' -->';
if ($original_url) {
    $video_sources[] = [
        'src'   => esc_url($original_url),
        'label' => intval($video_meta['height']) . 'p', // Use height as label
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

// it does not seem to be picked up by the Plyr player, but it is needed for the HTML5 video tag
$film_player_options_html_string = "crossorigin ";

$film_player_options = get_field('film_player_options');
if( $film_player_options ) {
    foreach ($film_player_options as $film_player_option ) {
        $film_player_options_html_string .= $film_player_option . " ";
    }
}

$plyr_config = [
    "autoplay" => "true",
    "muted" => "true"
];

// Build the JSON string as usual
$plyr_config = [
    "muted" => "true"
];


// Build JSON string (add spaces if you like as before)
$json = json_encode($plyr_config, JSON_HEX_APOS | JSON_HEX_QUOT);
// Optional pretty formatting
$json = preg_replace('/^{/', '{ ', $json);
$json = preg_replace('/}$/', ' }', $json);
$json = preg_replace('/:/', ': ', $json);

// debugging output
$json = '{
  "title": "My Video",
  "autoplay": true,
  "controls": [
    "play-large", "play", "progress", "current-time", "mute", "volume", "captions", "settings", "fullscreen"
  ],
  "settings": ["captions", "quality", "speed", "loop"],
  "ratio": "16:9",
  "keyboard": { "focused": true, "global": false },
  "tooltips": { "controls": true, "seek": true },
  "seekTime": 10,
  "speed": { "selected": 1, "options": [0.5, 1, 1.5, 2] },
  "quality": { "default": 1080},
  "ads": { "enabled": false },
  "previewThumbnails": { "enabled": false }
}';

?>



<div class="container-fluid container-video">
    <div class="embed-container">
            <?php if (!empty($video_sources)) : ?>
                <video class="plyr film-player" 
                <?php echo $film_player_options_html_string; ?>     
                
                poster="<?php echo $poster; ?>" 
                data-plyr-config='<?php echo $json; ?>'
                >
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
                    <!-- Caption files -->
                    <!-- <track kind="captions" label="English" srclang="en" src=""
                            default>
                    <track kind="captions" label="Français" srclang="fr" src=""> -->
                    <!-- Fallback for browsers that don't support the <video> element -->
                    <a href="<?php echo esc_url($original_url); ?>" download>Download</a>
                </video>
            <?php endif; ?>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const player = new Plyr('.film-player');
        //player.muted = true; // or player.mute();
    });
</script>