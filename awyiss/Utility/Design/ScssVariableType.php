<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


enum ScssVariableType {
	case Color;
	case FontName;
	case FontStack;
	case FontWeight;
	case Number;
	case String;
}
