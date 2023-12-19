<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Cake\Event\Event;
use Cake\ORM\Behavior;


class EventTriggerBehavior extends Behavior {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'enabled' => TRUE,
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
		],
	];


	public function implementedEvents (): array {
		if ( ! $this->getConfig('enabled')) {
			return [];
		}

		$la_events = [];
		foreach ($this->getConfig('events') as $ls_event) {
			$la_events[ 'Model.' . $ls_event ] = $ls_event;
		}

		return $la_events;
	}


	public function __call (string $as_name, array $aa_arguments) {
		if ( ! $this->getConfig('enabled') || ! in_array($as_name, $this->getConfig('events'))) {
			trigger_error(sprintf('Call to undefined method %s::%s()', __CLASS__, $as_name), E_USER_ERROR);
		}

		$la_arguments = $aa_arguments;
		unset($la_arguments[0]);

		$ls_alias = \Cake\Utility\Inflector::singularize($this->table()->getAlias());
		$lo_event = new Event('Model.' . $ls_alias . '.' . $as_name, $this->table(), $la_arguments);
		$this->table()->getEventManager()->dispatch($lo_event);
	}
}