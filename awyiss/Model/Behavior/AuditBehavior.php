<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Authentication\IdentityInterface;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Association;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use RuntimeException;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AuditBehavior extends Behavior {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	protected array $auditData = [];
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'enabled' => true,
		'historyFields' => null,
		'implementedEvents' => [
			'buildValidator',
			'beforeCopy',
			'beforeSave',
			'beforeDelete',
			'afterSave',
			'afterDelete',
		],
		'implementedMethods' => [
			'countAuditData' => 'countAuditData',
			'getAuditData' => 'getAuditData',
			'getAuditHistoryFields' => 'getHistoryFields',
		],
		'ignoredFields' => [
			'createdOn',
			'createdBy',
			'changedOn',
			'changedBy',
			'deletedOn',
			'deletedBy',
			'publicationStart',
			'publicationEnd',
			'_i18n',
			'_locale',
			'_joinData',
		],
		'isPivotTable' => false,
		'setTimeOnCreate' => true,
		'setTimeOnUpdate' => true,
		'setTimeOnDelete' => true,
		'skip' => false,
		'leftTable' => null,
		'rightTable' => null,
	];
	/**
	 * @var \Authentication\IdentityInterface|null
	 */
	protected ?IdentityInterface $identity = null;


	/**
	 * Initialize the behavior with the provided configuration.
	 *
	 * @param array $config The configuration array.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		// Get the table and schema for the current model
		$table = $this->table();
		$schema = $table->getSchema();

		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $this->table()->getEntityClass();

		// Check if the table has the required columns and add the corresponding associations
		if ($schema->hasColumn('created_by')) {
			$entityClass::addFieldMapping('created_by', 'createdBy');
			$this->addAssociation('CreatedBy');
		}
		if ($schema->hasColumn('created_on')) {
			$entityClass::addFieldMapping('created_on', 'createdOn');
		}

		if ($schema->hasColumn('changed_by')) {
			$entityClass::addFieldMapping('changed_by', 'changedBy');
			$this->addAssociation('ChangedBy');
		}
		if ($schema->hasColumn('changed_on')) {
			$entityClass::addFieldMapping('changed_on', 'changedOn');
		}

		if ($schema->hasColumn('deleted_by')) {
			$entityClass::addFieldMapping('deleted_by', 'deletedBy');
			$this->addAssociation('DeletedBy');
		}
		if ($schema->hasColumn('deleted_on')) {
			$entityClass::addFieldMapping('deleted_on', 'deletedOn');
		}

		if ($this->getConfig('historyFields') === null) {
			$fields = $entityClass::mapFields($schema->columns());

			$fields = array_diff($fields, $this->getConfig('ignoredFields'), ['id']);

			$this->setConfig('historyFields', array_values($fields));
		}
	}


	/**
	 * Add an association to the table.
	 *
	 * @param string $alias The alias for the association.
	 * @return void
	 */
	protected function addAssociation(string $alias): void {
		$table = $this->table();

		// Add a belongsTo association to the table
		$table->belongsTo($alias . 'User', [
			'className' => 'Users',
			'foreignKey' => Inflector::underscore($alias),
		]);

		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $table->getEntityClass();
		$entityClass::addFieldMapping(Inflector::underscore($alias . 'User'), Inflector::variable($alias . 'User'));
	}


	/**
	 * Modify the query to join with the audit users and select their usernames.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
	 * @return \Cake\ORM\Query\SelectQuery The modified query.
	 */
	public function findWithAuditUsers(SelectQuery $query): SelectQuery {
		// Enable auto fields for the query if they are not already enabled
		if ($query->isAutoFieldsEnabled() === null) {
			$query->enableAutoFields();
		}

		// Define the associations to join with
		$associations = ['CreatedByUser', 'ChangedByUser', 'DeletedByUser'];
		foreach ($associations as $associationName) {
			// Skip the association if it does not exist
			if (!$this->table()->hasAssociation($associationName)) {
				continue;
			}

			// Join with the association and select the username
			$query->leftJoinWith($associationName)->select($associationName . '.username');
		}

		// Handle _matchingData
		$query->formatResults(function (CollectionInterface $results) {
			return $results->map(function (EntityInterface|array|null $row) {
				// Skip the row if it does not have _matchingData
				if (!$row || !isset($row['_matchingData'])) {
					return $row;
				}

				// Iterate over the matching data
				foreach ($row['_matchingData'] as $matchingKey => $user) {
					// Modify the row data based on the matching data
					$property = Inflector::variable($matchingKey);
					$row[ $property ] = $user->username;
					unset($row['_matchingData'][ $matchingKey ]);
				}

				// Remove the _matchingData key if it is empty
				if (empty($row['_matchingData'])) {
					unset($row['_matchingData']);
				}

				return $row;
			});
		});

		return $query;
	}


	/**
	 * Returns the audit data count for the provided entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return int
	 */
	public function countAuditData(EntityInterface $entity): int {
		return $this->auditDataQuery($entity)->count();
	}


	/**
	 * Returns the audit data for the provided entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 */
	public function getAuditData(EntityInterface $entity): array {
		return $this->auditDataQuery($entity)->toArray();
	}


	/**
	 * Returns the history fields for the table.
	 *
	 * @return array
	 */
	public function getHistoryFields(): array {
		return $this->getConfig('historyFields');
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function auditDataQuery(EntityInterface $entity): SelectQuery {
		$auditModel = $this->getTableLocator()->get('Audit');

		return $auditModel->find('all')->where([
			'OR' => [
				[
					'scope' => $this->table()->getTable(),
					'foreign_key' => $entity->get('id'),
				],
				[
					'subject_left_table' => $this->table()->getTable(),
					'subject_left_foreign_key' => $entity->get('id'),
				],
				[
					'subject_right_table' => $this->table()->getTable(),
					'subject_right_foreign_key' => $entity->get('id'),
				],
			],
		])->contain(['Users'])->orderBy(['Audit.created_on' => 'DESC']);
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Validation\Validator $validator
	 * @param string $name
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildValidator(EventInterface $event, Validator $validator, string $name): void {
		$validator->allowEmptyDateTime('createdOn');
		$validator->add('createdOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);

		$validator->allowEmptyString('createdBy');
		$validator->add('createdBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);

		$validator->allowEmptyDateTime('changedOn');
		$validator->add('changedOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);

		$validator->allowEmptyString('changedBy');
		$validator->add('changedBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);

		$validator->allowEmptyDateTime('deletedOn');
		$validator->add('deletedOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);

		$validator->allowEmptyString('deletedBy');
		$validator->add('deletedBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeDelete(EventInterface $event, Entity $entity, ArrayObject $options): void {
		$options['transactionId'] ??= vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'audit'));

		if ($queryOptions['skip'] === true || !$entity->allowsAudit()) {
			return;
		}

		$this->auditData[ $entity->get('id') ] = $this->buildEntityData($entity, true);
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection,PhpUnused
	 */
	public function beforeCopy(EventInterface $event, Entity $entity, ArrayObject $options): void {
		$entity->unset(['createdOn', 'createdBy', 'changedOn', 'changedBy']);
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options): void {
		$options['transactionId'] ??= vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

		$queryOtions = Hash::merge($this->getConfig(), Hash::get($options, 'audit'));

		if ($queryOtions['skip'] === true) {
			return;
		}

		$isNew = $entity->isNew();
		$identityId = $this->getIdentityId();
		$schema = $this->table()->getSchema();

		if (empty($entity->get('deleted'))) {
			if ($isNew && $schema->getColumn('created_on') && $queryOtions['setTimeOnCreate']) {
				// If the item is new, and if config wants it, set the create-info on this entity
				$this->setCreateInfo($entity, $identityId, $schema);
			}

			if (!$isNew && $schema->getColumn('changed_on') && $queryOtions['setTimeOnUpdate']) {
				// If the item is not new, and if config wants it, set the update-info on this entity
				$this->setUpdateInfo($entity, $identityId, $schema);
			}
		}
		elseif ($schema->getColumn('deleted') && (!$entity->hasOriginal('deleted') || $entity->get('deleted') != $entity->getOriginal('deleted'))) {
			// A soft delete will set the `deleted`-property. If this happens, and the config wants it, set the delete-info on this entity
			if ($schema->getColumn('deleted_on') && $queryOtions['setTimeOnDelete']) {
				$this->setDeleteInfo($entity, $identityId, $schema);
			}
		}

		if (
			!$entity->allowsAudit() ||
			(
				$entity->isNew() &&
				!$this->getConfig('isPivotTable')
			)
		) {
			return;
		}

		$this->auditData[ $entity->get('id') ] = $this->buildEntityData($entity);
	}


	/**
	 * Create the actual audit entry after the entity has been saved.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 */
	public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if (
			!$entity->allowsAudit() ||
			(
				$entity->isNew() &&
				!$this->getConfig('isPivotTable')
			)
		) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'audit'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		$entityData = $this->auditData[ $entity->get('id') ] ?? [];
		//No difference? Do nothing.
		if (
			!$this->getConfig('isPivotTable') &&
			(
				empty($entityData) ||
				(
					empty($entityData['changes']['old']) &&
					empty($entityData['changes']['new'])
				)
			)
		) {
			return;
		}

		$identityId = $this->getIdentityId();

		$type = match (true) {
			$entity->isNew() && $this->getConfig('isPivotTable') => 'c',
			!empty($entity->get('deleted')) => 'd',
			default => 'u',
		};

		//Set the data to be used in `newEntity`
		$auditData = [
			'transactionId' => $options['transactionId'],
			'type' => $type,
			'scope' => $event->getSubject()->getTable(),
			'foreignKey' => $entity->get('id'),
			'dataOld' => !empty($entityData['old']) ? base64_encode(gzcompress(json_encode($entityData['old']), 9)) : null,
			'dataNew' => !empty($entityData['new']) ? base64_encode(gzcompress(json_encode($entityData['new']), 9)) : null,
			'diff' => $entityData['changes'] ?? null,
			'createdOn' => new DateTime(),
			'createdBy' => $identityId,
		];

		/** @var \Awyiss\Model\Table\AuditTable $auditModel */
		$auditModel = $this->getTableLocator()->get('Audit');
		/** @var \Awyiss\Model\Entity\Audit $audit */
		$audit = $auditModel->newEntity($auditData);

		if ($this->getConfig('isPivotTable') && $this->getConfig('leftTable') && $this->getConfig('rightTable')) {
			$leftAssociation = $this->table()->getAssociation($this->getConfig('leftTable'));
			$rightAssociation = $this->table()->getAssociation($this->getConfig('rightTable'));

			if (!($leftAssociation instanceof BelongsTo) || !($rightAssociation instanceof BelongsTo)) {
				throw new RuntimeException('The `leftTable` and `rightTable` configurations must refer to BelongsToMany associations.');
			}

			$leftForeignKey = $leftAssociation->getForeignKey();
			$rightForeignKey = $rightAssociation->getForeignKey();

			$audit->subjectLeftForeignKey = $entity->get($leftForeignKey);
			$audit->subjectLeftTable = $leftAssociation->getTarget()->getTable();

			$audit->subjectRightForeignKey = $entity->get($rightForeignKey);
			$audit->subjectRightTable = $rightAssociation->getTarget()->getTable();
		}

		//Save the audit entity and skip the access check
		if (!$auditModel->save($audit)) {
			Log::error(sprintf('Could not save audit. Entity errors: `%s`', print_r($audit->getErrors(), true)));
			throw new RuntimeException('Could not save audit.');
		}

		unset($this->auditData[ $entity->get('id') ]);
	}

	/**
	 * Create the actual audit entry after the entity has been deleted.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 */
	public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if (!$entity->allowsAudit()) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'audit'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		$entityData = $this->auditData[ $entity->get('id') ] ?? [];

		$identityId = $this->getIdentityId();

		//Set the data to be used in `newEntity`
		$auditData = [
			'transactionId' => $options['transactionId'],
			'type' => 'd',
			'scope' => $event->getSubject()->getTable(),
			'foreignKey' => $entity->get('id'),
			'dataOld' => base64_encode(gzcompress(json_encode($entityData['old']), 9)),
			'dataNew' => base64_encode(gzcompress(json_encode($entityData['new']), 9)),
			'diff' => $entityData['changes'],
			'createdOn' => new DateTime(),
			'createdBy' => $identityId,
		];

		/** @var \Awyiss\Model\Table\AuditTable $auditModel */
		$auditModel = $this->getTableLocator()->get('Audit');
		/** @var \Awyiss\Model\Entity\Audit $audit */
		$audit = $auditModel->newEntity($auditData);

		if ($this->getConfig('isPivotTable') && $this->getConfig('leftTable') && $this->getConfig('rightTable')) {
			$leftAssociation = $this->table()->getAssociation($this->getConfig('leftTable'));
			$rightAssociation = $this->table()->getAssociation($this->getConfig('rightTable'));

			if (!($leftAssociation instanceof BelongsTo) || !($rightAssociation instanceof BelongsTo)) {
				throw new RuntimeException('The `leftTable` and `rightTable` configurations must refer to BelongsToMany associations.');
			}

			$leftForeignKey = $leftAssociation->getForeignKey();
			$rightForeignKey = $rightAssociation->getForeignKey();

			$audit->subjectLeftForeignKey = $entity->get($leftForeignKey);
			$audit->subjectLeftTable = $leftAssociation->getTarget()->getTable();

			$audit->subjectRightForeignKey = $entity->get($rightForeignKey);
			$audit->subjectRightTable = $rightAssociation->getTarget()->getTable();
		}

		//Save the audit entity and skip the access check
		if (!$auditModel->save($audit)) {
			Log::error(sprintf('Could not save audit. Entity errors: `%s`', print_r($audit->getErrors(), true)));
			throw new RuntimeException('Could not save audit.');
		}

		unset($this->auditData[ $entity->get('id') ]);
	}


	/**
	 * Sets the identity
	 *
	 * @param \Authentication\IdentityInterface $identity
	 * @return void
	 */
	public function setIdentity(IdentityInterface $identity): void {
		$this->identity = $identity;
	}


	/**
	 * @param string $name
	 * @param array $entityData
	 * @return array
	 */
	protected function auditField(string $name, array $entityData): array {
		$oldData = $entityData['old'][ $name ] ?? null;
		$newData = $entityData['new'][ $name ] ?? null;

		if ($oldData === $newData) {
			return $entityData;
		}

		$entityData['changes']['old'][ $name ] = $oldData;
		$entityData['changes']['new'][ $name ] = $newData;

		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @param \Cake\ORM\Association|false $association
	 * @param array $entityData
	 * @return array
	 */
	protected function auditAssociation(Entity $entity, string $field, Association|false $association, array $entityData): array {
		if (
			!$association ||
			(
				!$association instanceof BelongsToMany &&
				$association->getCascadeCallbacks() &&
				$association->hasBehavior('Audit') &&
				$association->getBehavior('Audit')->getConfig('enabled')
			)
		) {
			/**
			 * No association (set to false in getAssociations) or one with cascadeCallbacks = true
			 * means that property must not be part of the audit data.
			 * Associations with cascadeCallbacks set to true will have their own `afterSave`-event, creating a separate audit
			 */
			unset($entityData['old'][ $field ], $entityData['new'][ $field ]);


			return $entityData;
		}

		if ($association->type() == Association::ONE_TO_MANY) {
			$entityData = $this->cleanHasManyAssociationData($entity, $field, $association, $entityData);
		}
		elseif ($association->type() === Association::MANY_TO_MANY) {
			$entityData = $this->cleanBelongsToManyAssociationData($entity, $field, $association, $entityData);
		}
		elseif ($association->type() === Association::ONE_TO_ONE) {
			$entityData = $this->cleanHasOneAssociationData($entity, $field, $association, $entityData);
		}

		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $entityData
	 * @return array
	 */
	protected function auditMediaAssignments(Entity $entity, array $entityData): array {
		if (!$entity->getSource()) {
			return $entityData;
		}

		$newData = [];

		$blocklistedFields = [
			'id',
			'foreignKey',
			'deleted',
			'createdBy',
			'createdOn',
			'changedBy',
			'changedOn',
			'deletedBy',
			'deletedOn',
			'media',
			'mediaFolder',
		];

		if ($entity->get('mediaAssignments')) {
			$sourceTable = $this->fetchTable($entity->getSource());
			/** @var \Awyiss\Model\Entity $clonedEntity */
			$clonedEntity = unserialize(serialize($entity));

			$sourceTable->getBehavior('MediaAssignment')->rebuildMediaAssignments($clonedEntity);

			foreach ($clonedEntity->get('mediaAssignments') as $elementIdentifier => $elementAssignments) {
				$newData = $this->buildMediaAssignment(
					$newData,
					$elementIdentifier,
					$elementAssignments,
					$blocklistedFields
				);
			}
		}

		ksort($newData);

		$oldData = [];
		if ($entity->hasOriginal('mediaAssignments')) {
			$sourceTable = $this->fetchTable($entity->getSource());
			/** @var \Awyiss\Model\Entity $clonedEntity */
			$clonedEntity = unserialize(serialize($entity));
			$clonedEntity->patch($clonedEntity->extractOriginal());

			$sourceTable->getBehavior('MediaAssignment')->rebuildMediaAssignments($clonedEntity);

			foreach ($clonedEntity->get('mediaAssignments') as $elementIdentifier => $elementAssignments) {
				$oldData = $this->buildMediaAssignment(
					$oldData,
					$elementIdentifier,
					$elementAssignments,
					$blocklistedFields
				);
			}
		}

		ksort($oldData);

		// Even if the assignments are the same, they have to make their way into the db as plain arrays, not entities
		$entityData['old']['mediaAssignments'] = $oldData;
		$entityData['new']['mediaAssignments'] = $newData;

		if ($oldData === $newData) {
			return $entityData;
		}

		$entityData['changes']['old']['mediaAssignments'] = $oldData;
		$entityData['changes']['new']['mediaAssignments'] = $newData;

		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $entityData
	 * @return array
	 */
	protected function auditPublicationData(Entity $entity, array $entityData): array {
		if (!$entity->getSource()) {
			return $entityData;
		}

		$oldData = $newData = ['start' => ['dateTime' => null], 'end' => ['dateTime' => null]];
		// If the entity has no original publication data, the data hasn't changed
		if (
			!$entity->hasOriginal('_publicationData') ||
			$entity->get('_publicationData') === $entity->getOriginal('_publicationData')
		) {
			unset($entityData['changes']['old']['_publicationData'], $entityData['changes']['new']['_publicationData']);

			// If the entity has no publication data, we can skip the audit and unset the publication data from the entity data
			if ($entity->get('_publicationData') === null) {
				unset($entityData['old']['_publicationData'], $entityData['new']['_publicationData']);

				return $entityData;
			}

			// If the publication data is not empty, old = new
			$entityData['old']['_publicationData'] = $entity->get('_publicationData');
			$entityData['new']['_publicationData'] = $entity->get('_publicationData');

			return $entityData;
		}

		/** @var \Awyiss\Model\Entity\PublicationData $publicationData */
		foreach ($entity->getOriginal('_publicationData') ?? [] as $publicationData) {
			$date = $publicationData->hasOriginal('dateTime') ? $publicationData->getOriginal('dateTime') : $publicationData->get('dateTime');

			if ($date) {
				$date = $date->format('Y-m-d H:i:s');
			}

			$oldData[ $publicationData->type->value ] = [
				'dateTime' => $date ?: null,
			];
		}

		/**
		 * @var \Awyiss\Model\Entity\PublicationData $publicationData
		 */
		foreach ($entity->get('_publicationData') ?? [] as $publicationData) {
			$date = $publicationData->has('dateTime') ? $publicationData->get('dateTime') : null;

			if ($date) {
				$date = $date->format('Y-m-d H:i:s');
			}

			$newData[ $publicationData->type->value ] = [
				'dateTime' => $date ?: null,
			];
		}

		// Even if the publication data stayed the same, it has to make their way into the db as plain arrays, not entities
		$entityData['old']['_publicationData'] = $oldData;
		$entityData['new']['_publicationData'] = $newData;

		if ($oldData === $newData) {
			return $entityData;
		}

		$entityData['changes']['old']['_publicationData'] = $oldData;
		$entityData['changes']['new']['_publicationData'] = $newData;

		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $entityData
	 * @return array
	 */
	protected function auditTranslations(Entity $entity, array $entityData): array {
		if (!$entity->getSource()) {
			return $entityData;
		}

		$translateFields = null;
		/** @var \Awyiss\Model\Table $sourceTable */
		$sourceTable = $this->fetchTable($entity->getSource());
		if ($sourceTable->hasBehavior('Translate')) {
			$translateFields = $sourceTable->getBehavior('Translate')->getConfig('fields');
		}

		if (!$translateFields) {
			unset($entityData['old']['_translations'], $entityData['new']['_translations'], $entityData['changes']['old']['_translations'], $entityData['changes']['new']['_translations']);

			return $entityData;
		}

		$newTranslations = [];
		/**
		 * @var Entity $translatedEntity
		 * @noinspection PhpLoopCanBeConvertedToArrayMapInspection
		 */
		foreach (($entity->get('_translations') ?? []) as $languageShortcode => $translatedEntity) {
			$newTranslations[ $languageShortcode ] = $translatedEntity?->extract($translateFields, false, false) ?? null;
		}

		$hasOldTranslations = $entity->hasOriginal('_translations');
		$oldTranslations = $hasOldTranslations ? $entity->getOriginal('_translations') : $entity->get('_translations');
		if ($oldTranslations) {
			/** @var Entity $translatedEntity */
			foreach ($oldTranslations as $languageShortcode => $translatedEntity) {
				$oldTranslations[ $languageShortcode ] = [];
				foreach ($translateFields as $field) {
					if ($translatedEntity->hasOriginal($field)) {
						$value = $translatedEntity->getOriginal($field);
					}
					else {
						$value = $hasOldTranslations || !$translatedEntity->isDirty($field) ? $translatedEntity->get($field) : null;
					}

					$oldTranslations[ $languageShortcode ][ $field ] = $value;
				}

				if (!array_filter($oldTranslations[ $languageShortcode ], fn ($value) => $value !== null)) {
					// If the translations only contain null values, remove them
					unset($oldTranslations[ $languageShortcode ]);
				}
			}
		}

		// If old translations only contain null values, remove them
		if (!array_filter($oldTranslations, fn ($value) => $value !== null)) {
			$oldTranslations = null;
		}

		// Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$entityData['old']['_translations'] = $oldTranslations;
		$entityData['new']['_translations'] = $newTranslations;

		if ($oldTranslations === $newTranslations) {
			return $entityData;
		}

		$entityData['changes']['old']['_translations'] = $oldTranslations;
		$entityData['changes']['new']['_translations'] = $newTranslations;

		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $entityData
	 * @return void
	 */
	protected function auditAssociationTranslations(Entity $entity, array &$entityData): void {
		$translations = [];

		foreach ($entity->get('_translations') as $shortcode => $translatedEntity) {
			$translations[ $shortcode ] = array_diff_key($translatedEntity->toArray(), array_flip($translatedEntity->getVirtual()));

			// If the translations only contain null values, remove them
			if (array_filter($translations[ $shortcode ], fn ($value) => $value !== null) === []) {
				unset($translations[ $shortcode ]);
			}
		}

		if ($translations) {
			$entityData['_translations'] = $translations;
		}
	}


	/**
	 * Return the ID of the currently set identity
	 *
	 * @return ?int
	 */
	protected function getIdentityId(): ?int {
		return $this->getIdentity()?->getIdentifier();
	}


	/**
	 * Set the info for a new entity
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param int|null $identityId
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 * @return void
	 */
	protected function setCreateInfo(Entity $entity, ?int $identityId, TableSchemaInterface $schema): void {
		$entity->set('createdOn', DateTime::now());
		if ($identityId && $schema->getColumn('created_by')) {
			$entity->set('createdBy', $identityId);
		}

		$entity->unset(['changedOn', 'changedBy']);
	}


	/**
	 * Set the info for an existing entity
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param int|null $identityId
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 * @return void
	 */
	protected function setUpdateInfo(Entity $entity, ?int $identityId, TableSchemaInterface $schema): void {
		$entity->set('changedOn', DateTime::now());
		if ($identityId && $schema->getColumn('changed_by')) {
			$entity->set('changedBy', $identityId);
		}
	}


	/**
	 * Set the info for a deleted entity
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param int|null $identityId
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 * @return void
	 */
	protected function setDeleteInfo(Entity $entity, ?int $identityId, TableSchemaInterface $schema): void {
		$entity->set('deletedOn', DateTime::now());
		if ($identityId && $schema->getColumn('deleted_by')) {
			$entity->set('deletedBy', $identityId);
		}
	}


	/**
	 * @return array
	 */
	protected function getAssociations(): array {
		$associations = [];

		foreach ($this->_table->associations() as $association) {
			$property = Inflector::variable($association->getProperty());

			if (in_array($association->getTarget()->getTable(), ['i18n', 'media_assignments'])) {
				$association = false;
			}

			$associations[ $property ] = $association;
		}

		return $associations;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @param \Cake\ORM\Association|\Awyiss\ORM\Association\HasOne $association
	 * @param array $entityData
	 * @return array
	 */
	protected function cleanHasOneAssociationData(Entity $entity, string $field, Association|HasOne $association, array $entityData): array {
		/** @noinspection DuplicatedCode */
		$keys = (array)$association->getBindingKey();
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $association->getSource()->getEntityClass();
		$keys = $entityClass::mapFields($keys);

		$foreignKeys = (array)$association->getForeignKey();
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $association->getTarget()->getEntityClass();
		$foreignKeys = $entityClass::mapFields($foreignKeys);

		$keys = array_flip(array_merge($keys, $foreignKeys));
		$keys['_locale'] = true;
		$keys['_translations'] = true;

		$associatedEntity = $entity->get($field);

		$newData = [];
		$originalEntityData = $entityData;
		if ($associatedEntity instanceof Entity) {
			$newData = $associatedEntity->extract(null, false, false);
			$newData = array_diff_key($newData, $keys);

			if (!$associatedEntity->isDirty()) {
				$entityData['old'][ $field ] = $newData;
				$entityData['new'][ $field ] = $newData;

				return $entityData;
			}

			if (isset($newData['_translations'])) {
				/** @var \Awyiss\Model\Entity $translation */
				foreach ($newData['_translations'] as $languageShortcode => $translation) {
					$newData['_translations'][ $languageShortcode ] = array_diff_key($translation->extract(null, false, false), $keys);
					unset($newData['_translations'][ $languageShortcode ]['locale']);
				}
			}
		}

		$oldData = [];
		if (!$entity->hasOriginal($field)) {
			if ($entity->get($field) instanceof Entity && !$entity->get($field)->isNew()) {
				$oldData = array_diff_key($entity->get($field)->getOriginalValues(), $keys);
			}
			else {
				if (!isset($newData)) {
					return $originalEntityData;
				}

				//Do not fuck around with the original entity;
				$clonedEntity = unserialize(serialize($entity));
				$this->table()->loadInto($clonedEntity, [$association->getName()]);
				if ($clonedEntity->get($field) instanceof Entity) {
					$oldData = $clonedEntity->get($field)->getOriginalValues();
				}
			}

			$oldData = array_diff_key($oldData, $keys);

			if (isset($oldData['_translations'])) {
				/** @var \Awyiss\Model\Entity $translation */
				foreach ($oldData['_translations'] as $languageShortcode => $translation) {
					$oldData['_translations'][ $languageShortcode ] = array_diff_key($translation->extractOriginal(null, false), $keys);
					unset($oldData['_translations'][ $languageShortcode ]['locale']);
				}
			}
		}
		elseif ($associatedEntity) {
			$oldData = array_diff_key($associatedEntity->getOriginalValues(), $keys);
		}

		unset($oldData['_locale'], $newData['_locale']);

		/** @noinspection DuplicatedCode */
		$entityData['old'][ $field ] = $oldData;
		$entityData['new'][ $field ] = $newData;

		if ($oldData === $newData) {
			return $entityData;
		}

		$entityData['changes']['old'][ $field ] = $oldData;
		$entityData['changes']['new'][ $field ] = $newData;


		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @param \Cake\ORM\Association|\Awyiss\ORM\Association\HasMany $association
	 * @param array $entityData
	 * @return array
	 */
	protected function cleanHasManyAssociationData(Entity $entity, string $field, Association|HasMany $association, array $entityData): array {
		/** @noinspection DuplicatedCode */
		$keys = (array)$association->getBindingKey();
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $association->getSource()->getEntityClass();
		$keys = $entityClass::mapFields($keys);

		$foreignKeys = (array)$association->getForeignKey();
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $association->getTarget()->getEntityClass();
		$foreignKeys = $entityClass::mapFields($foreignKeys);

		$keys = array_flip(array_merge($keys, $foreignKeys));
		$keys['_locale'] = true;
		$keys['_translations'] = true;

		$oldData = $entityData['old'][ $field ] ?? null;
		$newData = $entityData['new'][ $field ] ?? null;

		if ($newData) {
			/** @var \Awyiss\Model\Entity $associationEntity */
			foreach ($newData as $key => $associationEntity) {
				$newData[ $key ] = array_diff_key($associationEntity->toArray(), $keys + array_flip($associationEntity->getVirtual()));

				if ($associationEntity->has('_translations')) {
					$this->auditAssociationTranslations($associationEntity, $newData[ $key ]);
				}
			}
		}

		if (!$entity->hasOriginal($field)) {
			if (!isset($newData)) {
				return $entityData;
			}

			// Do not fuck around with the original entity;
			$clonedEntity = unserialize(serialize($entity));
			$clonedEntity->unset($field);
			$this->table()->loadInto($clonedEntity, [$association->getName()]);
			$oldData = $clonedEntity->get($field);
		}

		if ($oldData) {
			/** @var Entity $associationEntity */
			foreach ($oldData as $key => $associationEntity) {
				$oldData[ $key ] = array_diff_key($associationEntity->toArray(), $keys + array_flip($associationEntity->getVirtual()));

				if ($associationEntity->has('_translations')) {
					$this->auditAssociationTranslations($associationEntity, $oldData[ $key ]);
				}
			}
		}

		if ($oldData && $newData) {
			foreach ($newData as $key => &$newEntityData) {
				if (!isset($oldData[ $key ])) {
					continue;
				}

				// Set all fields that are not present in the new entity to the values of the old entity
				$newEntityData += $oldData[ $key ];
			}
			unset($newEntityData);
		}

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$entityData['old'][ $field ] = $oldData;
		$entityData['new'][ $field ] = $newData;

		if ($oldData === $newData) {
			return $entityData;
		}

		$entityData['changes']['old'][ $field ] = $oldData;
		$entityData['changes']['new'][ $field ] = $newData;


		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @param \Cake\ORM\Association|\Awyiss\ORM\Association\BelongsToMany $association
	 * @param array $entityData
	 * @return array
	 * @noinspection PhpFunctionCyclomaticComplexityInspection
	 */
	protected function cleanBelongsToManyAssociationData(Entity $entity, string $field, Association|BelongsToMany $association, array $entityData): array {
		$keys = (array)$association->getBindingKey();
		if (count($keys) > 1) {
			dd('ohoh', $keys, __FILE__, __LINE__);
		}

		$joinKey = '_joinData';
		$junctionKeys = [];
		$hasThrough = $association->hasThrough();
		if ($hasThrough) {
			$junctionKeys = [
				$association->getBindingKey(),
				$association->getForeignKey(),
				$association->getTargetForeignKey(),
			];
		}

		$oldData = $entityData['old'][ $field ] ?? null;
		$newData = $entityData['new'][ $field ] ?? null;

		if ($newData) {
			foreach ($newData as $key => $newEntity) {
				if ($newEntity instanceof Entity) {
					$newData[ $key ] = $newEntity->extract($keys, false, false);

					/** @var \Awyiss\Model\Entity $joinData */
					$joinData = $newEntity->get($joinKey);
					if (!$hasThrough || !$joinData) {
						continue;
					}

					//Disable the audit for the junction entity
					$joinData->disableAudit();

					$newData[ $key ]['_joinData'] = array_diff_key($joinData->extract(), array_flip($junctionKeys));
				}
				else {
					$newData[ $key ] = $newEntity;
				}
			}

			$newData = array_filter($newData);
		}

		if (!$entity->hasOriginal($field)) {
			if (!isset($newData)) {
				return $entityData;
			}

			// Do not fuck around with the original entity;
			$clonedEntity = unserialize(serialize($entity));
			$clonedEntity->unset($field);
			$this->table()->loadInto($clonedEntity, [$association->getName()]);
			$oldData = $clonedEntity->get($field);
		}

		if ($oldData) {
			foreach ($oldData as $key => $oldEntity) {
				$oldEntityData = $oldEntity->extract($keys);
				$oldData[ $key ] = $oldEntityData;

				/** @var Entity $joinData */
				$joinData = $oldEntity->get($joinKey);
				if (!$hasThrough || !$joinData) {
					continue;
				}

				$oldData[ $key ]['_joinData'] = array_diff_key($joinData->extract(), array_flip($junctionKeys));
			}
		}

		// If only ids are part of the diff, set the diff as <propertyname>._ids
		if (!$hasThrough && count($keys) === 1 && $keys[0] === 'id') {
			$oldData = ['_ids' => array_column($oldData ?? [], 'id')];
			$newData = ['_ids' => array_column($newData ?? [], 'id')];

			// Sort the ids
			sort($oldData['_ids']);
			sort($newData['_ids']);
		}

		// Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$entityData['old'][ $field ] = $oldData;
		$entityData['new'][ $field ] = $newData;

		if ($oldData === $newData) {
			return $entityData;
		}

		$entityData['changes']['old'][ $field ] = $oldData;
		$entityData['changes']['new'][ $field ] = $newData;


		return $entityData;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param bool $deleted
	 * @return array
	 */
	protected function buildEntityData(Entity $entity, bool $deleted = false): array {
		$entityData = [
			'old' => $entity->getOriginalValues(),
			'new' => $entity->extract(null, false, false),
			'changes' => [
				'old' => [],
				'new' => [],
			],
		];

		$allFields = array_keys(array_merge($entityData['old'], $entityData['new']));
		$allFields = array_diff($allFields, $entity->getVirtual());
		$ignoredFields = $this->getConfig('ignoredFields');

		foreach ($entity->getVirtual() as $virtualField) {
			unset(
				$entityData['old'][ $virtualField ],
				$entityData['new'][ $virtualField ],
				$allFields[ $virtualField ],
			);
		}

		$associationTypes = $this->getAssociations();

		foreach ($allFields as $field) {
			if (in_array($field, $ignoredFields)) {
				unset($entityData['old'][ $field ], $entityData['new'][ $field ]);
				continue;
			}

			if ($field === 'mediaAssignments') {
				$entityData = $this->auditMediaAssignments($entity, $entityData);
				continue;
			}

			if ($field === '_publicationData') {
				$entityData = $this->auditPublicationData($entity, $entityData);
				continue;
			}

			if ($field === '_translations') {
				$entityData = $this->auditTranslations($entity, $entityData);
				continue;
			}

			if (array_key_exists($field, $associationTypes)) {
				$entityData = $this->auditAssociation($entity, $field, $associationTypes[ $field ], $entityData);
				continue;
			}

			$entityData = $this->auditField($field, $entityData);
		}

		//No difference? Do nothing.
		if (empty($entityData['changes']['old']) && empty($entityData['changes']['new']) && !$deleted) {
			return [];
		}

		// Sort all arrays
		$sort = function ($a, $b) {
			// If both start with an underscore, sort by the field name
			if (str_starts_with($a, '_') && str_starts_with($b, '_')) {
				return $a <=> $b;
			}

			if (str_starts_with($a, '_')) {
				return 1;
			}

			if (str_starts_with($b, '_')) {
				return -1;
			}

			return $a <=> $b;
		};

		uksort($entityData['old'], $sort);
		uksort($entityData['new'], $sort);
		uksort($entityData['changes']['old'], $sort);
		uksort($entityData['changes']['new'], $sort);

		return $entityData;
	}


	/**
	 * @param array $data
	 * @param string|int $elementIdentifier
	 * @param array $elementAssignments
	 * @param array $blocklistedFields
	 * @return array
	 */
	protected function buildMediaAssignment(array $data, string|int $elementIdentifier, array $elementAssignments, array $blocklistedFields): array {
		foreach ($elementAssignments as $selectorIdentifier => $selectorAssignments) {
			if ($selectorAssignments instanceof Entity) {
				$values = $selectorAssignments->extract(null, false, false);

				$values = array_diff_key($values, array_flip($blocklistedFields));

				ksort($values);

				$data[ $elementIdentifier ][ $selectorIdentifier ] = $values;

				continue;
			}

			foreach ($selectorAssignments as $key => $mediaAssignment) {
				$values = $mediaAssignment->extract(null, false, false);

				$values = array_diff_key($values, array_flip($blocklistedFields));

				ksort($values);

				$data[ $elementIdentifier ][ $selectorIdentifier ][ $key ] = $values;
			}
		}

		if (is_array($data[ $elementIdentifier ] ?? null)) {
			ksort($data[ $elementIdentifier ]);
		}

		return $data;
	}
}
