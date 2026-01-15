<?php
/*
Template: Self-hosted video with Plyr + Videopack resolutions
*/

$self_host_film_id = get_field('self_host_film');
?>

<div class="container-fluid container-video">
    <div class="embed-container">
        <?php
        // Render Plyr video player with adaptive quality and captions
        echo render_plyr_video_player($self_host_film_id);
        // 
        // echo render_plyr_video_player('https://www.youtube.com/watch?v=GEvuxpfVtFw');
        // echo render_plyr_video_player('https://vimeo.com/747432421');
        ?>
    </div>
</div>


<script src="<?php echo get_template_directory_uri(); ?>/library/js/plyr-adaptive-quality.js"></script>
