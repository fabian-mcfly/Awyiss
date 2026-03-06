<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * CustomerGroupAccessType enum
 * Defines the access types for customer group access settings.
 * Controls whether an entity is available to all groups, hidden on login, or limited to specific groups.
 */
enum CustomerGroupAccessType: string {
	/**
	 * Entity is available to all customer groups
	 */
	case AllGroups = 'allGroups';
	/**
	 * Entity is only visible to non-logged-in users
	 */
	case HideOnLogin = 'hideOnLogin';
	/**
	 * Entity is only visible to assigned customer groups
	 */
	case SpecificGroups = 'specificGroups';
}
