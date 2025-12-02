<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Pages scope of the backend
 */
class PagesPolicy extends AbstractPolicy {
	/**
	 * @var \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$permissionOptions = parent::loadPermissionOptions();

		$permissionOptions->load('contents', [
			'className' => SimplePermissionOption::class,
		]);


		return $permissionOptions;
	}
}
