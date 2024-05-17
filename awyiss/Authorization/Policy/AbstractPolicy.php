<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;


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
	public static function getScope(): string {
		if (!isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -6);
			static::$scope = AuthorizationService::sanitizeScope(static::$scope);
		}


		return static::$scope;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function getPermissionOptions(): PermissionOptionCollection {
		if (!isset(static::$permissionOptionCollection)) {
			static::$permissionOptionCollection = static::loadPermissionOptions();
		}


		return static::$permissionOptionCollection;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function getPermissionOption(string $identifier): ?PermissionOptionInterface {
		if (!isset(static::$permissionOptionCollection)) {
			static::$permissionOptionCollection = static::loadPermissionOptions();
		}

		$ls_identifier = AuthorizationService::sanitizeIdentifier($identifier);

		if (static::$permissionOptionCollection->has($ls_identifier)) {
			return static::$permissionOptionCollection->get($ls_identifier);
		}


		return null;
	}
}
