<?php declare(strict_types=1);


namespace Awyiss\Event;


use Cake\Event\EventListenerInterface;


abstract class AbstractListener implements EventListenerInterface {
	protected static ?string $scope = NULL;


	public static function getScope (): string {
		if (static::$scope === NULL) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -8);
			static::$scope = \Cake\Utility\Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}
}