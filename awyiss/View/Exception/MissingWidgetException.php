<?php declare(strict_types=1);


namespace Awyiss\View\Exception;


use Cake\View\Exception\MissingTemplateException;


/**
 * Used when an element file cannot be found.
 */
class MissingWidgetException extends MissingTemplateException {
	/**
	 * @var string
	 */
	protected string $type = 'Widget';
}
