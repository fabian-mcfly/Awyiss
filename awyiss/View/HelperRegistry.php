<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Core\App;
use Cake\View\HelperRegistry as BaseHelperRegistry;


/**
 * Helper Registry
 * Re-implemented to use \Awyiss\Core\App::className
 */
class HelperRegistry extends BaseHelperRegistry {
	/**
	 * Use \Awyiss\Core\App::className to resolve class names.
	 *
	 * @inheritDoc
	 */
	protected function _resolveClassName(string $class): ?string {
		/** @var class-string<\Cake\View\Helper>|null */
		return App::className($class, 'View/Helper', 'Helper');
	}
}
