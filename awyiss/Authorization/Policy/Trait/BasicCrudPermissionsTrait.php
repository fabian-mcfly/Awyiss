<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\SimplePermission;
use Awyiss\Configuration\ConfigOptionsProvider;


/**
 * This Trait offers simple CRUD permissions
 */
trait BasicCrudPermissionsTrait {
	/**
	 * Creates a `PermissionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * If a config options file for the current scope is available, create another permission for the `configure`-identifier
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissions (): PermissionCollection {
		$lo_permissions = new PermissionCollection(static::getScope());

		$lo_permissions->load('read', [
			'className' => SimplePermission::class,
		]);

		$lo_permissions->load('create', [
			'className' => SimplePermission::class,
		]);

		$lo_permissions->load('update', [
			'className' => SimplePermission::class,
		]);

		$lo_permissions->load('delete', [
			'className' => SimplePermission::class,
		]);

		if (ConfigOptionsProvider::getConfigurationFile(static::getScope())) {
			$lo_permissions->load('configure', [
				'className' => SimplePermission::class,
			]);
		}

		return $lo_permissions;
	}
}