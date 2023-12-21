<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Model\Entity;
use Awyiss\ORM\Behavior;
use Cake\Event\Event;
use RuntimeException;


/**
 * This behavior dispatches all possible table events with a modified name.
 * This allows listening to, for example, `Model.Foobar.beforeSave` instead of just `Model.beforeSave`,
 * with "Foobar" being the name of the currently used model
 */
class EventTriggerBehavior extends Behavior {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'enabled' => true,
		'events' => [
			'beforeMarshal',
			'afterMarshal',
			'beforeFind',
			'buildValidator',
			'buildRules',
			'beforeRules',
			'afterRules',
			'beforeSave',
			'beforeCreate',
			'beforeUpdate',
			'afterSave',
			'afterCreate',
			'afterUpdate',
			'afterSaveCommit',
			'afterCreateCommit',
			'afterUpdateCommit',
			'beforeDelete',
			'afterDelete',
			'afterDeleteCommit',
			'beforeSoftDelete',
			'afterSoftDelete',
			'afterSoftDeleteCommit',
		],
		'implementedMethods' => [],
	];


	/**
	 * Will return all config values of `events`-settings, prefixed with 'Model.'
	 */
	public function implementedEvents(): array {
		if (!$this->getConfig('enabled')) {
			return [];
		}

		$la_events = [];
		foreach ($this->getConfig('events') as $ls_event => $lx_callable) {
			if (is_numeric($ls_event)) {
				if (!is_string($lx_callable)) {
					throw new RuntimeException(sprintf('When provided a callable, the key must be a string. `%s` given', gettype($ls_event)));
				}
				$ls_event = 'Model.' . $lx_callable;
			}

			$la_events[ $ls_event ] = $lx_callable;
		}


		return $la_events;
	}


	/**
	 * Magic method that will be called since no real event listener function exists.
	 *
	 * @param string $as_name
	 * @param array $aa_arguments
	 * @return void
	 */
	public function __call(string $as_name, array $aa_arguments): void {
		if (!$this->getConfig('enabled') || !in_array($as_name, $this->getConfig('events'))) {
			//Trigger the same error the call of undefined methods would normally trigger.
			trigger_error(sprintf('Call to undefined method %s::%s()', static::class, $as_name), E_USER_ERROR);
		}

		//Saving an entitiy should create custom events
		if (in_array($as_name, ['beforeSave', 'afterSave', 'afterSaveCommit']) && isset($aa_arguments[1]) && is_a($aa_arguments[1], Entity::class)) {
			//TODO: check what happens when stopping this event when the entity is deleted
			/**
			 * If the custom events method returns false, the newly created event was stopped.
			 * This means: don't send the other model-specific events
			 */
			if (!$this->triggerCreateUpdateEvents($as_name, $aa_arguments[1], $aa_arguments)) {
				return;
			}
		}

		$la_arguments = $aa_arguments;
		unset($la_arguments[0]);

		$ls_alias = $this->table()->getAlias();
		//Create a new event with the modified name and dispatch it.
		$lo_event = new Event('Model.' . $ls_alias . '.' . $as_name, $this->table(), $la_arguments);
		$this->table()->getEventManager()->dispatch($lo_event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($lo_event->isStopped()) {
			$aa_arguments[0]->stopPropagation();
			$aa_arguments[0]->setResult($lo_event->getResult());
		}
	}


	/**
	 * Trigger custom events when creating or updaten entities, depending on their isNew()-value
	 *
	 * @param string $as_name
	 * @param Entity $ao_entity
	 * @param array $aa_arguments
	 * @return bool
	 */
	protected function triggerCreateUpdateEvents(string $as_name, Entity $ao_entity, array $aa_arguments): bool {
		//If the entity has a `deleted`-property, and it's trueish, don't send the custom events
		if (property_exists($ao_entity, 'deleted') && $ao_entity->deleted) {
			return true;
		}

		$ls_name = match (true) {
			$as_name == 'beforeSave' && $ao_entity->isNew() => 'beforeCreate',
			$as_name == 'beforeSave' && !$ao_entity->isNew() => 'beforeUpdate',
			$as_name == 'afterSave' && $ao_entity->isNew() => 'afterCreate',
			$as_name == 'afterSave' && !$ao_entity->isNew() => 'afterUpdate',
			$as_name == 'afterSaveCommit' && $ao_entity->isNew() => 'afterCreateCommit',
			$as_name == 'afterSaveCommit' && !$ao_entity->isNew() => 'afterUpdateCommit',
		};

		//Create a new event with the modified name and dispatch it.
		$lo_event = new Event('Model.' . $ls_name, $this->table(), $aa_arguments);
		$this->table()->getEventManager()->dispatch($lo_event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($lo_event->isStopped()) {
			$aa_arguments[0]->stopPropagation();
			$aa_arguments[0]->setResult($lo_event->getResult());


			return false;
		}


		return true;
	}
}
