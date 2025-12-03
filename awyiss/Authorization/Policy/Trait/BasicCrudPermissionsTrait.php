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
		$permissionOptions = new PermissionOptionCollection(static::getScope());

		$permissionOptions->add('read', SimplePermissionOption::class);

		$permissionOptions->add('create', SimplePermissionOption::class);

		$permissionOptions->add('update', SimplePermissionOption::class);

		$permissionOptions->add('delete', SimplePermissionOption::class);

		if (ConfigOptionsProvider::getConfigOptionsFile(static::getScope())) {
			$permissionOptions->add('configure', SimplePermissionOption::class);
		}

		return $permissionOptions;
	}
}
