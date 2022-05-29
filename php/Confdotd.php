<?php
namespace confdotd;

/**
 * Класс для работы с директорией конфигов (conf.d)
 */
class Confdotd {
	/**
	 * Условие выборки
	 */
	protected Condition $condition;
	
	/**
	 * Директория в файловой системе
	 */
	protected string $dir;
	
	/**
	 * Директория для вкл/выкл ссылок
	 */
	protected string $dirEnabled;
	
	public function __construct( string $dir, Condition $condition, string $dirEnabled = '' ) {
		$this->dir			= $this->NormalizePath( $dir );
		$this->condition	= $condition;
		$this->dirEnabled	= ( $dirEnabled == '' ) ? $this->dir.'/enabled' : $this->NormalizePath( $dirEnabled );
	}
	
	/**
	 * Нормализация пути
	 * 1. исключение повторных слэшэй
	 * 2. исключение слэша в конце
	 */
	protected function NormalizePath( string $dir ) : string {
		return preg_replace( [ '/\/+/', '/\/$/' ], [ '/', '' ], $dir );
	}
	
	/**
	 * Получение списка конфигов
	 */
	public function List( ) : array {
		$ret = [ ];
		$entries = scandir( $this->dir );
		foreach( $entries as $file ) {
			$entry = new Entry( $file, $this->dir );
			
			if ( ( $file != '.' ) && ( $file != '..' ) && $this->condition->Match( $entry ) ) {
				$entry->enabled = $this->IsEnabled( $entry );
				
				$ret[ ] = $entry;
			}
		}
		
		return $ret;
	}
	
	/**
	 * Проверка, что элемент принадлежит той же директории
	 */
	protected function CheckDir( Entry $entry ) {
		if ( $entry->dir !== $this->dir ) { // элемент не принадлежит этой директории
			throw new \RuntimeException( 'Invalid entry dir' );
		}
	}
	
	/**
	 * Добавление конфига
	 */
	public function Add( string $name ) : ?Entry {
		$filename = $this->dir.'/'.$name;
		if ( !file_exists( $filename ) && ( $file = fopen( $filename, 'w' ) ) ) { // файла нет, создадим пустой
			fclose( $file );
			
			return new Entry( $name, $this->dir );
		}
		
		return null;
	}
	
	/**
	 * Удаление конфига
	 */
	public function Delete( Entry $entry ) : bool {
		$this->CheckDir( $entry );
		
		$linkPath = $this->dirEnabled.'/'.$entry->name;
		
		if ( is_link( $linkPath ) && !unlink( $linkPath ) ) { // конфиг был ранее включен, убираем мусор
			return false; // не удалось удалить ссылку, не достаточно прав
		}
		
		$configPath = $this->dir.'/'.$entry->name;
		
		return ( file_exists( $configPath ) && unlink( $configPath ) );
	}
	
	/**
	 * Проверка, что элемент включен
	 */
	public function IsEnabled( Entry $entry ) : bool {
		$this->CheckDir( $entry );
		
		$origPath = $this->dir.'/'.$entry->name;
		$linkPath = $this->dirEnabled.'/'.$entry->name;
		
		return ( file_exists( $linkPath ) && is_link( $linkPath ) && ( realpath( $linkPath ) == $origPath ) );
	}

	/**
	 * Включить конфиг
	 */
	public function Enable( Entry $entry ) : bool {
		$this->CheckDir( $entry );
		
		$configPath = $this->dir.'/'.$entry->name;
		
		if ( !file_exists( $configPath ) ) {
			throw new \RuntimeException( 'Entry not found' );
		}
		
		$linkPath = $this->dirEnabled.'/'.$entry->name;
		
		if ( !file_exists( $linkPath ) && symlink( $configPath, $linkPath ) ) {
			$entry->enabled = true;
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Отключить конфиг
	 */
	public function Disable( Entry $entry ) : bool {
		$this->CheckDir( $entry );
		
		$filename = $this->dirEnabled.'/'.$entry->name;
		if ( file_exists( $filename ) && unlink( $filename ) ) {
			$entry->enabled = false;
			
			return true;
		}
		
		return false;
	}
}
