<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


/**
 * Enum for the types of an SCSS variable.
 */
enum ScssVariableType {
	case Color;
	case FontName;
	case FontStack;
	case FontWeight;
	case Number;
	case String;
}
