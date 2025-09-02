<?php

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use BackedEnum;
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
use ReflectionEnum;
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
	 * @param array $additionalData
	 * @param array $options
	 * @return EntityInterface
	 */
	public function newDefaultEntity(array $additionalData = [], array $options = []): EntityInterface {
		if (!$this->getConfig('enabled')) {
			// Calling this method when the behavior is disabled results in an exception
			throw new RuntimeException(sprintf('The method `newDefaultEntity()` is not available since the `%s` Behavior is not enabled', static::class));
		}

		// Retrieve the class that's used by the table for the creation of new entities
		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $this->table()->getEntityClass();
		/** @var \Awyiss\Model\Entity $lo_entity */
		$lo_entity = new $ls_entityClass([], ['source' => $this->table()->getRegistryAlias()]);

		$lo_table = $this->table();

		$lo_schema = $lo_table->getSchema();
		//Get the default values
		$la_defaults = $lo_schema->defaultValues();

		$la_defaults += array_combine($lo_schema->columns(), array_fill(0, count($lo_schema->columns()), null));

		//No primary keys
		$la_defaults = array_diff_key($la_defaults, array_flip($lo_schema->getPrimaryKey()));

		//Typecast the defaults based on the schema
		$this->typecastDefaults($la_defaults, $lo_schema);

		$la_additionalData = $additionalData;
		if ($additionalData) {
			// Unmap the fields in case the additional data contains mapped keys
			$la_additionalData = $ls_entityClass::unmapFields($additionalData, true);
		}

		if (
			$lo_table->hasBehavior('Categories') &&
			$lo_table->getBehavior('Categories')->getConfig('enabled') === true &&
			($options['includeCategory'] ?? true) === true
		) {
			$this->addCategoryDefault($la_defaults, $lo_table, $lo_attributesTable ?? null);
		}

		if ($lo_table->hasAttributes()) {
			/** @var \Cake\ORM\Association&\Awyiss\Model\Table $lo_attributesTable */
			$lo_attributesTable = $lo_table->getAssociation($lo_table->getAttributesTableName(true));
			/** @var \Awyiss\Model\Entity $ls_attributesEntityClass */
			$ls_attributesEntityClass = $lo_attributesTable->getEntityClass();
			$la_attributeColumns = $lo_attributesTable->getSchema()->columns();
			$la_attributeData = $la_additionalData['attributes'] ?? [];

			// Check if any of the attribute columns are part of the additional data directly
			foreach ($la_attributeColumns as $ls_column) {
				$ls_column = $ls_attributesEntityClass::unmapField($ls_column);
				if (array_key_exists($ls_column, $la_additionalData)) {
					$la_attributeData[ $ls_column ] = $la_additionalData[ $ls_column ];
					unset($la_additionalData[ $ls_column ]);
					continue;
				}

				$ls_column = $ls_attributesEntityClass::mapField($ls_column);
				if (array_key_exists($ls_column, $la_additionalData)) {
					$la_attributeData[ $ls_column ] = $la_additionalData[ $ls_column ];
					unset($la_additionalData[ $ls_column ]);
				}
			}

			$la_defaults['attributes'] = $lo_attributesTable->newDefaultEntity($la_attributeData);
			/** @noinspection PhpVariableNamingConventionInspection */
			unset($la_additionalData['attributes']);
		}

		$lo_entity = $this->marshallDefaults($lo_entity, $la_defaults, $la_additionalData, $options);

		//Set the entity to the attributes entity
		if ($lo_table->hasAttributes()) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_entity->attributes->setEntity($lo_entity);
		}


		return $lo_entity;
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		/** @noinspection PhpParamsInspection */
		$this->processArray($data, $this->_table);
	}


	/**
	 * @param array $defaults
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\ORM\Association|null $attributes
	 * @return void
	 */
	protected function addCategoryDefault(array &$defaults, Table $table, ?Association $attributes = null): void {
		/** @var \Awyiss\Model\Behavior\CategoriesBehavior $lo_categories */
		$lo_categories = $table->getBehavior('Categories');

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $table->getEntityClass();

		$ls_column = $lo_categories->getConfig('field') ?: $lo_categories->getConfig('identifier');
		$ls_column = $ls_entityClass::unmapField($ls_column);

		if ($attributes && $attributes->getSchema()->getColumn($ls_column)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$defaults[ $attributes->getProperty() ][ $ls_column ] = $lo_categories->getConfig('selectedCategory');
		}

		if ($table->getSchema()->getColumn($ls_column)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$defaults[ $ls_column ] = $lo_categories->getConfig('selectedCategory');
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $defaults
	 * @param array $additionalData
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function marshallDefaults(EntityInterface $entity, array $defaults, array $additionalData, array $options): EntityInterface {
		$la_defaults = $additionalData;
		/** @var \Awyiss\Model\Entity $entity */
		$la_defaults += $entity::unmapFields($entity->defaultValues(), true);
		$la_defaults += $defaults;

		$la_options = $options + [
			'fields' => array_keys($la_defaults),
			'setter' => false,
			'validate' => false,
			'events' => false,
		];

		$lo_marshaller = $this->table()->marshaller();

		return $lo_marshaller->merge($entity, $la_defaults, $la_options);
	}


	/**
	 * Copyright (c) 2024 Awyiss
	 * Copyright (c) 2019 Mark Scherer
	 *
	 * @param \ArrayObject $data
	 * @param \Awyiss\Model\Table $table
	 * @return void
	 * @copyright https://github.com/dereuromark/cakephp-shim/tree/master
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function processArray(ArrayObject $data, Table $table): void {
		$la_associations = [];

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $table->getEntityClass();

		/** @var \Cake\ORM\Association $lo_association */
		foreach ($table->associations() as $lo_association) {
			$la_associations[ $ls_entityClass::unmapField($lo_association->getProperty()) ] = $lo_association->getName();
		}

		foreach ($data as $ls_key => $lx_value) {
			if (is_numeric($ls_key)) {
				continue;
			}

			if (array_key_exists($ls_key, $la_associations)) {
				if ($lx_value === null) {
					continue;
				}

				if ($lx_value === '') {
					$lx_value = $ls_key === 'attributes' ? [] : null;
				}
				elseif ($lx_value instanceof EntityInterface) {
					/** @noinspection PhpParamsInspection */
					$lx_value = $this->processEntity(
						$lx_value,
						$table->getAssociation($la_associations[ $ls_entityClass::unmapField($ls_key) ])->getTarget()
					);
				}
				elseif (is_array($lx_value) || $lx_value instanceof ArrayObject) {
					if (!$lx_value instanceof ArrayObject) {
						$lx_value = new ArrayObject($lx_value);
					}

					/** @noinspection PhpParamsInspection */
					$this->processArray(
						$lx_value,
						$table->getAssociation($la_associations[ $ls_entityClass::unmapField($ls_key) ])->getTarget()
					);

					$lx_value = $lx_value->getArrayCopy();
				}

				$data[ $ls_key ] = $lx_value;

				continue;
			}

			$lb_nullable = Hash::get((array)$table->getSchema()->getColumn($ls_entityClass::unmapField($ls_key)), 'null');

			if ($lb_nullable !== true) {
				continue;
			}

			if ($lx_value !== '') {
				continue;
			}

			$lx_default = Hash::get((array)$table->getSchema()->getColumn($ls_entityClass::unmapField($ls_key)), 'default');
			$data[ $ls_key ] = $lx_default;
		}
	}


	/**
	 * Copyright (c) 2024 Awyiss
	 * Copyright (c) 2019 Mark Scherer
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Model\Table $table
	 * @return \Cake\Datasource\EntityInterface
	 * @copyright https://github.com/dereuromark/cakephp-shim/tree/master
	 */
	protected function processEntity(EntityInterface $entity, Table $table): EntityInterface {
		$la_associations = [];
		/** @var \Cake\ORM\Association $lo_association */
		foreach ($table->associations() as $lo_association) {
			$la_associations[ $entity::mapField($lo_association->getProperty()) ] = $lo_association->getName();
		}

		foreach ($entity->getDirty() as $ls_field) {
			$lx_value = $entity->get($ls_field);

			if (array_key_exists($ls_field, $la_associations)) {
				if ($lx_value !== null) {
					if ($lx_value === '') {
						$lx_value = null;
					}
					elseif ($lx_value instanceof EntityInterface) {
						/** @noinspection PhpParamsInspection */
						$lx_value = $this->processEntity($lx_value, $table->getAssociation($la_associations[ $ls_field ])->getTarget());
					}
					elseif (is_array($lx_value) || $lx_value instanceof ArrayObject) {
						if (!$lx_value instanceof ArrayObject) {
							$lx_value = new ArrayObject($lx_value);
						}

						/** @noinspection PhpParamsInspection */
						$this->processArray($lx_value, $table->getAssociation($la_associations[ $ls_field ])->getTarget());

						$lx_value = $lx_value->getArrayCopy();
					}

					$entity->set($ls_field, $lx_value);
				}

				continue;
			}


			if ($entity instanceof Entity) {
				$ls_field = $entity::unmapField($ls_field);
			}

			$lb_nullable = Hash::get((array)$table->getSchema()->getColumn($ls_field), 'null');

			if ($lb_nullable !== true) {
				continue;
			}

			if ($lx_value !== '') {
				continue;
			}

			$lx_default = Hash::get((array)$table->getSchema()->getColumn($ls_field), 'default');
			$entity->set($ls_field, $lx_default);
		}


		return $entity;
	}


	/**
	 * @param array $defaults
	 * @param \Cake\Datasource\SchemaInterface $schema
	 * @return void
	 */
	protected function typecastDefaults(array &$defaults, SchemaInterface $schema): void {
		$la_typeMap = $schema->typeMap();

		foreach ($defaults as $ls_column => &$lx_default) {
			if (is_null($lx_default)) {
				//No default value? That's already the entities default.
				continue;
			}

			if (str_starts_with($la_typeMap[ $ls_column ], 'enum-')) {
				$lo_dbType = TypeFactory::build($la_typeMap[ $ls_column ]);
				if ($lo_dbType instanceof EnumType) {
					$lx_default = $this->typecastEnumDefault($lx_default, $lo_dbType);
					continue;
				}
			}

			//Typecast each default value, depending on the column type
			try {
				$lx_default = match ($la_typeMap[ $ls_column ]) {
					'boolean' => match ($lx_default) {
						'1', 'true', 'TRUE' => true,
						'0', 'false', 'FALSE' => false,
						default => boolval($lx_default),
					},
					'date' => $lx_default ? new Date($lx_default) : null,
					'datetime' => $lx_default && $lx_default !== 'current_timestamp()' ? new DateTime($lx_default) : null,
					'float' => floatval($lx_default),
					'integer' => intval($lx_default),
					'json' => json_decode(trim($lx_default, '\'')),
					'string', 'text' => strval($lx_default),
					'time' => $lx_default ? new Time($lx_default) : null,
				};
			}
			/** @noinspection PhpMultipleClassDeclarationsInspection */
			catch (UnhandledMatchError) {
				dd($la_typeMap[ $ls_column ], __FILE__, __LINE__, $lx_default);
			}
		}
		unset($lx_default);
	}


	/**
	 * @param mixed $default
	 * @param \Cake\Database\Type\EnumType $dbType
	 * @return \BackedEnum|null
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function typecastEnumDefault(mixed $default, EnumType $dbType): ?BackedEnum {
		/**
		 * Since CakePHP 5.2.6 or .7 sqlite will return "FALSE" as default value for columns with default 0
		 * and "TRUE" for columns with default 1.
		 * This does not match the enum backing type and will throw an exception in EnumType::toPHP().
		 * Here we convert "FALSE" to 0 and "TRUE" to 1 for int backed enums.
		 */
		$ls_className = $dbType->getEnumClassName();

		// Check if it's an int-backed enum using reflection
		$lb_isIntBackedEnum = false;
		if (enum_exists($ls_className)) {
			$lo_reflection = new ReflectionEnum($ls_className);
			$lb_isIntBackedEnum = $lo_reflection->getBackingType()->getName() === 'int';
		}

		if ($lb_isIntBackedEnum) {
			$default = match ($default) {
				'true', 'TRUE' => 1,
				'false', 'FALSE' => 0,
				default => intval($default),
			};
		}

		return $dbType->toPHP($default, $this->table()->getConnection()->getDriver());
	}
}
