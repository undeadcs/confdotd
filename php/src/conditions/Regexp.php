<?php
namespace confdotd\conditions;

use confdotd\Entry;

/**
 * Регулярное выражение
 */
class Regexp extends File {
	/**
	 * Регулярное выражение
	 */
	protected string $pattern;
	
	/**
	 * Конструктор
	 * 
	 * @param string $pattern регулярное выражение
	 */
	public function __construct( string $pattern = '/\.conf$/' ) {
		$this->pattern = $pattern;
	}
	
	/**
	 * Подпадает ли элемент директории под условие выборки
	 * 
	 * @param Entry $entry элемент директории конфигов
	 */
	public function Match( Entry $entry ) : bool {
		return parent::Match( $entry ) && preg_match( $this->pattern, $entry->dir.'/'.$entry->name );
	}
}
