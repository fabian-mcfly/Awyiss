<?php declare(strict_types=1);


namespace Awyiss\Database\Type;


use BackedEnum;
use Cake\Database\Driver;
use Cake\Database\Type\IntegerType as BaseIntegerType;


/**
 * Integer type converter.
 * Use to convert integer data between PHP and the database types.
 */
class IntegerType extends BaseIntegerType {
	/**
	 * @param mixed $value
	 * @param \Cake\Database\Driver $driver
	 * @return int|null
	 */
	public function toDatabase(mixed $value, Driver $driver): ?int {
		if ($value === null || $value === '') {
			return null;
		}

		if ($value instanceof BackedEnum) {
			$value = $value->value;
		}

		$this->checkNumeric($value);


		return (int)$value;
	}
}
