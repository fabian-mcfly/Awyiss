<?php

declare(strict_types=1);


namespace Awyiss\Authorization\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;


trait FullPermissionsTrait {
	public static function getPermissions (): ?PermissionCollection {
		return null;
	}


	public static function isAccessible (): bool {
		return TRUE;
	}
}