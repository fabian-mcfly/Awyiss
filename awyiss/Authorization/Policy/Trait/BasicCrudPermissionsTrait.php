<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Configuration\ConfigOptionsProvider;


/**
 * This Trait offers simple CRUD permissions
 */
trait BasicCrudPermissionsTrait {
	/**
	 * Creates a `PermissionOptionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * If a config options file for the current scope is available, create another permission for the `configure`-identifier
	 *
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$lo_permissionOptions = new PermissionOptionCollection(static::getScope());

		$lo_permissionOptions->add('read', SimplePermissionOption::class);

		$lo_permissionOptions->add('create', SimplePermissionOption::class);

		$lo_permissionOptions->add('update', SimplePermissionOption::class);

		$lo_permissionOptions->add('delete', SimplePermissionOption::class);

		if (ConfigOptionsProvider::getConfigOptionsFile(static::getScope())) {
			$lo_permissionOptions->add('configure', SimplePermissionOption::class);
		}

		return $lo_permissionOptions;
	}
}
