<?php
namespace confdotd\conditions;

use confdotd\Entry;

/**
 * Паттерн как в командной строке
 */
class ShellPattern extends File {
	/**
	 * Флаги функции fnmatch
	 */
	protected int $flags;
	
	/**
	 * Шаблон для шелла
	 */
	protected string $pattern;
	
	/**
	 * Конструктор
	 * 
	 * @param string $pattern Шаблон для шелла
	 * @param int $flags Флаги функции fnmatch
	 */
	public function __construct( string $pattern = '*.conf', int $flags = 0 ) {
		$this->pattern	= $pattern;
		$this->flags	= $flags;
	}
	
	/**
	 * Подпадает ли элемент директории под условие выборки
	 * 
	 * @param Entry $entry элемент директории конфигов
	 */
	public function Match( Entry $entry ) : bool {
		return parent::Match( $entry ) && fnmatch( $this->pattern, $entry->dir.'/'.$entry->name, $this->flags );
	}
}