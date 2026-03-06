<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Utility\Inflector;
use BackedEnum;
use RuntimeException;


/**
 * This class represents a single configuration item with the following properties:
 * - an identifier
 * - a type of the value, e.g. 'string' or 'integer'
 * - a default value in case none is set
 * - the `localizable`-attribute (boolean) to indicate whether the value can be set independently per language
 * - the `nullable` to indicate whether the value can be empty
 *
 * It's used in classes that implement `ConfigOptionsInterface`
 *
 * @see ConfigOptionsInterface::initializeConfigOptions
 */
class ConfigOption {
	/**
	 * @var mixed|null
	 */
	protected mixed $defaultValue = null;
	/**
	 * @var string
	 */
	protected string $description = '';
	/**
	 * @var bool Can the config value be set independently per language
	 */
	protected bool $localizable = true;
	/**
	 * @var string
	 */
	protected string $identifier;
	/**
	 * @var array{global: bool, localized: bool} Can the config value be empty or is it required?
	 */
	protected array $nullable = [
		'global' => true,
		'localized' => false,
	];
	/**
	 * @var bool Can the config value be set by the user
	 */
	protected bool $personalizable = false;
	/**
	 * @var string|null
	 */
	protected ?string $title = null;
	/**
	 * @var ConfigOptionType
	 */
	protected ConfigOptionType $type = ConfigOptionType::String;
	/**
	 * @var callable|null Can hold a callable that casts the provided value
	 */
	protected $typecast = null;
	/**
	 * @var callable|null Can hold a callable that validates the provided value
	 */
	protected $validate = null;
	/**
	 * @var callable|array|class-string|null Possible values for type "listvalue"
	 */
	protected mixed $values = null;


	/**
	 * @param string $identifier
	 * @param \Awyiss\Configuration\ConfigOptionType $type
	 * @param mixed|null $defaultValue
	 * @param string $description
	 * @param bool $localizable
	 * @param array|bool|null $nullable
	 * @param bool $personalizable
	 * @param string|null $title
	 * @param callable|null $typecast
	 * @param callable|null $validate
	 * @param callable|array|string|null $values
	 */
	public function __construct(
		string $identifier,
		ConfigOptionType $type = ConfigOptionType::String,
		mixed $defaultValue = null,
		string $description = '',
		bool $localizable = true,
		bool|array|null $nullable = null,
		bool $personalizable = false,
		?string $title = null,
		?callable $typecast = null,
		?callable $validate = null,
		array|callable|string|null $values = null,
	) {
		$this->setDefaultValue($defaultValue);

		$this->setDescription($description);

		$this->setLocalizable($localizable);

		$this->setIdentifier($identifier);

		if (isset($nullable)) {
			if (is_bool($nullable)) {
				$this->setNullable($nullable);
				$this->setNullable($nullable, true);
			}
			elseif (is_array($nullable)) {
				if (isset($nullable['global'])) {
					$this->setNullable($nullable['global']);
				}

				if (isset($nullable['localized'])) {
					$this->setNullable($nullable['localized'], true);
				}
			}
		}

		$this->setPersonalizable($personalizable);

		$this->setType($type);

		$this->setTitle($title);

		$this->setTypecast($typecast);

		$this->setValidate($validate);

		$this->setValues($values);
	}


	/**
	 * @return mixed
	 */
	public function getDefaultValue(): mixed {
		return $this->defaultValue;
	}


	/**
	 * @param mixed $defaultValue
	 * @return self
	 */
	public function setDefaultValue(mixed $defaultValue): static {
		$this->defaultValue = $defaultValue;


		return $this;
	}


	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}


	/**
	 * @param string $description
	 * @return ConfigOption
	 */
	public function setDescription(string $description): static {
		$this->description = $description;


		return $this;
	}


	/**
	 * @return string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * @param string $identifier
	 * @return self
	 */
	public function setIdentifier(string $identifier): static {
		$this->identifier = Inflector::variable($identifier);


		return $this;
	}


	/**
	 * @return bool
	 */
	public function isLocalizable(): bool {
		return $this->localizable;
	}


	/**
	 * @param bool $localizable
	 */
	public function setLocalizable(bool $localizable): void {
		$this->localizable = $localizable;
	}


	/**
	 * @param bool $localized
	 * @return bool
	 */
	public function isNullable(bool $localized = false): bool {
		return $this->nullable[ $localized ? 'localized' : 'global' ];
	}


	/**
	 * @param bool $nullable
	 * @param bool $localized
	 */
	public function setNullable(bool $nullable, bool $localized = false): void {
		$this->nullable[ $localized ? 'localized' : 'global' ] = $nullable;
	}


	/**
	 * @return bool
	 */
	public function isPersonalizable(): bool {
		return $this->personalizable;
	}


	/**
	 * @param bool $personalizable
	 */
	public function setPersonalizable(bool $personalizable): void {
		$this->personalizable = $personalizable;
	}


	/**
	 * @return mixed
	 */
	public function getPrintableValue(): mixed {
		$value = $this->defaultValue;

		if ($value === null) {
			return null;
		}

		if ($this->getType() === ConfigOptionType::ListKey) {
			return $this->getValues(true)[ $value ] ?? $value;
		}

		if ($this->getType() === ConfigOptionType::ValueCollection) {
			$typecastValues = [];
			$possibleValues = $this->getValues(true);

			if (!is_array($value)) {
				$value = json_decode($value, true);
			}

			$values = $value;
			if (!is_array($values)) {
				$values = [$values];
			}

			foreach ($values as $key => $value) {
				if (array_key_exists($value, $possibleValues)) {
					$typecastValues[ $key ] = $possibleValues[ $value ];
				}
			}

			return implode(', ', $typecastValues);
		}

		return match ($this->type) {
			ConfigOptionType::Bool => $value ? 'true' : 'false',
			ConfigOptionType::Json, ConfigOptionType::List, ConfigOptionType::ValueCollection => array_is_list($value) ? implode(', ', $value) : print_r($value, true),
			default => $value,
		};
	}


	/**
	 * @return string|null
	 */
	public function getTitle(): ?string {
		return $this->title;
	}


	/**
	 * @param string|null $title
	 * @return $this
	 */
	public function setTitle(?string $title): static {
		$this->title = $title;


		return $this;
	}

	/**
	 * @return ConfigOptionType
	 */
	public function getType(): ConfigOptionType {
		return $this->type;
	}


	/**
	 * @param ConfigOptionType $type
	 * @return self
	 */
	public function setType(ConfigOptionType $type): static {
		$this->type = $type;


		return $this;
	}


	/**
	 * @return callable|null
	 */
	public function getTypecast(): ?callable {
		return $this->typecast;
	}


	/**
	 * @param callable|null $type
	 * @return self
	 */
	public function setTypecast(?callable $type): static {
		$this->typecast = $type;


		return $this;
	}


	/**
	 * @return callable|null
	 */
	public function getValidate(): ?callable {
		return $this->validate;
	}


	/**
	 * @param callable|null $validate
	 * @return self
	 */
	public function setValidate(?callable $validate): static {
		$this->validate = $validate;


		return $this;
	}


	/**
	 * Validates the provided `$value` to match the type stored in `self::$type`.
	 *
	 * Returns
	 * - true for a valid value or
	 * - false for invalid ones or
	 * - an error message string if a value is not localizable or empty but not nullable
	 *
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return string|bool
	 */
	public function validateConfigValue(mixed $value, ?string $languageShortcode = null): bool|string {
		if ($this->getValidate()) {
			return $this->getValidate()($value, $languageShortcode);
		}

		return $this->validate($value, $languageShortcode);
	}


	/**
	 * Internal validation method that validates the provided `$value`
	 * to match the type stored in `self::$type`.
	 *
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return string|bool
	 */
	public function validate(mixed $value, ?string $languageShortcode = null): bool|string {
		if ($languageShortcode !== null && $languageShortcode !== '' && !$this->isLocalizable()) {
			return __d('Configuration', 'error_option_not_localizable');
		}

		if ($value === null) {
			if (!$this->isNullable($languageShortcode !== null)) {
				return __d('Configuration', 'error_option_not_nullable');
			}


			return true;
		}


		if (
			$this->getType() === ConfigOptionType::Enum ||
			$this->getType() === ConfigOptionType::ListKey ||
			$this->getType() === ConfigOptionType::ValueCollection
		) {
			$values = $this->getValues(true, $languageShortcode);
			if (!$values) {
				throw new RuntimeException(sprintf('Cannot validate option `%s` with type `%s` without a list of values', $this->identifier, $this->type->name));
			}
		}


		if ($this->getType() === ConfigOptionType::Enum) {
			if (!is_string($value) && !is_int($value)) {
				return false;
			}


			/** @var \BackedEnum $values */
			return (bool)$values::tryFrom($value);
		}

		if ($this->getType() === ConfigOptionType::ListKey) {
			/** @noinspection PhpUndefinedVariableInspection */
			return array_key_exists($value, $values);
		}

		if ($this->getType() === ConfigOptionType::ValueCollection) {
			$decodedValues = $value;
			if (!is_array($decodedValues)) {
				$decodedValues = json_decode($decodedValues, true);
			}

			/** @noinspection PhpUndefinedVariableInspection */
			return count($decodedValues) === count(array_intersect($decodedValues, array_keys($values)));
		}


		return $this->getType()->validate($value, $this->isNullable($languageShortcode !== null));
	}


	/**
	 * Casts the provided `$value` to a type, specified in `self::$type`
	 *
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return mixed
	 */
	public function typecastConfigValue(mixed $value, ?string $languageShortcode = null): mixed {
		if ($this->getTypecast()) {
			return $this->getTypecast()($value, $languageShortcode);
		}

		if (
			$this->getType() === ConfigOptionType::Enum ||
			$this->getType() === ConfigOptionType::ListKey ||
			$this->getType() === ConfigOptionType::ValueCollection
		) {
			$values = $this->getValues(true, $languageShortcode);
			if (!is_array($values) && $values instanceof BackedEnum) {
				throw new RuntimeException(sprintf('Cannot typecast option `%s` with type `%s` without a list of values', $this->identifier, $this->getType()->name));
			}
		}

		if ($this->getType() === ConfigOptionType::Enum) {
			/** @noinspection PhpUndefinedVariableInspection */
			return $this->typecastEnum($value, $values);
		}

		if ($this->getType() === ConfigOptionType::ListKey) {
			/** @noinspection PhpUndefinedVariableInspection */
			return $this->typecastListKey($value, $values);
		}

		if ($this->getType() === ConfigOptionType::ValueCollection) {
			/** @noinspection PhpUndefinedVariableInspection */
			return $this->typecastValueCollection($value, $values);
		}

		return $this->getType()->cast($value, $this->isNullable($languageShortcode !== null));
	}


	/**
	 * @return callable|array|class-string|null
	 */
	public function getValues(bool $returnEvaluated = false, ?string $languageShortcode = null): array|callable|string|null {
		if ($returnEvaluated) {
			if (is_string($this->values) && !enum_exists($this->values)) {
				return $this->values->cases();
			}
			elseif (is_callable($this->values)) {
				return call_user_func($this->values, $languageShortcode);
			}
		}

		return $this->values;
	}


	/**
	 * @param callable|array|class-string|null $values
	 * @return $this
	 */
	public function setValues(array|callable|string|null $values): static {
		if (is_string($values) && !enum_exists($values)) {
			throw new RuntimeException(sprintf('Provided values must be an array or a valid enum. `%s` given.', gettype($values)));
		}

		$this->values = $values;


		return $this;
	}


	/**
	 * @param mixed $value
	 * @param mixed $values
	 * @return \BackedEnum|null
	 */
	protected function typecastEnum(mixed $value, mixed $values): mixed {
		//If the value already is a case of the provided enum class, return it
		/** @noinspection PhpUndefinedVariableInspection */
		if ($value instanceof $values) {
			return $value;
		}

		if (!is_string($value) && !is_int($value)) {
			return null;
		}

		/** @var \BackedEnum $values */
		return $values::tryFrom($value);
	}


	/**
	 * @param mixed $value
	 * @param mixed $values
	 * @return mixed
	 */
	protected function typecastListKey(mixed $value, mixed $values): mixed {
		/** @noinspection PhpUndefinedVariableInspection */
		if (in_array($value, array_keys($values), true)) {
			return $value;
		}

		// If value is only numeric, cast it to int to match the key type
		if (is_numeric($value) && ctype_digit((string)$value)) {
			$value = (int)$value;

			if (in_array($value, array_keys($values), true)) {
				return $value;
			}
		}

		// If the value is not a key, search for it in the values and return the corresponding key
		$key = array_search($value, $values, true);

		return $key !== false ? $key : null;
	}


	/**
	 * @param mixed $value
	 * @param mixed $values
	 * @return mixed
	 */
	protected function typecastListValue(mixed $value, mixed $values): mixed {
		if (in_array($value, $values, true)) {
			return $value;
		}

		$key = array_search($value, $values);

		return $key !== false ? $values[ $key ] : null;
	}


	/**
	 * @param mixed $value
	 * @param mixed $values
	 * @return mixed
	 */
	protected function typecastValueCollection(mixed $value, mixed $values): mixed {
		$typecastValues = $value;
		if (!is_array($typecastValues)) {
			$typecastValues = $typecastValues ? json_decode($typecastValues, true) : [];
		}

		if (!is_array($typecastValues)) {
			$typecastValues = [$typecastValues];
		}

		// Remove all items that aren't keys in $values
		$typecastValues = array_intersect($typecastValues, array_keys($values));

		return $typecastValues ?: null;
	}
}
