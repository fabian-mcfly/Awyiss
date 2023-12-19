<?php declare(strict_types=1);


namespace Awyiss\Event;


trait EventListenerTrait {
	protected static string $scope;


	public static function getScope (): string {
		if (!isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -8);
			static::$scope = \Cake\Utility\Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}
}