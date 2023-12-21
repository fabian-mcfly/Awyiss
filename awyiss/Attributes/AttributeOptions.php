<?php declare(strict_types=1);


namespace Awyiss\Attributes;


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
	 * @param array{disabled: array|bool|callable, identifier: string, readonly: bool|callable, options: array|callable, value: mixed|callable} $aa_settings
	 */
	public function __construct(#[ArrayShape(self::SETTINGS_SHAPE)] array $aa_settings = []) {
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
			if (!isset($aa_settings[ $ls_key ])) {
				continue;
			}

			$ls_method = Inflector::camelize($ls_key);
			if ($ls_method === 'ToScalar') {
				$ls_method = 'Scalar';
			}

			$ls_method = 'set' . $ls_method;
			if (method_exists($this, $ls_method)) {
				$this->{$ls_method}($aa_settings[ $ls_key ]);
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
	 * @param string $as_identifier
	 * @return self
	 * @noinspection PhpUnused
	 */
	public function setIdentifier(string $as_identifier): static {
		$this->identifier = AttributeOptionsProvider::sanitizeIdentifier($as_identifier);


		return $this;
	}


	/**
	 * @param array $aa_currentOptions
	 * @param \Cake\View\Form\ContextInterface $ao_entity
	 * @return array
	 */
	public function buildOptions(array $aa_currentOptions, Entity $ao_entity): array {
		$la_currentOptions = $aa_currentOptions;

		$lx_disabled = $this->getDisabled(true, $ao_entity, $la_currentOptions);
		$lx_options = $this->getOptions(true, $ao_entity, $la_currentOptions);
		$lx_readonly = $this->getReadonly(true, $ao_entity, $la_currentOptions);
		$lx_value = $this->getValue(true, $ao_entity, $la_currentOptions);

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
	 * @return callable|array|bool
	 * @noinspection PhpUnused
	 */
	public function getDisabled(bool $ab_evaluate = false, ?Entity $ao_entity = null, array &$aa_currentOptions = []): mixed {
		if ($ab_evaluate && is_callable($this->disabled)) {
			return call_user_func_array($this->disabled, [$ao_entity, &$aa_currentOptions]);
		}


		return $this->disabled;
	}


	/**
	 * @param callable|array|bool $ax_disabled
	 * @return self
	 * @noinspection PhpUnused
	 */
	public function setDisabled(array|bool|callable $ax_disabled): static {
		$this->disabled = $ax_disabled;


		return $this;
	}


	/**
	 * @return callable|array
	 * @noinspection PhpUnused
	 */
	public function getOptions(bool $ab_evaluate = false, ?Entity $ao_entity = null, array &$aa_currentOptions = []): mixed {
		if ($ab_evaluate && is_callable($this->options)) {
			return call_user_func_array($this->options, [$ao_entity, &$aa_currentOptions]);
		}


		return $this->options;
	}


	/**
	 * @param callable|array $ax_options
	 * @return self
	 */
	public function setOptions(mixed $ax_options): static {
		$this->options = $ax_options;


		return $this;
	}


	/**
	 * @return callable|bool
	 * @noinspection PhpUnused
	 */
	public function getReadonly(bool $ab_evaluate = false, ?Entity $ao_entity = null, array &$aa_currentOptions = []): mixed {
		if ($ab_evaluate && is_callable($this->readonly)) {
			return call_user_func_array($this->readonly, [$ao_entity, &$aa_currentOptions]);
		}


		return $this->readonly;
	}


	/**
	 * @param callable|bool $ax_readonly
	 * @return AttributeOptions
	 * @noinspection PhpUnused
	 */
	public function setReadonly(bool|callable $ax_readonly): static {
		$this->readonly = $ax_readonly;


		return $this;
	}


	/**
	 * @param bool $ab_evaluate
	 * @param mixed|null $ax_value
	 * @param Entity|null $ao_entity
	 * @param array $aa_currentOptions
	 * @return mixed
	 */
	public function getScalar(bool $ab_evaluate = false, mixed $ax_value = null, ?Entity $ao_entity = null, array &$aa_currentOptions = []): mixed {
		if ($ab_evaluate && is_callable($this->toScalar)) {
			return call_user_func_array($this->toScalar, [$ax_value, $ao_entity, &$aa_currentOptions]);
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
	public function getValue(bool $ab_evaluate = false, ?Entity $ao_entity = null, array &$aa_currentOptions = []): mixed {
		if ($ab_evaluate && is_callable($this->value)) {
			return call_user_func_array($this->value, [$ao_entity, &$aa_currentOptions]);
		}


		return $this->value;
	}


	/**
	 * @param mixed $ax_value
	 * @return AttributeOptions
	 */
	public function setValue(mixed $ax_value): static {
		$this->value = $ax_value;


		return $this;
	}


	/**
	 * @param array $ax_value
	 * @param Entity|null $ao_entity
	 * @return string|bool
	 */
	public function validateValue(mixed $ax_value, ?Entity $ao_entity): bool|string {
		$lx_validate = $this->getValidate();

		//No validation? Every value is valid.
		if ($lx_validate === false) {
			return true;
		}
		elseif ($lx_validate === null) {
			$lx_disabled = $this->getDisabled(true, $ao_entity);

			//Disabled means no value is allowed
			if (in_array($lx_disabled, ['disabled', true], true) && !empty($ax_value)) {
				return false;
			}

			$lx_value = $ax_value;
			if (!is_scalar($lx_value) && $lx_value !== null) {
				$lx_value = $this->getScalar(true, $lx_value, $ao_entity);
			}

			$lb_inOptions = array_key_exists($lx_value, $this->getOptions(true, $ao_entity));
			$lb_inDisabled = is_array($lx_disabled) && in_array($lx_value, $lx_disabled) ? $lx_disabled : false;


			return $lb_inOptions && !$lb_inDisabled;
		}
		elseif (is_callable($lx_validate)) {
			return $lx_validate($ax_value, $ao_entity, $this);
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
	 * @param mixed $ax_validate
	 * @return AttributeOptions
	 * @noinspection PhpUnused
	 */
	public function setValidate(mixed $ax_validate = null): static {
		$this->validate = $ax_validate;


		return $this;
	}
}
