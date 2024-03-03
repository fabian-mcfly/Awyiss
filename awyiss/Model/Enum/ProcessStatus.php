<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * ProcessStatus enum
 *
 * Used to track the current status of image preview generation
 */
enum ProcessStatus: int {
	case Undefined = 0;
	case Success = 1;
	case InProgress = 2;
	case Fail = 3;
	case NotRequired = -1;
}
