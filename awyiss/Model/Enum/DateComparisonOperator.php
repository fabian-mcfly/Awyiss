<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * DateComparisonOperator Enum
 */
enum DateComparisonOperator: string {
	case SinceLastLogin = 'since_last_login';
	case Last24Hours = 'last_24_hours';
	case Today = 'today';
	case Yesterday = 'yesterday';
	case ThisWeek = 'this_week';
	case LastWeek = 'last_week';
	case ThisMonth = 'this_month';
	case LastMonth = 'last_month';
	case ThisYear = 'this_year';
	case LastYear = 'last_year';
}
