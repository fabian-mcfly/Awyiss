<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


use Awyiss\Utility\Inflector;
use InvalidArgumentException;


/**
 * ResizeStrategy enum
 * Used to define the strategy for resizing images
 */
enum ResizeStrategy: int {
	case Contain = 1;
	case Cover = 2;
	case Crop = 3;
	case Stretch = 4;


	/**
	 * Normalize a resize strategy value
	 *
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $value
	 * @return static
	 */
	public static function normalize(self|string|int $value): self {
		if ($value instanceof self) {
			return $value;
		}

		// If the resize strategy is a string, check if it is a valid enum case (name, not value)
		if (is_string($value)) {
			$ls_resizeStrategy = self::class . '::' . Inflector::camelize($value);

			if (!defined($ls_resizeStrategy)) {
				throw new InvalidArgumentException('Invalid resize strategy: ' . $value);
			}

			return constant($ls_resizeStrategy);
		}

		return self::from($value);
	}
}
