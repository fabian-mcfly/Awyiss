<?php declare(strict_types=1);


namespace Awyiss\Validation;


use Cake\Validation\Validation as BaseValidation;


class Validation extends BaseValidation {
	/**
	 * Check if the given value is not a boolean.
	 * Useful in combination with isScalar() to disallow boolean values
	 * but allow other scalar types.
	 *
	 * @param mixed $check
	 * @return bool
	 */
	public static function notBoolean(mixed $check): bool {
		return !is_bool($check);
	}
}
