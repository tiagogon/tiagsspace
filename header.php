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

		<?php // ----- CSS ----- ?>


			<link rel="stylesheet" id="bootstrap-css" href="<?php bloginfo('template_url'); ?>/library/css/bootstrap.css" type="text/css" media="all">

			<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/library/js/swiper/swiper.min.css">

		<?php // ----- FONTS ----- ?>
			<link rel="preconnect" href="https://fonts.googleapis.com">
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:ital,wght@0,400;0,600;1,400;1,600&display=swap" rel="stylesheet">


			<!-- <style type="text/css">
			@font-face{ font-family: "calyces-regular-webfont";
				src: url("<?php bloginfo('template_url'); ?>/library/fonts/charlotterohde/calyces-regular-webfont.woff") format("woff"); }
			@font-face{ font-family: "serifbabe-regular-webfont";
				src: url("<?php bloginfo('template_url'); ?>/library/fonts/charlotterohde/serifbabe-regular-webfont.woff") format("woff"); }
			@font-face{ font-family: "SerifbabeAlpha-Regular";
				src: url("<?php bloginfo('template_url'); ?>/library/fonts/charlotterohde/SerifbabeAlpha-Regular-2.woff2") format("woff"); }
			@font-face{ font-family: "Keroine-IntenseLegere";
				src: url("<?php bloginfo('template_url'); ?>/library/fonts/charlotterohde/Keroine-IntenseLegere.woff") format("woff"); }
			@font-face{ font-family: "Keroine-DouxExtreme";
				src: url("<?php bloginfo('template_url'); ?>/library/fonts/charlotterohde/Keroine-DouxExtreme.woff") format("woff"); }
			</style> -->


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


			<?php // Mmenu ?>
	        	<script src="<?php bloginfo('template_url'); ?>/library/js/mmenu-js-master/dist/mmenu.js"></script>

				<?php
				// MMenu Configuration ?>
				<script>


					//
					document.addEventListener(
						"DOMContentLoaded", () => {
							new Mmenu( "#my-menu", {
								// "pageScroll": {
		 						// 	  "scroll": true,
		 						// 	 "update": true,
		 						//   },
								"extensions": [
									"position-right",
									//"position-front",
									"border-none",
									"multiline",
									//"pagedim-black"
									// "fx-menu-slide",
									// "fx-panels-slide-0",
									"shadow-panels",
							   	],
							    "counters": true,
							    "iconPanels":
									{
										"add": true,
										"blockPanel":true,
										"visible": 1,
								    }
							     ,
								 // "drag":{
									//  "open": true,
								 // }
							   "navbar": [
								   {
								   "add": false,
								   }
							   ],
							   "navbars": [
								  {
									  "use": false,
									 "position": "top",
									 // "content": [
									 //    "searchfield"
									 // ]
								  }
							  ],
							  // backButton: {
								//    // back button options
							  //  },
							  // wrappers: ["wordpress"],
							  // scrollBugFix: {
		                    	// 	"use": true
		                		// }
						  }, {
				                // scrollBugFix: {
				                //     "use": true
				                // },
							}
					  );

						}

					);
				</script>



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



		<header id="site-header" class="header-front-page container-fluid side-padding Fixed" role="banner">
		<div class="clearfix row">



			<?php // Get name of the section for mobile

				$section_name = '';

				// // If is custom post archive or singular
				// if (is_singular( 'hyper' ) OR is_post_type_archive('hyper')) {
				// 	$section_name = ' _ <a href="'.get_post_type_archive_link( 'hyper').'"><span style="font-style: italic;">hyper</a></span>';
				// }
				// if (is_singular( 'log' ) OR is_post_type_archive('log')) {
				// 	$section_name = ' _ <a href="'.get_post_type_archive_link( 'log').'"><span style="font-style: italic;">log</a></span>';
				// }
				// if (is_singular( 'emulsion' ) OR is_post_type_archive('emulsion')) {
				// 	$section_name = ' _ <a href="'.get_post_type_archive_link( 'emulsion').'"><span style="font-style: italic;">emulsion</a></span>';
				// }
				// if (is_singular( 'dusk' ) OR is_post_type_archive('dusk')) {
				// 	$section_name = ' _ <a href="'.get_post_type_archive_link( 'dusk').'"><span style="font-style: italic;">dusk</a></span>';
				// }
				// if (is_singular( 'films' ) OR is_post_type_archive('films')) {
				// 	$section_name = ' _ <a href="'.get_post_type_archive_link( 'films').'"><span style="font-style: italic;">films</a></span>';
				// }
				//
				// // If is a Log Branch
				// if (is_tax( 'log-branch' )) {
				// 	$term =	$wp_query->queried_object;
				//
				// 	$section_name = ' _ <a href="'.get_post_type_archive_link( 'log').'"><span style="font-style: italic;">log</a></span>';
				// 	// removed from php:
				// 	// //<span style="font-style: italic;">'.$term->name.'</span>
				// }

			?>


			<div id="topbar-parent" class="col-48">

				<div id="topbar">

					<ul id="menu">

						<?php
							// // --- Tags and Custom taxonamies menu item ---
							// if ( is_tag() ) {
						    //     echo '<li class="">
							// 		<span class="active">Tag: '.str_replace(" ","",single_tag_title("", false)).'</span>
							// 	</li>';
						    // }
							//
							// if ( is_tax() AND !is_tax('log-branch') ) {
							// 	$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
							//
						    //     echo '<li class="">
							// 		<span class="active">'.$term->taxonomy.': '.str_replace(" ","",$term->name).'</span>
							// 	</li>';
						    // }
						 ?>

						<li><a href="#my-menu" >Reveal</a></li>
					</ul>
				</div>
			</div>

		</div>
		</header> <!-- end header -->
