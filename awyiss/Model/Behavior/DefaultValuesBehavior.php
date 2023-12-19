<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
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
		'enabled' => TRUE,
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
	 *
	 * @return EntityInterface
	 */
	public function newDefaultEntity (array $aa_additionalData = []): EntityInterface {
		if ( ! $this->getConfig('enabled')) {
			//Calling this method when the behavior is disabled results in an exception
			throw new RuntimeException(sprintf('The method `newDefaultEntity()` is not available since the `%s` Behavior is not enabled', static::class));
		}

		//Retreive the class that's used by the table for the creation of new entities
		$ls_entityClass = $this->table()->getEntityClass();
		/** @var EntityInterface $lo_entity */
		$lo_entity = new $ls_entityClass([], ['source' => $this->table()->getRegistryAlias()]);

		//Get the default values
		$la_defaults = $this->table()->getSchema()->defaultValues();
		//Get the column types
		$la_typeMap = $this->table()->getSchema()->typeMap();
		foreach ($la_defaults AS $ls_column => &$lx_default) {
			if (is_null($lx_default)) {
				//No default value? That's already the entities default.
				continue;
			}

			//Typecast each default value, depending on the column type
			try {
				$lx_default = match ($la_typeMap[ $ls_column ]) {
					'boolean' => boolval($lx_default),
					'date' => $lx_default ? new Date($lx_default) : NULL,
					'datetime' => $lx_default ? new DateTime($lx_default) : NULL,
					'float' => floatval($lx_default),
					'integer' => intval($lx_default),
					'json' => json_decode(trim($lx_default, '\'')),
					'string', 'text' => strval($lx_default),
					'time' => $lx_default ? new Time($lx_default) : NULL,
				};
			}
			catch (UnhandledMatchError) {
				dd($la_typeMap[ $ls_column ], __FILE__, __LINE__);
			}
		}
		unset($lx_default);

		/** @var Table $lo_table */
		$lo_table = $this->table();
		if ($lo_table->hasAttributes()) {
			/** @var Association&Table $lo_attributes */
			$lo_attributes = $lo_table->getAssociation($lo_table->getAttributesTable(TRUE));
			$la_defaults[ $lo_attributes->getProperty() ] = $lo_attributes->newDefaultEntity();
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_defaults = $aa_additionalData + $lo_entity->defaultValues() + $la_defaults;

		$la_options = [
			'fields' => array_keys($la_defaults),
			'validate' => FALSE,
		];
		$lo_marshaller = $lo_table->marshaller();


		return $lo_marshaller->merge($lo_entity, $la_defaults, $la_options);
	}
}
