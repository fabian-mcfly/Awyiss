<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;


trait FullPermissionsTrait {
	protected static ?PermissionCollection $permissionCollection = NULL;
	protected static string $scope;


	public static function getScope (): string {
		if (static::$scope === NULL) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -6);
			static::$scope = \Cake\Utility\Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 */
	public static function getPermissions (): PermissionCollection {
		if (static::$permissionCollection === NULL) {
			static::$permissionCollection = new PermissionCollection(static::getScope());
		}

		return static::$permissionCollection;
	}
}