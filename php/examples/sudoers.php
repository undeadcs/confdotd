<?php
/*
 * show config names for sudo
 * single directory example
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

$confd = new Confdotd( '/etc/sudoers.d/', new File );
$names = $confd->List( );
// README file usually exists
$names = array_filter( $names, fn( $entry ) => $entry->name !== 'README' );

foreach( $names as $name ) {
	echo $name->name."\n";
}