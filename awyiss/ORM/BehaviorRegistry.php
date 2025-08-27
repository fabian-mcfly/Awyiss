<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\Core\App;
use Cake\ORM\BehaviorRegistry as BaseBehaviorRegistry;


/**
 * Custom BehaviorRegistry
 */
class BehaviorRegistry extends BaseBehaviorRegistry {
	/**
	 * Reimplemented 1:1 use \Awyiss\Core\App
	 * instead of \Cake\Core\App for `App::className` to allow custom classes
	 * overriding behaviors
	 *
	 * @inheritDoc
	 */
	public static function className(string $class): ?string {
		return App::className($class, 'Model/Behavior', 'Behavior') ?: App::className($class, 'ORM/Behavior', 'Behavior');
	}
}
