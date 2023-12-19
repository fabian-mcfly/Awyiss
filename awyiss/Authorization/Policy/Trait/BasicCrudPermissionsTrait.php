<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


trait BasicCrudPermissionsTrait {
	protected static ?PermissionCollection $lo_permissionCollection = NULL;
	protected static $ls_scope;


	public static function getScope (): string {
		if (static::$ls_scope === NULL) {
			$la_parts = explode('\\', static::class);
			static::$ls_scope = array_pop($la_parts);
			static::$ls_scope = substr(static::$ls_scope, 0, -6);
			static::$ls_scope = \Cake\Utility\Inflector::underscore(static::$ls_scope);
		}

		return static::$ls_scope;
	}


	public static function getPermissions (): PermissionCollection {
		if (static::$lo_permissionCollection === NULL) {
			static::$lo_permissionCollection = static::_loadPermissions();
		}

		return static::$lo_permissionCollection;
	}


	public static function getPermission (string $as_identifier): ?PermissionInterface {
		if (static::$lo_permissionCollection === NULL) {
			static::$lo_permissionCollection = static::_loadPermissions();
		}

		if (static::$lo_permissionCollection->has($as_identifier)) {
			return static::$lo_permissionCollection->get($as_identifier);
		}

		return NULL;
	}


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