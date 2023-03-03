<?php
/*
 * скрипт проверяет работу операций над конфигами
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
use confdotd\Condition;
use confdotd\Entry;

$dir = __DIR__.'/configs-available';
$dirEnabled = __DIR__.'/configs-enabled';

if ( !file_exists( $dir ) ) {
	mkdir( $dir, 0755, true );
}
if ( !file_exists( $dirEnabled ) ) {
	mkdir( $dirEnabled, 0755, true );
}

PrintTitle( '1. file ' );
TestConfigs( $dir, $dirEnabled, new File );
echo "\n";

PrintTitle( '2. regexp ' );
TestConfigs( $dir, $dirEnabled, new Regexp );
echo "\n";

PrintTitle( '3. shellpattern ' );
TestConfigs( $dir, $dirEnabled, new ShellPattern );
echo "\n";

exit( 0 );

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function PrintTitle( string $title ) {
	echo '== '.str_pad( $title, 75, '=', STR_PAD_RIGHT )."\n";
}

function EntriesToNames( array $entries ) : array {
	$names = [ ];
	
	foreach( $entries as $entry ) {
		$names[ ] = $entry->name;
	}
	
	return $names;
}

function EntriesEnabled( array $entries ) : array {
	$enabled = [ ];
	
	foreach( $entries as $entry ) {
		if ( $entry->enabled ) {
			$enabled[ ] = $entry;
		}
	}
	
	return $enabled;
}

function EntriesDisabled( array $entries ) : array {
	$enabled = [ ];
	
	foreach( $entries as $entry ) {
		if ( !$entry->enabled ) {
			$enabled[ ] = $entry;
		}
	}
	
	return $enabled;
}

function TestConfigs( string $dir, string $dirEnabled, Condition $condition ) {
	echo "removing existing files from '$dir'\n";
	passthru( 'find '.escapeshellarg( $dir ).' -maxdepth 1 -type f -name \'*.conf\' -print -delete' );
	
	echo "removing existing files from '$dirEnabled'\n";
	passthru( 'find '.escapeshellarg( $dirEnabled ).' -maxdepth 1 -type l -name \'*.conf\' -print -delete' );
	
	clearstatcache( true ); // обязательно true, иначе symlink будет возвращать false и ругаться
	
	$confd = new Confdotd( $dir, $condition, $dirEnabled );
	
	$testConfigs = [
		'config01.conf', 'config02.conf', 'config03.conf', 'config04.conf', 'config05.conf', 'config06.conf',
		'config07.conf', 'config08.conf', 'config09.conf', 'config10.conf', 'config11.conf', 'config12.conf'
	];
	sort( $testConfigs );
	
	#1. добавление конфига: добавляем конфиги, их листинг должен совпадать
	echo 'adding...';
	foreach( $testConfigs as $name ) {
		$entry = $confd->Add( $name );
		if ( !$entry ) {
			throw new RuntimeException( "failed to add '$name' config" );
		}
		
		file_put_contents( $entry->dir.'/'.$entry->name, $name."\n" );
	}
	
	$names = EntriesToNames( $confd->List( ) );
	if ( $testConfigs != $names ) {
		var_dump( $testConfigs, $names );
		throw new RuntimeException( 'failed to create configs pool' );
	}
	
	echo "OK\n";
	
	#2. включение конфигов: включаем конфиги, после листинга выбираем enabled и сравниваем
	echo 'enabling...';
	$testEnabled = [ 'config03.conf', 'config04.conf', 'config09.conf', 'config11.conf' ];
	sort( $testEnabled );
	
	foreach( $testEnabled as $name ) {
		if ( !$confd->Enable( $name ) ) {
			throw new RuntimeException( "failed to enable '$name' config" );
		}
	}
	
	$notExists = 'non_existing.conf';
	
	if ( $confd->Enable( $notExists ) ) {
		throw new RuntimeException( 'enabled non existing config' );
	}
	if ( is_link( $dirEnabled.'/'.$notExists ) ) {
		throw new RuntimeException( 'trash link created' );
	}
	
	$names = EntriesToNames( EntriesEnabled( $confd->List( ) ) );
	if ( $testEnabled != $names ) {
		var_dump( $testEnabled, $names );
		throw new RuntimeException( 'failed to enable configs' );
	}
	
	echo "OK\n";
	
	#3. отключение конфигов: отключаем конфиги, после листинга выбираем enabled и сравниваем с оставшимися
	echo 'disabling...';
	$testDisabled = [ 'config04.conf', 'config09.conf' ];
	sort( $testDisabled );
	$testEnabled = array_diff( $testEnabled, $testDisabled ); // оставшиеся enabled
	sort( $testEnabled );
	
	foreach( $testDisabled as $name ) {
		if ( !$confd->Disable( $name ) ) {
			throw new RuntimeException( "failed to disable '$name' config" );
		}
	}
	
	$names = EntriesToNames( EntriesEnabled( $confd->List( ) ) );
	if ( $testEnabled != $names ) {
		var_dump( $testEnabled, $names );
		throw new RuntimeException( 'failed to disable configs' );
	}
	
	echo "OK\n";
	
	#4. удаление: удаляем конфиги, после листинга выбираем enabled и сравниваем с оставшимися, проверям удаление символьных ссылок
	echo 'deleting...';
	$testDeleted = [ 'config01.conf', 'config03.conf' ];
	sort( $testDeleted );
	$testEnabled = array_diff( $testEnabled, $testDeleted ); // оставшиеся enabled
	sort( $testEnabled );
	
	foreach( $testDeleted as $name ) {
		if ( !$confd->Delete( $name ) ) {
			throw new RuntimeException( "failed to delete '$name' config" );
		}
	}
	
	clearstatcache( true );

	$names = EntriesToNames( EntriesEnabled( $confd->List( ) ) );
	if ( $testEnabled != $names ) {
		var_dump( $testEnabled, $names );
		throw new RuntimeException( 'failed to delete configs' );
	}
	
	foreach( $testDeleted as $config ) {
		if ( is_link( $dirEnabled.'/'.$config ) ) {
			throw new RuntimeException( "link for '$config' was not deleted\n" );
		}
	}
	
	echo "OK\n";
}
