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
	 * @param mixed $ax_value The value to convert.
	 * @param \Cake\Database\Driver $ao_driver The driver instance to convert with.
	 * @return string|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function toDatabase(mixed $ax_value, Driver $ao_driver): ?string {
		if ($ax_value === null || is_string($ax_value)) {
			return $ax_value;
		}

		if ($ax_value instanceof Stringable) {
			return (string)$ax_value;
		}

		if (is_scalar($ax_value)) {
			return (string)$ax_value;
		}

		if ($ax_value instanceof BackedEnum && is_string($ax_value->value)) {
			return $ax_value->value;
		}

		throw new InvalidArgumentException(
			sprintf(
				'Cannot convert value `%s` of type `%s` to string',
				print_r($ax_value, true),
				get_debug_type($ax_value)
			)
		);
	}
}
