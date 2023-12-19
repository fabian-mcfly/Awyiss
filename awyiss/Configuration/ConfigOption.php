<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Cake\Utility\Inflector;
use JetBrains\PhpStorm\ArrayShape;


/**
 * @todo add a description property
 *
 * This class represents a single configuration item with the following properties:
 * - a name
 * - a type of the value, e.g. 'string' or 'integer'
 * - a default value in case none is set
 * - the `localizable`-attribute (boolean) to indicate whether the value can be set independently per language
 * - the `nullable` to indicate whether the value can be empty
 *
 * It's used in classes that implement `ConfigOptionsInterface`
 *
 * @see \Awyiss\Configuration\ConfigOptionsInterface::initializeConfigOptions()
 */
class ConfigOption {
	/**
	 * @var null|mixed
	 */
	protected mixed $defaultValue = NULL;
	/**
	 * @var bool Can the config value be set independently per language
	 */
	protected bool $localizable = TRUE;
	/**
	 * @var string
	 */
	protected string $name;
	/**
	 * @var array{global: bool, localized: bool} Can the config value be empty or is it required?
	 */
	protected array $nullable = [
		'global' => TRUE,
		'localized' => FALSE,
	];
	/**
	 * @var \Awyiss\Configuration\ConfigOptionTypes
	 */
	protected ConfigOptionTypes $type;
	/**
	 * Holds the array definition for `\JetBrains\PhpStorm\ArrayShape` used in `__construct`
	 */
	protected const SETTINGS_SHAPE = [
		'defaultValue' => 'mixed',
		'localizable' => 'bool',
		'name' => 'string',
		'nullable' => 'bool|array',
		'type' => 'string',
	];


	/**
	 * @param array{defaultValue: mixed, localizable: bool, name: string, nullable: bool|array, type: string} $aa_settings
	 */
	public function __construct (#[ArrayShape(self::SETTINGS_SHAPE)] array $aa_settings = []) {
		if (isset($aa_settings['defaultValue'])) {
			$this->setDefaultValue($aa_settings['defaultValue']);
		}

		if (isset($aa_settings['localizable']) && is_bool($aa_settings['localizable'])) {
			$this->setLocalizable($aa_settings['localizable']);
		}

		if (isset($aa_settings['name'])) {
			$this->setName($aa_settings['name']);
		}

		if (isset($aa_settings['nullable'])) {
			if (is_bool($aa_settings['nullable'])) {
				$this->setNullable($aa_settings['nullable']);
				$this->setNullable($aa_settings['nullable'], TRUE);
			}
			elseif (is_array($aa_settings['nullable'])) {
				if (isset($aa_settings['nullable']['global'])) {
					$this->setNullable($aa_settings['nullable']['global']);
				}

				if (isset($aa_settings['nullable']['localized'])) {
					$this->setNullable($aa_settings['nullable']['localized'], TRUE);
				}
			}
		}

		$this->setType($aa_settings['type'] ?? ConfigOptionTypes::TYPE_STRING);
	}


	/**
	 * @return mixed
	 */
	public function getDefaultValue (): mixed {
		return $this->defaultValue;
	}


	/**
	 * @param mixed $ax_defaultValue
	 *
	 * @return self
	 */
	public function setDefaultValue (mixed $ax_defaultValue): static {
		$this->defaultValue = $ax_defaultValue;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getName (): string {
		return $this->name;
	}


	/**
	 * @param string $as_name
	 *
	 * @return self
	 */
	public function setName (string $as_name): static {
		$this->name = Inflector::underscore($as_name);

		return $this;
	}


	/**
	 * @return \Awyiss\Configuration\ConfigOptionTypes
	 */
	public function getType (): ConfigOptionTypes {
		return $this->type;
	}


	/**
	 * @param \Awyiss\Configuration\ConfigOptionTypes $ae_type
	 *
	 * @return self
	 */
	public function setType (ConfigOptionTypes $ae_type): static {
		$this->type = $ae_type;

		return $this;


	}


	/**
	 * @return bool
	 */
	public function isLocalizable (): bool {
		return $this->localizable;
	}


	/**
	 * @param bool $ab_localizable
	 */
	public function setLocalizable (bool $ab_localizable): void {
		$this->localizable = $ab_localizable;
	}


	/**
	 * @param bool $ab_localized
	 *
	 * @return bool
	 */
	public function isNullable (bool $ab_localized = FALSE): bool {
		return $this->nullable[ $ab_localized ? 'localized' : 'global' ];
	}


	/**
	 * @param bool $ab_nullable
	 * @param bool $ab_localized
	 */
	public function setNullable (bool $ab_nullable, bool $ab_localized = FALSE): void {
		$this->nullable[ $ab_localized ? 'localized' : 'global' ] = $ab_nullable;
	}


	/**
	 * Validates the provided `$ax_value` to match the type stored in `self::$type`.
	 *
	 * Returns
	 * - TRUE for a valid value or
	 * - FALSE for invalid ones or
	 * - an error message string if a value is not localizable or empty but not nullable
	 *
	 * @param mixed $ax_value
	 * @param null|string $as_languageShortcode
	 *
	 * @return bool|string
	 */
	public function validateConfigValue (mixed $ax_value, ?string $as_languageShortcode = NULL): bool|string {
		if ($as_languageShortcode !== NULL && !$this->isLocalizable()) {
			return __('validation::error_option_not_localizable');
		}

		if ($ax_value === NULL) {
			if (!$this->isNullable($as_languageShortcode !== NULL)) {
				return __('validation::error_option_not_nullable');
			}

			return TRUE;
		}

		return $this->getType()->validateType($ax_value, $this->isNullable($as_languageShortcode !== NULL));
	}


	/**
	 * Casts the provided `$ax_value` to a type, specified in `self::$type`
	 *
	 * @param mixed $ax_value
	 *
	 * @return mixed
	 */
	public function typecastConfigValue (mixed $ax_value): mixed {
		return $this->getType()->typeCast($ax_value);
	}
}