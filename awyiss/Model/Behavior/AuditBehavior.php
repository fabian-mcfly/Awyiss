<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Authentication\IdentityInterface;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
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
use Cake\Utility\Inflector;
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
	protected array $_defaultConfig = [
		'enabled' => true,
		'implementedEvents' => [
			'buildValidator',
			'beforeCopy',
			'beforeSave',
			'beforeDelete',
			'afterSave',
		],
		'ignoredFields' => [
			'createdOn',
			'createdBy',
			'changedOn',
			'changedBy',
			'deletedOn',
			'deletedBy',
			'_i18n',
			'_locale',
			'_joinData',
		],
		'setTimeOnCreate' => true,
		'setTimeOnUpdate' => true,
		'setTimeOnDelete' => true,
		'skip' => false,
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
		$lo_table = $this->table();
		$lo_schema = $lo_table->getSchema();

		// Check if the table has the required columns and add the corresponding associations
		if ($lo_schema->hasColumn('created_by')) {
			$this->addAssociation('CreatedBy');
		}

		if ($lo_schema->hasColumn('changed_by')) {
			$this->addAssociation('ChangedBy');
		}

		if ($lo_schema->hasColumn('deleted_by')) {
			$this->addAssociation('DeletedBy');
		}
	}


	/**
	 * Add an association to the table.
	 *
	 * @param string $alias The alias for the association.
	 * @return void
	 */
	protected function addAssociation(string $alias): void {
		$lo_table = $this->table();

		// Add a belongsTo association to the table
		$lo_table->belongsTo($alias . 'User', [
			'className' => 'Users',
			'foreignKey' => Inflector::underscore($alias),
		]);
	}


	/**
	 * Modify the query to join with the audit users and select their usernames.
	 *
	 * @param SelectQuery $query The query to modify.
	 * @return SelectQuery The modified query.
	 */
	public function findWithAuditUsers(SelectQuery $query): SelectQuery {
		// Enable auto fields for the query if they are not already enabled
		if ($query->isAutoFieldsEnabled() === null) {
			$query->enableAutoFields();
		}

		// Define the associations to join with
		$la_associations = ['CreatedByUser', 'ChangedByUser', 'DeletedByUser'];
		foreach ($la_associations as $ls_associationName) {
			// Skip the association if it does not exist
			if (!$this->table()->hasAssociation($ls_associationName)) {
				continue;
			}

			// Join with the association and select the username
			$query->leftJoinWith($ls_associationName)->select($ls_associationName . '.username');
		}

		// Handle _matchingData
		$query->formatResults(function (CollectionInterface $results) {
			return $results->map(function (EntityInterface|array|null $row) {
				$lx_row = $row;
				// Skip the row if it does not have _matchingData
				if (!$lx_row || !isset($lx_row['_matchingData'])) {
					return $lx_row;
				}

				// Iterate over the matching data
				foreach ($lx_row['_matchingData'] as $ls_matchingKey => $lo_user) {
					// Modify the row data based on the matching data
					$ls_property = Inflector::variable($ls_matchingKey);
					$lx_row[ $ls_property ] = $lo_user->username;
					unset($lx_row['_matchingData'][ $ls_matchingKey ]);
				}

				// Remove the _matchingData key if it is empty
				if (empty($lx_row['_matchingData'])) {
					unset($lx_row['_matchingData']);
				}


				return $lx_row;
			});
		});


		return $query;
	}


	/**
	 * @param EventInterface $event
	 * @param Validator $validator
	 * @param string $name
	 * @return Validator
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildValidator(EventInterface $event, Validator $validator, string $name): Validator {
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


		return $validator;
	}


	/**
	 * @param EventInterface $event
	 * @param Entity $entity
	 * @param ArrayObject $options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeDelete(EventInterface $event, Entity $entity, ArrayObject $options): void {
		if (!isset($options['transactionId'])) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$options['transactionId'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
		}
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param EventInterface $event
	 * @param Entity $entity
	 * @param ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeCopy(EventInterface $event, Entity $entity, ArrayObject $options): void {
		$entity->unset(['createdOn', 'changedOn', 'changedBy', 'changedOn']);
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param EventInterface $event
	 * @param Entity $entity
	 * @param ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options): void {
		if (!isset($options['transactionId'])) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$options['transactionId'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($options, 'audit'));

		if ($la_options['skip'] === true) {
			return;
		}

		$lb_isNew = $entity->isNew();
		$li_identityId = $this->getIdentityId();
		$lo_schema = $this->table()->getSchema();

		if (empty($entity->deleted)) {
			if ($lb_isNew && $lo_schema->getColumn('created_on') && $la_options['setTimeOnCreate']) {
				//If the item is new, and if config wants it, set the create-info on this entity
				$this->setCreateInfo($entity, $li_identityId, $lo_schema);
			}
			elseif (!$lb_isNew && $lo_schema->getColumn('changed_on') && $la_options['setTimeOnUpdate']) {
				//If the item is not new, and if config wants it, set the update-info on this entity
				$this->setUpdateInfo($entity, $li_identityId, $lo_schema);
			}
		}
		//elseif ($lo_schema->getColumn('deleted') && ! empty($entity->deleted) && ( ! $entity->hasOriginal('deleted') || $entity->deleted != $entity->getOriginal('deleted'))) {
		elseif ($lo_schema->getColumn('deleted') && (!$entity->hasOriginal('deleted') || $entity->deleted != $entity->getOriginal('deleted'))) {
			//A soft delete will set the `deleted`-property. If this happens, and the config wants it, set the delete-info on this entity
			if ($lo_schema->getColumn('deleted_on') && $la_options['setTimeOnDelete']) {
				$this->setDeleteInfo($entity, $li_identityId, $lo_schema);
			}
		}

		if ($entity->isNew() || !$entity->allowsAudit()) {
			return;
		}

		$this->auditData[ $entity->id ] = $this->buildEntityData($entity);
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param EventInterface $event
	 * @param Entity $entity
	 * @param ArrayObject $options
	 */
	public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if ($entity->isNew() || !$entity->allowsAudit()) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($options, 'audit'));

		if ($la_options['skip'] === true) {
			return;
		}

		$la_entityData = $this->auditData[ $entity->id ];
		//No difference? Do nothing.
		if (empty($la_entityData) || empty($la_entityData['changes']['old']) && empty($la_entityData['changes']['new'])) {
			return;
		}

		$li_identityId = $this->getIdentityId();

		//Set the data to be used in `newEntity`
		$la_auditData = [
			'transactionId' => $options['transactionId'],
			'type' => !empty($entity->deleted) ? 'd' : 'u',
			'scope' => $event->getSubject()->getTable(),
			'parentId' => $entity->get('id'),
			'dataOld' => $la_entityData['old'],
			'dataNew' => $la_entityData['new'],
			'diff' => $la_entityData['changes'],
			'createdOn' => new DateTime(),
			'createdBy' => $li_identityId,
		];

		$lo_auditModel = $this->getTableLocator()->get('Audit');
		$lo_audit = $lo_auditModel->newEntity($la_auditData);

		//Save the audit entity and skip the access check
		if (!$lo_auditModel->save($lo_audit)) {
			Log::error(sprintf('Could not save audit. Entity errors: `%s`', print_r($lo_audit->getErrors(), true)));
			throw new RuntimeException('Could not save audit.');
		}

		unset($this->auditData[ $entity->id ]);
	}


	/**
	 * Sets the identity
	 *
	 * @param IdentityInterface $identity
	 * @return void
	 * @noinspection PhpUnused
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
		$lx_oldData = $entityData['old'][ $name ] ?? null;
		$lx_newData = $entityData['new'][ $name ] ?? null;

		if ($lx_oldData === $lx_newData) {
			return $entityData;
		}

		$la_entityData = $entityData;

		$la_entityData['changes']['old'][ $name ] = $lx_oldData;
		$la_entityData['changes']['new'][ $name ] = $lx_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $entity
	 * @param string $field
	 * @param Association|false $association
	 * @param array $entityData
	 * @return array
	 */
	protected function auditAssociation(Entity $entity, string $field, Association|false $association, array $entityData): array {
		$la_entityData = $entityData;

		if (!$association || ($association->getCascadeCallbacks() && $association->hasBehavior('Audit') && $association->getBehavior('Audit')->getConfig('enabled'))) {
			/**
			 * No association (set to false in getAssociations) or one with cascadeCallbacks = true
			 * means that property must not be part of the audit data.
			 * Assocations with cascadeCallbacks set to true will have their own `afterSave`-event, creating a separat audit
			 */
			unset($la_entityData['old'][ $field ], $la_entityData['new'][ $field ]);


			return $la_entityData;
		}

		if ($association->type() == Association::ONE_TO_MANY) {
			$la_entityData = $this->cleanHasManyAssociationData($entity, $field, $association, $la_entityData);
		}
		elseif ($association->type() === Association::MANY_TO_MANY) {
			$la_entityData = $this->cleanBelongsToManyAssociationData($entity, $field, $association, $la_entityData);
		}
		elseif ($association->type() === Association::ONE_TO_ONE) {
			$la_entityData = $this->cleanHasOneAssociationData($entity, $field, $association, $la_entityData);
		}


		return $la_entityData;
	}


	/**
	 * @param Entity $entity
	 * @param array $entityData
	 * @return array
	 */
	protected function auditMediaAssignments(Entity $entity, array $entityData): array {
		if (!$entity->getSource()) {
			return $entityData;
		}

		$la_entityData = $entityData;

		$la_newData = [];

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if ($entity->mediaAssignments) {
			$lo_sourceTable = $this->fetchTable($entity->getSource());
			$lo_clonedEntity = clone $entity;

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_sourceTable->getBehavior('MediaAssignment')->rebuildMediaAssignments($lo_clonedEntity);

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			foreach ($lo_clonedEntity->mediaAssignments as $ls_compositeIdentifier => $la_compositeAssignments) {
				foreach ($la_compositeAssignments as $ls_selectorIdentifier => $lx_selectorAssignments) {
					if ($lx_selectorAssignments instanceof Entity) {
						$la_newData[ $ls_compositeIdentifier ][ $ls_selectorIdentifier ] = $lx_selectorAssignments->extract(null, false, false);
						continue;
					}

					foreach ($lx_selectorAssignments as $li_key => $lo_mediaAssignment) {
						$la_newData[ $ls_compositeIdentifier ][ $ls_selectorIdentifier ][ $li_key ] = $lo_mediaAssignment->extract(null, false, false);
					}
				}
			}
		}

		$la_blocklistedFields = [
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
		];

		$la_oldData = [];
		if ($entity->hasOriginal('mediaAssignments')) {
			/** @var Entity $lo_mediaAssignment */
			foreach ($entity->getOriginal('mediaAssignments') as $ls_compositeIdentifier => $la_compositeAssignments) {
				foreach ($la_compositeAssignments as $ls_selectorIdentifier => $lx_selectorAssignments) {
					if ($lx_selectorAssignments instanceof Entity) {
						$la_values = $lx_selectorAssignments->extract(null, false, false);

						$la_values = array_diff_key($la_values, array_flip($la_blocklistedFields));

						$la_oldData[ $ls_compositeIdentifier ][ $ls_selectorIdentifier ] = $la_values;
						continue;
					}

					foreach ($lx_selectorAssignments as $li_key => $lo_mediaAssignment) {
						$la_values = $lo_mediaAssignment->extract(null, false, false);

						$la_values = array_diff_key($la_values, array_flip($la_blocklistedFields));

						$la_oldData[ $ls_compositeIdentifier ][ $ls_selectorIdentifier ][ $li_key ] = $la_values;
					}
				}
			}
		}

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old']['mediaAssignments'] = $la_oldData;
		$la_entityData['new']['mediaAssignments'] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old']['mediaAssignments'] = $la_oldData;
		$la_entityData['changes']['new']['mediaAssignments'] = $la_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $entity
	 * @param array $entityData
	 * @return array
	 */
	protected function auditTranslations(Entity $entity, array $entityData): array {
		if (!$entity->getSource()) {
			return $entityData;
		}

		/** @var \Awyiss\Model\Table $lo_sourceTable */
		$lo_sourceTable = $this->fetchTable($entity->getSource());
		$la_translate = $lo_sourceTable->getConfig('translate');
		if (!$la_translate) {
			return $entityData;
		}

		$la_newTranslations = [];
		/** @var Entity $lo_translatedEntity */
		foreach (($entity->_translations ?? []) as $ls_languageShortcode => $lo_translatedEntity) {
			$la_newTranslations[ $ls_languageShortcode ] = $lo_translatedEntity->extract($la_translate['fields'], false, false);
		}

		$la_oldTranslations = [];
		if ($entity->hasOriginal('_translations')) {
			/** @var Entity $lo_translatedEntity */
			foreach ($entity->getOriginal('_translations') as $ls_languageShortcode => $lo_translatedEntity) {
				foreach ($la_translate['fields'] as $ls_field) {
					if ($lo_translatedEntity->hasOriginal($ls_field)) {
						$lx_value = $lo_translatedEntity->getOriginal($ls_field);
					}
					else {
						$lx_value = $lo_translatedEntity->get($ls_field);
					}

					$la_oldTranslations[ $ls_languageShortcode ][ $ls_field ] = $lx_value;
				}
			}
		}

		$la_entityData = $entityData;

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old']['_translations'] = $la_oldTranslations;
		$la_entityData['new']['_translations'] = $la_newTranslations;

		if ($la_oldTranslations === $la_newTranslations) {
			return $la_entityData;
		}

		$la_entityData['changes']['old']['_translations'] = $la_oldTranslations;
		$la_entityData['changes']['new']['_translations'] = $la_newTranslations;


		return $la_entityData;
	}


	/**
	 * Return the ID of the currently set identity
	 *
	 * @return ?int
	 */
	protected function getIdentityId(): ?int {
		$lo_identity = $this->getIdentity();
		$li_identityId = $lo_identity?->getIdentifier();

		if ($lo_identity instanceof UsersExternal) {
			$li_identityId *= -1;
		}


		return $li_identityId;
	}


	/**
	 * Set the info for a new entity
	 *
	 * @param Entity $entity
	 * @param int|null $identityId
	 * @param TableSchemaInterface $schema
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
	 * @param Entity $entity
	 * @param int|null $identityId
	 * @param TableSchemaInterface $schema
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
	 * @param Entity $entity
	 * @param int|null $identityId
	 * @param TableSchemaInterface $schema
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
		$la_associations = [];

		foreach ($this->_table->associations() as $lo_association) {
			$ls_property = Inflector::variable($lo_association->getProperty());

			if (in_array($lo_association->getTarget()->getTable(), ['i18n', 'media_assignments'])) {
				$lo_association = false;
			}

			$la_associations[ $ls_property ] = $lo_association;
		}


		return $la_associations;
	}


	/**
	 * @param Entity $entity
	 * @param string $field
	 * @param Association|HasOne $association
	 * @param array $entityData
	 * @return array
	 */
	protected function cleanHasOneAssociationData(Entity $entity, string $field, Association|HasOne $association, array $entityData): array {
		$la_keys = (array)$association->getBindingKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $association->getSource()->getEntityClass();
		$la_keys = $ls_entityClass::mapFields($la_keys);

		$la_foreignKeys = (array)$association->getForeignKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $association->getTarget()->getEntityClass();
		$la_foreignKeys = $ls_entityClass::mapFields($la_foreignKeys);

		$la_keys = array_flip(array_merge($la_keys, $la_foreignKeys));

		//$lo_entity->extract($la_keys, false, false)
		$lo_associatedEntity = $entity->get($field);

		$la_newData = [];
		$la_entityData = $entityData;
		if ($lo_associatedEntity instanceof Entity) {
			$la_newData = $lo_associatedEntity->extract(null, false, false);
			$la_newData = array_diff_key($la_newData, $la_keys);

			if (!$lo_associatedEntity->isDirty()) {
				$la_entityData['old'][ $field ] = $la_newData;
				$la_entityData['new'][ $field ] = $la_newData;

				return $la_entityData;
			}
		}

		$la_oldData = [];
		if (!$entity->hasOriginal($field)) {
			/** @var Entity $lo_entity */
			$lo_entity = $entity->get($field);
			if (is_a($lo_entity, 'Awyiss\Model\Entity') && !$entity->get($field)->isNew()) {
				$la_oldData = array_diff_key($lo_entity->getOriginalValues(), $la_keys);
			}
			else {
				if (!isset($la_newData)) {
					return $entityData;
				}

				//Do not fuck around with the original entity;
				$lo_clonedEntity = clone $entity;
				$this->table()->loadInto($lo_clonedEntity, [$association->getName()]);
				if ($lo_clonedEntity->get($field)) {
					//$oldData[ $field ] = $lo_clonedEntity->get($field);
					$la_oldData = $lo_clonedEntity->get($field)->getOriginalValues();
				}
			}

			$la_oldData = array_diff_key($la_oldData, $la_keys);
		}
		elseif ($lo_associatedEntity) {
			$la_oldData = array_diff_key($lo_associatedEntity->getOriginalValues(), $la_keys);
		}

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old'][ $field ] = $la_oldData;
		$la_entityData['new'][ $field ] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old'][ $field ] = $la_oldData;
		$la_entityData['changes']['new'][ $field ] = $la_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $entity
	 * @param string $field
	 * @param Association|HasMany $association
	 * @param array $entityData
	 * @return array
	 */
	protected function cleanHasManyAssociationData(Entity $entity, string $field, Association|HasMany $association, array $entityData): array {
		$la_keys = (array)$association->getBindingKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $association->getSource()->getEntityClass();
		$la_keys = $ls_entityClass::mapFields($la_keys);

		$la_foreignKeys = (array)$association->getForeignKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $association->getTarget()->getEntityClass();
		$la_foreignKeys = $ls_entityClass::mapFields($la_foreignKeys);

		$la_keys = array_flip(array_merge($la_keys, $la_foreignKeys));

		$la_oldData = $entityData['old'][ $field ] ?? null;
		$la_newData = $entityData['new'][ $field ] ?? null;

		if ($la_newData) {
			/** @var Entity $lo_entity */
			foreach ($la_newData as $li_key => $lo_entity) {
				$la_newData[ $li_key ] = array_diff_key($lo_entity->toArray(), $la_keys + array_flip($lo_entity->getVirtual()));
			}
		}

		if (!$entity->hasOriginal($field)) {
			if (!isset($la_newData)) {
				return $entityData;
			}

			//Do not fuck around with the original entity;
			$lo_clonedEntity = clone $entity;
			$lo_clonedEntity->unset($field);
			$this->table()->loadInto($lo_clonedEntity, [$association->getName()]);
			$la_oldData = $lo_clonedEntity->get($field);
		}

		if ($la_oldData) {
			/** @var Entity $lo_entity */
			foreach ($la_oldData as $li_key => $lo_entity) {
				$la_oldData[ $li_key ] = array_diff_key($lo_entity->toArray(), $la_keys + array_flip($lo_entity->getVirtual()));
			}
		}

		$la_entityData = $entityData;

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old'][ $field ] = $la_oldData;
		$la_entityData['new'][ $field ] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old'][ $field ] = $la_oldData;
		$la_entityData['changes']['new'][ $field ] = $la_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $entity
	 * @param string $field
	 * @param Association|BelongsToMany $association
	 * @param array $entityData
	 * @return array
	 * @noinspection PhpFunctionCyclomaticComplexityInspection
	 */
	protected function cleanBelongsToManyAssociationData(Entity $entity, string $field, Association|BelongsToMany $association, array $entityData): array {
		$la_keys = (array)$association->getBindingKey();
		if (count($la_keys) > 1) {
			dd('ohoh', $la_keys, __FILE__, __LINE__);
		}

		$ls_joinKey = '_joinData';
		$la_junctionKeys = [];
		$lb_hasThrough = $association->hasThrough();
		if ($lb_hasThrough) {
			$la_junctionKeys = [
				$association->getBindingKey(),
				$association->getForeignKey(),
				$association->getTargetForeignKey(),
			];
		}

		/*array_walk($la_keys, function(&$key) {
			$key = Inflector::variable($key);
		});*/

		$la_oldData = $entityData['old'][ $field ] ?? null;
		$la_newData = $entityData['new'][ $field ] ?? null;

		if ($la_newData) {
			foreach ($la_newData as $li_key => $lo_entity) {
				if ($lo_entity instanceof Entity) {
					$la_newData[ $li_key ] = $lo_entity->extract($la_keys, false, false);

					/** @var Entity $lo_joinData */
					$lo_joinData = $lo_entity->get($ls_joinKey);
					if (!$lb_hasThrough || !$lo_joinData) {
						continue;
					}

					//Disable the audit for the junction entity
					$lo_joinData->disableAudit();

					$la_newData[ $li_key ]['_joinData'] = array_diff_key($lo_joinData->extract(), array_flip($la_junctionKeys));
				}
				else {
					$la_newData[ $li_key ] = $lo_entity;
				}
			}

			$la_newData = array_filter($la_newData);
		}

		if (!$entity->hasOriginal($field)) {
			if (!isset($la_newData)) {
				return $entityData;
			}

			//Do not fuck around with the original entity;
			$lo_clonedEntity = clone $entity;
			$this->table()->loadInto($lo_clonedEntity, [$association->getName()]);
			$la_oldData = $lo_clonedEntity->get($field);
		}

		if ($la_oldData ?? null) {
			foreach ($la_oldData as $li_key => $lo_entity) {
				$la_oldEntityData = $lo_entity->extract($la_keys);
				$la_oldData[ $li_key ] = $la_oldEntityData;

				/** @var Entity $lo_joinData */
				$lo_joinData = $lo_entity->get($ls_joinKey);
				if (!$lb_hasThrough || !$lo_joinData) {
					continue;
				}

				$la_oldData[ $li_key ]['_joinData'] = array_diff_key($lo_joinData->extract(), array_flip($la_junctionKeys));
			}
		}

		//If only ids are part of the diff, set the diff as <propertyname>._ids
		if (!$lb_hasThrough && count($la_keys) === 1 && $la_keys[0] === 'id') {
			$la_oldData = ['_ids' => array_column($la_oldData, 'id')];
			$la_newData = ['_ids' => array_column($la_newData, 'id')];
		}
		/*else {
			uasort($la_oldData, function($a, $b) use ($la_keys) {
				return $a[ $la_keys[0] ] <=> $b[ $la_keys[0] ];
			});
		}*/

		$la_entityData = $entityData;

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old'][ $field ] = $la_oldData;
		$la_entityData['new'][ $field ] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old'][ $field ] = $la_oldData;
		$la_entityData['changes']['new'][ $field ] = $la_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $entity
	 * @return array
	 */
	protected function buildEntityData(Entity $entity): array {
		$la_entityData = [
			'old' => $entity->getOriginalValues(),
			'new' => $entity->extract(null, false, false),
			'changes' => [
				'old' => [],
				'new' => [],
			],
		];


		$la_allFields = array_keys(array_merge($la_entityData['old'], $la_entityData['new']));
		$la_allFields = array_diff($la_allFields, $entity->getVirtual());
		$la_ignoredFields = $this->getConfig('ignoredFields');

		$la_associationTypes = $this->getAssociations();

		foreach ($la_allFields as $ls_field) {
			if (in_array($ls_field, $la_ignoredFields)) {
				unset($la_entityData['old'][ $ls_field ], $la_entityData['new'][ $ls_field ]);
				continue;
			}

			if ($ls_field === 'mediaAssignments') {
				$la_entityData = $this->auditMediaAssignments($entity, $la_entityData);
				continue;
			}

			if ($ls_field === '_translations') {
				$la_entityData = $this->auditTranslations($entity, $la_entityData);
				continue;
			}

			if (array_key_exists($ls_field, $la_associationTypes)) {
				$la_entityData = $this->auditAssociation($entity, $ls_field, $la_associationTypes[ $ls_field ], $la_entityData);
				continue;
			}

			$la_entityData = $this->auditField($ls_field, $la_entityData);
		}

		//No difference? Do nothing.
		if (empty($la_entityData['changes']['old']) && empty($la_entityData['changes']['new'])) {
			return [];
		}

		return $la_entityData;
	}
}
