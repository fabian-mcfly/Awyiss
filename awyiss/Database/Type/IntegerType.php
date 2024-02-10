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
	 * @param mixed $ax_value
	 * @param \Cake\Database\Driver $ao_driver
	 * @return int|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function toDatabase(mixed $ax_value, Driver $ao_driver): ?int {
		if ($ax_value === null || $ax_value === '') {
			return null;
		}

		$lx_value = $ax_value;

		if ($lx_value instanceof BackedEnum) {
			$lx_value = $lx_value->value;
		}

		$this->checkNumeric($lx_value);


		return (int)$lx_value;
	}
}
