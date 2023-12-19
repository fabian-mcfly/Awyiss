<?php declare(strict_types=1);


namespace Awyiss\Configuration;


class ConfigOption {
	protected mixed $defaultValue = NULL;
	protected bool $localizable = TRUE;
	protected string $name;
	protected array $nullable = [
		'global' => TRUE,
		'localized' => FALSE,
	];
	protected string $type = self::TYPE_STRING;


	public const TYPE_STRING = 'string';
	public const TYPE_INTEGER = 'integer';
	public const TYPE_BOOL = 'bool';
	public const TYPE_JSON = 'json';


	public function __construct (array $aa_settings = []) {
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

		if (isset($aa_settings['type'])) {
			$this->setType($aa_settings['type']);
		}
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
	public function setDefaultValue (mixed $ax_defaultValue): self {
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
	public function setName (string $as_name): self {
		$this->name = \Cake\Utility\Inflector::underscore($as_name);

		return $this;
	}


	/**
	 * @return string
	 */
	public function getType (): string {
		return $this->type;
	}


	/**
	 * @param string $as_type
	 *
	 * @return self
	 */
	public function setType (string $as_type): self {
		if (!in_array($as_type, [
			static::TYPE_STRING,
			static::TYPE_INTEGER,
			static::TYPE_BOOL,
			static::TYPE_JSON,
		])) {
			throw new \RuntimeException(sprintf('The given type `%s` is not a valid ConfigOption type', $as_type));
		}

		$this->type = $as_type;

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


	public function validateConfigValue (mixed $ax_value, ?string $as_languagesShortcode = NULL): bool|string {
		if ($as_languagesShortcode !== NULL && !$this->isLocalizable()) {
			return __('validation::error_option_not_localizable');
		}

		if ($ax_value === NULL) {
			if (!$this->isNullable($as_languagesShortcode !== NULL)) {
				return __('validation::error_option_not_nullable');
			}

			return TRUE;
		}

		switch ($this->getType()) {
			case static::TYPE_INTEGER:
				return is_int($ax_value) || (is_string($ax_value) && ctype_digit($ax_value));

			case static::TYPE_BOOL:
				/*
				 * Type bool consideres everything boolish to be a valid value
				 * since the \Awyiss\Model\Entity\Configuration saves everything as a string
				 * and does not differentiate between the type here.
				 */
				return is_bool($ax_value) || in_array($ax_value, [1, 0, '1', '0'], TRUE);

			case static::TYPE_JSON:
				try {
					$la_value = json_decode($ax_value, TRUE, 16, JSON_THROW_ON_ERROR);

					if (empty($la_value) && !$this->isNullable($as_languagesShortcode !== NULL)) {
						return __('validation::error_option_not_nullable');
					}

					return TRUE;
				}
				/** @noinspection PhpMultipleClassDeclarationsInspection */
				catch (\JsonException) {
					return FALSE;
				}

			case static::TYPE_STRING:
				//ceck the type string last because everything's supposed to be a string
				return is_string($ax_value);
		}

		return FALSE;
	}


	public function typecastConfigValue (mixed $ax_value): mixed {
		/** @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection */
		switch ($this->getType()) {
			case static::TYPE_INTEGER:
				return intval($ax_value);

			case static::TYPE_BOOL:
				return boolval($ax_value);

			case static::TYPE_JSON:
				return json_decode($ax_value);
		}


		return $ax_value;
	}
}