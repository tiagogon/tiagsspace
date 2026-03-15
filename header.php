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

		<header id="site-header" class="header-front-page container-fluid side-padding Fixed" role="banner">

			<div class="row">

				<div class="topbar-left col-32 multi-collapse show">

					<?php get_template_part( 'template-parts/header', 'title' ); ?>

				</div>

				<div id="topbar" class="col-16 ml-auto">
						<a data-toggle="collapse" data-target=".multi-collapse" href="#collapseMenu" role="button" aria-expanded="false" aria-controls="collapseMenu">
							<span class="reveal">
								<?php if (is_singular() && !is_page('index')) {
									echo '<span class="short">R</span><span class="long">Reveal</span>';
								}else {
									echo "Reveal";
								} ?>
							</span>
							<span class="hide">Hide</span>
						</a>
				</div>

			</div> <!-- row -->

		</header> <!-- end header -->

		<?php get_template_part( 'template-parts/header', 'menu' ); ?>


		<?php get_template_part( 'template-parts/header', 'lower' ); ?>
