<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


/**
 * Enum with all possible permission types.
 */
enum PermissionAccess: int {
	case OPTION_GRANTED = 1;
	case OPTION_DENIED = 0;
}
