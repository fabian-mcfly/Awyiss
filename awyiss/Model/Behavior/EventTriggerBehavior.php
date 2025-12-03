<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Model\Entity;
use Awyiss\ORM\Behavior;
use BadMethodCallException;
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

		$events = [];
		foreach ($this->getConfig('events') as $eventName => $callable) {
			if (is_numeric($eventName)) {
				if (!is_string($callable)) {
					throw new RuntimeException(sprintf('When provided a callable, the key must be a string. `%s` given', gettype($eventName)));
				}
				$eventName = 'Model.' . $callable;
			}

			$events[ $eventName ] = $callable;
		}


		return $events;
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
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
		}

		//Saving an entity should create custom events
		if (in_array($name, ['beforeSave', 'afterSave', 'afterSaveCommit']) && isset($arguments[1]) && is_a($arguments[1], Entity::class)) {
			if (($arguments[2]['isCopy'] ?? false) === true && $arguments[1]->isNew()) {
				$return = $this->dispatchCopyEvents($name, ...$arguments);
			}
			else {
				$return = $this->dispatchCreateUpdateEvents($name, ...$arguments);
			}

			if ($return === false) {
				return;
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

		$alias = $this->table()->getAlias();

		if (str_starts_with($alias, 'Child')) {
			$alias = substr($alias, 5);
		}
		elseif (str_starts_with($alias, 'Parent')) {
			$alias = substr($alias, 6);
		}

		$this->alias = $alias;


		return $this->alias;
	}


	/**
	 * @param string $name
	 * @param \Cake\Event\Event $originalEvent
	 * @param array $arguments
	 * @return bool
	 */
	protected function dispatchEvent(string $name, Event $originalEvent, mixed ...$arguments): bool {
		//Create a new event with the modified name and dispatch it.
		$event = new Event('Model.' . $name, $this->table(), $arguments);
		$this->table()->getEventManager()->dispatch($event);

		//If the new event was stopped, stop the old one as well and set the result.
		if ($event->isStopped()) {
			$originalEvent->stopPropagation();
			$originalEvent->setResult($event->getResult());


			return false;
		}


		return true;
	}


	/**
	 * Trigger custom events when creating or updating entities, depending on their isNew()-value
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

		$name = match (true) {
			$name == 'beforeSave' && $subject->isNew() => 'beforeCreate',
			$name == 'beforeSave' && !$subject->isNew() => 'beforeUpdate',
			$name == 'afterSave' && $subject->isNew() => 'afterCreate',
			$name == 'afterSave' && !$subject->isNew() => 'afterUpdate',
			$name == 'afterSaveCommit' && $subject->isNew() => 'afterCreateCommit',
			$name == 'afterSaveCommit' && !$subject->isNew() => 'afterUpdateCommit',
		};

		//Create a new event with the modified name and dispatch it.
		if (!$this->dispatchEvent($name, $originalEvent, $subject, ...$arguments)) {
			return false;
		}


		//Create a new table specific event with the modified name and dispatch it.
		return $this->dispatchEvent($this->getAlias() . '.' . $name, $originalEvent, $subject, ...$arguments);
	}


	/**
	 * @param string $name
	 * @param \Cake\Event\Event $originalEvent
	 * @param \Awyiss\Model\Entity $subject
	 * @param array $arguments
	 * @return bool|null
	 */
	protected function dispatchCopyEvents(string $name, Event $originalEvent, Entity $subject, mixed ...$arguments): ?bool {
		$name = match (true) {
			$name == 'beforeSave' => 'beforeCopy',
			$name == 'afterSave' => 'afterCopy',
			$name == 'afterSaveCommit' => 'afterCopyCommit',
		};

		//Create a new event with the modified name and dispatch it.
		if (!$this->dispatchEvent($name, $originalEvent, $subject, ...$arguments)) {
			return false;
		}


		//Create a new table specific event with the modified name and dispatch it.
		return $this->dispatchEvent($this->getAlias() . '.' . $name, $originalEvent, $subject, ...$arguments);
	}
}
