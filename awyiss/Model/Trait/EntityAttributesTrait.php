<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Model\Entity;
use Awyiss\ORM\Association\HasOne;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use InvalidArgumentException;


/**
 * Adds attribute-specific logic to entities
 */
trait EntityAttributesTrait {
	/**
	 * Constructor
	 *
	 * @param array $properties
	 * @param array $options
	 */
	public function __construct(array $properties = [], array $options = []) {
		parent::__construct($properties, $options);

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());
		if ($lo_table->hasAttributes()) {
			/** @var HasOne $lo_association */
			$lo_association = $lo_table->getAssociation($lo_table->getAttributesTableName(true));

			$this->initAttributesField($lo_association, $lo_association->getForeignKey());
		}
	}


	/**
	 * Sets each of the attribute's values as
	 *
	 * @param \Awyiss\ORM\Association\HasOne|\Awyiss\Model\Table $attributesTable
	 * @param string $foreignKey
	 * @return void
	 */
	public function initAttributesField(HasOne $attributesTable, string $foreignKey): void {
		$lo_attributes = $this->_fields['attributes'] ?? null;

		if (!$lo_attributes || !is_a($lo_attributes, Entity::class)) {
			return;
		}

		$la_translatableFields = [];
		if ($attributesTable->hasBehavior('Translate')) {
			$la_translatableFields = $attributesTable->getBehavior('Translate')->getConfig('fields', []);
		}

		/** @var \Cake\Datasource\EntityInterface $lo_attributes */
		foreach ($lo_attributes->_fields as $ls_key => $lx_value) {
			if (in_array($ls_key, ['id', $foreignKey, '_i18n', '_translations'])) {
				continue;
			}

			if (str_ends_with($ls_key, '_translation') && in_array(substr($ls_key, 0, -12), $la_translatableFields)) {
				continue;
			}

			$this->setVirtual([$ls_key], true);
		}
	}


	/**
	 * @inheritDoc
	 */
	public function &get(string $field) {
		if ($field === '') {
			throw new InvalidArgumentException('Cannot get an empty field');
		}

		$lx_value = null;

		if (isset($this->_fields[ $field ])) {
			$lx_value = &$this->_fields[ $field ];
		}

		$ls_method = static::_accessor($field, 'get');
		if ($ls_method) {
			$lx_value = $this->{$ls_method}($lx_value);
		}

		//Return a value if found or if an accessor exists.
		if (!is_null($lx_value) || $ls_method || $field === '_translations') {
			return $lx_value;
		}

		/**
		 * @noinspection PhpUnnecessaryLocalVariableInspection Does this sound _unnecessary_ to you?
		 *    Uncaught ParseError: syntax error, unexpected token "&", expecting ";"
		 */
		$lx_value = &$this->getFromAttribute($field);


		return $lx_value;
	}


	/**
	 * Return the value of a field of the attached attribute entity (or null)
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function &getFromAttribute(string $field): mixed {
		$lx_value = null;

		// No attributes field = no value to fetch from there
		if (empty($this->_fields['attributes']) || !($this->_fields['attributes'] instanceof Entity)) {
			return $lx_value;
		}

		/** @var \Cake\Datasource\EntityInterface $lo_attributesEntity */
		$lo_attributesEntity = $this->_fields['attributes'];

		$ls_field = $field;
		if (str_starts_with($ls_field, 'attributes.')) {
			$ls_field = substr($ls_field, 11);
		}

		/**
		 * @noinspection PhpUnnecessaryLocalVariableInspection Does this sound _unnecessary_ to you?
		 *    Uncaught ParseError: syntax error, unexpected token "&", expecting ";"
		 */
		$lx_value = &$lo_attributesEntity->get($ls_field);


		return $lx_value;
	}


	/**
	 * @inheritDoc
	 * @param array|string $field
	 * @param mixed|null $value
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function set(array|string $field, mixed $value = null, array $options = []): EntityInterface {
		if (is_string($field) && in_array($field, ['_locale', '_translations'])) {
			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return parent::set($field, $value, $options);
		}

		$lx_field = $field;

		if (($this->_fields['attributes'] ?? null) instanceof Entity) {
			/** @var Entity $lo_attributes */
			$lo_attributes = $this->_fields['attributes'];

			if (is_string($lx_field) && $lo_attributes->has($lx_field)) {
				$lo_attributes->set($lx_field, $value, $options);

				return $this;
			}
			elseif (is_array($lx_field)) {
				$la_attributeFields = [];
				foreach ($lx_field as $ls_field => $lx_value) {
					if (in_array($ls_field, ['_locale', '_translations'])) {
						continue;
					}

					if ($lo_attributes->has($ls_field)) {
						$la_attributeFields[ $ls_field ] = $lx_value;
						unset($lx_field[ $ls_field ]);
					}
				}

				$lo_attributes->set($la_attributeFields, $value, $options);
			}
		}


		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::set($lx_field, $value, $options);
	}


	/**
	 * @inheritDoc
	 * @param array|string $values
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function patch(array $values, array $options = []): EntityInterface {
		if (($this->_fields['attributes'] ?? null) instanceof Entity) {
			/** @var \Awyiss\Model\Entity $lo_attributes */
			$lo_attributes = $this->_fields['attributes'];

			$la_attributeFields = [];
			foreach ($values as $ls_field => $lx_value) {
				if (in_array($ls_field, ['_locale', '_translations'])) {
					continue;
				}

				if ($lo_attributes->has($ls_field)) {
					$la_attributeFields[ $ls_field ] = $lx_value;
					/** @noinspection PhpVariableNamingConventionInspection */
					unset($values[ $ls_field ]);
				}
			}

			$lo_attributes->patch($la_attributeFields, $options);
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
