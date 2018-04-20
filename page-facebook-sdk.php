<?php
/*
Template Name: Facebook SDK playground
*/
?>

<?php get_header(); ?>
		<div class="container">
			
			<div id="content" class="clearfix row">
			
				<div id="main" class="col col-lg-12 clearfix" role="main">


					<?php 
						 
						session_start();
						require_once __DIR__ . '/Facebook/autoload.php'; // download official fb sdk for php @ https://github.com/facebook/php-graph-sdk

						$fb = new Facebook\Facebook([
						  'app_id' => '383792498697921',
						  'app_secret' => '2e96ef4d24f108d282bc6125595ceace',
						  'default_graph_version' => 'v2.12'
						  ]);

						
						// Follow this https://adamboother.com/blog/automatically-posting-to-a-facebook-page-using-the-facebook-sdk-v5-for-php-facebook-api/
							// Get the 
							// https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id=383792498697921&client_secret=2e96ef4d24f108d282bc6125595ceace&fb_exchange_token=EAAFdDqeNZAsEBAGTAlY2zeN3MDA2ZAm3zUmjSwubBSJH9JEWAACgX1pBg3BZB3oDMDQEgREuSftcbfrebVDz3ZCaUFDBXcCN5y2LTEcJZAavMKFED6ZCP8V3kYL7lvuFE8ZCTmDgBtu76PWgBVKsYLX2NZAHPvdZBGT3U3kEbiJ9T7NVJt6SBL00DdAC2bHoGcaQZD

						$pageAccessToken ='EAAFdDqeNZAsEBAMTffbvGCpjaItiakjmN1p4e9voGfwlkqUYvt5h42Pg6e1e31Fytz4FUmVQ8WmRpzV5IHvstKlYWSk5DkEZAMFtlcgL75SZAx1ZC8yi1OgXl4WaXnqt9JUEx50SGfy2ZAawehx8qWZC55pHZAgKEoZD';

						
						//  --- Post on a personal page: People and Places in Trouble --- 
						// does not work aftre a while. permissions error. something expires here and i don't know what

							// //Post property to Facebook
							// $linkData = [
							//  'link' => 'https://trouble.place',
							//  'message' => 'testing Trbl3 Bot'
							// ];

							// try {
							//  $response = $fb->post('/trouble.place/feed', $linkData, $pageAccessToken);
							// } catch(Facebook\Exceptions\FacebookResponseException $e) {
							//  echo 'Graph returned an error: '.$e->getMessage();
							//  exit;
							// } catch(Facebook\Exceptions\FacebookSDKException $e) {
							//  echo 'Facebook SDK returned an error: '.$e->getMessage();
							//  exit;
							// }
							// $graphNode = $response->getGraphNode();

							
						//  --- Post on a page: People and Places in Trouble --- 

							// Stuff to post
							$stuff_to_post = [
								//'link' => 'https://trouble.place',
								'message' => 'SWAN >> https://trouble.place/emulsion/swan/',
								'source' => $fb->fileToUpload(__DIR__.'/erich.jpg'),
							];

							// post on behalf of page
							$pages = $fb->get('/me/accounts', $pageAccessToken);
							$pages = $pages->getGraphEdge()->asArray();
							foreach ($pages as $key) {
								// if ($key['name'] == 'People and Places in Trouble') { // original way: https://youtu.be/c4GDuYZ0Y6U
								if ($key['id'] == '336833940709') {
									//$post = $fb->post('/'.$key['id'].'/feed', $stuff_to_post, $key['access_token']);
									$post = $fb->post('/'.$key['id'].'/photos', $stuff_to_post, $key['access_token']);
									$response = $post->getGraphNode()->asArray();
									
									// DEBUG
									// print_r($response);
								}
							}

					?>
					
			
				</div> <!-- end #main -->
    
				<?php //get_sidebar(); // sidebar 1 ?>
    
			</div> <!-- end #content -->

		</div> <!-- end #container -->
		
<?php get_footer(); ?>