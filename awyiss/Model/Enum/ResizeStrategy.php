<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


/**
 * ResizeStrategy enum
 *
 * Used to define the strategy for resizing images
 */
enum ResizeStrategy: int {
	case Contain = 1;
	case Cover = 2;
	case Crop = 3;
	case Stretch = 4;
}
