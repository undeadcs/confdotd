<?php
namespace confdotd\condition;

use confdotd\Condition;
use confdotd\Entry;

/**
 * Регулярное выражение
 */
class Regexp extends File {
	protected string $pattern;
	
	public function __construct( string $pattern = '/\.conf$/' ) {
		$this->pattern = $pattern;
	}
	
	/**
	 * Подпадает ли элемент директории под условие выборки
	 */
	public function Match( Entry $entry ) : bool {
		return parent::Match( $entry ) && preg_match( $this->pattern, $entry->dir.'/'.$entry->name );
	}
}
