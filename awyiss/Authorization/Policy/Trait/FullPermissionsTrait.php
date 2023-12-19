<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Trait;


use Awyiss\Authorization\Permission\PermissionCollection;


trait FullPermissionsTrait {
	protected static $lo_permissionCollection;
	protected static $ls_scope;


	public static function getScope (): string {
		if (static::$ls_scope === NULL) {
			$la_parts = explode('\\', static::class);
			static::$ls_scope = array_pop($la_parts);
			static::$ls_scope = substr(static::$ls_scope, 0, -6);
			static::$ls_scope = \Cake\Utility\Inflector::underscore(static::$ls_scope);
		}

		return static::$ls_scope;
	}


	public static function getPermissions (): PermissionCollection {
		if (static::$lo_permissionCollection === NULL) {
			static::$lo_permissionCollection = new PermissionCollection(static::getScope());
		}

		return static::$lo_permissionCollection;
	}
}