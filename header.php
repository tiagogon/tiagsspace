<!doctype html>

<!--[if IEMobile 7 ]> <html <?php language_attributes(); ?>class="no-js iem7"> <![endif]-->
<!--[if lt IE 7 ]> <html <?php language_attributes(); ?> class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html <?php language_attributes(); ?> class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html <?php language_attributes(); ?> class="no-js ie8"> <![endif]-->
<!--[if (gte IE 9)|(gt IEMobile 7)|!(IEMobile)|!(IE)]><!-->

<html <?php language_attributes(); ?> class="no-js"><!--<![endif]-->

	<head>
		<?php // ----- META ----- ?>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php wp_title( '|', true, 'right' ); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
 <!--320-->

		<?php // ----- CSS ----- ?>
			<link rel="stylesheet" id="bootstrap-css" href="<?php bloginfo('template_url'); ?>/library/css/bootstrap.css" type="text/css" media="all">

				<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/library/js/swiper/swiper.min.css">


		<?php // ----- FONTS ----- ?>
			<link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,600,600i" rel="stylesheet">


		<?php wp_head(); ?>

		<?php // ----- SCRIPTS ----- ?>

			<?php // picturefill ?>
	            <script>
					// Picture element HTML5 shiv
					document.createElement( "picture" );
				</script>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/picturefill/picturefill.min.js" async></script>
				<?php /*<script src="//cdnjs.cloudflare.com/ajax/libs/picturefill/2.3.1/picturefill.min.js"  async></script>*/ ?>


			<?php // Modernizer ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/modernizr/modernizr.min.js"></script>
				<?php /*<script src="//cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>*/ ?>

			<?php // Jquery Library ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/jquery/jquery.min.js"></script>
				<?php /*<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>*/ ?>

			<?php // Slick Nav  ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/slicknav/jquery.slicknav.min.js"></script>
				<?php /*<script src="//cdnjs.cloudflare.com/ajax/libs/SlickNav/1.0.4/jquery.slicknav.min.js"></script>	*/ ?>

			<?php // Masonry ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/masonry/masonry.pkgd.min.js"></script>
				<?php /*<script src="//cdnjs.cloudflare.com/ajax/libs/masonry/3.3.0/masonry.pkgd.min.js">
				masonry</script>*/ ?>

			<?php // Classie ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/classie/classie.min.js"></script>
				<?php /* <script src="//cdnjs.cloudflare.com/ajax/libs/classie/1.0.1/classie.min.js"></script> */ ?>

			<?php // Clean Analitics URL campaings ?>
				<script src="<?php bloginfo('template_url'); ?>/library/js/fresh_url/fresh_url.min.js" async></script>

			<?php // Slider Swiper ?>
		  		<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/swiper/swiper.jquery.min.js"></script>




		  	<?php // Play video on iphone wihtout fullscreen ?>
		  		<!-- <script src="<?php bloginfo('template_url'); ?>/library/js/iphone-inline-video-master/dist/iphone-inline-video.min.js"></script> -->

		<!-- end of wordpress head -->
		<!-- IE8 fallback moved below head to work properly. Added respond as well. Tested to work. -->
			<!-- media-queries.js (fallback) -->
		<!--[if lt IE 9]>
			<script src="https://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
		<![endiPosts Categorized: burns seriesf]-->

		<!-- html5.js -->
		<!--[if lt IE 9]>
			<script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->

			<!-- respond.js -->
		<!--[if lt IE 9]>
		          <script type='text/javascript' src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.js"></script>
		<![endif]-->

		<!-- jquery -->

	</head>

	<body <?php body_class();?> >

		<header id="site-header" class="header-front-page container-fluid side-padding" role="banner">
		<div class="clearfix row">

			<?php // Get name of the section for mobile

				$section_name = '';

				// If is custom post archive or singular
				if (is_singular( 'hyper' ) OR is_post_type_archive('hyper')) {
					$section_name = ' _ <a href="'.get_post_type_archive_link( 'hyper').'"><span style="font-style: italic;">hyper</a></span>';
				}
				if (is_singular( 'log' ) OR is_post_type_archive('log')) {
					$section_name = ' _ <a href="'.get_post_type_archive_link( 'log').'"><span style="font-style: italic;">log</a></span>';
				}
				if (is_singular( 'emulsion' ) OR is_post_type_archive('emulsion')) {
					$section_name = ' _ <a href="'.get_post_type_archive_link( 'emulsion').'"><span style="font-style: italic;">emulsion</a></span>';
				}
				if (is_singular( 'dusk' ) OR is_post_type_archive('dusk')) {
					$section_name = ' _ <a href="'.get_post_type_archive_link( 'dusk').'"><span style="font-style: italic;">dusk</a></span>';
				}
				if (is_singular( 'films' ) OR is_post_type_archive('films')) {
					$section_name = ' _ <a href="'.get_post_type_archive_link( 'films').'"><span style="font-style: italic;">films</a></span>';
				}

				// If is a Log Branch
				if (is_tax( 'log-branch' )) {
					$term =	$wp_query->queried_object;

					$section_name = ' _ <a href="'.get_post_type_archive_link( 'log').'"><span style="font-style: italic;">log</a></span>';
					// removed from php:
					// //<span style="font-style: italic;">'.$term->name.'</span>
				}

			?>


			<div id="topbar-parent" class="col-12">
				<div id="topbar">
					<ul id="menu">
						<?php if (!is_home()) { ?>
							<li class=""><a title="<?php echo get_bloginfo('description'); ?>" href="<?php echo home_url(); ?>" class="<?php if (is_home()) { echo "active";} ?>">all</a></li>
						<?php } ?>

						<?php

							// --- Tags and Custom taxonamies menu item ---

							if ( is_tag() ) {
						        echo '<li class="">
									<a href="#" class="active">Tag: '.str_replace(" ","",single_tag_title("", false)).'</a>
								</li>';
						    }

							if ( is_tax() AND !is_tax('log-branch') ) {
								$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );

						        echo '<li class="">
									<a href="#" class="active">'.$term->taxonomy.': '.str_replace(" ","",$term->name).'</a>
								</li>';
						    }


						 ?>

						<li><a href="<?php echo get_post_type_archive_link( 'films'); ?>" class="<?php if (is_singular( 'films' ) OR is_post_type_archive('films')) { echo "active";} if (is_singular( 'films' )) { echo "active";} if (is_singular( 'films' )) { echo " belongs";} ?>">films</a></li>

						<li><a href="<?php echo get_post_type_archive_link( 'hyper'); ?>" class="<?php if ( is_post_type_archive('hyper')) { echo "active";} if (is_singular( 'hyper' ) ) { echo " belongs";} ?>">🦠</a></li>

						<li><a href="<?php echo get_post_type_archive_link( 'dusk'); ?>" class="<?php if ( is_post_type_archive('dusk')) { echo "active";} if (is_singular( 'dusk' )) { echo " belongs";} ?>">dusk</a></li>

						<?php /* <li><a href="<?php echo get_post_type_archive_link( 'emulsion'); ?>" class="<?php if (is_post_type_archive('emulsion')) { echo "active";} if (is_singular( 'emulsion' )) { echo " belongs";} ?>">emulsion</a></li> */?>

						<?php /*
						<li><a href="<?php echo get_term_link( 'blwww', 'log-branch'); ?>" class="<?php if (is_singular( 'log' )
										OR is_post_type_archive('log')
										or is_tax('log-branch')) { echo " belongs";} ?>">log</a></li>
						 */ ?>

						<li><a href="<?php echo get_post_type_archive_link( 'log'); ?>" class="<?php if (is_singular( 'log' )
										OR is_post_type_archive('log')
										or is_tax('log-branch')) { echo " belongs";} ?>">Log</a></li>

						<!-- <li ><a id="trigger-overlay-follow" href="#"><span class="fa fa-rss"></span></a></li> -->
					</ul>
				</div>
			</div>


			<?php if (is_singular( 'log' )
					OR is_post_type_archive('log')
					or is_tax('log-branch')) { ?>

				<div class="submenu submenu-log col-xs-12 col-sm-12 col-md-12">
					<ul>

						<li><a href="<?php echo get_post_type_archive_link( 'log'); ?>" class="<?php if (is_post_type_archive('log')) { echo "active";} ?>">all</a></li>

						<li><a href="<?php echo get_term_link( 'blwww', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','blwww')) { echo "active";} if ((is_single() and has_term( 'blwww', 'log-branch' ))) { echo " belongs";} ?>">blwww</a></li>

						<li><a href="<?php echo get_term_link( 'hrzn', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','hrzn')) { echo "active";} if ((is_single() and has_term( 'hrzn', 'log-branch' ))) { echo " belongs";} ?>">hrzn</a></li>

						<li><a href="<?php echo get_term_link( 'frntr', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','frntr')) { echo "active";} if ((is_single() and has_term( 'frntr', 'log-branch' ))) { echo " belongs";} ?>">frntr</a></li>

						<li><a href="<?php echo get_term_link( 'sdwlk', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','sdwlk')) { echo "active";} if ((is_single() and has_term( 'sdwlk', 'log-branch' ))) { echo " belongs";} ?>">sdwlk</a></li>

						<li><a href="<?php echo get_term_link( 'plnts', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','plnts')) { echo "active";} if ((is_single() and has_term( 'plnts', 'log-branch' ))) { echo " belongs";} ?>">plnts</a></li>

						<!-- <li><a href="<?php echo get_term_link( 'rchv', 'log-branch'); ?>" class="<?php if (is_tax('log-branch','rchv') or (is_single() and has_term( 'rchv', 'log-branch' ))) { echo "active";} ?>">rchv</a></li> -->

					</ul>
				</div>

			<?php } ?>
		</div>
		</header> <!-- end header -->

		<?php // Mobile Menu ?>
		<script>
			$(function(){
				$('#topbar').slicknav({
					prependTo: '#topbar-parent',//'.header-front-page',
					//closeOnClick: true,
					label :'<span class="open">[_]</span><span class="close">[x]</span>',
					duplicate : false,
					removeIds : false,
				});
			});
		</script>
