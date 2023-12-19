<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Languages scope of the backend
 */
class LanguagesPolicy extends AbstractPolicy {
	protected static PermissionOptionCollection $permissionOptionCollection;
	protected static string $scope;
}