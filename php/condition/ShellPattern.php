<?php
namespace confdotd\condition;

use confdotd\Condition;
use confdotd\Entry;

/**
 * Паттерн как в командной строке
 */
class ShellPattern extends File {
	protected int $flags;
	protected string $pattern;
	
	public function __construct( string $pattern = '*.conf', int $flags = 0 ) {
		$this->pattern	= $pattern;
		$this->flags	= $flags;
	}
	
	/**
	 * Подпадает ли элемент директории под условие выборки
	 */
	public function Match( Entry $entry ) : bool {
		return parent::Match( $entry ) && fnmatch( $this->pattern, $entry->dir.'/'.$entry->name, $this->flags );
	}
}