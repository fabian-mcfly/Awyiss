<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\SimplePermission;
use Awyiss\Authorization\Policy\AbstractPolicy;
use Awyiss\Authorization\Policy\Trait\BasicCrudPermissionsTrait;


class ContentTemplatesPolicy extends AbstractPolicy {
	use BasicCrudPermissionsTrait {
		loadPermissions as _loadPermissions;
	}


	protected static PermissionCollection $permissionCollection;
	protected static string $scope;


	protected static function loadPermissions (): ?PermissionCollection {
		$lo_permissions = static::_loadPermissions();

		$lo_permissions->load('configure', [
			'className' => SimplePermission::class,
		]);

		return $lo_permissions;
	}
}