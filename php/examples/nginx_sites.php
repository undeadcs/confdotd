<?php
/*
 * listing nginx sites
 * you need access rights to /etc/nginx/sites-available and /etc/nginx/sites-enabled
 * best way is to add you username to www-data group
 * pair directory example
 */

// hmm, autoload? xD
require_once( __DIR__.'/../src/Condition.php' );
require_once( __DIR__.'/../src/Entry.php' );
require_once( __DIR__.'/../src/conditions/File.php' );
require_once( __DIR__.'/../src/conditions/Regexp.php' );
require_once( __DIR__.'/../src/conditions/ShellPattern.php' );
require_once( __DIR__.'/../src/Confdotd.php' );

use confdotd\conditions\File;
use confdotd\Confdotd;

$confd = new Confdotd( '/etc/nginx/sites-available', new File, '/etc/nginx/sites-enabled' );
$sites = $confd->List( );

foreach( $sites as $site ) {
	echo $site->name.' <'.( $site->enabled ? 'enabled' : 'disabled' ).">\n";
}
