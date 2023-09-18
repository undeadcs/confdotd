<?php
namespace confdotd\tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use confdotd\Confdotd;
use confdotd\Condition;
use confdotd\conditions\File;
use confdotd\conditions\Regexp;
use confdotd\conditions\ShellPattern;

/**
 * Общие тесты
 */
class CommonTests extends TestCase {
	const
		FETCH_ENABLED	= 1 << 0,
		FETCH_DISABLED	= 1 << 1;
	
	protected string $tmpdir;
	
	public function setUp( ) : void {
		$this->tmpdir = tempnam( __DIR__, 'tst' );
		
		if ( !$this->tmpdir ) {
			throw \RuntimeException( 'Failed to create temporary directory' );
		}
		
		unlink( $this->tmpdir );
		mkdir( $this->tmpdir );
		mkdir( $this->tmpdir.'/available' );
		mkdir( $this->tmpdir.'/enabled' );
	}
	
	protected function RemoveEntries( string $dir ) : void {
		$entries = scandir( $dir, SCANDIR_SORT_ASCENDING );
		foreach( $entries as $entry ) {
			if ( ( $entry != '.' ) && ( $entry != '..' ) ) {
				if ( is_dir( $dir.'/'.$entry ) ) {
					rmdir( $dir.'/'.$entry );
				} else {
					unlink( $dir.'/'.$entry );
				}
			}
		}
	}
	
	public function tearDown( ) : void {
		$this->RemoveEntries( $this->tmpdir.'/available' );
		$this->RemoveEntries( $this->tmpdir.'/enabled' );
		
		rmdir( $this->tmpdir.'/available' );
		rmdir( $this->tmpdir.'/enabled' );
		rmdir( $this->tmpdir );
	}
	
	public static function configsProvider( ) : array {
		$all = [
			'conf01.conf',
			'conf02.conf',
			'conf03.conf',
			'conf04.conf',
			'conf05.conf',
			'conf06.conf',
			'conf07.conf'
		];
		$enabled = [ 'conf02.conf', 'conf04.conf', 'conf05.conf' ];
		$disabled = [ 'conf01.conf', 'conf03.conf', 'conf06.conf', 'conf07.conf' ];
		$deleted = [ 'conf02.conf', 'conf07.conf'  ]; // удаляем один включенный, другой выключенный
		
		return [
			[ new File, $all, $enabled, $disabled, $deleted ],
			[ new Regexp( '/\.conf$/' ), $all, $enabled, $disabled, $deleted ],
			[ new ShellPattern( '*.conf' ), $all, $enabled, $disabled, $deleted ]
		];
	}
	
	protected function GetActualNames( Confdotd $confd, int $flags = self::FETCH_ENABLED | self::FETCH_DISABLED ) : array {
		$entries = $confd->List( );
		$actualNames = [ ];
		
		foreach( $entries as $entry ) {
			if ( ( $entry->enabled && ( $flags & self::FETCH_ENABLED ) ) ||
				( !$entry->enabled && ( $flags & self::FETCH_DISABLED ) )
			) {
				$actualNames[ ] = $entry->name;
			}
		}
		
		sort( $actualNames );
		
		return $actualNames;
	}
	
	/**
	 * Проверка всех конфигов с их добавлением и включением
	 */
	protected function CheckAll( Confdotd $confd, array $all, bool $pair = true ) : void {
		foreach( $all as $name ) {
			$this->assertNotNull( $confd->Add( $name ) );
			$this->assertTrue( is_file( $this->tmpdir.'/available/'.$name ) ); // должен быть создан файл
			$this->assertTrue( $confd->Enable( $name ) ); // для одной директории ничего по сути не делает
			
			if ( $pair ) {
				$this->assertTrue( is_link( $this->tmpdir.'/enabled/'.$name ) ); // должна быть создана ссылка
				$this->assertEquals( $this->tmpdir.'/available/'.$name, realpath( $this->tmpdir.'/enabled/'.$name ) ); // ссылка должна вести на файл конфига
			} else {
				$this->assertTrue( $confd->IsEnabled( $name ) ); // всегда включен
				$this->assertFalse( $confd->Disable( $name ) ); // невозможно отключить, только удалить
			}
		}
	}
	
	/**
	 * Проверка всего списка
	 */
	protected function CheckListAll( Confdotd $confd, array $all ) : void {
		mkdir( $this->tmpdir.'/available/invalidentry.conf' ); // invalidentry.conf - не должно попадать в список, т.к. директория
		$actualNames = $this->GetActualNames( $confd );
		$this->assertEquals( $all, $actualNames );
	}
	
	/**
	 * Проверка удаления
	 */
	protected function CheckDelete( Confdotd $confd, array $deleted, array $enabled = [ ] ) : void {
		foreach( $deleted as $name ) {
			$this->assertTrue( $confd->Delete( $name ) );
			$this->assertFalse( is_file( $this->tmpdir.'/available/'.$name ) ); // файл должен быть удален
			
			if ( in_array( $name, $enabled ) ) { // при удалении включенного конфига его ссылка должна быть удалена
				$this->assertFalse( is_link( $this->tmpdir.'/enabled/'.$name ) );
			}
		}
	}
	
	/**
	 * Общее тестирование
	 */
	#[ DataProvider( 'configsProvider' ) ]
	public function testConfigs( Condition $condition, array $all, array $enabled, array $disabled, array $deleted ) : void {
		$confd = new Confdotd( $this->tmpdir.'/available', $condition, $this->tmpdir.'/enabled' );
		
		$this->CheckAll( $confd, $all );
		
		foreach( $disabled as $name ) {
			$this->assertTrue( $confd->Disable( $name ) );
			$this->assertFalse( $confd->IsEnabled( $name ) );
			$this->assertFalse( is_link( $this->tmpdir.'/enabled/'.$name ) ); // ссылка должна быть удалена
		}
		
		foreach( $enabled as $name ) {
			$this->assertTrue( $confd->IsEnabled( $name ) );
		}
		
		$this->CheckListAll( $confd, $all );
		
		$enabledNames = $this->GetActualNames( $confd, self::FETCH_ENABLED );
		$this->assertEquals( $enabled, $enabledNames );
		
		$disabledNames = $this->GetActualNames( $confd, self::FETCH_DISABLED );
		$this->assertEquals( $disabled, $disabledNames );
		
		$this->CheckDelete( $confd, $deleted, $enabled );
	}
	
	/**
	 * Тестирование пир работы только с одной директорией
	 */
	#[ DataProvider( 'configsProvider' ) ]
	public function testSingleDir( Condition $condition, array $all, array $enabled, array $disabled, array $deleted ) : void {
		$confd = new Confdotd( $this->tmpdir.'/available', $condition );
		
		$this->CheckAll( $confd, $all, false );
		$this->CheckListAll( $confd, $all );
		$this->CheckDelete( $confd, $deleted );
	}
}
