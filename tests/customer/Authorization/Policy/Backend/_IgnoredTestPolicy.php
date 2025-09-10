<?php declare(strict_types=1);


namespace Customer\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission will be ignored due to
 * the underscore in the file name
 */
class _IgnoredTestPolicy extends AbstractPolicy { // phpcs:ignore
	/**
	 * @var PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;
}
