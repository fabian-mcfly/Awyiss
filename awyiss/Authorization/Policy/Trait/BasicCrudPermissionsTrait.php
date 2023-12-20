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

		$lo_permissionOptions->load('read', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissionOptions->load('create', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissionOptions->load('update', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissionOptions->load('delete', [
			'className' => SimplePermissionOption::class,
		]);

		if (ConfigOptionsProvider::getConfigOptionsFile(static::getScope())) {
			$lo_permissionOptions->load('configure', [
				'className' => SimplePermissionOption::class,
			]);
		}


		return $lo_permissionOptions;
	}
}
