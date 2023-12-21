<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;


/**
 * Interface with method signatures for regular policies
 *
 * @property PermissionOptionCollection $permissionOptionCollection
 */
interface PolicyInterface {
	/**
	 * Returns the scope for the policy
	 *
	 * @return string
	 */
	public static function getScope(): string;


	/**
	 * Returns the complete `PermissionOptionCollection`
	 *
	 * @return PermissionOptionCollection
	 */
	public static function getPermissionOptions(): PermissionOptionCollection;


	/**
	 * Returns one `PermissionOptionInterface` for the provided `$as_identifier`, otherwise null
	 *
	 * @param string $as_identifier
	 * @return PermissionOptionInterface|null
	 */
	public static function getPermissionOption(string $as_identifier): ?PermissionOptionInterface;
}
