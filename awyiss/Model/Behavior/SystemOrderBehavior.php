<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Inflector;
use BackedEnum;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * SystemOrderBehavior handles records and tables that have a `systemOrder` column.
 * It changes the position of other records after changing the position of one record.
 *
 * It also guarantees the `systemOrder`-column to have a valid value (no gaps, no duplicates)
 *
 * It's possible to limit the order to a specific scope using the option key `relatedColumns`.
 *
 * Example:
 * Using `'relatedColumns' => ['foo', 'bar']` limits this behavior to all items
 * that have the same values for the columns `foo` and `bar` the current entity has.
 */
class SystemOrderBehavior extends Behavior {
	/**
	 * Placeholder in <select>-elements that mark the current position. If this one's selected, no changes were made
	 */
	final public const CURRENT_VALUE_PLACEHOLDER = '__CURRENT__';


	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'direction' => SORT_ASC,
		'enabled' => true,
		'field' => 'systemOrder',
		'implementedEvents' => [
			'beforeCopy',
			'beforeFind',
			'beforeMarshal',
			'beforeSave',
			'afterSave',
			'beforeDelete',
			'beforeSoftDelete',
			'afterDelete',
			'afterSoftDelete',
			'afterDeleteCommit',
		],
		'implementedMethods' => [
			'addSystemOrderQueryConditions' => 'addQueryConditions',
			'getHighestSystemOrder' => 'getHighestSystemOrder',
			'getSystemOrderRelatedColumns' => 'getRelatedColumns',
			'hasDirtyRelatedSystemOrderColumns' => 'hasDirtyRelatedColumns',
		],
		'relatedColumns' => [],
		'skip' => false,
	];
	/**
	 * @var array
	 */
	protected array $rememberedData = [];


	/**
	 * Before finding entities, add a default order by clause (ascending by system_order)
	 *
	 * @param EventInterface $event
	 * @param SelectQuery $query
	 * @param ArrayObject $options
	 * @param bool $primary
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$query->orderByAsc($this->table()->getAlias() . '.system_order');
	}


	/**
	 * When marshalling an entity, unset the `system_order`-property in case it's value equals static::CURRENT_VALUE_PLACEHOLDER.
	 * This means, no changes to the system_order column have been made.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		if (isset($data['systemOrder']) && $data['systemOrder'] === static::CURRENT_VALUE_PLACEHOLDER) {
			/** @noinspection PhpVariableNamingConventionInspection */
			unset($data['systemOrder']);
		}
		elseif (isset($data['system_order']) && $data['system_order'] === static::CURRENT_VALUE_PLACEHOLDER) {
			/** @noinspection PhpVariableNamingConventionInspection */
			unset($data['system_order']);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpUnused
	 */
	public function beforeCopy(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if ($options['_primary'] === false) {
			return;
		}

		if ($entity->extractOriginalChanged($this->getConfig('relatedColumns'))) {
			return;
		}

		/**
		 * @noinspection PhpUndefinedFieldInspection
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		if ($entity->systemOrder >= $entity->originalEntity->systemOrder) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity->systemOrder++;
		}
	}

	/**
	 * Before saving an entity, make sure the value for system_order is valid.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if ($options['_primary'] !== true) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));
		$la_options['field'] = $la_options['field'] ? Inflector::variable($la_options['field']) : '';

		if ($la_options['skip'] === true) {
			return;
		}


		if ($la_options['field'] !== 'systemOrder') {
			$this->setSystemOrderByField($entity, $la_options['field']);
		}
		else {
			$li_hightesSystemOrder = $this->getHighestSystemOrder($entity);
			if ($entity->isNew()) {
				$this->setSystemOrderForNewEntity($entity, $li_hightesSystemOrder);

				//Return here since the rest of the method handles logic related to existing entities
				return;
			}

			//The position is never allowed to be below 1, because cool orders start at 1, not 0.
			if ($entity->get('systemOrder') < 1) {
				$entity->set('systemOrder', 1);
			}
		}


		/** @var \Awyiss\Model\Entity $entity */
		$la_relatedColumns = $entity::mapFields($this->getConfig('relatedColumns'));

		$la_dirtyRelatedFields = array_merge(
			$entity->extractOriginalChanged($la_relatedColumns),
			$entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($la_relatedColumns), true) ?? []
		);

		if (!$entity->isNew()) {
			$this->rememberedData[ $entity->get('id') ] = $la_dirtyRelatedFields;
		}

		$li_systemOrderOld = $entity->hasOriginal('systemOrder') ? $entity->getOriginal('systemOrder') : null;
		if (!$li_systemOrderOld && $la_dirtyRelatedFields) {
			$li_systemOrderOld = $entity->get('systemOrder');
		}

		//The related columns have changed
		if ($la_dirtyRelatedFields) {
			//If the item is being moved to a new scope, it's allowed to take the highest system order plus 1
			/**
			 * @noinspection PhpUndefinedVariableInspection
			 */
			if ($la_options['field'] === 'systemOrder' && $entity->get('systemOrder') > $li_hightesSystemOrder + 1) {
				$entity->set('systemOrder', $li_hightesSystemOrder + 1);
			}
		}
		else {
			//If the item stays inside its current scope, it's allowed to take the highest system order/**
			/**
			 * @noinspection PhpUndefinedVariableInspection
			 */
			if ($la_options['field'] === 'systemOrder' && $entity->get('systemOrder') > $li_hightesSystemOrder) {
				$entity->set('systemOrder', $li_hightesSystemOrder);
			}

			/*
			 * When creating a record, the systemOrder is always dirty.
			 * But when updating, it's only dirty if it contains a possible value.
			 * So if we needed to reset it to a valid value and this equals the old one, it's not dirty.
			 */
			if ($li_systemOrderOld === $entity->get('systemOrder')) {
				$entity->setDirty('systemOrder', false);
			}
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//If it's not the primary afterSave action, there is no need to continue, since siblings have not changed
		if ($options['_primary'] !== true) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));

		if ($la_options['skip'] === true) {
			return;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		if ($entity->isNew()) {
			$this->updateAfterInsert($event, $entity);


			//Return here since the rest of the method handles logic related to existing entities
			return;
		}

		$la_dirtyRelatedFields = $this->rememberedData[ $entity->get('id') ] ?? [];
		unset($this->rememberedData[ $entity->get('id') ]);


		//The related columns have changed
		if ($la_dirtyRelatedFields) {
			/**
			 * Once a field related to the current scope is dirty, it is not enough to update the systemOrder
			 * of the records in the scope, since it was moved out of it, and into a new scope.
			 *
			 * This results in two necessary steps:
			 *    - update the old scope's items
			 *    - update the new scope's items
			 *
			 * - `A B C D E        =>        A B D E`
			 * - `1 2 3 4 5        =>        1 2 3 4`
			 *
			 * ---
			 *
			 * - `a b c d e        =>        a b C c d e`
			 * - `1 2 3 4 5        =>        1 2 3 4 5 6`
			 *
			 * Moving C to the position 3 of the scope of the lowercase letters means D and E need their systemOrder decreased by 1,
			 * while A and B need to stay untouched.
			 * This is the same as deleting a record, so `updateAfterRemove` will be used.
			 *
			 * It also means that c, d and e need their systemOrder increased by 1,
			 * while a and b need to stay untouched.
			 * This is the same as creating a new record, thus calling `updateAfterInsert()` is enough.
			 */

			$this->updateAfterRemove($event, $entity, $la_dirtyRelatedFields);
			$this->updateAfterInsert($event, $entity);
		}
		elseif ($entity->isDirty('systemOrder')) {
			//No related columns have changed. This means the item was moved inside its scope.
			$li_systemOrderNew = $entity->get('systemOrder');
			$li_systemOrderOld = $entity->getOriginal('systemOrder');

			//Create a new query and get all records inside the entity's scope, without the entity itself
			$lo_query = $this->addQueryConditions($lo_table->find(), $entity, false, false);
			$lo_query->where([
				$ls_tableAlias . '.id !=' => $entity->get('id'),
			]);

			/**
			 * The item is being moved to the front.
			 *
			 * All items between (and including) the new position and the old position need to move one to the back (+1)
			 *
			 * - `A B C D E        =>        A D B C E`
			 * - `1 2 3 4 5        =>        1 2 3 4 5`
			 *
			 * Moving D to position 2 means B and C need their systemOrder increased by 1,
			 * while A and E need to stay untouched.
			 */
			if ($li_systemOrderNew < $li_systemOrderOld) {
				$lo_query->where([
					$ls_tableAlias . '.system_order >=' => $li_systemOrderNew,
					$ls_tableAlias . '.system_order <' => $li_systemOrderOld,
				]);
			}
			/**
			 * The item is being moved to the back.
			 * All items between (and including) the new position and the old position need to move one to the front (-1)
			 *
			 * - `A B C D E        =>        A C D B E`
			 * - `1 2 3 4 5        =>        1 2 3 4 5`
			 *
			 * Moving B to position 4 means C and D need their system_order decreased by 1,
			 * while A and E need to stay untouched.
			 */
			else {
				$lo_query->where([
					$ls_tableAlias . '.system_order >' => $li_systemOrderOld,
					$ls_tableAlias . '.system_order <=' => $li_systemOrderNew,
				]);
			}

			$lo_records = $lo_query->all();


			if (!$lo_records->count()) {
				//No records found? The item is alone in its scope. But that's okay. Not all entities are gregarious animals
				return;
			}

			//If we move an item forwards, me move all items one to the back
			$lb_forward = $li_systemOrderNew < $li_systemOrderOld;


			$la_records = $lo_records->toArray();
			//Increase/decrease the system order of all records
			array_walk($la_records, function (EntityInterface $record) use ($lb_forward): void {
				/*
				 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
				 * This might happen if the fetch records have no attribute association but available attributes.
				 * In this case, a default attribute entity gets set but this could be invalid.
				 */

				/** @var \Awyiss\Model\Entity $record */
				$record->clean();

				$record->set('systemOrder', $record->get('systemOrder') + ($lb_forward ? 1 : -1));
			});

			$this->saveMany($lo_table, $la_records, $event);
		}
	}


	/**
	 * Before a delete, set the systemOrder to 999999, so it'll no longer be part of the group.
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//If there's no original `deleted`-value or if that original value is empty
		if (!$entity->hasOriginal('deleted') || empty($entity->getOriginal('deleted'))) {
			$entity->set('systemOrder', 999999);
		}
	}


	/**
	 * Before a delete, set the systemOrder to 999999, so it'll no longer be part of the group.
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->beforeDelete($event, $entity, $options);
	}


	/**
	 * After a delete, call the `updateAfterRemove`-method since deleting an item means it's no longer part of the scope.
	 *
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//If it's not the primary delete action, there is no need to call updateAfterRemove, since all siblings will be deleted as well
		if ($options['_primary'] !== true) {
			return;
		}

		$lo_options = new ArrayObject(Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder')));
		if ($lo_options['skip'] === true) {
			/*
			 * If the system order behavior is skipped, remember the original values of the related columns for the given entity
			 * and handle them in the afterDeleteCommit event.
			 *
			 * This will make sure that the update will not run on entities that will be deleted inside the same transaction
			 */
			$la_relatedColumns = $this->getConfig('relatedColumns');
			$this->rememberedData[ $entity->get('id') ] = array_merge(
				$entity->extractOriginalChanged($la_relatedColumns),
				$entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($la_relatedColumns), true) ?? []
			);


			return;
		}

		$this->updateAfterRemove($event, $entity);
	}


	/**
	 * After a delete, call the `updateAfterRemove`-method since deleting an item means it's no longer part of the scope.
	 *
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->afterDelete($event, $entity, $options);
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDeleteCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if (isset($this->rememberedData[ $entity->get('id') ])) {
			/*
			 * Only after the delete statement got committed, update the entity's siblings
			 */
			$la_values = $this->rememberedData[ $entity->get('id') ];
			unset($this->rememberedData[ $entity->get('id') ]);

			$this->updateAfterRemove($event, $entity, $la_values);
		}
	}


	/**
	 * Add conditions to the query that limit the results to a specific scope, specified by the 'relatedColumns' config
	 * setting.
	 * For example:
	 * Contents are ordered individually per specific page (page_id), specific template position and specific parent
	 * content (parent_id)
	 *
	 * @param SelectQuery|null $query
	 * @param EntityInterface $entity
	 * @param bool $preferOriginal
	 * @param bool $includeMediaAssignments
	 * @return SelectQuery|false
	 */
	public function addQueryConditions(?SelectQuery $query, EntityInterface $entity, bool $preferOriginal = false, bool $includeMediaAssignments = true): SelectQuery|false {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$lo_table = $this->table();
		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $query;
		if (!$lo_query) {
			//If no query was provided, create one
			$lo_query = $this->table()->find();

			if ($includeMediaAssignments && in_array($ls_tableAlias, ['Contents', 'Widgets'])) {
				$lo_query->find('mediaAssignments', useMediaEntity: true);
			}
		}

		/** @var \Awyiss\Model\Entity $lo_attributes */
		$lo_attributes = $entity->get('attributes');
		foreach ($this->getConfig('relatedColumns') as $ls_column) {
			if (in_array($ls_column, ['id', 'systemOrder', 'system_order'])) {
				continue;
			}

			if (str_starts_with($ls_column, 'attributes.') && $lo_attributes) {
				$ls_column = substr($ls_column, 11);

				$lx_value = $lo_attributes->get($ls_column);
				if ($preferOriginal && $lo_attributes->hasOriginal($ls_column)) {
					$lx_value = $lo_attributes->getOriginal($ls_column);
				}

				$ls_column = $lo_attributes::unmapField($ls_column);

				$ls_column = $lo_table->getAttributesTableName(true) . '.' . $ls_column;
			}
			else {
				//Add each related column as a where clause, with a value of the entity's current or old value for this column
				$lx_value = $entity->get($ls_column);

				if ($preferOriginal && $entity->hasOriginal($ls_column)) {
					$lx_value = $entity->getOriginal($ls_column);
				}

				$ls_column = $ls_entityClass::unmapField($ls_column);
			}

			if (!str_contains($ls_column, '.')) {
				$ls_column = $ls_tableAlias . '.' . $ls_column;
			}

			$ls_isNullCondition = is_null($lx_value) ? ' IS' : null;
			$lo_query->where([$ls_column . $ls_isNullCondition => $lx_value]);
		}

		return $lo_query;
	}


	/**
	 * Retrieve the current highest system order for the scope of the provided entity
	 *
	 * @param EntityInterface $entity
	 * @return int
	 */
	public function getHighestSystemOrder(EntityInterface $entity): int {
		if (!$this->getConfig('enabled')) {
			return 0;
		}

		$lo_table = $this->table();

		$lo_query = $this->addQueryConditions($lo_table->find(), $entity, false, false);

		$lo_record = $lo_query->select('system_order')->orderByDesc('system_order')->first();


		return $lo_record ? $lo_record->get('systemOrder') : 0;
	}


	/**
	 * Return the columns, related to the system order. Columns with the same value form a scope.
	 *
	 * @param EntityInterface|null $entity
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function getRelatedColumns(?EntityInterface $entity = null): array {
		if (!$this->getConfig('enabled')) {
			return [];
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');

		if (!$entity) {
			return $la_relatedColumns;
		}


		$la_dirty = $entity->getDirty();
		$lo_attributes = $entity->get('attributes');
		if ($lo_attributes instanceof EntityInterface) {
			$la_dirty = array_merge($la_dirty, $lo_attributes->getDirty());
		}


		return array_intersect($la_dirty, $this->table()->extractAttributeFields($la_relatedColumns, true));
	}


	/**
	 * Returns whether the entity has dirty related columns.
	 * Dirty requires the column to be an original as well.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function hasDirtyRelatedColumns(EntityInterface $entity): bool {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$la_relatedColumns = $this->getRelatedColumns($entity);

		foreach ($la_relatedColumns as $ls_column) {
			if ($entity->isDirty($ls_column) && $entity->hasOriginal($ls_column)) {
				return true;
			}

			if (
				$entity->get('attributes') instanceof EntityInterface &&
				$entity->get('attributes')->isDirty($ls_column) &&
				$entity->get('attributes')->hasOriginal($ls_column)
			) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param string $field
	 * @param int $direction
	 * @param \Cake\Event\EventInterface|null $event
	 * @param array $additionalWhere
	 * @return iterable|false
	 * @throws \Exception
	 */
	public function rebuildSystemOrder(string $field, int $direction = SORT_ASC, ?EventInterface $event = null, array $additionalWhere = []): iterable|false {
		if (Inflector::variable($field) === 'systemOrder') {
			return $this->ensureGaplessSystemOrder($event, $additionalWhere);
		}

		$lo_table = $this->table();

		if (str_starts_with($field, 'attributes.')) {
			$ls_fieldType = $lo_table->getAttributesTable()->getSchema()->getColumnType(substr($field, 11));
		}
		elseif ($lo_table->fieldIsAttribute($field)) {
			$ls_fieldType = $lo_table->getAttributesTable()->getSchema()->getColumnType($field);
		}
		else {
			$ls_fieldType = $lo_table->getSchema()->getColumnType($field);
		}

		$lo_query = $lo_table->find();

		if ($additionalWhere) {
			$lo_query->where($additionalWhere);
		}

		$lo_records = $lo_query->all()->sortBy(
			$field,
			$direction,
			in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
		);

		//dd($lo_records->toArray(), $direction, in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC);

		return $this->_rebuildSystemOrder($lo_table, $lo_records, $event);
	}


	/**
	 * This method moves all elements to the back, depending on the position of the newly created resp. changed entity
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param EntityInterface $entity
	 * @throws \Exception
	 */
	protected function updateAfterInsert(EventInterface $event, EntityInterface $entity): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		//Retrieve all records in the same scope of the entity
		$lo_query = $this->addQueryConditions($lo_table->find(), $entity, false, false);
		//that are not the entity itself and have a systemOrder larger than or equal the entity's.
		$lo_query->where([
			$ls_tableAlias . '.id !=' => $entity->get('id'),
			$ls_tableAlias . '.system_order >=' => $entity->get('systemOrder'),
		]);

		$lo_records = $lo_query->all();

		if (!$lo_records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$la_records = $lo_records->toArray();
		//Increase the system order of all records
		array_walk($la_records, function (EntityInterface $record): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */
			$record->clean();

			/** @var \Awyiss\Model\Entity $record */
			$record->set('systemOrder', $record->get('systemOrder') + 1);
		});

		$this->saveMany($lo_table, $la_records, $event);
	}


	/**
	 * This method moves all elements to the front, depending on the position of the modified entity
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param EntityInterface $entity
	 * @param array $originalData
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function updateAfterRemove(EventInterface $event, EntityInterface $entity, array $originalData = []): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_entity = $entity;

		$li_systemOrder = $lo_entity->get('systemOrder');

		if ($lo_entity->hasOriginal('systemOrder')) {
			$li_systemOrder = $lo_entity->getOriginal('systemOrder');
		}

		$lo_attributes = $lo_entity->get('attributes');
		/*
		 * If the entity has attributes and original data is provided, create a clone and set both,
		 * the entity and the attribute, to a clean, "old" state.
		 *
		 * This is necessary since related entities (the attribute in this case) will be saved and marked as clean
		 * before the main entity, so attributes, at this point, have no original values and cannot be used
		 * to retrieve the records of the old scope.
		 */
		if (
			$originalData &&
			$lo_attributes &&
			array_filter($this->getConfig('relatedColumns'), fn ($field) => str_starts_with($field, 'attributes.'))
		) {
			$lo_entity = clone $lo_entity;

			$lo_attributes = clone $lo_attributes;
			$lo_entity->set('attributes', $lo_attributes);

			$lo_entity->set($originalData, [
				'asOriginal' => true,
				'guard' => false,
				'setter' => false,
			]);

			$lo_entity->clean();
			if ($lo_attributes) {
				$lo_attributes->clean();
			}
		}

		//Retrieve all records in the same scope of the entity
		$lo_query = $this->addQueryConditions($lo_table->find(), $lo_entity, true, false);
		//that are not the entity itself and have a systemOrder larger than or equal the entity's old position.
		$lo_query->where([
			$ls_tableAlias . '.id !=' => $lo_entity->get('id'),
			$ls_tableAlias . '.system_order >=' => $li_systemOrder,
		]);

		$lo_records = $lo_query->all();

		if (!$lo_records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$la_records = $lo_records->toArray();
		//Decrease the system order of all records
		array_walk($la_records, function (EntityInterface $record): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */

			/** @var \Awyiss\Model\Entity $record */
			$record->clean();

			$record->set('systemOrder', $record->get('systemOrder') - 1);
		});

		$this->saveMany($lo_table, $la_records, $event);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return void
	 */
	protected function setSystemOrderByField(EntityInterface $entity, string $field): void {
		$lo_table = $this->table();
		$ls_fieldType = $lo_table->getSchema()->getColumnType($field);
		$lo_query = $this->addQueryConditions($lo_table->find(), $entity, false, false);

		$ls_field = $field;
		if (str_starts_with($ls_field, 'attributes.')) {
			$ls_field = substr($ls_field, 11);
		}

		if ($lo_table->fieldIsAttribute($ls_field)) {
			$ls_attributesTable = $lo_table->getAttributesTableName(true);
			/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
			$ls_entityClass = $lo_table->$ls_attributesTable->getEntityClass();

			$ls_field = $ls_attributesTable . '.' . $ls_entityClass::unmapField($ls_field);
		}
		else {
			/** @var \Awyiss\Model\Entity $entity */
			$ls_field = $entity::unmapField($ls_field);
		}

		$lo_query->select(['id', $ls_field]);

		if (!$entity->isNew()) {
			$lo_query->where(['id !=' => $entity->get('id')]);
		}

		$la_records = $lo_query->all()->append([$entity])->sortBy(
			$field,
			$this->getConfig('direction'),
			in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
		)->toList();

		foreach ($la_records as $li_key => $lo_entity) {
			if ($entity->isNew()) {
				if (!$lo_entity->id) {
					$entity->set('systemOrder', $li_key + 1);
					break;
				}

				continue;
			}

			if ($lo_entity->id === $entity->get('id')) {
				$entity->set('systemOrder', $li_key + 1);
				break;
			}
		}

		if (
			!$entity->isNew() &&
			(
				!$entity->hasOriginal('systemOrder') ||
				$entity->get('systemOrder') === $entity->getOriginal('systemOrder')
			)
		) {
			$entity->setDirty('systemOrder', false);
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param int $hightesSystemOrder
	 * @return void
	 */
	protected function setSystemOrderForNewEntity(EntityInterface $entity, int $hightesSystemOrder): void {
		$li_systemOrder = $entity->get('systemOrder');

		//Make sure the systemOrder is set and not higher than the max. allowed value plus 1
		if (is_null($li_systemOrder) || $li_systemOrder > $hightesSystemOrder) {
			$entity->set('systemOrder', $hightesSystemOrder + 1);
		}
		//The position is never allowed to be below 1, because cool orders start at 1, not 0.
		elseif ($li_systemOrder === 0) {
			$entity->set('systemOrder', $hightesSystemOrder + 1);
		}
		elseif ($li_systemOrder < 0) {
			$entity->set('systemOrder', 1);
		}
	}


	/**
	 * Save all found records, but skip the rules check, the audit and the system order behavior on those to avoid recursion.
	 *
	 * @param \Awyiss\Model\Table $table
	 * @param array $records
	 * @param \Cake\Event\EventInterface|null $event
	 * @return iterable|false
	 * @throws \Exception
	 */
	protected function saveMany(Table $table, array $records, ?EventInterface $event = null): iterable|false {
		try {
			return $table->saveMany($records, [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'mediaAssignments' => ['skip' => true],
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			]);
		}
		catch (PersistenceFailedException $ex) {
			if ($event) {
				$event->stopPropagation();
				$event->setResult($ex->getEntity()->getErrors());
			}

			return false;
		}
	}


	/**
	 * Ensure that the system order is gapless and starts at 1.
	 *
	 * @param \Cake\Event\EventInterface|null $event
	 * @param array $additionalWhere
	 * @return iterable|false
	 * @throws \Exception
	 */
	public function ensureGaplessSystemOrder(?EventInterface $event = null, array $additionalWhere = []): iterable|false {
		$lo_table = $this->table();
		$lo_query = $lo_table->find();

		if ($additionalWhere) {
			$lo_query->where($additionalWhere);
		}

		$lo_records = $lo_query->all()->sortBy('system_order', SORT_ASC);

		return $this->_rebuildSystemOrder($lo_table, $lo_records, $event);
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Collection\CollectionInterface $records
	 * @param \Cake\Event\EventInterface|null $event
	 * @return iterable|false
	 * @throws \Exception
	 */
	protected function _rebuildSystemOrder(Table $table, CollectionInterface $records, ?EventInterface $event): iterable|false {
		$la_relatedColumns = $this->getConfig('relatedColumns');

		if ($la_relatedColumns) {
			$la_relatedColumns = $table->extractAttributeFields($la_relatedColumns, true);
			$la_groupedItems = $records->groupBy(function (EntityInterface $entity) use ($la_relatedColumns) {
				$lo_entity = $entity;
				$la_values = array_map(function (string $field) use ($lo_entity) {
					$lx_value = $lo_entity->get($field);

					if ($lx_value instanceof BackedEnum) {
						$lx_value = $lx_value->value;
					}

					return $lx_value ?? '-';
				}, $la_relatedColumns);


				return implode('_', $la_values);
			})->reject(function (array $items): bool {
				return count($items) === 1;
			})->each(function (array $items): void {
				//Increase the system order of all records
				/** @noinspection PhpVariableNamingConventionInspection */
				array_walk($items, function (EntityInterface $record, int $index): void {
					/*
					 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
					 * This might happen if the fetch records have no attribute association but available attributes.
					 * In this case, a default attribute entity gets set but this could be invalid.
					 */

					/** @var \Awyiss\Model\Entity $record */
					$record->clean();

					$record->set('systemOrder', $index + 1);
				});
			});

			$la_items = $la_groupedItems->unfold()->toList();
		}
		else {
			$records->each(function (array $items): void {
				//Increase the system order of all records
				/** @noinspection PhpVariableNamingConventionInspection */
				array_walk($items, function (EntityInterface $record, int $index): void {
					/*
					 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
					 * This might happen if the fetch records have no attribute association but available attributes.
					 * In this case, a default attribute entity gets set but this could be invalid.
					 */

					/** @var \Awyiss\Model\Entity $record */
					$record->clean();

					$record->set('systemOrder', $index + 1);
				});
			});

			$la_items = $records->toList();
		}

		if (!$la_items) {
			return [];
		}

		return $this->saveMany($table, $la_items, $event);
	}
}
