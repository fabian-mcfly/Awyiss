<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the PageRoles scope of the backend
 */
class PageRolesPolicy extends AbstractPolicy {
	protected static PermissionCollection $permissionCollection;
	protected static string $scope;
}