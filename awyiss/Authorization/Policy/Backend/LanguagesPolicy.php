<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;


class LanguagesPolicy extends AbstractPolicy {
	use \Awyiss\Authorization\Policy\Trait\BasicCrudPermissionsTrait;


	protected static PermissionCollection $permissionCollection;
	protected static string $scope;
}