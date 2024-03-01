<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\SchemaInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\ORM\Association;
use Cake\Utility\Hash;
use RuntimeException;
use UnhandledMatchError;


/**
 * This behavior offers a `newDefaultEntity`-method on the table object which will
 * - create an entity
 * - load the table schema
 * - set the entity properties to the database default values
 *
 * Integrated NullBehavior from "dereuromark"
 *
 * @see https://github.com/dereuromark/cakephp-shim
 * @see https://github.com/dereuromark/cakephp-shim/blame/master/src/Model/Behavior/NullableBehavior.php
 */
class DefaultValuesBehavior extends Behavior {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'enabled' => true,
		'implementedEvents' => [
			'beforeMarshal',
		],
		'implementedMethods' => [
			'newDefaultEntity' => 'newDefaultEntity',
		],
	];


	/**
	 * Returns a new entity for the table the method was called on,
	 * populated with the default values set in the database.
	 *
	 * @param array $aa_additionalData
	 * @param array $aa_options
	 * @return EntityInterface
	 */
	public function newDefaultEntity(array $aa_additionalData = [], array $aa_options = []): EntityInterface {
		if (!$this->getConfig('enabled')) {
			//Calling this method when the behavior is disabled results in an exception
			throw new RuntimeException(sprintf('The method `newDefaultEntity()` is not available since the `%s` Behavior is not enabled', static::class));
		}

		//Retreive the class that's used by the table for the creation of new entities
		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $this->table()->getEntityClass();
		/** @var \Awyiss\Model\Entity $lo_entity */
		$lo_entity = new $ls_entityClass([], ['source' => $this->table()->getRegistryAlias()]);

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$lo_schema = $lo_table->getSchema();
		//Get the default values
		$la_defaults = $lo_schema->defaultValues();

		$la_defaults += array_combine($lo_schema->columns(), array_fill(0, count($lo_schema->columns()), null));

		//No primary keys
		$la_defaults = array_diff_key($la_defaults, array_flip($lo_schema->getPrimaryKey()));

		//Typecast the defaults based on the schema
		$this->typecastDefaults($la_defaults, $lo_schema);

		if ($lo_table->hasAttributes()) {
			/** @var \Cake\ORM\Association&\Awyiss\Model\Table $lo_attributes */
			$lo_attributes = $lo_table->getAssociation($lo_table->getAttributesTableName(true));
			$la_defaults[ $ls_entityClass::mapField($lo_attributes->getProperty()) ] = $lo_attributes->newDefaultEntity();
		}

		if (
			$lo_table->hasBehavior('Categories') &&
			$lo_table->getBehavior('Categories')->getConfig('enabled') === true &&
			($aa_options['includeCategory'] ?? true) === true
		) {
			$this->addCategoryDefault($la_defaults, $lo_table, $lo_attributes ?? null);
		}

		$la_additionalData = $aa_additionalData;
		if ($aa_additionalData) {
			//Map the fields in case the additional data contains mapped keys
			$la_additionalData = $ls_entityClass::unmapFields($aa_additionalData, true);
		}


		return $this->marshallDefaults($lo_entity, $la_defaults, $la_additionalData, $aa_options);
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \ArrayObject $ao_data
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal(EventInterface $ao_event, ArrayObject $ao_data, ArrayObject $ao_options): void {
		$this->processArray($ao_data, $this->_table);
	}


	/**
	 * @param array $aa_defaults
	 * @param \Awyiss\Model\Table $ao_table
	 * @param \Cake\ORM\Association|null $ao_attributes
	 * @return void
	 */
	protected function addCategoryDefault(array &$aa_defaults, Table $ao_table, ?Association $ao_attributes = null): void {
		/** @var \Awyiss\Model\Behavior\CategoriesBehavior $lo_categories */
		$lo_categories = $ao_table->getBehavior('Categories');

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $ao_table->getEntityClass();

		$ls_column = $lo_categories->getConfig('fieldname') ?: $lo_categories->getConfig('identifier');
		$ls_column = $ls_entityClass::unmapField($ls_column);


		if ($ao_attributes) {
			if ($ao_attributes->getSchema()->getColumn($ls_column)) {
				$aa_defaults[ $ao_attributes->getProperty() ][ $ls_column ] = $lo_categories->getConfig('selectedCategory');
			}
		}
		else {
			if ($ao_table->getSchema()->getColumn($ls_column)) {
				$aa_defaults[ $ls_column ] = $lo_categories->getConfig('selectedCategory');
			}
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param array $aa_data
	 * @param array $aa_additionalData
	 * @param array $aa_options
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function marshallDefaults(EntityInterface $ao_entity, array $aa_defaults, array $aa_additionalData, array $aa_options): EntityInterface {
		/** @var \Awyiss\Model\Entity $ao_entity */
		$la_defaults = $aa_additionalData + $ao_entity->defaultValues() + $aa_defaults;

		$la_options = $aa_options + [
			'fields' => array_keys($la_defaults),
			'setter' => false,
			'validate' => false,
		];

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$lo_marshaller = $lo_table->marshaller();


		return $lo_marshaller->merge($ao_entity, $la_defaults, $la_options);
	}


	/**
	 * Copyright (c) 2024 Awyiss
	 * Copyright (c) 2019 Mark Scherer
	 *
	 * @param \ArrayObject|array $aa_data
	 * @param \Cake\ORM\Table $ao_table
	 * @return \ArrayObject|array
	 * @copyright https://github.com/dereuromark/cakephp-shim/tree/master
	 */
	protected function processArray(ArrayObject|array $aa_data, Table $ao_table): ArrayObject|array {
		$la_associations = [];

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $ao_table->getEntityClass();

		/** @var \Cake\ORM\Association $lo_association */
		foreach ($ao_table->associations() as $lo_association) {
			$la_associations[ $ls_entityClass::unmapField($lo_association->getProperty()) ] = $lo_association->getName();
		}

		$la_data = $aa_data;
		foreach ($la_data as $ls_key => $lx_value) {
			if (is_numeric($ls_key)) {
				continue;
			}

			if (array_key_exists($ls_key, $la_associations)) {
				if ($lx_value !== null) {
					if ($lx_value === '') {
						$lx_value = $ls_key === 'attributes' ? [] : null;
					}
					elseif ($lx_value instanceof EntityInterface) {
						$lx_value = $this->processEntity(
							$lx_value,
							$ao_table->getAssociation($la_associations[ $ls_entityClass::unmapField($ls_key) ])->getTarget()
						);
					}
					else {
						$lx_value = $this->processArray(
							$lx_value,
							$ao_table->getAssociation($la_associations[ $ls_entityClass::unmapField($ls_key) ])->getTarget()
						);
					}

					$la_data[ $ls_key ] = $lx_value;
				}

				continue;
			}

			$lb_nullable = Hash::get((array)$ao_table->getSchema()->getColumn($ls_entityClass::unmapField($ls_key)), 'null');

			if ($lb_nullable !== true) {
				continue;
			}

			if ($lx_value !== '') {
				continue;
			}

			$lx_default = Hash::get((array)$ao_table->getSchema()->getColumn($ls_entityClass::unmapField($ls_key)), 'default');
			$la_data[ $ls_key ] = $lx_default;
		}


		return $la_data;
	}


	/**
	 * Copyright (c) 2024 Awyiss
	 * Copyright (c) 2019 Mark Scherer
	 *
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @param \Cake\ORM\Table $ao_table
	 * @return \Cake\Datasource\EntityInterface
	 * @copyright https://github.com/dereuromark/cakephp-shim/tree/master
	 */
	protected function processEntity(EntityInterface $ao_entity, Table $ao_table): EntityInterface {
		$la_associations = [];
		/** @var \Cake\ORM\Association $lo_association */
		foreach ($ao_table->associations() as $lo_association) {
			$la_associations[ $ao_entity::mapField($lo_association->getProperty()) ] = $lo_association->getName();
		}

		foreach ($ao_entity->getDirty() as $ls_field) {
			$lx_value = $ao_entity->get($ls_field);

			if (array_key_exists($ls_field, $la_associations)) {
				if ($lx_value !== null) {
					if ($lx_value === '') {
						$lx_value = null;
					}
					elseif ($lx_value instanceof EntityInterface) {
						$lx_value = $this->processEntity($lx_value, $ao_table->getAssociation($la_associations[ $ls_field ])->getTarget());
					}
					elseif (is_array($lx_value) || $lx_value instanceof ArrayObject) {
						$lx_value = $this->processArray($lx_value, $ao_table->getAssociation($la_associations[ $ls_field ])->getTarget());
					}

					$ao_entity->set($ls_field, $lx_value);
				}

				continue;
			}


			if ($ao_entity instanceof Entity) {
				$ls_field = $ao_entity::unmapField($ls_field);
			}

			$lb_nullable = Hash::get((array)$ao_table->getSchema()->getColumn($ls_field), 'null');

			if ($lb_nullable !== true) {
				continue;
			}

			if ($lx_value !== '') {
				continue;
			}

			$lx_default = Hash::get((array)$ao_table->getSchema()->getColumn($ls_field), 'default');
			$ao_entity->set($ls_field, $lx_default);
		}


		return $ao_entity;
	}


	/**
	 * @param array $aa_defaults
	 * @param \Cake\Datasource\SchemaInterface $ao_schema
	 * @return void
	 */
	protected function typecastDefaults(array &$aa_defaults, SchemaInterface $ao_schema): void {
		$la_typeMap = $ao_schema->typeMap();

		foreach ($aa_defaults as $ls_column => &$lx_default) {
			if (is_null($lx_default)) {
				//No default value? That's already the entities default.
				continue;
			}

			//Typecast each default value, depending on the column type
			try {
				$lx_default = match ($la_typeMap[ $ls_column ]) {
					'boolean' => boolval($lx_default),
					'date' => $lx_default ? new Date($lx_default) : null,
					'datetime' => $lx_default ? new DateTime($lx_default) : null,
					'float' => floatval($lx_default),
					'integer' => intval($lx_default),
					'json' => json_decode(trim($lx_default, '\'')),
					'string', 'text' => strval($lx_default),
					'time' => $lx_default ? new Time($lx_default) : null,
				};
			}
			catch (UnhandledMatchError) {
				if (str_starts_with($la_typeMap[ $ls_column ], 'enum-')) {
					$lo_dbType = TypeFactory::build($la_typeMap[ $ls_column ]);
					if ($lo_dbType instanceof EnumType) {
						$lx_default = $lo_dbType->toPHP($lx_default, $this->table()->getConnection()->getDriver());


						return;
					}
				}

				dd($la_typeMap[ $ls_column ], __FILE__, __LINE__, $lx_default);
			}
		}
		unset($lx_default);
	}
}
