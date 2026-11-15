<?php
/**
 * Local runner for library/migrate-cidade.php (the MAMP site has no WP-CLI).
 * Production uses `wp tiagsspace cidade-migrate` instead.
 *
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php bin/cidade-migrate.php --dry-run
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php bin/cidade-migrate.php
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php bin/cidade-migrate.php --reverse
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php bin/cidade-migrate.php --dusk-to-cidade [--dry-run|--reverse]
 */
if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}
$opts = array_slice( $argv, 1 );
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';

$run = in_array( '--dusk-to-cidade', $opts, true ) ? 'tiagsspace_move_dusk_to_cidade' : 'tiagsspace_migrate_cityburns_to_cidade';
$run( array(
	'dry_run' => in_array( '--dry-run', $opts, true ),
	'reverse' => in_array( '--reverse', $opts, true ),
	'force'   => in_array( '--force', $opts, true ),
) );
