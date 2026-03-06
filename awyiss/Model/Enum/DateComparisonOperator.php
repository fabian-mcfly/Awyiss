<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * DateComparisonOperator Enum
 */
enum DateComparisonOperator: string {
	case SinceLastLogin = 'sinceLastLogin';
	case Last24Hours = 'last24Hours';
	case Today = 'today';
	case Yesterday = 'yesterday';
	case ThisWeek = 'thisWeek';
	case LastWeek = 'lastWeek';
	case ThisMonth = 'thisMonth';
	case LastMonth = 'lastMonth';
	case ThisYear = 'thisYear';
	case LastYear = 'lastYear';
}
