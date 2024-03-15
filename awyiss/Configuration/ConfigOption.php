<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Cake\Utility\Inflector;
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
	 * @var callable|array|class-string|null Possible values for type "listvalue"
	 */
	protected mixed $values = null;


	/**
	 * @param string $identifier
	 * @param \Awyiss\Configuration\ConfigOptionType|null $type
	 * @param mixed|null $defaultValue
	 * @param string $description
	 * @param bool $localizable
	 * @param array|bool|null $nullable
	 * @param bool $personalizable
	 * @param string|null $title
	 * @param callable|null $typecast
	 * @param callable|array|string|null $values
	 */
	public function __construct(
		string $identifier,
		?ConfigOptionType $type = null,
		mixed $defaultValue = null,
		string $description = '',
		bool $localizable = true,
		bool|array|null $nullable = null,
		bool $personalizable = false,
		?string $title = null,
		?callable $typecast = null,
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

		$this->setType($type ?? ConfigOptionType::String);

		$this->setTitle($title);

		$this->setTypecast($typecast);

		$this->setValues($values);
	}


	/**
	 * @return mixed
	 */
	public function getDefaultValue(): mixed {
		return $this->defaultValue;
	}


	/**
	 * @param mixed $ax_defaultValue
	 * @return self
	 */
	public function setDefaultValue(mixed $ax_defaultValue): static {
		$this->defaultValue = $ax_defaultValue;


		return $this;
	}


	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}


	/**
	 * @param string $as_description
	 * @return ConfigOption
	 */
	public function setDescription(string $as_description): static {
		$this->description = $as_description;


		return $this;
	}


	/**
	 * @return string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * @param string $as_identifier
	 * @return self
	 */
	public function setIdentifier(string $as_identifier): static {
		$this->identifier = Inflector::variable($as_identifier);


		return $this;
	}


	/**
	 * @return bool
	 */
	public function isLocalizable(): bool {
		return $this->localizable;
	}


	/**
	 * @param bool $ab_localizable
	 */
	public function setLocalizable(bool $ab_localizable): void {
		$this->localizable = $ab_localizable;
	}


	/**
	 * @param bool $ab_localized
	 * @return bool
	 */
	public function isNullable(bool $ab_localized = false): bool {
		return $this->nullable[ $ab_localized ? 'localized' : 'global' ];
	}


	/**
	 * @param bool $ab_nullable
	 * @param bool $ab_localized
	 */
	public function setNullable(bool $ab_nullable, bool $ab_localized = false): void {
		$this->nullable[ $ab_localized ? 'localized' : 'global' ] = $ab_nullable;
	}


	/**
	 * @return bool
	 */
	public function isPersonalizable(): bool {
		return $this->personalizable;
	}


	/**
	 * @param bool $ab_nullable
	 * @param bool $ab_localized
	 */
	public function setPersonalizable(bool $ab_personalizable): void {
		$this->personalizable = $ab_personalizable;
	}


	/**
	 * @return mixed
	 */
	public function getPrintableValue(): mixed {
		$lx_value = $this->defaultValue;

		if ($lx_value === null) {
			return null;
		}

		if ($this->getType() === ConfigOptionType::ListKey) {
			return $this->getValues(true)[ $lx_value ] ?? $lx_value;
		}

		return match ($this->type) {
			ConfigOptionType::Bool => $lx_value ? 'true' : 'false',
			ConfigOptionType::JsonArray => array_is_list($lx_value) ? implode(', ', $lx_value) : print_r($lx_value, true),
			ConfigOptionType::JsonObject => print_r($lx_value, true),
			default => $lx_value,
		};
	}


	/**
	 * @return string|null
	 */
	public function getTitle(): ?string {
		return $this->title;
	}


	/**
	 * @param string|null $as_title
	 * @return $this
	 */
	public function setTitle(?string $as_title): static {
		$this->title = $as_title;


		return $this;
	}

	/**
	 * @return ConfigOptionType
	 */
	public function getType(): ConfigOptionType {
		return $this->type;
	}


	/**
	 * @param ConfigOptionType $ae_type
	 * @return self
	 */
	public function setType(ConfigOptionType $ae_type): static {
		$this->type = $ae_type;


		return $this;
	}


	/**
	 * @return callable|null
	 */
	public function getTypecast(): ?callable {
		return $this->typecast;
	}


	/**
	 * @param callable|null $ae_type
	 * @return self
	 */
	public function setTypecast(?callable $ac_type): static {
		$this->typecast = $ac_type;


		return $this;
	}


	/**
	 * Validates the provided `$ax_value` to match the type stored in `self::$type`.
	 *
	 * Returns
	 * - true for a valid value or
	 * - false for invalid ones or
	 * - an error message string if a value is not localizable or empty but not nullable
	 *
	 * @param mixed $ax_value
	 * @param string|null $as_languageShortcode
	 * @return string|bool
	 */
	public function validateConfigValue(mixed $ax_value, ?string $as_languageShortcode = null): bool|string {
		if ($as_languageShortcode !== null && $as_languageShortcode !== '' && !$this->isLocalizable()) {
			return __d('configuration', 'error_option_not_localizable');
		}

		if ($ax_value === null) {
			if (!$this->isNullable($as_languageShortcode !== null)) {
				return __d('configuration', 'error_option_not_nullable');
			}


			return true;
		}


		if (
			$this->getType() === ConfigOptionType::Enum ||
			$this->getType() === ConfigOptionType::ListKey ||
			$this->getType() === ConfigOptionType::ListValue
		) {
			$lx_values = $this->getValues(true, $as_languageShortcode);
			if (!$lx_values) {
				throw new RuntimeException(sprintf('Cannot validate option `%s` with type `%s` without a list of values', $this->identifier, ConfigOptionType::ListValue->name));
			}
		}


		if ($this->getType() === ConfigOptionType::Enum) {
			if (!is_string($ax_value) && !is_int($ax_value)) {
				return false;
			}


			/** @var \BackedEnum $lx_values */
			return (bool)$lx_values::tryFrom($ax_value);
		}

		if ($this->getType() === ConfigOptionType::ListKey) {
			/** @noinspection PhpUndefinedVariableInspection */
			return array_key_exists($ax_value, $lx_values);
		}

		if ($this->getType() === ConfigOptionType::ListValue) {
			/** @noinspection PhpUndefinedVariableInspection */
			return in_array($ax_value, $lx_values, true);
		}


		return $this->getType()->validate($ax_value, $this->isNullable($as_languageShortcode !== null));
	}


	/**
	 * Casts the provided `$ax_value` to a type, specified in `self::$type`
	 *
	 * @param mixed $ax_value
	 * @param string|null $as_languageShortcode
	 * @return mixed
	 */
	public function typecastConfigValue(mixed $ax_value, ?string $as_languageShortcode = null): mixed {
		if ($this->getTypecast()) {
			return $this->getTypecast()($ax_value, $as_languageShortcode);
		}

		if (
			$this->getType() === ConfigOptionType::Enum ||
			$this->getType() === ConfigOptionType::ListKey ||
			$this->getType() === ConfigOptionType::ListValue
		) {
			$lx_values = $this->getValues(true, $as_languageShortcode);
			if (!$lx_values) {
				throw new RuntimeException(sprintf('Cannot typecast option `%s` with type `%s` without a list of values', $this->identifier, ConfigOptionType::ListValue->name));
			}
		}

		if ($this->getType() === ConfigOptionType::Enum) {
			//If the value already is a case of the provided enum class, return it
			/** @noinspection PhpUndefinedVariableInspection */
			if ($ax_value instanceof $lx_values) {
				return $ax_value;
			}

			if (!is_string($ax_value) && !is_int($ax_value)) {
				return null;
			}

			/** @var \BackedEnum $lx_values */
			return $lx_values::tryFrom($ax_value);
		}

		if ($this->getType() === ConfigOptionType::ListKey) {
			/** @noinspection PhpUndefinedVariableInspection */
			if (in_array($ax_value, array_keys($lx_values), true)) {
				return $ax_value;
			}

			foreach ([array_keys($lx_values), $lx_values] as $la_values) {
				/** @noinspection PhpUndefinedVariableInspection */
				if (in_array($ax_value, $la_values, true)) {
					return $ax_value;
				}

				$lx_key = array_search($ax_value, $la_values);


				return $lx_key !== false ? $la_values[ $lx_key ] : null;
			}
		}

		if ($this->getType() === ConfigOptionType::ListValue) {
			/** @noinspection PhpUndefinedVariableInspection */
			if (in_array($ax_value, $lx_values, true)) {
				return $ax_value;
			}

			$lx_key = array_search($ax_value, $lx_values);

			return $lx_key !== false ? $lx_values[ $lx_key ] : null;
		}

		$lx_value = $this->getType()->cast($ax_value, $this->isNullable($as_languageShortcode !== null));

		if ($lx_value === null) {
			return null;
		}


		return $lx_value;
	}


	/**
	 * @return callable|array|class-string|null
	 */
	public function getValues(bool $ab_returnEvaluated = false, ?string $as_languageShortcode = null): array|callable|string|null {
		if ($ab_returnEvaluated) {
			if (is_string($this->values) && !enum_exists($this->values)) {
				return $this->values->cases();
			}
			elseif (is_callable($this->values)) {
				return call_user_func($this->values, $as_languageShortcode);
			}
		}

		return $this->values;
	}


	/**
	 * @param callable|array|class-string|null $ax_values
	 * @return $this
	 */
	public function setValues(array|callable|string|null $ax_values): static {
		if (is_string($ax_values) && !enum_exists($ax_values)) {
			throw new RuntimeException(sprintf('Provided values must be an array or a valid enum. `%s` given.', gettype($ax_values)));
		}

		$this->values = $ax_values;


		return $this;
	}
}
