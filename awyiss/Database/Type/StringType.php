<?php declare(strict_types=1);


namespace Awyiss\Database\Type;


use BackedEnum;
use Cake\Database\Driver;
use Cake\Database\Type\StringType as BaseType;
use InvalidArgumentException;
use Stringable;


/**
 * String type converter.
 * Use to convert string data between PHP and the database types.
 */
class StringType extends BaseType {
	/**
	 * Convert string data into the database format.
	 *
	 * @param mixed $value The value to convert.
	 * @param \Cake\Database\Driver $driver The driver instance to convert with.
	 * @return string|null
	 */
	public function toDatabase(mixed $value, Driver $driver): ?string {
		if ($value === null || is_string($value)) {
			return $value;
		}

		if ($value instanceof Stringable) {
			return (string)$value;
		}

		if (is_scalar($value)) {
			return (string)$value;
		}

		if ($value instanceof BackedEnum && is_string($value->value)) {
			return $value->value;
		}

		throw new InvalidArgumentException(
			sprintf(
				'Cannot convert value `%s` of type `%s` to string',
				print_r($value, true),
				get_debug_type($value)
			)
		);
	}
}
