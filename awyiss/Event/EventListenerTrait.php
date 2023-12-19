<?php declare(strict_types=1);


namespace Awyiss\Event;


use Cake\Utility\Inflector;


/**
 * Trait for reusable EventListener methods
 */
trait EventListenerTrait {
	/**
	 * Return the scope of the EventListener instance
	 *
	 * @return string
	 */
	public static function getScope (): string {
		if (!isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -8);
			static::$scope = Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}
}