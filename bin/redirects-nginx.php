<?php
/**
 * Prints the nginx snippet generated from library/redirects.php. No WordPress needed.
 *
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php bin/redirects-nginx.php > server/nginx/tiagsspace-redirects.conf
 */
require dirname( __DIR__ ) . '/library/redirects.php';
echo tiagsspace_redirects_nginx_snippet();
