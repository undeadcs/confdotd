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
			if ( ( $file != '.' ) && ( $file != '..' ) ) {
				$entry = new Entry( $file, $this->dir );
				
				if ( $this->condition->Match( $entry ) ) {
					$entry->enabled = $this->IsEnabled( $entry->name );
					$ret[ ] = $entry;
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * Добавление конфига
	 */
	public function Add( string $name ) : ?Entry {
		$filename = $this->dir.'/'.$name;
		
		if ( file_exists( $filename ) || touch( $filename ) ) { // файла нет, создадим пустой
			return new Entry( $name, $this->dir );
		}
		
		return null;
	}
	
	/**
	 * Удаление конфига
	 */
	public function Delete( string $name ) : bool {
		$linkPath = $this->dirEnabled.'/'.$name;
		
		if ( is_link( $linkPath ) && !unlink( $linkPath ) ) { // конфиг был ранее включен, убираем мусор
			return false; // не удалось удалить ссылку, не достаточно прав
		}
		
		$configPath = $this->dir.'/'.$name;
		
		return ( file_exists( $configPath ) && unlink( $configPath ) );
	}
	
	/**
	 * Проверка, что элемент включен
	 */
	public function IsEnabled( string $name ) : bool {
		$origPath = $this->dir.'/'.$name;
		$linkPath = $this->dirEnabled.'/'.$name;
		
		return ( file_exists( $origPath ) && is_link( $linkPath ) && ( realpath( $linkPath ) == $origPath ) );
	}

	/**
	 * Включить конфиг
	 */
	public function Enable( string $name ) : bool {
		$configPath = $this->dir.'/'.$name;
		$linkPath = $this->dirEnabled.'/'.$name;
		
		return !is_link( $linkPath ) && symlink( $configPath, $linkPath );
	}
	
	/**
	 * Отключить конфиг
	 */
	public function Disable( string $name ) : bool {
		$filename = $this->dirEnabled.'/'.$name;
		
		return is_link( $filename ) && unlink( $filename );
	}
}
