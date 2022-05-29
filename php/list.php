<?php
/*
 * скрипт выводит список конфигов по указанным путям и типу условия
 * например: php list.php /etc/apache2/conf-available /etc/apache2/conf-enabled shellpattern
 */
require_once( __DIR__.'/Entry.php' );
require_once( __DIR__.'/Condition.php' );
require_once( __DIR__.'/condition/File.php' );
require_once( __DIR__.'/condition/Regexp.php' );
require_once( __DIR__.'/condition/ShellPattern.php' );
require_once( __DIR__.'/Confdotd.php' );

use confdotd\Confdotd;
use confdotd\condition\Regexp;
use confdotd\condition\ShellPattern;
use confdotd\condition\File;

if ( $_SERVER[ 'argc' ] < 4 ) {
	echo basename( __FILE__ )." path path-enabled[type of matching: file(default), regexp, shellpattern]\n";
	exit( 1 );
}

$dir = $_SERVER[ 'argv' ][ 1 ];

if ( !file_exists( $dir ) ) {
	echo "[ERROR] directory '$dir' not found\n";
	exit( 1 );
}
if ( !is_dir( $dir ) ) {
	echo "[ERROR] '$dir' is not a directory\n";
	exit( 1 );
}

$dirEnabled = $_SERVER[ 'argv' ][ 2 ];

if ( !file_exists( $dirEnabled ) ) {
	echo "[ERROR] directory '$dirEnabled' not found\n";
	exit( 1 );
}
if ( !is_dir( $dirEnabled ) ) {
	echo "[ERROR] '$dirEnabled' is not a directory\n";
	exit( 1 );
}

$conditionName = 'file';
$condition = new File;

if ( isset( $_SERVER[ 'argv' ][ 3 ] ) ) {
	switch( $_SERVER[ 'argv' ][ 3 ] ) {
		case 'regexp':
			$condition = new Regexp;
			$conditionName = 'regexp';
			break;
		case 'shellpattern':
			$condition = new ShellPattern;
			$conditionName = 'shellpattern';
			break;
	}
}

echo "searching configs in '$dir' by '$conditionName' condition\n";

$confd = new Confdotd( $dir, $condition, $dirEnabled );
$configs = $confd->List( );

if ( !$configs ) {
	echo "[ERROR] Configs not found\n";
	exit( 1 );
}

$maxColumns = [ 0 => 0, 1 => 10 ];

foreach( $configs as $config ) {
	$len = mb_strlen( $config->name, 'UTF-8' );
	if ( $len > $maxColumns[ 0 ] ) {
		$maxColumns[ 0 ] = $len;
	}
}

echo '+-'.str_repeat( '-', $maxColumns[ 0 ] ).'-+-'.str_repeat( '-', $maxColumns[ 1 ] )."-+\n";
echo '| '.str_pad( 'Name', $maxColumns[ 0 ], ' ', STR_PAD_BOTH ).' | '.str_pad( 'Enabled', $maxColumns[ 1 ], ' ', STR_PAD_BOTH )." |\n";
echo '+-'.str_repeat( '-', $maxColumns[ 0 ] ).'-+-'.str_repeat( '-', $maxColumns[ 1 ] )."-+\n";

foreach( $configs as $config ) {
	echo '| '.str_pad( $config->name, $maxColumns[ 0 ], ' ', STR_PAD_RIGHT ).' | '.str_pad( ( $config->enabled ? 'enabled' : '' ), $maxColumns[ 1 ], ' ', STR_PAD_BOTH )." |\n";
}

echo '+-'.str_repeat( '-', $maxColumns[ 0 ] ).'-+-'.str_repeat( '-', $maxColumns[ 1 ] )."-+\n";
