<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * ComparisonOperator Enum
 */
enum ComparisonOperator: string {
	case Equal = '=';
	case NotEqual = '!=';
	case Contains = 'contains';
	case NotContains = 'not_contains';
	case StartsWith = 'starts_with';
	case NotStartsWith = 'not_starts_with';
	case EndsWith = 'ends_with';
	case NotEndsWith = 'not_ends_with';
	case In = 'in';
	case NotIn = 'not_in';
	case LessThan = '<';
	case LessThanOrEqual = '<=';
	case GreaterThan = '>';
	case GreaterThanOrEqual = '>=';
	case Between = 'between';
	case NotBetween = 'not_between';
	case LengthEqual = 'length_equal';
	case LengthNotEqual = 'length_not_equal';
	case ShorterThan = 'shorter_than';
	case ShorterThanOrEqual = 'shorter_than_or_equal';
	case LongerThan = 'longer_than';
	case LongerThanOrEqual = 'longer_than_or_equal';
	case Regexp = 'regexp';
}
