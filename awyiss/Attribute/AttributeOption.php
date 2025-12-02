<?php declare(strict_types=1);


namespace Awyiss\Attribute;


use Awyiss\Model\Entity;
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
 * @see \Awyiss\Attribute\AttributeOptionsCollectionInterface::initializeAttributeOptions
 */
class AttributeOption {
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
	 * @param callable|array|bool|null $disabled
	 * @param string $identifier
	 * @param callable|array|null $options
	 * @param callable|bool|null $readonly
	 * @param callable|null $toScalar
	 * @param mixed|null $validate
	 * @param mixed|null $value
	 */
	public function __construct(
		string $identifier,
		array|bool|callable|null $disabled = null,
		array|callable|null $options = null,
		bool|callable|null $readonly = null,
		?callable $toScalar = null,
		mixed $validate = null,
		mixed $value = null
	) {
		if ($disabled) {
			$this->setDisabled($disabled);
		}

		$this->setIdentifier($identifier);

		if ($options) {
			$this->setOptions($options);
		}

		if ($readonly) {
			$this->setReadonly($readonly);
		}

		if ($toScalar) {
			$this->setToScalar($toScalar);
		}

		if ($validate) {
			$this->setValidate($validate);
		}

		if ($value) {
			$this->setValue($value);
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
	public function buildOptions(array $currentOptions, ?Entity $entity = null): array {
		$disabled = $this->getDisabled(true, $entity, $currentOptions);
		$options = $this->getOptions(true, $entity, $currentOptions);
		$readonly = $this->getReadonly(true, $entity, $currentOptions);
		$value = $this->getValue(true, $entity, $currentOptions);

		if ($disabled !== null) {
			$currentOptions['disabled'] = $disabled;
		}

		if ($options !== null) {
			$currentOptions['options'] = $options;
		}

		if ($readonly !== null) {
			$currentOptions['readonly'] = $readonly;
		}

		if ($value !== null) {
			$currentOptions['val'] = $value;
		}

		return $currentOptions;
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
	 * @return AttributeOption
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
	public function getToScalar(bool $evaluate = false, mixed $value = null, ?Entity $entity = null, array &$currentOptions = []): mixed {
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
	public function setToScalar(?callable $toScalar = null): static {
		$this->toScalar = $toScalar;

		return $this;
	}


	/**
	 * @param mixed $value
	 * @param \Awyiss\Model\Entity|null $entity
	 * @param array $currentOptions
	 * @return mixed
	 */
	public function toScalar(mixed $value, ?Entity $entity = null, array &$currentOptions = []): mixed {
		if (!$this->toScalar) {
			return $value;
		}

		return call_user_func_array($this->toScalar, [$value, $entity, &$currentOptions]);
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
	 * @return AttributeOption
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
	public function validateValue(mixed $value, ?Entity $entity = null): bool|string {
		$validate = $this->getValidate();

		// No validation? Every value is valid.
		if ($validate === false) {
			return true;
		}

		// Return the result of the callable, if given
		if (is_callable($validate)) {
			return $validate($value, $entity, $this);
		}

		// Any other value is invalid
		if ($validate !== null) {
			throw new RuntimeException(sprintf('No valid `valite` option set in `%s`.', static::class));
		}

		$disabled = $this->getDisabled(true, $entity);

		// Disabled means no value is allowed
		if (in_array($disabled, ['disabled', true], true) && !empty($value)) {
			return false;
		}

		if ($value === null) {
			return true;
		}

		$options = $this->getOptions(true, $entity);

		// If the value is an array, we need to check if all values are valid
		if (is_array($value)) {
			$inOptions = count(array_intersect_key($options, array_flip($value))) === count($value);
			$inDisabled = array_intersect($value, (array)$disabled);

			return $inOptions && !$inDisabled;
		}

		if (!is_scalar($value)) {
			$value = $this->toScalar($value, $entity);
		}

		$inOptions = array_key_exists($value, $options);
		$inDisabled = is_array($disabled) && in_array($value, $disabled) ? $disabled : false;

		return $inOptions && !$inDisabled;
	}


	/**
	 * @return mixed
	 */
	public function getValidate(): mixed {
		return $this->validate;
	}


	/**
	 * @param mixed $validate
	 * @return AttributeOption
	 * @noinspection PhpUnused
	 */
	public function setValidate(mixed $validate = null): static {
		$this->validate = $validate;

		return $this;
	}
}
