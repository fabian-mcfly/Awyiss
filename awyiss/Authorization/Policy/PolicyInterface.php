<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


/**
 * Interface with method signatures for regular policies
 */
interface PolicyInterface {
	/**
	 * Returns the scope for the policy
	 *
	 * @return string
	 */
	public static function getScope (): string;


	/**
	 * Returns the complete `PermissionCollection`
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 */
	public static function getPermissions (): PermissionCollection;


	/**
	 * Returns one `PermissionInterface` for the provided `$as_identifier`, otherwise NULL
	 *
	 * @param string $as_identifier
	 *
	 * @return NULL|\Awyiss\Authorization\Permission\PermissionInterface
	 */
	public static function getPermission (string $as_identifier): ?PermissionInterface;
}