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
	case ListValue;
	case String;


	/**
	 * Validates the provided value to be of the correct type for the enum.
	 *
	 * @param mixed $ax_value
	 * @param bool $ab_isNullable
	 * @return string|bool
	 */
	public function validate(mixed $ax_value, bool $ab_isNullable = false): bool|string {
		if ($ab_isNullable && $ax_value === null) {
			return true;
		}

		switch ($this) {
			case self::Bool:
				/**
				 * Type bool consideres everything boolish to be a valid value
				 * since the \Model\Entity\Configuration saves everything as a string
				 * and does not differentiate between the type here.
				 */
				return is_bool($ax_value) || in_array($ax_value, [1, 0, '1', '0'], true);

			case self::Float:
				return is_float($ax_value) || ($ax_value === (float)$ax_value);

			case self::Integer:
				return is_int($ax_value) || ($ax_value === (int)$ax_value);

			case self::List:
			case self::JsonArray:
			case self::JsonObject:
				try {
					$la_value = json_decode($ax_value, true, 512, JSON_THROW_ON_ERROR);
				}
				catch (Exception | TypeError) {
					return false;
				}
				return is_array($la_value);

			case self::Enum:
			case self::ListValue:
				throw new RuntimeException(sprintf('Cannot validate case `%s` in `%s` in the enum directly. Use `\Awyiss\Configuration\ConfigOption::validateConfigValue` instead.', $this->name, self::class));

			case self::String:
				return is_string($ax_value);
		}


		return false;
	}


	/**
	 * Casts the provided `$ax_value` to the correct type
	 *
	 * @param mixed $ax_value
	 * @param bool $ab_isNullable
	 * @return mixed
	 */
	public function cast(mixed $ax_value, bool $ab_isNullable = false): mixed {
		if ($ab_isNullable && $ax_value === null) {
			return null;
		}

		/**
		 * @noinspection PhpUncoveredEnumCasesInspection
		 * @noinspection PhpTernaryExpressionCanBeReplacedWithConditionInspection
		 */
		return match ($this) {
			self::Bool => $ax_value === 'false' ? false : boolval($ax_value),
			self::Float => floatval($ax_value),
			self::Integer => intval($ax_value),
			self::List => json_decode($ax_value ?? '', true),
			self::JsonArray => json_decode($ax_value ?? '', true),
			self::JsonObject => json_decode($ax_value ?? ''),
			self::String => strval($ax_value),
		};
	}
}
