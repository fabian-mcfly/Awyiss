<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


/**
 * ENUM for all available PermissionType.
 * A permission can define a preferred input to be used by the view.
 */
enum PermissionOptionType: string {
	case TYPE_CHECKBOX = 'checkbox';
	case TYPE_RADIO = 'radio';
	case TYPE_SELECT = 'select';
	case TYPE_MULTISELECT = 'select_multi';
}
