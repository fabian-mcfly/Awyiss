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
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string $as_scope
	 * @param null|array $aa_additionalData
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 * @throws \ReflectionException
	 * @see PermissionCollection::scopeIsAccessible
	 */
	public function scopeIsAccessible(string $as_scope, ?array $aa_additionalData = NULL, string|array ...$ax_identifier);
}
