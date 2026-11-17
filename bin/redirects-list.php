<?php
/**
 * Prints every old slug stored on a post that redirects (see library/redirects.php), as Markdown.
 * Local runner (the MAMP site has no WP-CLI); on the server: wp tiagsspace redirects slugs
 *
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php bin/redirects-list.php > server/redirects/OLD-SLUGS.md
 */
if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
echo tiagsspace_redirect_old_slugs_markdown();
