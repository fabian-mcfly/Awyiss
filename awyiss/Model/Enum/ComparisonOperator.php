<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * ComparisonOperator Enum
 */
enum ComparisonOperator: string {
	case Equal = '=';
	case NotEqual = '!=';
	case Contains = 'contains';
	case NotContains = 'notContains';
	case StartsWith = 'startsWith';
	case NotStartsWith = 'notStartsWith';
	case EndsWith = 'endsWith';
	case NotEndsWith = 'notEndsWith';
	case In = 'in';
	case NotIn = 'notIn';
	case LessThan = '<';
	case LessThanOrEqual = '<=';
	case GreaterThan = '>';
	case GreaterThanOrEqual = '>=';
	case Between = 'between';
	case NotBetween = 'notBetween';
	case LengthEqual = 'lengthEqual';
	case LengthNotEqual = 'lengthNotEqual';
	case ShorterThan = 'shorterThan';
	case ShorterThanOrEqual = 'shorterThanOrEqual';
	case LongerThan = 'longerThan';
	case LongerThanOrEqual = 'longerThanOrEqual';
	case Regexp = 'regexp';
}
