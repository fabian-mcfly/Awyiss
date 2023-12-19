<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Exception;
use TypeError;


/**
 * Valid data types for values used in `ConfigOption`
 */
enum ConfigOptionTypes: string {
	case TYPE_JSON = 'json';
	case TYPE_BOOL = 'bool';
	case TYPE_STRING = 'string';
	case TYPE_INTEGER = 'integer';


	/**
	 * Validates the provided value to be of the correct type for the enum.
	 *
	 * @param mixed $value
	 * @param bool $ab_isNullable
	 *
	 * @return bool|string
	 */
	public function validateType (mixed $value, bool $ab_isNullable = FALSE): bool|string {
		switch ($this) {
			case self::TYPE_INTEGER:
				return is_int($value) || (is_string($value) && ctype_digit($value));

			case self::TYPE_BOOL:
				/*
				 * Type bool consideres everything boolish to be a valid value
				 * since the \Model\Entity\Configuration saves everything as a string
				 * and does not differentiate between the type here.
				 */
				 return is_bool($value) || in_array($value, [1, 0, '1', '0'], TRUE);

			case self::TYPE_JSON:
				try {
					$la_value = json_decode($value, TRUE, 16, JSON_THROW_ON_ERROR);

					if (empty($la_value) && ! $ab_isNullable) {
						return __('validation::error_option_not_nullable');
					}

					return TRUE;
				}
				catch (Exception|TypeError) {
					return FALSE;
				}

			case self::TYPE_STRING:
				return is_string($value);
		}

		return FALSE;
	}


	/**
	 * Casts the provided `$ax_value` to the correct type
	 *
	 * @param mixed $ax_value
	 *
	 * @return mixed
	 */
	public function typeCast (mixed $ax_value): mixed {
		return match ($this) {
			self::TYPE_INTEGER => intval($ax_value),
			self::TYPE_BOOL => boolval($ax_value),
			self::TYPE_JSON => json_decode($ax_value),
			self::TYPE_STRING => strval($ax_value),
		};
	}
}
