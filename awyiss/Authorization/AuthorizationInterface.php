<?php

declare(strict_types=1);


namespace Awyiss\Authorization;


use Awyiss\Authorization\Permission\PermissionCollection;


interface AuthorizationInterface {
	public static function getPermissions (): ?PermissionCollection;
	public static function isAccessible (): bool;
}