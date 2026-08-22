<?php

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Inflector;
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
	protected array $_defaultConfig = [ // phpcs:ignore
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
			throw new RuntimeException(
				sprintf('The method `newDefaultEntity()` is not available since the `%s` Behavior is not enabled', static::class)
			);
		}

		// Retrieve the class that's used by the table for the creation of new entities
		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $this->table()->getEntityClass();
		/** @var \Awyiss\Model\Entity $entity */
		$entity = new $entityClass([], ['source' => $this->table()->getRegistryAlias()]);

		$table = $this->table();

		$schema = $table->getSchema();
		//Get the default values
		$defaults = $schema->defaultValues();

		$defaults += array_combine($schema->columns(), array_fill(0, count($schema->columns()), null));

		//No primary keys
		$defaults = array_diff_key($defaults, array_flip($schema->getPrimaryKey()));

		//Typecast the defaults based on the schema
		$this->typecastDefaults($defaults, $schema);

		if (
			$table->hasBehavior('Categories')
			&& $table->getBehavior('Categories')->getConfig('enabled') === true
			&& ($options['includeCategory'] ?? true) === true
		) {
			$this->addCategoryDefault($defaults, $table, $attributesTable ?? null);
		}

		if ($table->hasAttributes()) {
			/** @var \Cake\ORM\Association&\Awyiss\Model\Table $attributesTable */
			$attributesTable = $table->getAssociation($table->getAttributesTableName(true));
			$attributeColumns = $attributesTable->getSchema()->columns();
			$attributeData = $additionalData['attributes'] ?? [];

			// Check if any of the attribute columns are part of the additional data directly
			foreach ($attributeColumns as $column) {
				if (array_key_exists($column, $additionalData)) {
					$attributeData[ $column ] = $additionalData[ $column ];
					unset($additionalData[ $column ]);
				}
			}

			$defaults['attributes'] = $attributesTable->newDefaultEntity($attributeData);
			unset($additionalData['attributes']);
		}

		$entity = $this->marshallDefaults($entity, $defaults, $additionalData, $options);

		//Set the entity to the attributes entity
		if ($table->hasAttributes()) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity->attributes->setEntity($entity);
		}


		return $entity;
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
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
		/** @var \Awyiss\Model\Behavior\CategoriesBehavior $categories */
		$categories = $table->getBehavior('Categories');

		$column = $categories->getConfig('field') ?: $categories->getConfig('identifier');

		try {
			$categoryKeys = array_combine(
				array_map(
					fn(mixed $key) => is_string($key) ? Inflector::variable($key) : $key,
					array_keys($categories->getCategories() ?? [])
				),
				array_keys($categories->getCategories() ?? []),
			);
		}
		catch (RuntimeException) {
			$categoryKeys = [];
		}

		$selectedValue = $categories->getConfig('selectedCategory');
		$selectedValue = $categoryKeys[ $selectedValue ] ?? $selectedValue;

		if ($attributes && $attributes->getSchema()->getColumn($column)) {
			$defaults[ $attributes->getProperty() ][ $column ] = $selectedValue;
		}

		if ($table->getSchema()->getColumn($column)) {
			$defaults[ $column ] = $selectedValue;
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
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$defaults = $additionalData + $entity->defaultValues() + $defaults;

		$options += [
			'fields' => array_keys($defaults),
			'setter' => false,
			'validate' => false,
			'events' => false,
		];

		$marshaller = $this->table()->marshaller();

		return $marshaller->merge($entity, $defaults, $options);
	}


	/**
	 * Copyright (c) 2024 Awyiss
	 * Copyright (c) 2019 Mark Scherer
	 *
	 * @param \ArrayObject $data
	 * @param \Awyiss\Model\Table $table
	 * @return void
	 * @copyright https://github.com/dereuromark/cakephp-shim/tree/master
	 */
	protected function processArray(ArrayObject $data, Table $table): void {
		$associations = [];

		/** @var \Cake\ORM\Association $association */
		foreach ($table->associations() as $association) {
			$associations[ $association->getProperty() ] = $association->getName();
		}

		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				continue;
			}

			if (is_string($value)) {
				// Trim whitespace from string values
				$value = mb_trim($value);
				$data[ $key ] = $value;
			}

			if (array_key_exists($key, $associations)) {
				if ($value === null) {
					continue;
				}

				if ($value === '') {
					$value = $key === 'attributes' ? [] : null;
				}
				elseif ($value instanceof EntityInterface) {
					/** @noinspection PhpParamsInspection */
					$value = $this->processEntity(
						$value,
						$table->getAssociation($associations[ $key ])->getTarget()
					);
				}
				elseif (is_array($value) || $value instanceof ArrayObject) {
					if (!$value instanceof ArrayObject) {
						$value = new ArrayObject($value);
					}

					/** @noinspection PhpParamsInspection */
					$this->processArray(
						$value,
						$table->getAssociation($associations[ $key ])->getTarget()
					);

					$value = $value->getArrayCopy();
				}

				$data[ $key ] = $value;

				continue;
			}

			$nullable = Hash::get((array)$table->getSchema()->getColumn($key), 'null');

			if ($nullable !== true) {
				continue;
			}

			if ($value !== '') {
				continue;
			}

			$data[ $key ] = Hash::get((array)$table->getSchema()->getColumn($key), 'default');
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
		$associations = [];
		/** @var \Cake\ORM\Association $association */
		foreach ($table->associations() as $association) {
			$associations[ $association->getProperty() ] = $association->getName();
		}

		foreach ($entity->getDirty() as $field) {
			$value = $entity->get($field);

			if (is_string($value)) {
				// Trim whitespace from string values
				$value = mb_trim($value);
				$entity->set($field, $value, ['setter' => false]);
			}

			if (array_key_exists($field, $associations)) {
				if ($value !== null) {
					if ($value === '') {
						$value = null;
					}
					elseif ($value instanceof EntityInterface) {
						/** @noinspection PhpParamsInspection */
						$value = $this->processEntity($value, $table->getAssociation($associations[ $field ])->getTarget());
					}
					elseif (is_array($value) || $value instanceof ArrayObject) {
						if (!$value instanceof ArrayObject) {
							$value = new ArrayObject($value);
						}

						/** @noinspection PhpParamsInspection */
						$this->processArray($value, $table->getAssociation($associations[ $field ])->getTarget());

						$value = $value->getArrayCopy();
					}

					$entity->set($field, $value);
				}

				continue;
			}

			$nullable = Hash::get((array)$table->getSchema()->getColumn($field), 'null');

			if ($nullable !== true) {
				continue;
			}

			if ($value !== '') {
				continue;
			}

			$default = Hash::get((array)$table->getSchema()->getColumn($field), 'default');
			$entity->set($field, $default);
		}


		return $entity;
	}


	/**
	 * @param array $defaults
	 * @param \Cake\Datasource\SchemaInterface $schema
	 * @return void
	 */
	protected function typecastDefaults(array &$defaults, SchemaInterface $schema): void {
		$typeMap = $schema->typeMap();

		foreach ($defaults as $column => &$default) {
			if (is_null($default)) {
				//No default value? That's already the entities default.
				continue;
			}

			if (str_starts_with($typeMap[ $column ], 'enum-')) {
				$dbType = TypeFactory::build($typeMap[ $column ]);
				if ($dbType instanceof EnumType) {
					$default = $this->typecastEnumDefault($default, $dbType);
					continue;
				}
			}

			//Typecast each default value, depending on the column type
			try {
				$default = match ($typeMap[ $column ]) {
					'boolean' => match ($default) {
						'1', 'true', 'TRUE' => true,
						'0', 'false', 'FALSE' => false,
						default => boolval($default),
					},
					'date' => $default ? new Date($default) : null,
					'datetime' => $default && $default !== 'current_timestamp()' ? new DateTime($default) : null,
					'float' => floatval($default),
					'integer' => intval($default),
					'json' => json_decode(trim($default, '\'')),
					'string', 'text' => strval($default),
					'time' => $default ? new Time($default) : null,
				};
			}
			/** @noinspection PhpMultipleClassDeclarationsInspection */
			catch (UnhandledMatchError) {
				dd($typeMap[ $column ], __FILE__, __LINE__, $default);
			}
		}
		unset($default);
	}


	/**
	 * @param mixed $default
	 * @param \Cake\Database\Type\EnumType $dbType
	 * @return \BackedEnum|null
	 */
	protected function typecastEnumDefault(mixed $default, EnumType $dbType): ?BackedEnum {
		/**
		 * Since CakePHP 5.2.6 or .7 sqlite will return "FALSE" as default value for columns with default 0
		 * and "TRUE" for columns with default 1.
		 * This does not match the enum backing type and will throw an exception in EnumType::toPHP().
		 * Here we convert "FALSE" to 0 and "TRUE" to 1 for int backed enums.
		 */
		$className = $dbType->getEnumClassName();

		// Check if it's an int-backed enum using reflection
		$isIntBackedEnum = false;
		if (enum_exists($className)) {
			$reflection = new ReflectionEnum($className);
			$isIntBackedEnum = $reflection->getBackingType()->getName() === 'int';
		}

		if ($isIntBackedEnum) {
			$default = match ($default) {
				'true', 'TRUE' => 1,
				'false', 'FALSE' => 0,
				default => intval($default),
			};
		}

		return $dbType->toPHP(
			$default,
			$this
				->table()
				->getConnection()
				->getDriver()
		);
	}
}
