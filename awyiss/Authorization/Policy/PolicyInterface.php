<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


interface PolicyInterface {
	/**
	 * @return string
	 */
	public static function getScope (): string;


	/**
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 */
	public static function getPermissions (): PermissionCollection;


	/**
	 * @param string $as_identifier
	 *
	 * @return null|\Awyiss\Authorization\Permission\PermissionInterface
	 */
	public static function getPermission (string $as_identifier): ?PermissionInterface;
}