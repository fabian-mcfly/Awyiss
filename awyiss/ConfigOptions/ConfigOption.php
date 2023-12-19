<?php


namespace Awyiss\ConfigOptions;


class ConfigOption {
	protected mixed $defaultValue = NULL;
	protected string $name;
	protected string $type = self::TYPE_STRING;


	public const TYPE_STRING = 'string';
	public const TYPE_NUMBER = 'number';
	public const TYPE_BOOL = 'bool';
	public const TYPE_JSONLIST = 'json_list';


	public function __construct (array $aa_settings = []) {
		if (isset($aa_settings['defaultValue'])) {
			$this->setDefaultValue($aa_settings['defaultValue']);
		}

		if (isset($aa_settings['name'])) {
			$this->setName($aa_settings['name']);
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
		$this->name = $as_name;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getType (): int {
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
			static::TYPE_NUMBER,
			static::TYPE_BOOL,
			static::TYPE_JSONLIST,
		])) {
			throw new \RuntimeException(sprintf('The given type `%s` is not a valid ConfigOption type', $as_type));
		}

		$this->type = $as_type;

		return $this;
	}
}