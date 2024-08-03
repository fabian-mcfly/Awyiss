<?php declare(strict_types=1);


namespace Awyiss\Attribute;


use Awyiss\Model\Entity;
use Cake\Utility\Inflector;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;


/**
 * This class represents a single options item with the following properties:
 * - an identifier
 * - a type of the value, e.g. 'string' or 'integer'
 * - a default value in case none is set
 * - the `localizable`-attribute (boolean) to indicate whether the value can be set independently per language
 * - the `nullable` to indicate whether the value can be empty
 *
 * It's used in classes that implement `AttributeOptionsInterface`
 *
 * @see AttributeOptionsInterface::initializeAttributeOptions
 */
class AttributeOptions {
	/**
	 * Holds the array definition for `\JetBrains\PhpStorm\ArrayShape` used in `__construct`
	 */
	protected const SETTINGS_SHAPE = [
		'disabled' => 'array|bool|callable',
		'identifier' => 'string',
		'options' => 'array|callable',
		'readonly' => 'bool|callable',
		'toScalar' => 'callable|null',
		'validate' => 'mixed|callable',
		'value' => 'mixed|callable',
	];
	/**
	 * @var callable|array|bool
	 */
	protected mixed $disabled = null;
	/**
	 * @var string
	 */
	protected string $identifier;
	/**
	 * @var callable|array
	 */
	protected mixed $options = null;
	/**
	 * @var callable|bool
	 */
	protected mixed $readonly = null;
	/**
	 * @var ?callable
	 */
	protected mixed $toScalar = null;
	/**
	 * @var mixed|callable
	 */
	protected mixed $validate = null;
	/**
	 * @var mixed|callable
	 */
	protected mixed $value = null;


	/**
	 * @param array{disabled: array|bool|callable, identifier: string, readonly: bool|callable, options: array|callable, value: mixed|callable} $settings
	 */
	public function __construct(#[ArrayShape(self::SETTINGS_SHAPE)] array $settings = []) {
		foreach (
			[
				'disabled',
				'identifier',
				'options',
				'readonly',
				'toScalar',
				'validate',
				'value',
			] as $ls_key
		) {
			if (!isset($settings[ $ls_key ])) {
				continue;
			}

			$ls_method = Inflector::camelize($ls_key);
			if ($ls_method === 'ToScalar') {
				$ls_method = 'Scalar';
			}

			$ls_method = 'set' . $ls_method;
			if (method_exists($this, $ls_method)) {
				$this->{$ls_method}($settings[ $ls_key ]);
			}
		}

		if (!isset($this->identifier)) {
			throw new RuntimeException('The `identifier` attribute is not set.');
		}
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
	 * @noinspection PhpUnused
	 */
	public function setIdentifier(string $identifier): static {
		$this->identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);


		return $this;
	}


	/**
	 * @param array $currentOptions
	 * @param \Awyiss\Model\Entity|null $entity
	 * @return array
	 */
	public function buildOptions(array $currentOptions, ?Entity $entity): array {
		$la_currentOptions = $currentOptions;

		$lx_disabled = $this->getDisabled(true, $entity, $la_currentOptions);
		$lx_options = $this->getOptions(true, $entity, $la_currentOptions);
		$lx_readonly = $this->getReadonly(true, $entity, $la_currentOptions);
		$lx_value = $this->getValue(true, $entity, $la_currentOptions);

		if ($lx_disabled !== null) {
			$la_currentOptions['disabled'] = $lx_disabled;
		}

		if ($lx_options !== null) {
			$la_currentOptions['options'] = $lx_options;
		}

		if ($lx_readonly !== null) {
			$la_currentOptions['readonly'] = $lx_readonly;
		}

		if ($lx_value !== null) {
			$la_currentOptions['val'] = $lx_value;
		}


		return $la_currentOptions;
	}


	/**
	 * @param bool $evaluate
	 * @param \Awyiss\Model\Entity|null $entity
	 * @param array $currentOptions
	 * @return callable|array|bool
	 */
	public function getDisabled(bool $evaluate = false, ?Entity $entity = null, array &$currentOptions = []): mixed {
		if ($evaluate && is_callable($this->disabled)) {
			return call_user_func_array($this->disabled, [$entity, &$currentOptions]);
		}


		return $this->disabled;
	}


	/**
	 * @param callable|array|bool $disabled
	 * @return self
	 * @noinspection PhpUnused
	 */
	public function setDisabled(array|bool|callable $disabled): static {
		$this->disabled = $disabled;


		return $this;
	}


	/**
	 * @return callable|array
	 * @noinspection PhpUnused
	 */
	public function getOptions(bool $evaluate = false, ?Entity $entity = null, array &$currentOptions = []): mixed {
		if ($evaluate && is_callable($this->options)) {
			return call_user_func_array($this->options, [$entity, &$currentOptions]);
		}


		return $this->options;
	}


	/**
	 * @param callable|array $options
	 * @return self
	 */
	public function setOptions(mixed $options): static {
		$this->options = $options;


		return $this;
	}


	/**
	 * @return callable|bool
	 * @noinspection PhpUnused
	 */
	public function getReadonly(bool $evaluate = false, ?Entity $entity = null, array &$currentOptions = []): mixed {
		if ($evaluate && is_callable($this->readonly)) {
			return call_user_func_array($this->readonly, [$entity, &$currentOptions]);
		}


		return $this->readonly;
	}


	/**
	 * @param callable|bool $readonly
	 * @return AttributeOptions
	 * @noinspection PhpUnused
	 */
	public function setReadonly(bool|callable $readonly): static {
		$this->readonly = $readonly;


		return $this;
	}


	/**
	 * @param bool $evaluate
	 * @param mixed|null $value
	 * @param Entity|null $entity
	 * @param array $currentOptions
	 * @return mixed
	 */
	public function getScalar(bool $evaluate = false, mixed $value = null, ?Entity $entity = null, array &$currentOptions = []): mixed {
		if ($evaluate && is_callable($this->toScalar)) {
			return call_user_func_array($this->toScalar, [$value, $entity, &$currentOptions]);
		}


		return $this->toScalar;
	}


	/**
	 * @param mixed $toScalar
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setScalar(?callable $toScalar = null): static {
		$this->toScalar = $toScalar;


		return $this;
	}


	/**
	 * @return callable|bool
	 * @noinspection PhpUnused
	 */
	public function getValue(bool $evaluate = false, ?Entity $entity = null, array &$currentOptions = []): mixed {
		if ($evaluate && is_callable($this->value)) {
			return call_user_func_array($this->value, [$entity, &$currentOptions]);
		}


		return $this->value;
	}


	/**
	 * @param mixed $value
	 * @return AttributeOptions
	 */
	public function setValue(mixed $value): static {
		$this->value = $value;


		return $this;
	}


	/**
	 * @param array $value
	 * @param Entity|null $entity
	 * @return string|bool
	 */
	public function validateValue(mixed $value, ?Entity $entity): bool|string {
		$lx_validate = $this->getValidate();

		//No validation? Every value is valid.
		if ($lx_validate === false) {
			return true;
		}
		//No validate option set? We need to check the other options
		elseif ($lx_validate === null) {
			$lx_disabled = $this->getDisabled(true, $entity);

			//Disabled means no value is allowed
			if (in_array($lx_disabled, ['disabled', true], true) && !empty($value)) {
				return false;
			}

			$lx_value = $value;
			if ($value === null) {
				return true;
			}

			$la_options = $this->getOptions(true, $entity);

			// If the value is an array, we need to check if all values are valid
			if (is_array($lx_value)) {
				$lb_inOptions = count(array_intersect_key($la_options, array_flip($lx_value))) === count($lx_value);
				$lb_inDisabled = array_intersect($lx_value, (array)$lx_disabled);


				return $lb_inOptions && !$lb_inDisabled;
			}

			if (!is_scalar($lx_value)) {
				$lx_value = $this->getScalar(true, $lx_value, $entity);
			}

			$lb_inOptions = array_key_exists($lx_value, $la_options);
			$lb_inDisabled = is_array($lx_disabled) && in_array($lx_value, $lx_disabled) ? $lx_disabled : false;


			return $lb_inOptions && !$lb_inDisabled;
		}
		elseif (is_callable($lx_validate)) {
			return $lx_validate($value, $entity, $this);
		}

		throw new RuntimeException(sprintf('No valid `valite` option set in `%s`.', static::class));
	}


	/**
	 * @return mixed
	 */
	public function getValidate(): mixed {
		return $this->validate;
	}


	/**
	 * @param mixed $validate
	 * @return AttributeOptions
	 * @noinspection PhpUnused
	 */
	public function setValidate(mixed $validate = null): static {
		$this->validate = $validate;


		return $this;
	}
}
