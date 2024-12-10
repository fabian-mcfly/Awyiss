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
		$lo_permissionOptions = new PermissionOptionCollection(static::getScope());

		$lo_permissionOptions->load('read', [
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
