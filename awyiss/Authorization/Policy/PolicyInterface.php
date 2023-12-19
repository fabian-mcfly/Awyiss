<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


interface PolicyInterface {
	public static function getScope (): string;


	public static function getPermissions (): PermissionCollection;


	public static function getPermission (string $as_identifier): ?PermissionInterface;
}