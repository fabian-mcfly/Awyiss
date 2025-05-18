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
			'beforeSaveAssociations',
			'afterSaveAssociations',
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
	 * @param string $name
	 * @param array $arguments
	 * @return void
	 */
	public function __call(string $name, array $arguments): void {
		if (!$this->getConfig('enabled') || !in_array($name, $this->getConfig('events'))) {
			//Trigger the same error the call of undefined methods would normally trigger.
			trigger_error(sprintf('Call to undefined method %s::%s()', static::class, $name), E_USER_ERROR);
		}

		//Saving an entitiy should create custom events
		if (in_array($name, ['beforeSave', 'afterSave', 'afterSaveCommit']) && isset($arguments[1]) && is_a($arguments[1], Entity::class)) {
			if (($arguments[2]['isCopy'] ?? false) === true && $arguments[1]->isNew()) {
				$this->dispatchCopyEvents($name, ...$arguments);
			}
			else {
				$this->dispatchCreateUpdateEvents($name, ...$arguments);
			}
		}

		$this->dispatchEvent($this->getAlias() . '.' . $name, ...$arguments);
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
	 * @param string $name
	 * @param \Cake\Event\Event $originalEvent
	 * @param mixed $subject
	 * @param array $arguments
	 * @return bool
	 */
	protected function dispatchEvent(string $name, Event $originalEvent, mixed ...$arguments): bool {
		//Create a new event with the modified name and dispatch it.
		$lo_event = new Event('Model.' . $name, $this->table(), $arguments);
		$this->table()->getEventManager()->dispatch($lo_event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($lo_event->isStopped()) {
			$originalEvent->stopPropagation();
			$originalEvent->setResult($lo_event->getResult());


			return false;
		}


		return true;
	}


	/**
	 * Trigger custom events when creating or updaten entities, depending on their isNew()-value
	 *
	 * @param string $name
	 * @param \Cake\Event\Event $originalEvent
	 * @param \Awyiss\Model\Entity $subject
	 * @param array $arguments
	 * @return bool
	 */
	protected function dispatchCreateUpdateEvents(string $name, Event $originalEvent, Entity $subject, mixed ...$arguments): bool {
		//If the entity has a `deleted`-property, and it's trueish, don't send the custom events
		if (property_exists($subject, 'deleted') && $subject->deleted) {
			return true;
		}

		$ls_name = match (true) {
			$name == 'beforeSave' && $subject->isNew() => 'beforeCreate',
			$name == 'beforeSave' && !$subject->isNew() => 'beforeUpdate',
			$name == 'afterSave' && $subject->isNew() => 'afterCreate',
			$name == 'afterSave' && !$subject->isNew() => 'afterUpdate',
			$name == 'afterSaveCommit' && $subject->isNew() => 'afterCreateCommit',
			$name == 'afterSaveCommit' && !$subject->isNew() => 'afterUpdateCommit',
		};

		//Create a new event with the modified name and dispatch it.
		if (!$this->dispatchEvent($ls_name, $originalEvent, $subject, ...$arguments)) {
			return false;
		}


		//Create a new table specific event with the modified name and dispatch it.
		return $this->dispatchEvent($this->getAlias() . '.' . $ls_name, $originalEvent, $subject, ...$arguments);
	}


	/**
	 * @param string $name
	 * @param \Cake\Event\Event $originalEvent
	 * @param \Awyiss\Model\Entity $subject
	 * @param array $arguments
	 * @return bool|null
	 */
	protected function dispatchCopyEvents(string $name, Event $originalEvent, Entity $subject, mixed ...$arguments): ?bool {
		$ls_name = match (true) {
			$name == 'beforeSave' => 'beforeCopy',
			$name == 'afterSave' => 'afterCopy',
			$name == 'afterSaveCommit' => 'afterCopyCommit',
		};

		//Create a new event with the modified name and dispatch it.
		if (!$this->dispatchEvent($ls_name, $originalEvent, $subject, ...$arguments)) {
			return false;
		}


		//Create a new table specific event with the modified name and dispatch it.
		return $this->dispatchEvent($this->getAlias() . '.' . $ls_name, $originalEvent, $subject, ...$arguments);
	}
}
