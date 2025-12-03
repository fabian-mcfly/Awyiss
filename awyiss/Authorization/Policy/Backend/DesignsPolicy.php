<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Design scope
 */
class DesignsPolicy extends AbstractPolicy {
	/**
	 * @var PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * Creates a `PermissionOptionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 * If a config options file for the current scope is available, create another permission for the `configure`-identifier
	 *
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$permissionOptions = new PermissionOptionCollection(static::getScope());

		$permissionOptions->load('read', [
			'className' => SimplePermissionOption::class,
		]);

		$permissionOptions->load('load', [
			'className' => SimplePermissionOption::class,
		]);

		$permissionOptions->load('save', [
			'className' => SimplePermissionOption::class,
		]);

		$permissionOptions->load('use', [
			'className' => SimplePermissionOption::class,
		]);

		$permissionOptions->load('delete', [
			'className' => SimplePermissionOption::class,
		]);


		return $permissionOptions;
	}
}
