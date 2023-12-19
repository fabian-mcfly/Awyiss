<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Cake\Utility\Inflector;


/**
 * Classes that extend this one need to define
 * - `protected static PermissionOptionCollection $permissionOptionCollection;`
 * - `protected static string $scope;`
 */
abstract class AbstractPolicy implements PolicyInterface {
	use Trait\BasicCrudPermissionsTrait;


	/**
	 * @inheritDoc
	 */
	public static function getScope (): string {
		if ( ! isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -6);
			static::$scope = Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}


	/**
	 * @inheritDoc
	 *
	 * @throws \Exception
	 */
	public static function getPermissionOptions (): PermissionOptionCollection {
		if ( ! isset(static::$permissionOptionCollection)) {
			static::$permissionOptionCollection = static::loadPermissionOptions();
		}

		return static::$permissionOptionCollection;
	}


	/**
	 * @inheritDoc
	 *
	 * @throws \Exception
	 */
	public static function getPermissionOption (string $as_identifier): ?PermissionOptionInterface {
		if (!isset(static::$permissionOptionCollection)) {
			static::$permissionOptionCollection = static::loadPermissionOptions();
		}

		if (static::$permissionOptionCollection->has($as_identifier)) {
			return static::$permissionOptionCollection->get($as_identifier);
		}

		return NULL;
	}
}