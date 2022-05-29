<?php
namespace confdotd;

/**
 * Условие выбора конфига
 */
interface Condition {
	/**
	 * Подпадает ли элемент директории под условие выборки
	 */
	public function Match( Entry $entry ) : bool;
}
