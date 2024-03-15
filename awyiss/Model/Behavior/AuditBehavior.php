<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Authentication\IdentityInterface;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Association;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use RuntimeException;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AuditBehavior extends Behavior {
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
	 * @param EventInterface $ao_event
	 * @param Validator $ao_validator
	 * @param string $as_name
	 * @return Validator
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildValidator(EventInterface $ao_event, Validator $ao_validator, string $as_name): Validator {
		$ao_validator->allowEmptyDateTime('createdOn');
		$ao_validator->add('createdOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);

		$ao_validator->allowEmptyString('createdBy');
		$ao_validator->add('createdBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);

		$ao_validator->allowEmptyDateTime('changedOn');
		$ao_validator->add('changedOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);

		$ao_validator->allowEmptyString('changedBy');
		$ao_validator->add('changedBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);

		$ao_validator->allowEmptyDateTime('deletedOn');
		$ao_validator->add('deletedOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);

		$ao_validator->allowEmptyString('deletedBy');
		$ao_validator->add('deletedBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		return $ao_validator;
	}


	/**
	 * @param EventInterface $ao_event
	 * @param Entity $ao_entity
	 * @param ArrayObject $ao_options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeDelete(EventInterface $ao_event, Entity $ao_entity, ArrayObject $ao_options): void {
		if (!isset($ao_options['transactionId'])) {
			$ao_options['transactionId'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
			//Text::uuid()
		}
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param EventInterface $ao_event
	 * @param Entity $ao_entity
	 * @param ArrayObject $ao_options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeCopy(EventInterface $ao_event, Entity $ao_entity, ArrayObject $ao_options): void {
		$ao_entity->unset(['createdOn', 'changedOn', 'changedBy', 'changedOn']);
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param EventInterface $ao_event
	 * @param Entity $ao_entity
	 * @param ArrayObject $ao_options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, Entity $ao_entity, ArrayObject $ao_options): void {
		if (!isset($ao_options['transactionId'])) {
			$ao_options['transactionId'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'audit'));

		if ($la_options['skip'] === true) {
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
			elseif (!$lb_isNew && $lo_schema->getColumn('changed_on') && $la_options['setTimeOnUpdate']) {
				//If the item is not new, and if config wants it, set the update-info on this entity
				$this->setUpdateInfo($ao_entity, $li_identityId, $lo_schema);
			}
		}
		//elseif ($lo_schema->getColumn('deleted') && ! empty($ao_entity->deleted) && ( ! $ao_entity->hasOriginal('deleted') || $ao_entity->deleted != $ao_entity->getOriginal('deleted'))) {
		elseif ($lo_schema->getColumn('deleted') && (!$ao_entity->hasOriginal('deleted') || $ao_entity->deleted != $ao_entity->getOriginal('deleted'))) {
			//A soft delete will set the `deleted`-property. If this happens, and the config wants it, set the delete-info on this entity
			if ($lo_schema->getColumn('deleted_on') && $la_options['setTimeOnDelete']) {
				$this->setDeleteInfo($ao_entity, $li_identityId, $lo_schema);
			}
		}

		if ($ao_entity->isNew() || !$ao_entity->allowsAudit()) {
			return;
		}

		$this->auditData[ $ao_entity->id ] = $this->buildEntityData($ao_entity);
	}


	/**
	 * Before saving set information when creating, updating or deleting.
	 *
	 * @param EventInterface $ao_event
	 * @param Entity $ao_entity
	 * @param ArrayObject $ao_options
	 */
	public function afterSave(EventInterface $ao_event, Entity $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if ($ao_entity->isNew() || !$ao_entity->allowsAudit()) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'audit'));

		if ($la_options['skip'] === true) {
			return;
		}

		$la_entityData = $this->auditData[ $ao_entity->id ];
		//No difference? Do nothing.
		if (empty($la_entityData) || empty($la_entityData['changes']['old']) && empty($la_entityData['changes']['new'])) {
			return;
		}

		$li_identityId = $this->getIdentityId();

		//Set the data to be used in `newEntity`
		$la_auditData = [
			'transactionId' => $ao_options['transactionId'],
			'type' => !empty($ao_entity->deleted) ? 'd' : 'u',
			'scope' => $ao_event->getSubject()->getTable(),
			'parentId' => $ao_entity->get('id'),
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

		unset($this->auditData[ $ao_entity->id ]);
	}


	/**
	 * Returns the currently set identity
	 *
	 * @return IdentityInterface|null
	 */
	public function getIdentity(): ?IdentityInterface {
		return $this->identity;
	}


	/**
	 * Sets the identity
	 *
	 * @param IdentityInterface $ao_identity
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function setIdentity(IdentityInterface $ao_identity): void {
		$this->identity = $ao_identity;
	}


	/**
	 * @param string $as_name
	 * @param array $aa_entityData
	 * @return array
	 */
	protected function auditField(string $as_name, array $aa_entityData): array {
		$lx_oldData = $aa_entityData['old'][ $as_name ] ?? null;
		$lx_newData = $aa_entityData['new'][ $as_name ] ?? null;

		if ($lx_oldData === $lx_newData) {
			return $aa_entityData;
		}

		$la_entityData = $aa_entityData;

		$la_entityData['changes']['old'][ $as_name ] = $lx_oldData;
		$la_entityData['changes']['new'][ $as_name ] = $lx_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $ao_entity
	 * @param string $as_field
	 * @param Association|false $ao_association
	 * @param array $aa_entityData
	 * @return array
	 */
	protected function auditAssociation(Entity $ao_entity, string $as_field, Association|false $ao_association, array $aa_entityData): array {
		$la_entityData = $aa_entityData;

		if (!$ao_association || ($ao_association->getCascadeCallbacks() && $ao_association->hasBehavior('Audit') && $ao_association->getBehavior('Audit')->getConfig('enabled'))) {
			/**
			 * No association (set to false in getAssociations) or one with cascadeCallbacks = true
			 * means that property must not be part of the audit data.
			 * Assocations with cascadeCallbacks set to true will have their own `afterSave`-event, creating a separat audit
			 */
			unset($la_entityData['old'][ $as_field ], $la_entityData['new'][ $as_field ]);


			return $la_entityData;
		}

		if ($ao_association->type() == Association::ONE_TO_MANY) {
			$la_entityData = $this->cleanHasManyAssociationData($ao_entity, $as_field, $ao_association, $la_entityData);
		}
		/*elseif ($ao_association->type() === Association::MANY_TO_ONE) {
			$this->cleanBelongsToAssociationData($ao_entity, $as_field, $ao_association, $la_entityData);
		}*/
		elseif ($ao_association->type() === Association::MANY_TO_MANY) {
			/*if ($ao_association->hasThrough()) {
				$la_entityData = $this->cleanHasManyThroughAssociationData($ao_entity, $as_field, $ao_association, $la_entityData);
			}
			else {
				$la_entityData = $this->cleanBelongsToManyAssociationData($ao_entity, $as_field, $ao_association, $la_entityData);
			}*/
			$la_entityData = $this->cleanBelongsToManyAssociationData($ao_entity, $as_field, $ao_association, $la_entityData);
		}
		elseif ($ao_association->type() === Association::ONE_TO_ONE) {
			$la_entityData = $this->cleanHasOneAssociationData($ao_entity, $as_field, $ao_association, $la_entityData);
		}


		return $la_entityData;
	}


	/**
	 * @param Entity $ao_entity
	 * @param array $aa_entityData
	 * @return array
	 */
	protected function auditTranslations(Entity $ao_entity, array $aa_entityData): array {
		if (!$ao_entity->getSource()) {
			return $aa_entityData;
		}

		/** @var \Awyiss\Model\Table $lo_sourceTable */
		$lo_sourceTable = $this->fetchTable($ao_entity->getSource());
		$la_translate = $lo_sourceTable->getConfig('translate');
		if (!$la_translate) {
			return $aa_entityData;
		}

		$la_newTranslations = [];
		/** @var Entity $lo_translatedEntity */
		foreach (($ao_entity->_translations ?? []) as $ls_languageShortcode => $lo_translatedEntity) {
			$la_newTranslations[ $ls_languageShortcode ] = $lo_translatedEntity->extract($la_translate['fields'], false, false);
		}

		$la_oldTranslations = [];
		if ($ao_entity->hasOriginal('_translations')) {
			/** @var Entity $lo_translatedEntity */
			foreach ($ao_entity->getOriginal('_translations') as $ls_languageShortcode => $lo_translatedEntity) {
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

		$la_entityData = $aa_entityData;

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
	 * @param Entity $ao_entity
	 * @param int|null $ai_identityId
	 * @param TableSchemaInterface $ao_schema
	 * @return void
	 */
	protected function setCreateInfo(Entity $ao_entity, ?int $ai_identityId, TableSchemaInterface $ao_schema): void {
		$ao_entity->set('createdOn', DateTime::now());
		if ($ai_identityId && $ao_schema->getColumn('created_by')) {
			$ao_entity->set('createdBy', $ai_identityId);
		}

		$ao_entity->unset(['changedOn', 'changedBy']);
	}


	/**
	 * Set the info for an existing entity
	 *
	 * @param Entity $ao_entity
	 * @param int|null $ai_identityId
	 * @param TableSchemaInterface $ao_schema
	 * @return void
	 */
	protected function setUpdateInfo(Entity $ao_entity, ?int $ai_identityId, TableSchemaInterface $ao_schema): void {
		$ao_entity->set('changedOn', DateTime::now());
		if ($ai_identityId && $ao_schema->getColumn('changed_by')) {
			$ao_entity->set('changedBy', $ai_identityId);
		}
	}


	/**
	 * Set the info for a deleted entity
	 *
	 * @param Entity $ao_entity
	 * @param int|null $ai_identityId
	 * @param TableSchemaInterface $ao_schema
	 * @return void
	 */
	protected function setDeleteInfo(Entity $ao_entity, ?int $ai_identityId, TableSchemaInterface $ao_schema): void {
		$ao_entity->set('deletedOn', DateTime::now());
		if ($ai_identityId && $ao_schema->getColumn('deleted_by')) {
			$ao_entity->set('deletedBy', $ai_identityId);
		}
	}


	/**
	 * @return array
	 */
	protected function getAssociations(): array {
		$la_associations = [];

		foreach ($this->_table->associations() as $lo_association) {
			$ls_property = Inflector::variable($lo_association->getProperty());

			if ($lo_association->getTarget()->getTable() === 'i18n') {
				$lo_association = false;
			}

			$la_associations[ $ls_property ] = $lo_association;
		}


		return $la_associations;
	}


	/**
	 * @param Entity $ao_entity
	 * @param string $as_field
	 * @param Association|HasOne $ao_association
	 * @param array $aa_entityData
	 * @return array
	 */
	protected function cleanHasOneAssociationData(Entity $ao_entity, string $as_field, Association|HasOne $ao_association, array $aa_entityData): array {
		$la_keys = (array)$ao_association->getBindingKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $ao_association->getSource()->getEntityClass();
		$la_keys = $ls_entityClass::mapFields($la_keys);

		$la_foreignKeys = (array)$ao_association->getForeignKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $ao_association->getTarget()->getEntityClass();
		$la_foreignKeys = $ls_entityClass::mapFields($la_foreignKeys);

		$la_keys = array_flip(array_merge($la_keys, $la_foreignKeys));

		//$lo_entity->extract($la_keys, false, false)
		$lo_associatedEntity = $ao_entity->get($as_field);

		$la_newData = [];
		$la_entityData = $aa_entityData;
		if ($lo_associatedEntity && $lo_associatedEntity instanceof Entity) {
			$la_newData = $lo_associatedEntity->extract(null, false, false);
			$la_newData = array_diff_key($la_newData, $la_keys);

			if (!$lo_associatedEntity->isDirty()) {
				$la_entityData['old'][ $as_field ] = $la_newData;
				$la_entityData['new'][ $as_field ] = $la_newData;


				return $la_entityData;
			}
		}

		$la_oldData = [];
		if (!$ao_entity->hasOriginal($as_field)) {
			/** @var Entity $lo_entity */
			$lo_entity = $ao_entity->get($as_field);
			if (is_a($lo_entity, 'Awyiss\Model\Entity') && !$ao_entity->get($as_field)->isNew()) {
				$la_oldData = array_diff_key($lo_entity->getOriginalValues(), $la_keys);
			}
			else {
				if (empty($la_newData)) {
					return [];
				}

				//Do not fuck around with the original entity;
				$lo_clonedEntity = clone $ao_entity;
				$this->table()->loadInto($lo_clonedEntity, [$ao_association->getName()]);
				if ($lo_clonedEntity->get($as_field)) {
					//$aa_oldData[ $as_field ] = $lo_clonedEntity->get($as_field);
					$la_oldData = $lo_clonedEntity->get($as_field)->getOriginalValues();
				}
			}

			$la_oldData = array_diff_key($la_oldData, $la_keys);
		}
		elseif ($lo_associatedEntity) {
			$la_oldData = array_diff_key($lo_associatedEntity->getOriginalValues(), $la_keys);
		}

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old'][ $as_field ] = $la_oldData;
		$la_entityData['new'][ $as_field ] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old'][ $as_field ] = $la_oldData;
		$la_entityData['changes']['new'][ $as_field ] = $la_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $ao_entity
	 * @param string $as_field
	 * @param Association|HasMany $ao_association
	 * @param array $aa_entityData
	 * @return array
	 */
	protected function cleanHasManyAssociationData(Entity $ao_entity, string $as_field, Association|HasMany $ao_association, array $aa_entityData): array {
		$la_keys = (array)$ao_association->getBindingKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $ao_association->getSource()->getEntityClass();
		$la_keys = $ls_entityClass::mapFields($la_keys);

		$la_foreignKeys = (array)$ao_association->getForeignKey();
		/** @var Entity $ls_entityClass */
		$ls_entityClass = $ao_association->getTarget()->getEntityClass();
		$la_foreignKeys = $ls_entityClass::mapFields($la_foreignKeys);

		$la_keys = array_flip(array_merge($la_keys, $la_foreignKeys));

		$la_oldData = $aa_entityData['old'][ $as_field ] ?? null;
		$la_newData = $aa_entityData['new'][ $as_field ] ?? null;

		if ($la_newData) {
			/** @var Entity $lo_entity */
			foreach ($la_newData as $li_key => $lo_entity) {
				$la_newData[ $li_key ] = array_diff_key($lo_entity->toArray(), $la_keys + array_flip($lo_entity->getVirtual()));
			}
		}

		if (!$ao_entity->hasOriginal($as_field)) {
			if (!isset($la_newData)) {
				return $aa_entityData;
			}

			//Do not fuck around with the original entity;
			$lo_clonedEntity = clone $ao_entity;
			$lo_clonedEntity->unset($as_field);
			$this->table()->loadInto($lo_clonedEntity, [$ao_association->getName()]);
			$la_oldData = $lo_clonedEntity->get($as_field);
		}

		if ($la_oldData) {
			/** @var Entity $lo_entity */
			foreach ($la_oldData as $li_key => $lo_entity) {
				$la_oldData[ $li_key ] = array_diff_key($lo_entity->toArray(), $la_keys + array_flip($lo_entity->getVirtual()));
			}
		}

		$la_entityData = $aa_entityData;

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old'][ $as_field ] = $la_oldData;
		$la_entityData['new'][ $as_field ] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old'][ $as_field ] = $la_oldData;
		$la_entityData['changes']['new'][ $as_field ] = $la_newData;


		return $la_entityData;
	}


	/*protected function cleanHasManyThroughAssociationData (Entity $ao_entity, string $as_field, Association|BelongsToMany $ao_association, array $aa_entityData): array {
		$ls_joinDataProperty = '_joinData';
		$la_keys = (array)$ao_association->getBindingKey();
		$la_keys[] = $ls_joinDataProperty;

		$la_oldData = $aa_entityData['old'][ $as_field ] ?? null;
		$la_newData = $aa_entityData['new'][ $as_field ] ?? null;

		if ($la_newData) {
			foreach ($la_newData as $li_key => $lo_entity) {
				if ($lo_entity instanceof Entity) {
					$la_newData[ $li_key ] = $lo_entity->extract($la_keys, false, false);
				}
				else {
					$la_newData[ $li_key ] = $lo_entity;
				}
			}

			$la_newData = array_filter($la_newData);
		}

		dd($la_newData);

		return $aa_entityData;
	}*/


	/**
	 * @param Entity $ao_entity
	 * @param string $as_field
	 * @param Association|BelongsToMany $ao_association
	 * @param array $aa_entityData
	 * @return array
	 * @noinspection PhpFunctionCyclomaticComplexityInspection
	 */
	protected function cleanBelongsToManyAssociationData(Entity $ao_entity, string $as_field, Association|BelongsToMany $ao_association, array $aa_entityData): array {
		$la_keys = (array)$ao_association->getBindingKey();
		if (count($la_keys) > 1) {
			dd('ohoh', $la_keys, __FILE__, __LINE__);
		}

		$ls_joinKey = '_joinData';
		$la_junctionKeys = [];
		$lb_hasThrough = $ao_association->hasThrough();
		if ($lb_hasThrough) {
			$la_junctionKeys = [
				$ao_association->getBindingKey(),
				$ao_association->getForeignKey(),
				$ao_association->getTargetForeignKey(),
			];
		}

		/*array_walk($la_keys, function(&$as_key) {
			$as_key = Inflector::variable($as_key);
		});*/

		$la_oldData = $aa_entityData['old'][ $as_field ] ?? null;
		$la_newData = $aa_entityData['new'][ $as_field ] ?? null;

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

		if (!$ao_entity->hasOriginal($as_field)) {
			if (!isset($la_newData)) {
				return [];
			}

			//Do not fuck around with the original entity;
			$lo_clonedEntity = clone $ao_entity;
			$this->table()->loadInto($lo_clonedEntity, [$ao_association->getName()]);
			$la_oldData = $lo_clonedEntity->get($as_field);
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

		$la_entityData = $aa_entityData;

		//Even if the translations are the same, they have to make their way into the db as plain arrays, not entities
		$la_entityData['old'][ $as_field ] = $la_oldData;
		$la_entityData['new'][ $as_field ] = $la_newData;

		if ($la_oldData === $la_newData) {
			return $la_entityData;
		}

		$la_entityData['changes']['old'][ $as_field ] = $la_oldData;
		$la_entityData['changes']['new'][ $as_field ] = $la_newData;


		return $la_entityData;
	}


	/**
	 * @param Entity $ao_entity
	 * @return array
	 */
	protected function buildEntityData(Entity $ao_entity): array {
		$la_entityData = [
			'old' => $ao_entity->getOriginalValues(),
			'new' => $ao_entity->extract(null, false, false),
			'changes' => [
				'old' => [],
				'new' => [],
			],
		];


		$la_allFields = array_keys(array_merge($la_entityData['old'], $la_entityData['new']));
		$la_ignoredFields = $this->getConfig('ignoredFields');

		$la_associationTypes = $this->getAssociations();

		foreach ($la_allFields as $ls_field) {
			if (in_array($ls_field, $la_ignoredFields)) {
				unset($la_entityData['old'][ $ls_field ], $la_entityData['new'][ $ls_field ]);
				continue;
			}

			if (array_key_exists($ls_field, $la_associationTypes)) {
				$la_entityData = $this->auditAssociation($ao_entity, $ls_field, $la_associationTypes[ $ls_field ], $la_entityData);
				continue;
			}

			if ($ls_field === '_translations') {
				$la_entityData = $this->auditTranslations($ao_entity, $la_entityData);
				continue;
			}

			$la_entityData = $this->auditField($ls_field, $la_entityData);
		}

		//$la_data = Hash::diff($la_oldData, $la_newData);
		//$la_data = array_diff_key($la_data, array_flip($this->getConfig('ignoredColumns')));
		//$la_data = array_intersect_key($la_data, $la_oldData);
		//$la_data = $this->auditAssociations($ao_entity, $la_associationTypes, $la_oldData, $la_newData, $la_data);
		//$this->auditAttributes($ao_entity, $la_oldData, $la_newData, $la_data);

		//No difference? Do nothing.
		if (empty($la_entityData['changes']['old']) && empty($la_entityData['changes']['new'])) {
			return [];
		}


		return $la_entityData;
	}
}
