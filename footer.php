

		<?php wp_footer(); // js scripts are inserted using this function ?>


		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/assets/vendor/plyr/dist/plyr.min.js" ></script>
		
		<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Select all audio and video elements with the .plyr class
    document.querySelectorAll('.plyr').forEach(media => {
      // Set recommended attributes to ensure compatibility and autoplay behavior
      media.setAttribute('preload', 'auto');
      media.setAttribute('playsinline', '');
      
      // If autoplay is desired, set it and ensure muted is enabled
      if (media.hasAttribute('autoplay')) {
        media.muted = true;
      }

      // Initialize Plyr
      new Plyr(media);

      // Attempt autoplay if requested
      if (media.hasAttribute('autoplay')) {
        media.play().catch(e => {
          console.warn('Autoplay failed:', e);
        });
      }
    });
  });
</script>

	</body>

</html>
