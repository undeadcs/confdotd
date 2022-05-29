<?php
namespace confdotd\condition;

use confdotd\Condition;
use confdotd\Entry;

/**
 * Просто файл
 */
class File implements Condition {
	/**
	 * Подпадает ли элемент директории под условие выборки
	 */
	public function Match( Entry $entry ) : bool {
		return is_file( $entry->dir.'/'.$entry->name ); 
	}
}
