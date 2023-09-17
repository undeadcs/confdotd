<?php
namespace confdotd;

/**
 * Условие выбора конфига
 */
interface Condition {
	/**
	 * Подпадает ли элемент директории под условие выборки
	 * 
	 * @param Entry $entry элемент директории конфигов
	 */
	public function Match( Entry $entry ) : bool;
}
