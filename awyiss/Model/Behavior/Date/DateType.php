<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Date;


/**
 * Date fields usable in the `dates`-table
 */
enum DateType: string {
	case DATETIME = 'datetime';
	case DATE = 'date';
	case TIME = 'time';
}
