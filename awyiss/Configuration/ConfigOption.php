<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Cake\Utility\Inflector;
use JetBrains\PhpStorm\ArrayShape;
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
	 * Holds the array definition for `\JetBrains\PhpStorm\ArrayShape` used in `__construct`
	 */
	protected const SETTINGS_SHAPE = [
		'defaultValue' => 'mixed',
		'description' => 'string',
		'localizable' => 'bool',
		'identifier' => 'string',
		'nullable' => 'bool|array',
		'title' => 'string',
		'type' => 'string',
		'typecast' => 'callable|null',
	];
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
	 * @var string|null
	 */
	protected ?string $title = null;
	/**
	 * @var ConfigOptionType
	 */
	protected ConfigOptionType $type;
	/**
	 * @var callable|null Can hold a callable that casts the provided value
	 */
	protected $typecast = null;
	/**
	 * @var array|class-string Possible values for type "listvalue"
	 */
	protected array|string $values;


	/**
	 * @param array{defaultValue: mixed, localizable: bool, identifier: string, nullable: bool|array, type: string} $aa_settings
	 */
	public function __construct(#[ArrayShape(self::SETTINGS_SHAPE)] array|string $aa_settings = []) {
		$la_settings = $aa_settings;
		if (!is_array($aa_settings)) {
			$la_settings = ['identifier' => $aa_settings];
		}

		if (isset($la_settings['defaultValue'])) {
			$this->setDefaultValue($la_settings['defaultValue']);
		}

		if (isset($la_settings['description'])) {
			$this->setDescription($la_settings['description']);
		}

		if (isset($la_settings['localizable']) && is_bool($la_settings['localizable'])) {
			$this->setLocalizable($la_settings['localizable']);
		}

		if (isset($la_settings['identifier'])) {
			$this->setIdentifier($la_settings['identifier']);
		}

		if (isset($la_settings['nullable'])) {
			if (is_bool($la_settings['nullable'])) {
				$this->setNullable($la_settings['nullable']);
				$this->setNullable($la_settings['nullable'], true);
			}
			elseif (is_array($la_settings['nullable'])) {
				if (isset($la_settings['nullable']['global'])) {
					$this->setNullable($la_settings['nullable']['global']);
				}

				if (isset($la_settings['nullable']['localized'])) {
					$this->setNullable($la_settings['nullable']['localized'], true);
				}
			}
		}

		$this->setType($la_settings['type'] ?? ConfigOptionType::STRING);

		if (isset($la_settings['typecast'])) {
			$this->setTypecast($la_settings['typecast']);
		}

		if (isset($la_settings['values'])) {
			$this->setValues($la_settings['values']);
		}
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
		if ($as_languageShortcode !== null && !$this->isLocalizable()) {
			return __d('configuration', 'error_option_not_localizable');
		}

		if ($ax_value === null) {
			if (!$this->isNullable($as_languageShortcode !== null)) {
				return __d('configuration', 'error_option_not_nullable');
			}


			return true;
		}


		if ($this->getType() === ConfigOptionType::ENUM || $this->getType() === ConfigOptionType::LISTVALUE) {
			$lx_values = $this->getValues();
			if (!$lx_values) {
				throw new RuntimeException(sprintf('Cannot validate option `%s` with type `%s` without a list of values', $this->identifier, ConfigOptionType::LISTVALUE->value));
			}

			if ($this->getType() === ConfigOptionType::LISTVALUE) {
				dd($ax_value, __FILE__, __LINE__);
			}

			if (!is_string($ax_value) && !is_int($ax_value)) {
				return false;
			}


			/** @noinspection PhpUndefinedMethodInspection */
			return (bool)$lx_values::tryFrom($ax_value);
		}


		return $this->getType()->validate($ax_value, $this->isNullable($as_languageShortcode !== null));
	}


	/**
	 * Casts the provided `$ax_value` to a type, specified in `self::$type`
	 *
	 * @param mixed $ax_value
	 * @return mixed
	 */
	public function typecastConfigValue(mixed $ax_value): mixed {
		if ($this->getTypecast()) {
			return $this->getTypecast()($ax_value);
		}

		if ($this->getType() === ConfigOptionType::ENUM || $this->getType() === ConfigOptionType::LISTVALUE) {
			$lx_values = $this->getValues();
			if (!$lx_values) {
				throw new RuntimeException(sprintf('Cannot typecast option `%s` with type `%s` without a list of values', $this->identifier, ConfigOptionType::LISTVALUE->value));
			}

			if ($this->getType() === ConfigOptionType::LISTVALUE) {
				dd($ax_value, __FILE__, __LINE__);
			}

			//If the value already is a case of the provided enum class, return it
			if ($ax_value instanceof $lx_values) {
				return $ax_value;
			}

			if (!is_string($ax_value) && !is_int($ax_value)) {
				return $ax_value;
			}

			/** @noinspection PhpUndefinedMethodInspection */
			return $lx_values::tryFrom($ax_value);
		}

		return $this->getType()->cast($ax_value);
	}


	/**
	 * @return array|string
	 */
	public function getValues(): array|string {
		return $this->values;
	}


	/**
	 * @param array $ax_values
	 * @return $this
	 */
	public function setValues(array|string $ax_values): static {
		if (is_string($ax_values) && !enum_exists($ax_values)) {
			throw new RuntimeException(sprintf('Provided values must be an array or a valid enum. `%s` given.', gettype($ax_values)));
		}

		$this->values = $ax_values;


		return $this;
	}
}
