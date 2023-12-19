<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;


class TimeTrackerBehavior extends Behavior {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'enabled' => TRUE,
		'setTimeOnCreate' => TRUE,
		'setTimeOnUpdate' => TRUE,
		'setTimeOnDelete' => TRUE,
		'skipTimeTrackerBehavior' => FALSE,
	];


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$lb_isNew = $ao_entity->isNew();
		$la_options = \Cake\Utility\Hash::merge($this->getConfig(), $ao_options);

		if ($la_options['skipTimeTrackerBehavior'] === TRUE) {
			return TRUE;
		}

		/*$li_identityId = NULL;
		$lo_identity = \Cake\Routing\Router::getRequest()->getAttribute('identity');
		if ($lo_identity) {
			$li_identityId = $lo_identity->id;
			if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
				$li_identityId *= -1;
			}
		}
		//dd($lo_identity);*/

		$lo_schema = $this->table()->getSchema();
		$ls_identityColumn = NULL;
		if (empty($ao_entity->deleted)) {
			if ($lb_isNew && $lo_schema->getColumn('created_on') && $la_options['setTimeOnCreate']) {
				$ao_entity->set('created_on', \Cake\I18n\FrozenTime::now());
				if ($lo_schema->getColumn('created_by')) {
					$ls_identityColumn = 'created_by';
					//$ao_entity->set('created_by', $li_identityId);
				}
			}
			elseif ( ! $lb_isNew && $lo_schema->getColumn('changed_on') && $la_options['setTimeOnUpdate']) {
				$ao_entity->set('changed_on', \Cake\I18n\FrozenTime::now());
				if ($lo_schema->getColumn('changed_by')) {
					$ls_identityColumn = 'changed_by';
					//$ao_entity->set('changed_by', $li_identityId);
				}
			}
		}
		elseif ($lo_schema->getColumn('deleted') && ! empty($ao_entity->deleted) && $ao_entity->deleted != $ao_entity->getOriginal('deleted')) {
			if ($lo_schema->getColumn('deleted_on') && $la_options['setTimeOnDelete']) {
				$ao_entity->set('deleted_on', \Cake\I18n\FrozenTime::now());
				if ($lo_schema->getColumn('deleted_by')) {
					$ls_identityColumn = 'deleted_by';
					//ao_entity->set('deleted_by', $li_identityId);
				}
			}
		}

		if ($ls_identityColumn) {
			$lo_event = new Event('Behavior.TimeTracker.beforeSave', $this->table(), [
				'entity' => $ao_entity,
				'identityColumn' => $ls_identityColumn,
			]);
			$this->table()->getEventManager()->dispatch($lo_event);
		}

		return TRUE;
	}
}