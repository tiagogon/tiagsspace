<?php 	// Redirect to the Post where the media is atached
		wp_redirect(get_permalink($post->post_parent));
		exit; ?>