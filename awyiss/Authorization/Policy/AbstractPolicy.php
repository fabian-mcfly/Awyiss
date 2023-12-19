<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


/**
 * Classes that extend this one need to define
 *		protected static PermissionCollection $permissionCollection;
 * 		protected static string $scope;
 */
abstract class AbstractPolicy implements PolicyInterface {
	public static function getScope (): string {
		if (!isset(static::$scope)) {
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
		if (!isset(static::$permissionCollection)) {
			static::$permissionCollection = static::loadPermissions();
		}

		return static::$permissionCollection;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 */
	public static function getPermission (string $as_identifier): ?PermissionInterface {
		if (!isset(static::$permissionCollection)) {
			static::$permissionCollection = static::loadPermissions();
		}

		if (static::$permissionCollection->has($as_identifier)) {
			return static::$permissionCollection->get($as_identifier);
		}

		return NULL;
	}
}