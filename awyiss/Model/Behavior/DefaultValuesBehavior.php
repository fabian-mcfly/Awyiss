<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use RuntimeException;
use UnhandledMatchError;


/**
 * This behavior offers a `newDefaultEntity`-method on the table object which will
 * - create an entity
 * - load the table scheme
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
	protected $_defaultConfig = [
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
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function newDefaultEntity (array $aa_additionalData = []): EntityInterface {
		if ( ! $this->getConfig('enabled')) {
			//Calling this method when the behavior is disabled results in an exception
			throw new RuntimeException(sprintf('The method `newDefaultEntity()` is not available since the `%s` Behavior is not enabled', static::class));
		}

		//Retreive the class that's used by the table for the creation of new entities
		$ls_entityClass = $this->table()->getEntityClass();
		/** @var \Cake\Datasource\EntityInterface $lo_entity */
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
					'integer' => intval($lx_default),
					'json' => json_decode(trim($lx_default, '\'')),
					'string' => strval($lx_default),
				};
			}
			catch (UnhandledMatchError) {
				dd($la_typeMap[ $ls_column ], __FILE__, __LINE__);
			}
		}
		unset($lx_default);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_defaults = $aa_additionalData + $lo_entity->defaultValues() + $la_defaults;

		$la_options = [
			'fields' => array_keys($la_defaults),
			'validate' => FALSE,
		];
		$lo_marshaller = $this->table()->marshaller();

		return $lo_marshaller->merge($lo_entity, $la_defaults, $la_options);
	}
}