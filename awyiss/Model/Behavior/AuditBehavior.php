<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Authentication\IdentityInterface;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\ORM\Behavior;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AuditBehavior extends Behavior {
	use LocatorAwareTrait;


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
		'ignoredColumns' => [
			'created_on',
			'created_by',
			'changed_on',
			'changed_by',
			'deleted_on',
			'deleted_by',
		],
		'setTimeOnCreate' => TRUE,
		'setTimeOnUpdate' => TRUE,
		'setTimeOnDelete' => TRUE,
		'skip' => FALSE,
	];
	protected ?IdentityInterface $identity = NULL;


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'audit'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		$lb_isNew = $ao_entity->isNew();
		$li_identityId = $this->getIdentityId();
		$lo_schema = $this->table()->getSchema();

		if (empty($ao_entity->deleted)) {
			if ($lb_isNew && $lo_schema->getColumn('created_on') && $la_options['setTimeOnCreate']) {
				//If the item is new, and if config wants it, set the create-info on this entity
				$this->setCreateInfo($ao_entity, $li_identityId, $lo_schema);
			}
			elseif ( ! $lb_isNew && $lo_schema->getColumn('changed_on') && $la_options['setTimeOnUpdate']) {
				//If the item is not new, and if config wants it, set the update-info on this entity
				$this->setUpdateInfo($ao_entity, $li_identityId, $lo_schema);
			}
		}
		elseif ($lo_schema->getColumn('deleted') && ! empty($ao_entity->deleted) && $ao_entity->deleted != $ao_entity->getOriginal('deleted')) {
			//A soft delete will set the `deleted`-property. If this happens, and the config wants it, set the delete-info on this entity
			if ($lo_schema->getColumn('deleted_on') && $la_options['setTimeOnDelete']) {
				$this->setDeleteInfo($ao_entity, $li_identityId, $lo_schema);
			}
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @return void
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
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
		$la_diff = Hash::diff($la_oldData, $la_newData);
		$la_diff = array_diff_key($la_diff, array_flip($this->getConfig('ignoredColumns')));

		//No difference? Do nothing
		if (empty($la_diff)) {
			return;
		}

		$li_identityId = $this->getIdentityId();

		//Set the data to be used in `newEntity`
		$la_auditData = [
			'type' => ! empty($ao_entity->deleted) ? 'd' : 'u',
			'scope' => $ao_event->getSubject()->getTable(),
			'parent_id' => $ao_entity->get('id'),
			'data_old' => $la_oldData,
			'data_new' => $la_newData,
			'diff' => $la_diff,
			'created_on' => new FrozenTime(),
			'created_by' => $li_identityId,
		];

		$lo_auditModel = $this->getTableLocator()->get('Audit');
		$lo_audit = $lo_auditModel->newEntity($la_auditData);

		//Save the audit entity and skip the access check
		$lo_auditModel->save($lo_audit, [
			'access' => ['skip' => TRUE],
		]);
	}


	/**
	 * Sets the identity
	 *
	 * @param \Authentication\IdentityInterface $ao_identity
	 *
	 * @return void
	 *
	 * @noinspection PhpUnused
	 */
	public function setIdentity (IdentityInterface $ao_identity): void {
		$this->identity = $ao_identity;
	}


	/**
	 * Returns the currently set identity
	 *
	 * @return NULL|\Authentication\IdentityInterface
	 */
	public function getIdentity (): ?IdentityInterface {
		return $this->identity;
	}


	/**
	 * Return the ID of the currently set identity
	 *
	 * @return ?int
	 */
	protected function getIdentityId (): ?int {
		$li_identityId = NULL;

		if ($lo_identity = $this->getIdentity()) {
			$li_identityId = $lo_identity->getIdentifier();
		}

		if ($lo_identity instanceof UsersExternal) {
			$li_identityId *= -1;
		}

		return $li_identityId;
	}


	/**
	 * Set the info for a new entity
	 *
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param NULL|int $ai_identityId
	 * @param \Cake\Database\Schema\TableSchemaInterface $ao_schema
	 *
	 * @return void
	 */
	protected function setCreateInfo (EntityInterface $ao_entity, ?int $ai_identityId, TableSchemaInterface $ao_schema): void {
		$ao_entity->set('created_on', FrozenTime::now());
		if ($ai_identityId && $ao_schema->getColumn('created_by')) {
			$ao_entity->set('created_by', $ai_identityId);
		}
	}


	/**
	 * Set the info for an existing entity
	 *
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param NULL|int $ai_identityId
	 * @param \Cake\Database\Schema\TableSchemaInterface $ao_schema
	 *
	 * @return void
	 */
	protected function setUpdateInfo (EntityInterface $ao_entity, ?int $ai_identityId, TableSchemaInterface $ao_schema): void {
		$ao_entity->set('changed_on', FrozenTime::now());
		if ($ai_identityId && $ao_schema->getColumn('changed_by')) {
			$ao_entity->set('changed_by', $ai_identityId);
		}
	}


	/**
	 * Set the info for a deleted entity
	 *
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param NULL|int $ai_identityId
	 * @param \Cake\Database\Schema\TableSchemaInterface $ao_schema
	 *
	 * @return void
	 */
	protected function setDeleteInfo (EntityInterface $ao_entity, ?int $ai_identityId, TableSchemaInterface $ao_schema): void {
		$ao_entity->set('deleted_on', FrozenTime::now());
		if ($ai_identityId && $ao_schema->getColumn('deleted_by')) {
			$ao_entity->set('deleted_by', $ai_identityId);
		}
	}
}