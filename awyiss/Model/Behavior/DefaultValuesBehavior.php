<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\SchemaInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\ORM\Association;
use RuntimeException;
use UnhandledMatchError;


/**
 * This behavior offers a `newDefaultEntity`-method on the table object which will
 * - create an entity
 * - load the table schema
 * - set the entity properties to the database default values
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
		'implementedEvents' => [],
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
		$ls_entityClass = $this->table()->getEntityClass();
		/** @var EntityInterface $lo_entity */
		$lo_entity = new $ls_entityClass([], ['source' => $this->table()->getRegistryAlias()]);

		$lo_schema = $this->table()->getSchema();
		//Get the default values
		$la_defaults = $lo_schema->defaultValues();

		//Typecast the defaults based on the schema
		$this->typecastDefaults($la_defaults, $lo_schema);

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		if ($lo_table->hasAttributes()) {
			/** @var \Cake\ORM\Association&\Awyiss\Model\Table $lo_attributes */
			$lo_attributes = $lo_table->getAssociation($lo_table->getAttributesTable(true));
			$la_defaults[ $lo_attributes->getProperty() ] = $lo_attributes->newDefaultEntity();
		}

		if ($lo_table->hasBehavior('Categories') && ($aa_options['includeCategory'] ?? true) === true) {
			$this->addCategoryDefault($la_defaults, $lo_table, $lo_attributes ?? null);
		}


		return $this->marshallDefaults($lo_entity, $la_defaults, $aa_additionalData, $aa_options);
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

		$ls_column = $lo_categories->getConfig('fieldname') ?: $lo_categories->getConfig('identifier');

		if ($ao_attributes) {
			$aa_defaults[ $ao_attributes->getProperty() ][ $ls_column ] = $lo_categories->getConfig('selectedCategory');
		}
		else {
			$aa_defaults[ $ls_column ] = $lo_categories->getConfig('selectedCategory');
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param array $aa_data
	 * @param array $aa_additionalData
	 * @param array $aa_options
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function marshallDefaults(EntityInterface $ao_entity, array $aa_data, array $aa_additionalData, array $aa_options): EntityInterface {
		/** @var \Awyiss\Model\Entity $ao_entity */
		$la_defaults = $aa_additionalData + $ao_entity->defaultValues() + $aa_data;

		$la_options = $aa_options + [
			'fields' => array_keys($la_defaults),
			'validate' => false,
		];

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$lo_marshaller = $lo_table->marshaller();


		return $lo_marshaller->merge($ao_entity, $la_defaults, $la_options);
	}


	/**
	 * @param array $aa_defaults
	 * @param \Cake\Datasource\SchemaInterface $ao_schema
	 * @return void
	 */
	protected function typecastDefaults(array $aa_defaults, SchemaInterface $ao_schema): void {
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
				dd($la_typeMap[ $ls_column ], __FILE__, __LINE__);
			}
		}
		unset($lx_default);
	}
}
