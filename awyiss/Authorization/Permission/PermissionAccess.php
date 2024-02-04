<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


/**
 * Enum with all possible permission types.
 */
enum PermissionAccess: int {
	case Granted = 1;
	case Denied = 0;
}
