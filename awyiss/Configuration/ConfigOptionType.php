<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Exception;
use RuntimeException;
use TypeError;


/**
 * Valid data types for values used in `ConfigOption`
 */
enum ConfigOptionType {
	case Bool;
	case Enum;
	case Float;
	case Integer;
	case JsonArray;
	case JsonObject;
	case List;
	case ListKey;
	case String;
	case ValueCollection;


	/**
	 * Validates the provided value to be of the correct type for the enum.
	 *
	 * @param mixed $value
	 * @param bool $isNullable
	 * @return string|bool
	 */
	public function validate(mixed $value, bool $isNullable = false): bool|string {
		if ($isNullable && $value === null) {
			return true;
		}

		/** @noinspection PhpUncoveredEnumCasesInspection */
		switch ($this) {
			case self::Bool:
				/**
				 * Type bool consideres everything boolish to be a valid value
				 * since the \Model\Entity\Configuration saves everything as a string
				 * and does not differentiate between the type here.
				 */
				return is_bool($value) || in_array($value, [1, 0, '1', '0'], true);

			case self::Float:
				return is_float($value) || ($value === (float)$value);

			case self::Integer:
				return is_int($value) || ($value === (int)$value);

			case self::List:
			case self::JsonArray:
			case self::JsonObject:
				try {
					$la_value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
				}
				catch (Exception | TypeError) {
					return false;
				}
				return is_array($la_value);

			case self::Enum:
			case self::ValueCollection:
				throw new RuntimeException(sprintf('Cannot validate case `%s` in `%s` in the enum directly. Use `\Awyiss\Configuration\ConfigOption::validateConfigValue` instead.', $this->name, self::class));

			case self::String:
				return is_string($value);
		}


		return false;
	}


	/**
	 * Casts the provided `$value` to the correct type
	 *
	 * @param mixed $value
	 * @param bool $isNullable
	 * @return mixed
	 */
	public function cast(mixed $value, bool $isNullable = false): mixed {
		if ($isNullable && $value === null) {
			return null;
		}

		/**
		 * @noinspection PhpUncoveredEnumCasesInspection
		 * @noinspection PhpTernaryExpressionCanBeReplacedWithConditionInspection
		 */
		return match ($this) {
			self::Bool => $value === 'false' ? false : boolval($value),
			self::Float => floatval($value),
			self::Integer => intval($value),
			self::List, self::JsonArray, self::ValueCollection => json_decode($value ?? '', true),
			self::JsonObject => json_decode($value ?? ''),
			self::String => strval($value),
		};
	}
}
