<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Exception;
use RuntimeException;
use TypeError;


/**
 * Valid data types for values used in `ConfigOption`
 */
enum ConfigOptionType: string {
	case ARRAY = 'array';
	case BOOL = 'bool';
	case ENUM = 'enum';
	case FLOAT = 'float';
	case INTEGER = 'integer';
	case JSON = 'json';
	case LISTVALUE = 'listvalue';
	case STRING = 'string';


	/**
	 * Validates the provided value to be of the correct type for the enum.
	 *
	 * @param mixed $ax_value
	 * @param bool $ab_isNullable
	 * @return string|bool
	 */
	public function validate(mixed $ax_value, bool $ab_isNullable = false): bool|string {
		switch ($this) {
			case self::ARRAY:
				return is_array($ax_value);

			case self::BOOL:
				/**
				 * Type bool consideres everything boolish to be a valid value
				 * since the \Model\Entity\Configuration saves everything as a string
				 * and does not differentiate between the type here.
				 */
				return is_bool($ax_value) || in_array($ax_value, [1, 0, '1', '0'], true);

			case self::FLOAT:
				return is_float($ax_value) || ($ax_value === (float)$ax_value);

			case self::INTEGER:
				return is_int($ax_value) || ($ax_value === (int)$ax_value);

			case self::JSON:
				try {
					$la_value = json_decode($ax_value, true, 512, JSON_THROW_ON_ERROR);
					if (empty($la_value) && !$ab_isNullable) {
						return __d('configuration', 'error_option_not_nullable');
					}


					return true;
				}
				catch (Exception | TypeError) {
					return false;
				}

			case self::ENUM:
			case self::LISTVALUE:
				throw new RuntimeException(sprintf('Cannot validate case `%s` in `%s` in the enum directly. Use `\Awyiss\Configuration\ConfigOption::validateConfigValue` instead.', $this->name, self::class));

			case self::STRING:
				return is_string($ax_value);
		}


		return false;
	}


	/**
	 * Casts the provided `$ax_value` to the correct type
	 *
	 * @param mixed $ax_value
	 * @return mixed
	 */
	public function cast(mixed $ax_value): mixed {
		/** @noinspection PhpUncoveredEnumCasesInspection */
		return match ($this) {
			self::ARRAY => (array)$ax_value,
			self::BOOL => boolval($ax_value),
			self::FLOAT => floatval($ax_value),
			self::INTEGER => intval($ax_value),
			self::JSON => json_decode($ax_value),
			self::STRING => strval($ax_value),
		};
	}
}
