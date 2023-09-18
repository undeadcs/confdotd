<?php
namespace confdotd;

/**
 * Элемент директории конфигов
 */
class Entry {
	/**
	 * Имя без пути
	 */
	public string $name;
	
	/**
	 * Путь директории
	 */
	public string $dir;
	
	/**
	 * Включен ли конфиг
	 */
	public bool $enabled;
	
	/**
	 * Конструктор
	 * 
	 * @param string $name Имя без пути
	 * @param string $dir Путь директории
	 * @param bool $enabled Включен ли конфиг
	 */
	public function __construct( string $name, string $dir, bool $enabled = false ) {
		$this->name		= $name;
		$this->dir		= $dir;
		$this->enabled	= $enabled;
	}
}
