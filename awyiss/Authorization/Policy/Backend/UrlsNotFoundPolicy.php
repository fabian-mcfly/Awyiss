<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\AbstractPolicy;
use Awyiss\Configuration\ConfigOptionsProvider;


/**
 * Permission for the UrlsNotFound scope
 */
class UrlsNotFoundPolicy extends AbstractPolicy {
	/**
	 * @var PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * Creates a `PermissionOptionCollection` and `SimplePermission`
	 * for the identifiers 'read' and 'delete'.
	 *
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$permissionOptions = new PermissionOptionCollection(static::getScope());

		$permissionOptions->load('read', [
			'className' => SimplePermissionOption::class,
		]);

		$permissionOptions->load('delete', [
			'className' => SimplePermissionOption::class,
		]);

		if (ConfigOptionsProvider::getConfigOptionsFile(static::getScope())) {
			$permissionOptions->load('configure', [
				'className' => SimplePermissionOption::class,
			]);
		}

		return $permissionOptions;
	}
}
