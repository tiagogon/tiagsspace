<?php
/*
Header >> Collapse menu (navigation)
Triggered by the Reveal/Hide toggle in the top bar.
Contains: site link, main sections, CPT archives, taxonomy browsers, external links.
*/

// Menu grid column class (48-col grid)
$menu_groups_class = "col-sm-24 col-md-16 col-xl-9 menu-group";
?>

<div id="collapseMenu" class="collapse container-fluid index-block multi-collapse">
	<div class="row d-flex justify-content-start">

		<?php // ----- Site name ----- ?>
		<div class="<?php echo $menu_groups_class; ?>">
			<ul>
				<li>
					<a href="<?php echo home_url(); ?>">Tiags' Space</a>
				</li>
			</ul>
		</div>

		<?php // ----- Main pages ----- ?>
		<div class="<?php echo $menu_groups_class; ?>">
			<ul>
				<li>
					<a href="<?php echo home_url(); ?>" class="<?php if (is_home()) { echo "active"; } ?>">Visual</a>
				</li>
				<li>
					<a href="<?php echo get_permalink(get_page_by_path('index')); ?>" class="<?php if (is_page('index')) { echo "active"; } ?>">Index</a>
				</li>
			</ul>
		</div>

		<?php // ----- CPT archives + active log branches ----- ?>
		<div class="<?php echo $menu_groups_class; ?>">
			<ul>
				<li>
					<a href="<?php echo get_post_type_archive_link('hyper'); ?>" class="<?php if (is_post_type_archive('hyper')) { echo "active"; } if (is_singular('hyper')) { echo " active"; } ?>">Hyper</a>
				</li>
				<li>
					<a href="<?php echo get_post_type_archive_link('4k-lento'); ?>" class="<?php if (is_post_type_archive('4k-lento')) { echo "active"; } if (is_singular('4k-lento')) { echo " active"; } ?>">4K Lento</a>
				</li>
				<!-- <li>
					<a href="<?php echo get_post_type_archive_link('films'); ?>" class="<?php if (is_post_type_archive('Films')) { echo "active"; } if (is_singular('films')) { echo " active"; } ?>">Film</a>
				</li> -->
				<li>
					<a href="<?php echo get_post_type_archive_link('dusk'); ?>" class="<?php if (is_post_type_archive('dusk')) { echo "active"; } if (is_singular('dusk')) { echo " active"; } ?>">Dusk</a>
				</li>
				<li>
					<a data-toggle="collapse" href="#collapselog-branch" role="button" aria-expanded="false" aria-controls="collapsePlaces">Log</a>
				</li>
				<li>
					<ul class="collapse" id="collapselog-branch">
						<?php
						// Active (non-archived) log branches
						$terms = get_terms(array(
							'taxonomy'   => 'log-branch',
							'hide_empty' => true,
						));
						foreach ($terms as $term) {
							$custom_field_value = get_field('is_archived', $term);
							if (!$custom_field_value == true) {
								$elmentclass = "";
								if (is_tax('log-branch', $term)) {
									$elmentclass = "active";
								}
								echo '<li><a href="' . get_term_link($term) . '" class="' . $elmentclass . '">' . $term->name . '</a></li>';
							}
						}
						?>
					</ul>
				</li>
			</ul>
		</div>

		<?php // ----- Taxonomy browsers + Closed/archived ----- ?>
		<div class="<?php echo $menu_groups_class; ?>">
			<ul>
				<?php // Time (from taxonomy) ?>
				<li>
					<a data-toggle="collapse" href="#collapseDating" role="button" aria-expanded="false" aria-controls="collapseDating">Time</a>
					<ul class="collapse" id="collapseDating">
						<?php wp_list_categories(array(
							'taxonomy'     => 'from',
							'orderby'      => 'name',
							'order'        => 'DESC',
							'hide_empty'   => 1,
							'title_li'     => '',
							'hierarchical' => 1,
						)); ?>
					</ul>
				</li>

				<?php // Places ?>
				<li>
					<a data-toggle="collapse" href="#collapsePlaces" role="button" aria-expanded="false" aria-controls="collapsePlaces">Place</a>
					<ul class="collapse" id="collapsePlaces">
						<?php wp_list_categories(array(
							'taxonomy'     => 'places',
							'orderby'      => 'name',
							'hide_empty'   => 1,
							'title_li'     => '',
							'hierarchical' => 1,
						)); ?>
					</ul>
				</li>

				<?php // Medium ?>
				<li>
					<a data-toggle="collapse" href="#collapseMedium" role="button" aria-expanded="false" aria-controls="collapseMedium">Medium</a>
					<ul class="collapse" id="collapseMedium">
						<?php wp_list_categories(array(
							'taxonomy'     => 'medium',
							'orderby'      => 'name',
							'hide_empty'   => 1,
							'title_li'     => '',
							'hierarchical' => 1,
						)); ?>
					</ul>
				</li>

				<?php // Closed — archived series + log branches ?>
				<li>
					<a data-toggle="collapse" href="#collapsePast" role="button" aria-expanded="false" aria-controls="collapsePast">Closed</a>
					<ul class="collapse" id="collapsePast">
						<li>
							<a data-toggle="collapse" href="#collapsePastFromSeries" role="button" aria-expanded="false" aria-controls="collapsePastFromSeries">Series</a>
							<ul class="collapse" id="collapsePastFromSeries">
								<li>
									<a href="<?php echo get_post_type_archive_link('cityburns'); ?>" class="<?php if (is_post_type_archive('cityburns')) { echo "active"; } if (is_singular('cityburns')) { echo " active"; } ?>">City</a>
								</li>
							</ul>
						</li>
						<li>
							<a data-toggle="collapse" href="#collapsePastFromLog" role="button" aria-expanded="false" aria-controls="collapsePastFromLog">Log</a>
							<ul class="collapse" id="collapsePastFromLog">
								<?php
								// Archived log branches (ACF is_archived = true)
								$terms = get_terms(array(
									'taxonomy'   => 'log-branch',
									'hide_empty' => true,
								));
								$i = 0;
								foreach ($terms as $term) {
									$i++;
									$custom_field_value = get_field('is_archived', $term);
									if ($custom_field_value == true) {
										echo '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
									}
								}
								if ($i == 0) {
									echo '<li>None</li>';
								}
								?>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
		</div>

		<?php // ----- External links ----- ?>
		<div class="<?php echo $menu_groups_class; ?>">
			<ul>
				<li><a href="mailto:mail@tiags.space" target="_blank">Email</a></li>
				<li><a href="https://www.instagram.com/tiagsssss/" target="_blank">Instagram</a></li>
				<li><a href="https://soundcloud.com/tiagsssss" target="_blank">Soundcloud</a></li>
				<li><a href="https://podcasts.apple.com/ca/podcast/4k-lento/id1445312236" target="_blank">Mixcast</a></li>
				<li><a href="https://tiagssssspace.tumblr.com/" target="_blank">Tumblr</a></li>
				<li><a href="https://ra.co/dj/tiagsssss" target="_blank">RA</a></li>
				<li>
					<a data-toggle="collapse" href="#collapseReference" role="button" aria-expanded="false" aria-controls="collapsePast">Lists</a>
					<ul class="collapse" id="collapseReference">
						<li><a href="https://goodreads.com/tiags" target="_blank">Reading</a></li>
						<li><a href="https://letterboxd.com/tiagsssss/" target="_blank">Watching</a></li>
						<li><a href="https://tiags.tumblr.com/" target="_blank">Collecting</a></li>
					</ul>
				</li>
			</ul>
		</div>

	</div>
</div>
