<?php declare(strict_types=1);


namespace Awyiss\Authorization;


/**
 * Identity interface
 */
interface IdentityPermissionsInterface {
	/**
	 * Return the AccessCollection that's saved for this identity
	 *
	 * @return \Awyiss\Authorization\AccessCollection
	 */
	public function getAccess (): AccessCollection;
}
