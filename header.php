<?php

// ATENTION!!!
// REDIRECT WORDPRESS IF NOT LOGED IN

/* if( !is_user_logged_in()
	// AND !is_post_type_archive( "hyper" )
	// AND !is_singular( 'hyper' )
	) {
	// not public yet!
		wp_redirect('http://tumblr.trouble.place'); // default 302
		exit;

}RELEASE!*/?>

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
				<?php /*<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/3.0.8/css/swiper.min.css">*/ ?>


		<?php // ----- FONTS ----- ?>
			<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/library/fonts/font-awesome/css/font-awesome.min.css">
			<!-- <link href='https://fonts.googleapis.com/css?family=Raleway:600,600italic,800,800italic' rel='stylesheet' type='text/css'> -->
			<link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

			<!-- <link href="https://fonts.googleapis.com/css?family=Work+Sans:600,800" rel="stylesheet">
			<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,700,700i,800,800i" rel="stylesheet">
			<link href="https://fonts.googleapis.com/css?family=Montserrat:500,500i,700,700i" rel="stylesheet"> -->


		<?php wp_head(); ?>

		<?php // ----- SCRIPTS ----- ?>

			<?php // picturefill ?>
	            <script>
					// Picture element HTML5 shiv
					document.createElement( "picture" );
				</script>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/picturefill/picturefill.min.js"></script>
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
		  		<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/swiper/swiper.min.js"></script>

		  	<?php // infinite-scroll.com ?>
		  		<script src="https://unpkg.com/infinite-scroll@3/dist/infinite-scroll.pkgd.min.js"></script>

		  	<!-- <?php // DRAG IMAGES ORDER WITH packery
		  	if (is_user_logged_in()) { ?>
		  	    <script src="<?php bloginfo('template_url'); ?>/library/js/packery/packery.pkgd.min.js"></script>
		  	<?php }?> -->

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


		<!-- facebook SDK config -->
		<script>
		  window.fbAsyncInit = function() {
		    FB.init({
		      appId      : '1565561850224602',
		      xfbml      : true,
		      version    : 'v2.12'
		    });
		    FB.AppEvents.logPageView();
		  };

		  (function(d, s, id){
		     var js, fjs = d.getElementsByTagName(s)[0];
		     if (d.getElementById(id)) {return;}
		     js = d.createElement(s); js.id = id;
		     js.src = "https://connect.facebook.net/en_US/sdk.js";
		     fjs.parentNode.insertBefore(js, fjs);
		   }(document, 'script', 'facebook-jssdk'));
		</script>



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

						<li><a href="<?php echo get_post_type_archive_link( 'films'); ?>" class="<?php if (is_singular( 'films' ) OR is_post_type_archive('films')) { echo "active";} if (is_singular( 'films' )) { echo "active";} if (is_singular( 'films' )) { echo " belongs";} ?>">films</a></li>

						<li><a href="<?php echo get_post_type_archive_link( 'hyper'); ?>" class="<?php if ( is_post_type_archive('hyper')) { echo "active";} if (is_singular( 'hyper' ) ) { echo " belongs";} ?>">hyper </a></li>

						<li><a href="<?php echo get_post_type_archive_link( 'dusk'); ?>" class="<?php if ( is_post_type_archive('dusk')) { echo "active";} if (is_singular( 'dusk' )) { echo " belongs";} ?>">dusk</a></li>

						<li><a href="<?php echo get_post_type_archive_link( 'emulsion'); ?>" class="<?php if (is_post_type_archive('emulsion')) { echo "active";} if (is_singular( 'emulsion' )) { echo " belongs";} ?>">emulsion</a></li>

						<li><a href="<?php echo get_term_link( 'blwww', 'log-branch'); ?>" class="<?php if (is_singular( 'log' )
										OR is_post_type_archive('log')
										or is_tax('log-branch')) { echo " belongs";} ?>">log</a></li>

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
					label :'<span class="open fa fa-bars"></span><span class="close fa fa-times"></span>',
					duplicate : false,
					removeIds : false,
				});
			});
		</script>
