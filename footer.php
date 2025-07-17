

		<?php wp_footer(); // js scripts are inserted using this function ?>


		<?php
		// Audio (and video) Player support
		// -- CSS on header ?>
		<script src="<?php bloginfo('template_url'); ?>/library/js/plyr-master/assets/vendor/plyr/dist/plyr.min.js" ></script>
		<script>
			//const players = Plyr.setup('audio'); //can be audioTag, .someClass, #someID

			const players = Array.from(document.querySelectorAll('audio')).map(p => new Plyr(p));
			document.addEventListener('DOMContentLoaded', () => {
                                            Plyr.setup('.plyr');
                                        });
		</script>

	</body>

</html>
