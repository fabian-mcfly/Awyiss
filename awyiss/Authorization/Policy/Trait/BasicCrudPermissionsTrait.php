<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


trait BasicCrudPermissionsTrait {
	protected static ?PermissionCollection $permissionCollection = NULL;
	protected static ?string $scope = NULL;


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
			static::$permissionCollection = static::_loadPermissions();
		}

		return static::$permissionCollection;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 */
	public static function getPermission (string $as_identifier): ?PermissionInterface {
		if (static::$permissionCollection === NULL) {
			static::$permissionCollection = static::_loadPermissions();
		}

		if (static::$permissionCollection->has($as_identifier)) {
			return static::$permissionCollection->get($as_identifier);
		}

		return NULL;
	}


	/**
	 * @throws \Exception
	 */
	private static function _loadPermissions (): ?PermissionCollection {
		$lo_permissions = new PermissionCollection(static::getScope());

		$lo_permissions->load('create', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('update', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('delete', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		return $lo_permissions;
	}
}