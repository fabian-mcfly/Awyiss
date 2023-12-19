<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;


trait BasicCrudPermissionsTrait {
	/**
	 * @throws \Exception
	 */
	protected static function loadPermissions (): PermissionCollection {
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