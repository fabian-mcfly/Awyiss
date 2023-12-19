<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Authentication\IdentityInterface;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Utility\Hash;


class AuditBehavior extends Behavior {
	use \Cake\ORM\Locator\LocatorAwareTrait;


	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'enabled' => TRUE,
		'implementedEvents' => [
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
		],
		'implementedMethods' => [
			//'setIdentity' => 'setIdentity',
			//'getIdentity' => 'getIdentity',
		],
		'ignoredColumns' => ['created_on', 'created_by', 'changed_on', 'changed_by', 'deleted_on', 'deleted_by'],
		'setTimeOnCreate' => TRUE,
		'setTimeOnUpdate' => TRUE,
		'setTimeOnDelete' => TRUE,
		'skip' => FALSE,
	];
	protected ?IdentityInterface $identity = NULL;



	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$lb_isNew = $ao_entity->isNew();
		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'audit'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		$li_identityId = NULL;
		if ($lo_identity = $this->getIdentity()) {
			$li_identityId = $lo_identity->getIdentifier();
			if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
				$li_identityId *= -1;
			}
		}

		$lo_schema = $this->table()->getSchema();
		if (empty($ao_entity->deleted)) {
			if ($lb_isNew && $lo_schema->getColumn('created_on') && $la_options['setTimeOnCreate']) {
				$ao_entity->set('created_on', \Cake\I18n\FrozenTime::now());
				if ($li_identityId && $lo_schema->getColumn('created_by')) {
					$ao_entity->set('created_by', $li_identityId);
				}
			}
			elseif ( ! $lb_isNew && $lo_schema->getColumn('changed_on') && $la_options['setTimeOnUpdate']) {
				$ao_entity->set('changed_on', \Cake\I18n\FrozenTime::now());
				if ($li_identityId && $lo_schema->getColumn('changed_by')) {
					$ao_entity->set('changed_by', $li_identityId);
				}
			}
		}
		elseif ($lo_schema->getColumn('deleted') && ! empty($ao_entity->deleted) && $ao_entity->deleted != $ao_entity->getOriginal('deleted')) {
			if ($lo_schema->getColumn('deleted_on') && $la_options['setTimeOnDelete']) {
				$ao_entity->set('deleted_on', \Cake\I18n\FrozenTime::now());
				if ($li_identityId && $lo_schema->getColumn('deleted_by')) {
					$ao_entity->set('deleted_by', $li_identityId);
				}
			}
		}
	}


	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled') || $ao_entity->isNew()) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'audit'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		//Existing entities make their way into the audit database with the old and new data
		$la_oldData = $ao_entity->getOriginalValues();
		$la_newData = $ao_entity->extract(array_keys($la_oldData));
		$la_diff = \Cake\Utility\Hash::diff($la_oldData, $la_newData);
		$la_diff = array_diff_key($la_diff, array_flip($this->getConfig('ignoredColumns')));
		if (empty($la_diff)) return;

		$li_identityId = NULL;
		if ($lo_identity = $this->getIdentity()) {
			$li_identityId = $lo_identity->getIdentifier();
			if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
				$li_identityId *= -1;
			}
		}

		$la_auditData = [
			'type' => ! empty($ao_entity->deleted) ? 'd' : 'u',
			'scope' => $ao_event->getSubject()->getTable(),
			'parent_id' => $ao_entity->get('id'),
			'data_old' => $la_oldData,
			'data_new' => $la_newData,
			'diff' => $la_diff,
			'created_on' => new \Cake\I18n\FrozenTime(),
			'created_by' => $li_identityId,
		];

		$lo_auditModel = $this->getTableLocator()->get('Audit');
		$lo_audit = $lo_auditModel->newEntity($la_auditData);

		$lo_auditModel->save($lo_audit, [
			'access' => ['skip' => TRUE],
		]);
	}


	public function setIdentity (IdentityInterface $ao_identity) {
		$this->identity = $ao_identity;
	}


	public function getIdentity (): ?IdentityInterface {
		return $this->identity;
	}
}