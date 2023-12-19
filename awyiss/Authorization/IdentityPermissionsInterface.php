<?php declare(strict_types=1);


namespace Awyiss\Authorization;


/**
 * Identity interface
 */
interface IdentityPermissionsInterface {
	public function getAccess (): AccessCollection;
}
