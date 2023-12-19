<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Usergroups scope of the backend
 */
class UsergroupsPolicy extends AbstractPolicy {
	protected static PermissionOptionCollection $permissionOptionCollection;
	protected static string $scope;
}