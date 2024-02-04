<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


/**
 * ENUM for all available PermissionType.
 * A permission can define a preferred input to be used by the view.
 */
enum PermissionOptionType: string {
	case Checkbox = 'checkbox';
	case Radio = 'radio';
	case Select = 'select';
	case Multiselect = 'select_multi';
}
