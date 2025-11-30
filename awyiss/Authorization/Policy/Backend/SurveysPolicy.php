<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Surveys scope
 */
class SurveysPolicy extends AbstractPolicy {
	/**
	 * @var PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$lo_permissionOptions = parent::loadPermissionOptions();

		$lo_permissionOptions->load('analyze', [
			'className' => SimplePermissionOption::class,
		]);

		return $lo_permissionOptions;
	}
}
