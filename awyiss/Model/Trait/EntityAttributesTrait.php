<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\MissingPropertyException;
use InvalidArgumentException;


/**
 * Adds attribute-specific logic to entities
 */
trait EntityAttributesTrait {
	/**
	 * @inheritDoc
	 */
	public function &get(string $field) {
		return $this->getRequiredOrFail($field, false);
	}


	/**
	 * @inheritDoc
	 */
	public function &getRequiredOrFail(string $field, bool $requireFieldPresence = true): mixed {
		if ($field === '') {
			throw new InvalidArgumentException('Cannot get an empty field');
		}

		$value = null;

		if (isset($this->_fields[ $field ])) {
			$value = &$this->_fields[ $field ];
		}

		$method = static::_accessor($field, 'get');
		if ($method) {
			$value = $this->{$method}($value);
		}

		// Return a value if found or if an accessor exists.
		if (!is_null($value) || $method || $field === '_translations') {
			return $value;
		}

		// If the field is the foreign key of the attributes, do not return it
		$source = $this->getSource();
		$foreignKey = Inflector::variable(Inflector::singularize($source) . '_id');
		if ($field === $foreignKey) {
			return $value;
		}

		/**
		 * @noinspection PhpUnnecessaryLocalVariableInspection Does this sound _unnecessary_ to you?
		 *    Uncaught ParseError: syntax error, unexpected token "&", expecting ";"
		 */
		$value = &$this->getFromAttribute($field, false);


		return $value;
	}


	/**
	 * Return the value of a field of the attached attribute entity (or null)
	 *
	 * @param string $field
	 * @param bool $requireFieldPresence
	 * @return mixed
	 */
	public function &getFromAttribute(string $field, bool $requireFieldPresence = true): mixed {
		$value = null;

		// No attributes field = no value to fetch from there
		if (empty($this->_fields['attributes']) || !($this->_fields['attributes'] instanceof Entity)) {
			if ($requireFieldPresence) {
				throw new MissingPropertyException([
					'property' => $field,
					'entity' => $this::class,
				]);
			}

			return $value;
		}

		/** @var \Cake\Datasource\EntityInterface $attributesEntity */
		$attributesEntity = $this->_fields['attributes'];

		if (str_starts_with($field, 'attributes.')) {
			$field = substr($field, 11);
		}

		/**
		 * @noinspection PhpUnnecessaryLocalVariableInspection Does this sound _unnecessary_ to you?
		 *    Uncaught ParseError: syntax error, unexpected token "&", expecting ";"
		 */
		$value = &$attributesEntity->getRequiredOrFail($field, $requireFieldPresence);


		return $value;
	}


	/**
	 * @inheritDoc
	 * @param array|string $field
	 * @param mixed|null $value
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function set(array|string $field, mixed $value = null, array $options = []): EntityInterface {
		if (
			is_array($field)
			|| in_array($field, ['_locale', '_translations'])
		) {
			/**
			 * Let the parent method handle an array of fields
			 * and '_locale' or '_translations' fields.
			 *
			 * Since CakePHP 5.2.0, setting an array of fields
			 * is deprecated and will throw an exception in the future.
			 *
			 * @noinspection PhpIncompatibleReturnTypeInspection
			 */
			return parent::set($field, $value, $options);
		}

		/**
		 * If no attributes field is set,
		 * or if it is not an Entity,
		 * or if the field is not part of the attributes,
		 * let the parent method handle it
		 */
		if (
			!($this->_fields['attributes'] ?? null) instanceof Entity
			|| !$this->_fields['attributes']->has($field)
		) {
			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return parent::set($field, $value, $options);
		}

		/** @var \Awyiss\Model\Entity $attributes */
		$attributes = $this->_fields['attributes'];

		// Set the value in the attributes field
		$attributes->set($field, $value, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 * @param array|string $values
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function patch(array $values, array $options = []): EntityInterface {
		if (($this->_fields['attributes'] ?? null) instanceof Entity) {
			/** @var \Awyiss\Model\Entity $attributes */
			$attributes = $this->_fields['attributes'];

			$attributeFields = [];
			foreach ($values as $field => $value) {
				if (in_array($field, ['_locale', '_translations'])) {
					continue;
				}

				if ($attributes->has($field)) {
					$attributeFields[ $field ] = $value;
					unset($values[ $field ]);
				}
			}

			$attributes->patch($attributeFields, $options);
		}


		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::patch($values, $options);
	}


	/**
	 * Don't use the `has`-call that CakePHP introduced
	 *
	 * @param string $field
	 * @return bool
	 */
	public function __isset(string $field): bool {
		return $this->get($field) !== null;
	}
}
