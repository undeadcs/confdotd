<?php
namespace confdotd;

/**
 * Элемент директории конфигов
 */
class Entry {
	/**
	 * @var string имя без пути
	 */
	public string $name;
	
	/**
	 * @var string директория
	 */
	public string $dir;
	
	/**
	 * @var bool включен ли конфиг
	 */
	public bool $enabled;
	
	public function __construct( string $name, string $dir, bool $enabled = false ) {
		$this->name		= $name;
		$this->dir		= $dir;
		$this->enabled	= $enabled;
	}
}
