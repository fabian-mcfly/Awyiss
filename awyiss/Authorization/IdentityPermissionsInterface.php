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
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 */
	public function getPermissionCollection (): PermissionCollection;
}
