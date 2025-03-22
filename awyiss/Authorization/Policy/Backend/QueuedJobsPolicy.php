<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Queue scope
 */
class QueuedJobsPolicy extends AbstractPolicy {
	protected static PermissionOptionCollection $permissionOptionCollection;
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

		$lo_permissionOptions->load('restartFailed', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissionOptions->load('delete', [
			'className' => SimplePermissionOption::class,
		]);

		return $lo_permissionOptions;
	}
}
