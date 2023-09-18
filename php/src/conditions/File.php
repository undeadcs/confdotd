<?php
namespace confdotd\conditions;

use confdotd\Condition;
use confdotd\Entry;

/**
 * Просто файл
 */
class File implements Condition {
	/**
	 * Подпадает ли элемент директории под условие выборки
	 * 
	 * @param Entry $entry элемент директории конфигов
	 */
	public function Match( Entry $entry ) : bool {
		return is_file( $entry->dir.'/'.$entry->name ); 
	}
}
