<?php
/**
 * Plyr Video Player Helper
 * 
 * Renders a self-hosted video player with Plyr.js support,
 * adaptive quality selection, and WebVTT captions
 */

if (!function_exists('render_plyr_video_player')) {
    /**
     * Render a Plyr video player
     *
     * @param int|string $video_source      WordPress attachment ID, YouTube URL, or Vimeo URL
     * @param string     $poster_url        Optional poster image URL
     * @param array      $extra_html_attrs  Extra HTML attributes for the <video> element
     * 
     * @return string HTML markup for the video player
     */
    function render_plyr_video_player($video_source, $poster_url = '', $extra_html_attrs = []) {
        if (!$video_source) {
            return '';
        }

        // Detect video source type
        $is_external = is_string($video_source) && (strpos($video_source, 'http') === 0);
        $is_youtube = $is_external && (strpos($video_source, 'youtube.com') !== false || strpos($video_source, 'youtu.be') !== false);
        $is_vimeo = $is_external && strpos($video_source, 'vimeo.com') !== false;

        // Handle external providers (YouTube/Vimeo)
        if ($is_youtube || $is_vimeo) {
            return render_plyr_external_video($video_source, $poster_url, $extra_html_attrs);
        }

        // Handle self-hosted videos
        $video_attachment_id = intval($video_source);
        if (!$video_attachment_id) {
            return '';
        }

        // Get poster if not provided
        if (empty($poster_url)) {
            $thumbnail_id = get_post_thumbnail_id(get_the_ID());
            if ($thumbnail_id) {
                $poster_url = wp_get_attachment_url($thumbnail_id);
            }
        }
        $poster = $poster_url ? esc_url($poster_url) : '';

        // Start collecting video sources
        $video_sources = [];

        // Add original video
        $original_url = wp_get_attachment_url($video_attachment_id);
        $video_meta = wp_get_attachment_metadata($video_attachment_id);
        
        if ($original_url) {
            $video_sources[] = [
                'src'   => esc_url($original_url),
                'label' => intval($video_meta['height']) . 'p',
                'size'  => intval($video_meta['height']),
            ];
        }

        // Add encoded versions (child attachments)
        $children = get_children([
            'post_parent'    => $video_attachment_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'video',
            'numberposts'    => -1,
        ]);

        if ($children) {
            foreach ($children as $child) {
                $child_url = wp_get_attachment_url($child->ID);
                $child_meta = wp_get_attachment_metadata($child->ID);
                if ($child_url && $child_meta) {
                    $video_sources[] = [
                        'src'   => esc_url($child_url),
                        'label' => intval($child_meta['height']) . 'p',
                        'size'  => intval($child_meta['height']),
                    ];
                }
            }
        }

        // Get captions from KGVID plugin metadata
        $caption_tracks = [];
        $kgvid_meta = get_post_meta($video_attachment_id, '_kgvid-meta', true);
        if ($kgvid_meta && isset($kgvid_meta['track']) && is_array($kgvid_meta['track'])) {
            foreach ($kgvid_meta['track'] as $track) {
                if (is_array($track) && isset($track['src'])) {
                    $caption_tracks[] = [
                        'src'     => esc_url($track['src']),
                        'srclang' => esc_attr(strtolower($track['srclang'] ?? 'en')),
                        'label'   => esc_html($track['label'] ?? 'Captions'),
                        'kind'    => esc_attr($track['kind'] ?? 'captions'),
                    ];
                }
            }
        }

        // Build HTML attributes string
        $html_attrs = 'crossorigin ';
        
        // Add extra attributes if provided
        if (is_array($extra_html_attrs) && !empty($extra_html_attrs)) {
            foreach ($extra_html_attrs as $attr => $value) {
                $html_attrs .= esc_attr($attr) . '="' . esc_attr($value) . '" ';
            }
        }

        // Get ACF Film Player Options
        $film_player_options = get_field('film_player_options');
        if (!is_array($film_player_options)) {
            $film_player_options = [];
        }
        
        // DEBUG: Output ACF options
        echo '<!-- DEBUG Film Player Options: ' . print_r($film_player_options, true) . ' -->';
        
        // Build the Plyr config
        $plyr_config = [
            'title'    => get_the_title(),
            'settings' => ['captions', 'quality'],
            'captions' => [
                'active'   => true,
                'language' => 'auto',
                'update'   => true
            ],
            'ratio'    => '16:9',
            'tooltips' => ['controls' => false, 'seek' => false],
            'seekTime' => 10,
            'quality'  => ['default' => 1080],
            'ads'      => ['enabled' => false],
            'previewThumbnails' => ['enabled' => false],
            'fullscreen' => ['enabled' => true, 'fallback' => true, 'iosNative' => false, 'container' => null],
            'pip'      => ['enabled' => false],
        ];
        
        // Apply Film Player Options
        if (in_array('controls', $film_player_options)) {
            $plyr_config['controls'] = [
                'play-large', 'play', 'progress', 'current-time',
                'mute', 'captions', 'settings', 'fullscreen'
            ];
        } else {
            $plyr_config['controls'] = false;
        }
        
        if (in_array('keyboard', $film_player_options)) {
            $plyr_config['keyboard'] = ['focused' => true, 'global' => false];
        }
        
        if (in_array('loop', $film_player_options)) {
            $plyr_config['loop'] = ['active' => true];
        }
        
        if (in_array('autoplay', $film_player_options)) {
            $plyr_config['autoplay'] = true;
        }
        
        if (in_array('muted', $film_player_options)) {
            $plyr_config['muted'] = true;
        };

        $json = wp_json_encode($plyr_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // DEBUG: Output final config
        echo '<!-- DEBUG Plyr Config: ' . $json . ' -->';

        // Return early if no sources
        if (empty($video_sources)) {
            return '';
        }

        // Build the HTML output
        ob_start();
        ?>
        <video class="plyr film-player" data-debug="0"
            <?php echo $html_attrs; ?>
            poster="<?php echo $poster; ?>"
            data-plyr-config='<?php echo esc_attr($json); ?>'
            webkit-playsinline
            playsinline
            disablePictureInPicture
            <?php if (in_array('muted', $film_player_options)) : ?>muted<?php endif; ?>
            <?php if (in_array('autoplay', $film_player_options)) : ?>autoplay<?php endif; ?>
            <?php if (in_array('loop', $film_player_options)) : ?>loop<?php endif; ?>
            <?php if (in_array('controls', $film_player_options)) : ?>controls<?php endif; ?>
        >
            <?php foreach ($video_sources as $source) : ?>
                <source src="<?php echo $source['src']; ?>" type="video/mp4" size="<?php echo esc_attr($source['size']); ?>"<?php echo !empty($source['label']) ? ' label="' . esc_attr($source['label']) . '"' : ''; ?>>
            <?php endforeach; ?>
            
            <?php if (!empty($caption_tracks)) : ?>
                <?php foreach ($caption_tracks as $index => $track) : ?>
                    <track kind="<?php echo $track['kind']; ?>" src="<?php echo $track['src']; ?>" srclang="<?php echo $track['srclang']; ?>" label="<?php echo $track['label']; ?>"<?php echo ($index === 0) ? ' default' : ''; ?> crossorigin="anonymous">
                <?php endforeach; ?>
            <?php endif; ?>
            
            Your browser does not support the video tag.
            <a href="<?php echo esc_url($original_url); ?>" download>Download</a>
        </video>
        <?php
        return ob_get_clean();
    }
}

/**
 * Render a Plyr player for external video providers (YouTube, Vimeo)
 *
 * @param string $video_url         YouTube or Vimeo URL
 * @param string $poster_url        Optional poster image URL
 * @param array  $extra_html_attrs  Extra HTML attributes
 * 
 * @return string HTML markup for the video player
 */
if (!function_exists('render_plyr_external_video')) {
    function render_plyr_external_video($video_url, $poster_url = '', $extra_html_attrs = []) {
        if (!$video_url) {
            return '';
        }

        // Extract video ID from URL
        $video_id = '';
        $provider = '';

        // Handle Vimeo URLs
        if (strpos($video_url, 'vimeo.com') !== false) {
            $provider = 'vimeo';
            // Extract ID from vimeo.com/ID, vimeo.com/video/ID, or player.vimeo.com/video/ID
            preg_match('/(?:player\.)?vimeo\.com\/(?:video\/)?(\d+)/', $video_url, $matches);
            $video_id = $matches[1] ?? '';
        }
        // Handle YouTube URLs
        elseif (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
            $provider = 'youtube';
            // Extract ID from youtube.com/watch?v=ID or youtu.be/ID
            if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                $video_id = $matches[1];
            } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                $video_id = $matches[1];
            }
        }

        // If we couldn't extract an ID, return empty
        if (!$video_id || !$provider) {
            return '';
        }

        // Get poster if not provided
        if (empty($poster_url)) {
            $thumbnail_id = get_post_thumbnail_id(get_the_ID());
            if ($thumbnail_id) {
                $poster_url = wp_get_attachment_url($thumbnail_id);
            }
        }
        $poster = $poster_url ? esc_url($poster_url) : '';

        // Build HTML attributes string
        $html_attrs = '';
        
        // Add extra attributes if provided
        if (is_array($extra_html_attrs) && !empty($extra_html_attrs)) {
            foreach ($extra_html_attrs as $attr => $value) {
                $html_attrs .= esc_attr($attr) . '="' . esc_attr($value) . '" ';
            }
        }

        // Get ACF Film Player Options
        $film_player_options = get_field('film_player_options');
        if (!is_array($film_player_options)) {
            $film_player_options = [];
        }
        
        // DEBUG: Output ACF options
        echo '<!-- DEBUG External Film Player Options: ' . print_r($film_player_options, true) . ' -->';
        
        // Build the Plyr config for external providers
        $plyr_config = [
            'title'    => get_the_title(),
            'settings' => ['captions'],
            'captions' => [
                'active'   => true,
                'language' => 'auto',
                'update'   => true
            ],
            'ratio'    => '16:9',
            'tooltips' => ['controls' => false, 'seek' => false],
            'seekTime' => 10,
            'ads'      => ['enabled' => false],
            'previewThumbnails' => ['enabled' => false],
            'fullscreen' => ['enabled' => true, 'fallback' => true, 'iosNative' => false, 'container' => null],
            'pip'      => ['enabled' => false],
        ];
        
        // Apply Film Player Options
        if (in_array('controls', $film_player_options)) {
            $plyr_config['controls'] = [
                'play-large', 'play', 'progress', 'current-time',
                'mute', 'captions', 'settings', 'fullscreen'
            ];
        } else {
            $plyr_config['controls'] = false;
        }
        
        if (in_array('keyboard', $film_player_options)) {
            $plyr_config['keyboard'] = ['focused' => true, 'global' => false];
        }
        
        if (in_array('loop', $film_player_options)) {
            $plyr_config['loop'] = ['active' => true];
        }
        
        if (in_array('autoplay', $film_player_options)) {
            $plyr_config['autoplay'] = true;
        }
        
        if (in_array('muted', $film_player_options)) {
            $plyr_config['muted'] = true;
        }
        
        // Add provider-specific options - always block tracking
        if ($provider === 'vimeo') {
            $plyr_config['vimeo'] = [
                'dnt' => true  // Always block Vimeo tracking
            ];
        } elseif ($provider === 'youtube') {
            $plyr_config['youtube'] = [
                'noCookie' => true  // Always use privacy-enhanced mode (youtube-nocookie.com)
            ];
        }

        $json = wp_json_encode($plyr_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Build the HTML output
        ob_start();
        ?>
        <div class="plyr film-player" 
            data-debug="0"
            data-plyr-provider="<?php echo esc_attr($provider); ?>"
            data-plyr-embed-id="<?php echo esc_attr($video_id); ?>"
            <?php echo !empty($html_attrs) ? $html_attrs : ''; ?>
            data-plyr-config='<?php echo esc_attr($json); ?>'
            <?php if (!empty($poster)) : ?>
                style="background-image: url('<?php echo $poster; ?>'); background-size: cover; background-position: center; aspect-ratio: 16/9;"
            <?php endif; ?>
        ></div>
        <?php
        return ob_get_clean();
    }
}
