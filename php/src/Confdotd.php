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
	 * Директория с конфигами
	 */
	protected string $dir;
	
	/**
	 * Директория с ссылками для включения
	 */
	protected string $dirEnabled;
	
	/**
	 * Конструктор
	 * 
	 * @param string $dir Директория с конфигами
	 * @param Condition $condition Условие выборки
	 * @param string $dirEnabled Директория с ссылками
	 */
	public function __construct( string $dir, Condition $condition, string $dirEnabled = '' ) {
		$this->dir			= $this->NormalizePath( $dir );
		$this->condition	= $condition;
		$this->dirEnabled	= ( $dirEnabled == '' ) ? '' : $this->NormalizePath( $dirEnabled );
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
	 * Проверка наличия конфига
	 */
	public function Exists( string $name ) : bool {
		return file_exists( $this->dir.'/'.$name );
	}
	
	/**
	 * Поиск конфига по имени
	 */
	public function Find( string $name ) : ?Entry {
		return $this->Exists( $name ) ? new Entry( $name, $this->dir ) : null;
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
		
		return file_exists( $configPath ) && unlink( $configPath );
	}
	
	/**
	 * Проверка, что элемент включен
	 */
	public function IsEnabled( string $name ) : bool {
		if ( $this->dirEnabled == '' ) {
			return true;
		}
		
		$configPath = $this->dir.'/'.$name;
		$linkPath = $this->dirEnabled.'/'.$name;
		
		return file_exists( $configPath ) && is_link( $linkPath ) && ( realpath( $linkPath ) == $configPath );
	}

	/**
	 * Включить конфиг
	 */
	public function Enable( string $name ) : bool {
		if ( $this->dirEnabled == '' ) {
			return true;
		}
		
		$configPath = $this->dir.'/'.$name;
		$linkPath = $this->dirEnabled.'/'.$name;
		
		return file_exists( $configPath ) && !is_link( $linkPath ) && symlink( $configPath, $linkPath );
	}
	
	/**
	 * Отключить конфиг
	 */
	public function Disable( string $name ) : bool {
		if ( $this->dirEnabled == '' ) {
			return false;
		}
		
		$configPath = $this->dir.'/'.$name;
		$linkPath = $this->dirEnabled.'/'.$name;
		
		return file_exists( $configPath ) && is_link( $linkPath ) && unlink( $linkPath );
	}
}
