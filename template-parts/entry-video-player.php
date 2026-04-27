<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$video_source = get_field('self_host_film');

// Debug output
echo '<!-- DEBUG: video_source = ' . print_r($video_source, true) . ' -->';
?>

<div class="container-fluid container-video">
    <div class="embed-container">
        <?php
        // Render Plyr video player with adaptive quality and captions
        echo render_plyr_video_player($video_source);
        ?>
    </div>
</div>


<script src="<?php echo get_template_directory_uri(); ?>/library/js/plyr-adaptive-quality.js"></script>
