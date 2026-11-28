<!doctype html>
<html <?php language_attributes(); ?> class="no-js">

	<head>
		<?php // ----- META ----- ?>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<?php /* Title handled by add_theme_support('title-tag') */ ?>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">


		<?php wp_head(); ?>
					


	</head>

	<body <?php body_class();?> >

		<?php if (function_exists('wp_body_open')) { wp_body_open(); } ?>

		<header id="site-header" class="header-front-page container-fluid" role="banner">

			<div class="row">

				<div class="topbar-left col-32 multi-collapse show">

					<?php get_template_part( 'template-parts/header', 'title' ); ?>

				</div>

				<div id="topbar" class="col-16 ml-auto">
						<?php
						// The menu icon: the folder cursor's drawing (library/images/cursors/straight/src/folder.svg).
						// Opening the menu drops its front forward, 📁 → 📂 (.is-open, set by
						// library/js/header-helpers.js; styles in _region-header-nav.scss). The words stay
						// for screen readers. Designed on library/images/graphics.html (proposal 3c).
						?>
						<a class="menu-toggle" data-toggle="collapse" data-target=".multi-collapse" href="#collapseMenu" role="button" aria-expanded="false" aria-controls="collapseMenu">
							<svg class="menu-folder" viewBox="0 0 36 36" width="28" height="28" aria-hidden="true" focusable="false"><defs><linearGradient id="menu-folder-paper" x1="0" y1="1" x2="0" y2="0"><stop offset="0" class="menu-folder__paper-bottom"/><stop offset="1" class="menu-folder__paper-top"/></linearGradient></defs><path d="M3 28V8Q3 6 5 6H12.5Q14 6 15 7.5L15.8 8.5H31Q33 8.5 33 10.5V28Q33 30 31 30H5Q3 30 3 28Z"/><g class="menu-folder__front"><path d="M3 28V15Q3 13 5 13H31Q33 13 33 15V28Q33 30 31 30H5Q3 30 3 28Z"/></g></svg>
							<span class="reveal sr-only">Reveal</span>
							<span class="hide sr-only">Hide</span>
						</a>
				</div>

			</div> <!-- row -->

		</header> <!-- end header -->

		<?php get_template_part( 'template-parts/header', 'menu' ); ?>


		<?php get_template_part( 'template-parts/header', 'lower' ); ?>
