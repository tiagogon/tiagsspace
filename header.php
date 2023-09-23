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

			<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/library/js/swiper/swiper-bundle.min.css">


			<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/library/js/plyr-master/assets/vendor/plyr/dist/plyr.css">

		<?php wp_head(); ?>

		<?php // ----- SCRIPTS ----- ?>

			<?php // picturefill ?>
	      <script>
					// Picture element HTML5 shiv
					document.createElement( "picture" );
				</script>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/picturefill/picturefill.min.js" async></script>



			<?php // Modernizer ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/modernizr/modernizr.min.js"></script>
				<?php /*<script src="//cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>*/ ?>

			<?php // Jquery Library ?>
				<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/jquery/jquery.min.js"></script>
				<?php /*<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>*/ ?>

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
		  		<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/library/js/swiper/swiper-bundle.min.js"></script>

			<?php // Bootstrap Scripts ?>
					<script src="<?php bloginfo('template_url'); ?>/library/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>




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

			<div class="row">

				<div class="topbar-left col-32 multi-collapse show">

					<script>
						function showText(text){
						    document.getElementById("over-text").innerHTML=text;
						}
						function hide(){
						    document.getElementById("over-text").innerHTML="";
						}
					</script>

					<?php // Get name of the section for mobile

						$siteName = "Space";
						$Webpage_name = '<span class="d-inline d-sm-none">S</span><span class="d-none d-sm-inline">'.$siteName.'</span>';
						$header_left_title = '';

						// Defautl header title
						$header_left_title = '<h1><a href="'.home_url().'">'.$siteName.'</a><span id="over-text" class="d-none d-sm-inline"></span></h1>';


						if (is_singular()) {
							$header_left_title = '<h1><a href="'.home_url().'">S</a><span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('hyper')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Hyper<span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('4k-lento')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / 4K Lento<span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('films')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Film<span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('emulsion')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Emulsion<span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('dusk')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Dusk<span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('cityburns')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / City<span id="over-text" class="d-none d-sm-inline"></span></h1>';
						}
						if (is_post_type_archive('log')) {
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Log</h1>';
						}

						// Taxonamies
						if (is_tax( 'log-branch' )) {
							$term =	$wp_query->queried_object;
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / <a href="'.get_post_type_archive_link('log').'">Log</a> / '.$term->name.'</h1>';
							// removed from php:
							// //<span style=" ">'.$term->name.'</span>
						}
						if (is_tax( 'medium' )) {
							$term =	$wp_query->queried_object;
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Medium / '.$term->name.'<span id="over-text" class="d-none d-sm-inline"></span></h1>';
							// removed from php:
							// //<span style=" ">'.$term->name.'</span>
						}
						if (is_tax( 'from' )) {
							$term =	$wp_query->queried_object;
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Dating  / '.$term->name.'<span id="over-text" class="d-none d-sm-inline"></span></h1>';
							// removed from php:
							// //<span style=" ">'.$term->name.'</span>
						}
						if (is_tax( 'places' )) {
							$term =	$wp_query->queried_object;
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Place / '.$term->name.'<span id="over-text" class="d-none d-sm-inline"></span></h1>';
							// removed from php:
							// //<span style=" ">'.$term->name.'</span>
						}
						if (is_tag() ) {
							$tag = get_queried_object();
							$header_left_title = '<h1><a href="'.home_url().'">'.$Webpage_name.'</a> / Tag / '.$tag->name.'</h1>';
							// removed from php:
							// //<span style=" ">'.$term->name.'</span>
						}

						// Echo header title eveywhere
						echo $header_left_title; ?>

				</div>

				<div id="topbar" class="col-16 ml-auto">
						<a data-toggle="collapse" data-target=".multi-collapse" href="#collapseMenu" role="button" aria-expanded="false" aria-controls="collapseMenu">
							<span class="reveal">
								<?php if (is_singular()) {
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



		<div id="collapseMenu" class="collapse container-fluid index-block multi-collapse">

			<div class="row d-flex justify-content-start">

				<?php
					// define the size of the menu grid
					$menu_groups_class = "col-sm-24 col-md-16 col-xl-9 menu-group";
				?>

				<div class="<?php echo $menu_groups_class; ?>" >
					<ul>
						<?php //if (is_singular()) { ?>
							<li>
								<a href="<?php echo home_url(); ?>" class="">Space</a>
							</li>
						<?php //} ?>
					</ul>
				</div>
				<div class="<?php echo $menu_groups_class; ?>" >
					<ul>
						<li>
							<a href="<?php echo get_post_type_archive_link( 'hyper'); ?>" class="<?php if ( is_post_type_archive('hyper')) { echo "active";} if (is_singular( 'hyper' ) ) { echo " active";} ?>">Hyper</a>
						</li>
					 <!-- <li>
						 <a href="<?php echo get_post_type_archive_link( 'films'); ?>" class="<?php if ( is_post_type_archive('Films')) { echo "active";} if (is_singular( 'films' ) ) { echo " active";} ?>">Film</a>
					 </li> -->
					   <li>
						   <a href="<?php echo get_post_type_archive_link( 'dusk'); ?>" class="<?php if ( is_post_type_archive('dusk')) { echo "active";} if (is_singular( 'dusk' )) { echo " active";} ?>">Dusk</a>
					   </li>
						 <li>
							 <a data-toggle="collapse" href="#collapselog-branch" role="button" aria-expanded="false" aria-controls="collapsePlaces">Log</a>
						 </li>
						 <li>
							 <ul class="collapse" id="collapselog-branch">
							 <li>
								 <a href="<?php echo get_post_type_archive_link( 'log'); ?>" class="<?php if ( is_post_type_archive('log')) { echo "active";} if (is_singular( 'log' )) { echo " active";} ?>">All</a>
							 </li>
								 <?php
								 // Get the list of terms in the custom taxonomy
								 $terms = get_terms(array(
										 'taxonomy' => 'log-branch',
										 'hide_empty' => true,
								 ));
								 // Loop through each term
								 foreach ($terms as $term) {
										 // Get the custom field value for the term
										 $custom_field_value = get_field('is_archived', $term);

										 // Check if the custom field value is true or false
										 if (!$custom_field_value==true) {

											 // elmentclass
											 $elmentclass = "";
											 if ( is_tax( 'log-branch', $term) ) {
												 $elmentclass = "active";
												 ?>
												 	<script type="text/javascript">
												 		$('#collapselog-branch').collapse()
												 	</script>
												 <?php
											 }

											 // Display the term
											 echo '<li><a href="' . get_term_link($term) . '" class="' . $elmentclass . '">' . $term->name . '</a></li>';
										 }
								 }
								 ?>
							 </ul>
						 </li>
					</ul>
				</div>

				<div class="<?php echo $menu_groups_class; ?>" >
					<ul>
						<li>
 							<a href="<?php echo get_post_type_archive_link( '4k-lento'); ?>" class="<?php if ( is_post_type_archive('4k-lento')) { echo "active";} if (is_singular( '4k-lento' ) ) { echo " active";} ?>">4K Lento</a>
							<li>
								<a href="https://soundcloud.com/tiagsssss"  target="_blank">Soundcloud</a>
							</li>
							<li>
								<a href="https://podcasts.apple.com/ca/podcast/4k-lento/id1445312236" target="_blank">Podcast</a>
							</li>
						<li>
					</ul>
				</div>

				<div class="<?php echo $menu_groups_class; ?>" >
					<ul>
						<li>
							<a data-toggle="collapse" href="#collapseDating" role="button" aria-expanded="false" aria-controls="collapseDating">Time</a>
							<?php
							$args = array(
								'taxonomy'     => 'from',
								'orderby'      => 'name',
                'order' 			 => 'DESC',
								'hide_empty'   => 1,
								'title_li'     => '',
								'hierarchical' => 1,
								'walker'       => null,
							);
							?>
							<ul class="collapse" id="collapseDating">
								<?php wp_list_categories( $args ); ?>
							</ul>
						</li>

						<li>
							<a data-toggle="collapse" href="#collapsePlaces" role="button" aria-expanded="false" aria-controls="collapsePlaces">Place</a>

							<?php
							$args = array(
								'taxonomy'     => 'places',
								'orderby'      => 'name',
								'hide_empty'   => 1,
								'title_li'     => '',
								'hierarchical' => 1,
								'walker'       => null,
							);
							?>
							<ul class="collapse" id="collapsePlaces">
								<?php wp_list_categories( $args ); ?>
							</ul>
						</li>

						<li>
							<a data-toggle="collapse" href="#collapseMedium" role="button" aria-expanded="false" aria-controls="collapseMedium">Medium</a>
							<?php
							$args = array(
								'taxonomy'     => 'medium',
								'orderby'      => 'name',
								'hide_empty'   => 1,
								'title_li'     => '',
								'hierarchical' => 1,
								'walker'       => null,
							);
							?>
							<ul class="collapse" id="collapseMedium">
								<?php wp_list_categories( $args ); ?>
							</ul>
						</li>
						<li>
							<a data-toggle="collapse" href="#collapsePast" role="button" aria-expanded="false" aria-controls="collapsePast">Final</a>
							<ul class="collapse" id="collapsePast">
								<li>
									<a data-toggle="collapse" href="#collapsePastFromSeries" role="button" aria-expanded="false" aria-controls="collapsePastFromSeries">Series</a>
									<ul class="collapse" id="collapsePastFromSeries">
										<li>
											<a href="<?php echo get_post_type_archive_link( 'emulsion'); ?>" class="<?php if (is_post_type_archive('emulsion')) { echo "active";} if (is_singular( 'emulsion' )) { echo " active";} ?>">Emulsion</a>
										</li>
										<li>
											<a href="<?php echo get_post_type_archive_link( 'cityburns'); ?>" class="<?php if (is_post_type_archive('cityburns')) { echo "active";} if (is_singular( 'cityburns' )) { echo " active";} ?>">City</a>
										</li>
									</ul>
								</li>
								<li>
									<a data-toggle="collapse" href="#collapsePastFromLog" role="button" aria-expanded="false" aria-controls="collapsePastFromLog">Log</a>
									<ul class="collapse" id="collapsePastFromLog">
										<?php
										// Get the list of terms in the custom taxonomy
										$terms = get_terms(array(
												'taxonomy' => 'log-branch',
												'hide_empty' => true,
										));
										// Loop through each term
										foreach ($terms as $term) {
												// Get the custom field value for the term
												$custom_field_value = get_field('is_archived', $term);

												// Check if the custom field value is true or false
												if ($custom_field_value==true) {
														// Display the term
														echo '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
												}
										}
										?>
									</ul>
								</li>
							</ul>
						</li>

					</ul>
				</div>

				<div class="<?php echo $menu_groups_class; ?>" >
					<ul>
					<li>
						<a href="mailto:mail@tiags.space" target="_blank">Email</a>
					</li>
					<li>
						<a href="https://www.instagram.com/tiagsssss/" target="_blank">Instagram</a>
					</li>
					<li>
						<a href="https://ra.co/dj/tiagsssss" target="_blank">RA</a>
					</li>
					<!-- <li>
						<a href="https://vimeo.com/tiags" target="_blank">Vimeo</a>
					</li> -->
					<li>
						<a data-toggle="collapse" href="#collapseReference" role="button" aria-expanded="false" aria-controls="collapsePast">Lists</a>
						<ul class="collapse" id="collapseReference">
							<li>
								<a href="https://goodreads.com/tiags" target="_blank">Goodreads</a>
							</li>
							<li>
								<a href="https://letterboxd.com/tiagsssss/" target="_blank">Letterboxd</a>
							</li>
							<li>
								<a href="https://tiags.tumblr.com/" target="_blank">Misc.</a>
							</li>
						</ul>
					</li>
					<!-- <li>
						<a href="https://tiagssssspace.tumblr.com/"  target="_blank">Tumblr</a>
					</li>
					<li>
						<a href="https://twitter.com/tiagsssss" target="_blank">Twitter</a>
					</li> -->
					</ul>
				</div>







			</div>
		</div>
