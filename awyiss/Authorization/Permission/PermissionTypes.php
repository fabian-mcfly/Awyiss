<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


/**
 * ENUM for all available PermissionTypes.
 * A permission can define a preferred input to be used by the view.
 */
enum PermissionTypes: string {
	case TYPE_CHECKBOX = 'checkbox';
	case TYPE_RADIO = 'radio';
	case TYPE_SELECT = 'select';
	case TYPE_MULTISELECT = 'select_multi';
}
