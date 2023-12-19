<?php

declare(strict_types=1);


namespace Awyiss\Authorization\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;


trait BasicCrudPermissionsTrait {
	protected static $lo_permissionCollection;


	public static function getPermissions (): ?PermissionCollection {
		if (static::$lo_permissionCollection) {
			return static::$lo_permissionCollection;
		}

		$lo_permissions = new PermissionCollection(substr(static::class, 0, -10));

		$lo_permissions->load('overview', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('add', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('edit', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('delete', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		static::$lo_permissionCollection = $lo_permissions;

		return $lo_permissions;
	}


	public static function isAccessible (): bool {
		if (!static::$lo_permissionCollection) {
			static::getPermissions();
		}

		return TRUE;
	}
}