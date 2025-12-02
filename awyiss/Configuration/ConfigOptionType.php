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
	case Color;
	case Enum;
	case Float;
	case Integer;
	case Json;
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
				 * Type bool considers everything bool-ish to be a valid value
				 * since the \Model\Entity\Configuration saves everything as a string
				 * and does not differentiate between the type here.
				 */
				return is_bool($value) || in_array($value, [1, 0, '1', '0'], true);

			case self::Color:
				return empty($value) || (is_string($value) && preg_match('/^#[0-9A-F]{6,8}$/i', $value) === 1);

			case self::Float:
				return is_float($value) || ($value === (float)$value);

			case self::Integer:
				return is_int($value) || ($value === (int)$value);

			case self::List:
			case self::Json:
				try {
					$value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
				}
				catch (Exception | TypeError) {
					return false;
				}
				return is_array($value);

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
			self::Color => !empty($value) ? preg_replace('/[^#0-9A-F]/i', '', $value) : null,
			self::Float => floatval($value),
			self::Integer => intval($value),
			self::List, self::Json, self::ValueCollection => json_decode($value ?? '', true),
			self::String => strval($value),
		};
	}
}
