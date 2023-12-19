<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use RuntimeException;


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


	public function newDefaultEntity (array $aa_additionalData = []): EntityInterface {
		if ( ! $this->getConfig('enabled')) {
			throw new RuntimeException(sprintf('The method `newDefaultEntity()` is not available since the `%s` Behavior is not enabled', static::class));
		}

		$ls_entityClass = $this->table()->getEntityClass();
		/** @var \Cake\Datasource\EntityInterface $lo_entity */
		$lo_entity = new $ls_entityClass([], ['source' => $this->table()->getRegistryAlias()]);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_defaults = $aa_additionalData + $lo_entity->defaultValues() + $this->table()->getSchema()->defaultValues();

		$la_options = [
			'fields' => array_keys($la_defaults),
			'validate' => FALSE,
		];
		/*if ( ! isset($la_options['associated'])) {
			$la_options['associated'] = $this->table()->associations()->keys();
		}*/
		$lo_marshaller = $this->table()->marshaller();

		return $lo_marshaller->merge($lo_entity, $la_defaults, $la_options);
	}
}