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

		$lx_value = $value;

		if ($lx_value instanceof BackedEnum) {
			$lx_value = $lx_value->value;
		}

		$this->checkNumeric($lx_value);


		return (int)$lx_value;
	}
}
