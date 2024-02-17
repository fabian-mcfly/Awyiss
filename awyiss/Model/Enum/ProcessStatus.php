<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


enum ProcessStatus: int {
	case Undefined = 0;
	case Success = 1;
	case InProgress = 2;
	case Fail = 3;
	case NotRequired = -1;
}
