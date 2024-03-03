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
			'afterSave',
			'afterSaveCommit',
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
	 * @var string Clean name of the table alias
	 */
	protected string $alias;


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
			if (($aa_arguments[2]['isCopy'] ?? false) === true && $aa_arguments[1]->isNew()) {
				$this->dispatchCopyEvents($as_name, ...$aa_arguments);
			}
			else {
				$this->dispatchCreateUpdateEvents($as_name, ...$aa_arguments);
			}
		}

		$this->dispatchEvent($this->getAlias() . '.' . $as_name, ...$aa_arguments);
	}


	/**
	 * @return string
	 */
	protected function getAlias(): string {
		if (isset($this->alias)) {
			return $this->alias;
		}

		$ls_alias = $this->table()->getAlias();

		if (str_starts_with($ls_alias, 'Child')) {
			$ls_alias = substr($ls_alias, 5);
		}
		elseif (str_starts_with($ls_alias, 'Parent')) {
			$ls_alias = substr($ls_alias, 6);
		}

		$this->alias = $ls_alias;


		return $this->alias;
	}


	/**
	 * @param string $as_name
	 * @param \Cake\Event\Event $ao_originalEvent
	 * @param mixed $ao_subject
	 * @param array $aa_arguments
	 * @return bool
	 */
	protected function dispatchEvent(string $as_name, Event $ao_originalEvent, mixed ...$aa_arguments): bool {
		//Create a new event with the modified name and dispatch it.
		$lo_event = new Event('Model.' . $as_name, $this->table(), $aa_arguments);
		$this->table()->getEventManager()->dispatch($lo_event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($lo_event->isStopped()) {
			$ao_originalEvent->stopPropagation();
			$ao_originalEvent->setResult($lo_event->getResult());


			return false;
		}


		return true;
	}


	/**
	 * Trigger custom events when creating or updaten entities, depending on their isNew()-value
	 *
	 * @param string $as_name
	 * @param \Cake\Event\Event $ao_originalEvent
	 * @param \Awyiss\Model\Entity $ao_subject
	 * @param array $aa_arguments
	 * @return bool
	 */
	protected function dispatchCreateUpdateEvents(string $as_name, Event $ao_originalEvent, Entity $ao_subject, mixed ...$aa_arguments): bool {
		//If the entity has a `deleted`-property, and it's trueish, don't send the custom events
		if (property_exists($ao_subject, 'deleted') && $ao_subject->deleted) {
			return true;
		}

		$ls_name = match (true) {
			$as_name == 'beforeSave' && $ao_subject->isNew() => 'beforeCreate',
			$as_name == 'beforeSave' && !$ao_subject->isNew() => 'beforeUpdate',
			$as_name == 'afterSave' && $ao_subject->isNew() => 'afterCreate',
			$as_name == 'afterSave' && !$ao_subject->isNew() => 'afterUpdate',
			$as_name == 'afterSaveCommit' && $ao_subject->isNew() => 'afterCreateCommit',
			$as_name == 'afterSaveCommit' && !$ao_subject->isNew() => 'afterUpdateCommit',
		};

		//Create a new event with the modified name and dispatch it.
		if (!$this->dispatchEvent($ls_name, $ao_originalEvent, $ao_subject, ...$aa_arguments)) {
			return false;
		}


		//Create a new table specific event with the modified name and dispatch it.
		return $this->dispatchEvent($this->getAlias() . '.' . $ls_name, $ao_originalEvent, $ao_subject, ...$aa_arguments);
	}


	/**
	 * @param string $as_name
	 * @param \Cake\Event\Event $ao_originalEvent
	 * @param \Awyiss\Model\Entity $ao_subject
	 * @param array $aa_arguments
	 * @return bool|null
	 */
	protected function dispatchCopyEvents(string $as_name, Event $ao_originalEvent, Entity $ao_subject, mixed ...$aa_arguments): ?bool {
		$ls_name = match (true) {
			$as_name == 'beforeSave' => 'beforeCopy',
			$as_name == 'afterSave' => 'afterCopy',
			$as_name == 'afterSaveCommit' => 'afterCopyCommit',
		};

		//Create a new event with the modified name and dispatch it.
		if (!$this->dispatchEvent($ls_name, $ao_originalEvent, $ao_subject, ...$aa_arguments)) {
			return false;
		}


		//Create a new table specific event with the modified name and dispatch it.
		return $this->dispatchEvent($this->getAlias() . '.' . $ls_name, $ao_originalEvent, $ao_subject, ...$aa_arguments);
	}
}
