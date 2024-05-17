<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Awyiss\Authorization\Permission\PermissionCollection;


/**
 * Identity interface
 */
interface IdentityPermissionsInterface {
	/**
	 * Return the PermissionCollection that's saved for this identity
	 *
	 * @return PermissionCollection
	 */
	public function getPermissionCollection(): PermissionCollection;


	/**
	 * For a list of given identifiers, return true or false whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $identifier is used.
	 *
	 * @param string $scope
	 * @param array $additionalData
	 * @param array|string ...$identifier
	 * @return bool
	 * @throws \ReflectionException
	 * @see PermissionCollection::scopeIsAccessible
	 */
	public function scopeIsAccessible(string $scope, array $additionalData = [], string|array ...$identifier): bool;
}
