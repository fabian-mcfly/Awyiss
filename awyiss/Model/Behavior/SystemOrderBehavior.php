<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use BackedEnum;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * SystemOrderBehavior handles records and tables that have a `systemOrder` column.
 * It changes the position of other records after changing the positition of one record.
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
			'beforeFind',
			'beforeMarshal',
			'beforeSave',
			'afterSave',
			'beforeSoftDelete',
			'afterSoftDelete',
			'afterSoftDeleteCommit',
		],
		'implementedMethods' => [
			'addSystemOrderQueryConditions' => 'addQueryConditions',
			'getHighestSystemOrder' => 'getHighestSystemOrder',
			'getSystemOrderRelatedColumns' => 'getRelatedColumns',
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
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @param bool $ab_primary
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options, bool $ab_primary): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$ao_query->orderByAsc($this->table()->getAlias() . '.system_order');
	}


	/**
	 * When marshalling an entity, unset the `system_order`-property in case it's value equals static::CURRENT_VALUE_PLACEHOLDER.
	 * This means, no changes to the system_order column have been made.
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \ArrayObject $ao_data
	 * @param \ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal(EventInterface $ao_event, ArrayObject $ao_data, ArrayObject $ao_options): void {
		if (isset($ao_data['systemOrder']) && $ao_data['systemOrder'] === static::CURRENT_VALUE_PLACEHOLDER) {
			unset($ao_data['systemOrder']);
		}
		elseif (isset($ao_data['system_order']) && $ao_data['system_order'] === static::CURRENT_VALUE_PLACEHOLDER) {
			unset($ao_data['system_order']);
		}
	}


	/**
	 * Before saving an entity, make sure the value for system_order is valid.
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if ($ao_options['_primary'] !== true) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder'));

		if ($la_options['skip'] === true) {
			return;
		}


		if ($la_options['field'] !== 'systemOrder') {
			$this->setSystemOrderByField($ao_entity, $la_options['field']);
		}
		else {
			$li_hightesSystemOrder = $this->getHighestSystemOrder($ao_entity);
			if ($ao_entity->isNew()) {
				$this->setSystemOrderForNewEntity($ao_entity, $li_hightesSystemOrder);


				//Return here since the rest of the method handles logic related to existing entities
				return;
			}

			//The position is never allowed to be below 1, because cool orders start at 1, not 0.
			if ($ao_entity->get('systemOrder') < 1) {
				$ao_entity->set('systemOrder', 1);
			}
		}


		/** @var \Awyiss\Model\Entity $ao_entity */
		$la_relatedColumns = $ao_entity::mapFields($this->getConfig('relatedColumns'));

		$la_dirtyRelatedFields = array_merge(
			$ao_entity->extractOriginalChanged($la_relatedColumns),
			$ao_entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($la_relatedColumns), true) ?? []
		);

		if (!$ao_entity->isNew()) {
			$this->rememberedData[ $ao_entity->get('id') ] = $la_dirtyRelatedFields;
		}

		$li_systemOrderOld = $ao_entity->hasOriginal('systemOrder') ? $ao_entity->getOriginal('systemOrder') : null;
		if (!$li_systemOrderOld && $la_dirtyRelatedFields) {
			$li_systemOrderOld = $ao_entity->get('systemOrder');
		}

		//The related columns have changed
		if ($la_dirtyRelatedFields) {
			//If the item is being moved to a new scope, it's allowed to take the highest system order plus 1
			/**
			 * @noinspection PhpUndefinedVariableInspection
			 */
			if ($la_options['field'] === 'systemOrder' && $ao_entity->get('systemOrder') > $li_hightesSystemOrder + 1) {
				$ao_entity->set('systemOrder', $li_hightesSystemOrder + 1);
			}
		}
		else {
			//If the item stays inside its current scope, it's allowed to take the highest system order/**
			/**
			 * @noinspection PhpUndefinedVariableInspection
			 */
			if ($la_options['field'] === 'systemOrder' && $ao_entity->get('systemOrder') > $li_hightesSystemOrder) {
				$ao_entity->set('systemOrder', $li_hightesSystemOrder);
			}

			/*
			 * When creating a record, the systemOrder is always dirty.
			 * But when updating, it's only dirty if it contains a possible value.
			 * So if we needed to reset it to a valid value and this equals the old one, it's not dirty.
			 */
			if ($li_systemOrderOld === $ao_entity->get('systemOrder')) {
				$ao_entity->setDirty('systemOrder', false);
			}
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//If it's not the primary afterSave action, there is no need to continue, since siblings have not changed
		if ($ao_options['_primary'] !== true) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder'));

		if ($la_options['skip'] === true) {
			return;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		if ($ao_entity->isNew()) {
			$this->updateAfterInsert($ao_entity);


			//Return here since the rest of the method handles logic related to existing entities
			return;
		}

		$la_dirtyRelatedFields = $this->rememberedData[ $ao_entity->get('id') ] ?? [];
		unset($this->rememberedData[ $ao_entity->get('id') ]);

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

			$this->updateAfterRemove($ao_entity, $la_dirtyRelatedFields);
			$this->updateAfterInsert($ao_entity);
		}
		elseif ($ao_entity->isDirty('systemOrder')) {
			//No related columns have changed. This means the item was moved inside its scope.
			$li_systemOrderNew = $ao_entity->get('systemOrder');
			$li_systemOrderOld = $ao_entity->getOriginal('systemOrder');

			//Create a new query and get all records inside the entity's scope, without the entity itself
			$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);
			$lo_query->where([
				$ls_tableAlias . '.id !=' => $ao_entity->get('id'),
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
			array_walk($la_records, function (EntityInterface $ao_record) use ($lb_forward): void {
				/*
				 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
				 * This might happen if the fetch records have no attribute association but available attributes.
				 * In this case, a default attribute entity gets set but this could be invalid.
				 */

				/** @var \Awyiss\Model\Entity $ao_record */
				$ao_record->clean();

				$ao_record->set('systemOrder', $ao_record->get('systemOrder') + $lb_forward ? 1 : -1);
			});

			//Save all found records, but skip the rules check, the audit and the system order behavior on those to avoid recursion.
			$lo_table->saveMany($la_records, [
				'audit' => ['skip' => true],
				'checkRules' => false,
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
			]);
		}
	}


	/**
	 * Before a soft delete, set the systemOrder to 999999, so it'll no longer be part of the group.
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//If there's no original `deleted`-value or if that original value is empty
		if (!$ao_entity->hasOriginal('deleted') || empty($ao_entity->getOriginal('deleted'))) {
			$ao_entity->set('systemOrder', 999999);
		}
	}


	/**
	 * After a soft delete, call the `updateAfterRemove`-method since soft deleting an item means it's no longer part of the scope.
	 *
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//If it's not the primary softDelete action, there is no need to call updateAfterRemove, since all siblings will be deleted as well
		if ($ao_options['_primary'] !== true) {
			return;
		}

		$lo_options = new ArrayObject(Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder')));
		if ($lo_options['skip'] === true) {
			/*
			 * If the system order behavior is skipped, remember the orignal values of the related columns for the given entity
			 * and handle them in the afterSoftDeleteCommit event.
			 *
			 * This will make sure that the update will not run on entities that will be deleted inside the same transaction
			 */
			$la_relatedColumns = $this->getConfig('relatedColumns');
			$this->rememberedData[ $ao_entity->get('id') ] = array_merge(
				$ao_entity->extractOriginalChanged($la_relatedColumns),
				$ao_entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($la_relatedColumns), true) ?? []
			);


			return;
		}

		$this->updateAfterRemove($ao_entity);
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		if (isset($this->rememberedData[ $ao_entity->get('id') ])) {
			/*
			 * Only after the soft delete got committed, update the entity's siblings
			 * The soft delete behavior will ensure that only undeleted entties will be updated
			 */
			$la_values = $this->rememberedData[ $ao_entity->get('id') ];
			unset($this->rememberedData[ $ao_entity->get('id') ]);

			$this->updateAfterRemove($ao_entity, $la_values);
		}
	}


	/**
	 * Add conditions to the query that limit the results to a specific scope, specified by the 'relatedColumns' config
	 * setting.
	 * For example:
	 * Contents are ordered individually per specific page (page_id), specific template position and specific parent
	 * content (parent_id)
	 *
	 * @param SelectQuery|null $ao_query
	 * @param EntityInterface $ao_entity
	 * @param bool $ab_preferOriginal
	 * @return SelectQuery|false
	 */
	public function addQueryConditions(?SelectQuery $ao_query, EntityInterface $ao_entity, bool $ab_preferOriginal = false): SelectQuery|false {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$lo_table = $this->table();
		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $ao_query;
		if (!$lo_query) {
			//If no query was provided, create one
			$lo_query = $this->table()->find();
		}

		/** @var \Awyiss\Model\Entity $lo_attributes */
		$lo_attributes = $ao_entity->get('attributes');
		foreach ($this->getConfig('relatedColumns') as $ls_column) {
			if (in_array($ls_column, ['id', 'systemOrder', 'system_order'])) {
				continue;
			}

			if (str_starts_with($ls_column, 'attributes.') && $lo_attributes) {
				$ls_column = substr($ls_column, 11);

				$lx_value = $lo_attributes->get($ls_column);
				if ($ab_preferOriginal && $lo_attributes->hasOriginal($ls_column)) {
					$lx_value = $lo_attributes->getOriginal($ls_column);
				}

				$ls_column = $lo_attributes::unmapField($ls_column);

				$ls_column = $lo_table->getAttributesTableName(true) . '.' . $ls_column;
			}
			else {
				//Add each related column as a where clause, with a value of the entity's current or old value for this column
				$lx_value = $ao_entity->get($ls_column);

				if ($ab_preferOriginal && $ao_entity->hasOriginal($ls_column)) {
					$lx_value = $ao_entity->getOriginal($ls_column);
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
	 * Retreive the current highest system order for the scope of the provided entity
	 *
	 * @param EntityInterface $ao_entity
	 * @return int
	 */
	public function getHighestSystemOrder(EntityInterface $ao_entity): int {
		if (!$this->getConfig('enabled')) {
			return 0;
		}

		$lo_table = $this->table();

		$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);

		$lo_record = $lo_query->select('system_order')->orderByDesc('system_order')->first();


		return $lo_record ? $lo_record->get('systemOrder') : 0;
	}


	/**
	 * Return the columns, related to the system order. Columns with the same value form a scope.
	 *
	 * @param EntityInterface|null $ao_entity
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function getRelatedColumns(?EntityInterface $ao_entity = null): array {
		if (!$this->getConfig('enabled')) {
			return [];
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');

		if (!$ao_entity) {
			return $la_relatedColumns;
		}


		$la_dirty = $ao_entity->getDirty();
		$lo_attributes = $ao_entity->get('attributes');
		if ($lo_attributes instanceof EntityInterface) {
			$la_dirty = array_merge($la_dirty, $lo_attributes->getDirty());
		}


		return array_intersect($la_dirty, $this->table()->extractAttributeFields($la_relatedColumns, true));
	}


	/**
	 * @param string $as_field
	 * @param int $ai_direction
	 * @return void
	 * @throws \Exception
	 */
	public function rebuildSystemOrder(string $as_field, int $ai_direction = SORT_ASC): void {
		if ($as_field == 'systemOrder') {
			return;
		}

		$lo_table = $this->table();

		if (str_starts_with($as_field, 'attributes.')) {
			$ls_fieldType = $lo_table->getAttributesTable()->getSchema()->getColumnType(substr($as_field, 11));
		}
		elseif ($lo_table->fieldIsAttribute($as_field)) {
			$ls_fieldType = $lo_table->getAttributesTable()->getSchema()->getColumnType($as_field);
		}
		else {
			$ls_fieldType = $lo_table->getSchema()->getColumnType($as_field);
		}

		$lo_records = $lo_table->find()->all()->sortBy(
			$as_field,
			$ai_direction,
			in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
		);

		$la_relatedColumns = $this->getConfig('relatedColumns');
		if ($la_relatedColumns) {
			$la_relatedColumns = $lo_table->extractAttributeFields($la_relatedColumns, true);
			$la_groupedItems = $lo_records->groupBy(function (EntityInterface $ao_entity) use ($la_relatedColumns) {
				$la_values = array_map(function (string $as_field) use ($ao_entity) {
					$lx_value = $ao_entity->get($as_field);

					if ($lx_value instanceof BackedEnum) {
						$lx_value = $lx_value->value;
					}

					return $lx_value ?? '-';
				}, $la_relatedColumns);


				return implode('_', $la_values);
			})
			->reject(function (array $aa_items): bool {
				return count($aa_items) === 1;
			})
			->each(function (array $aa_items): void {
				//Increase the system order of all records
				array_walk($aa_items, function (EntityInterface $ao_record, int $ai_index): void {
					/*
					 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
					 * This might happen if the fetch records have no attribute association but available attributes.
					 * In this case, a default attribute entity gets set but this could be invalid.
					 */

					/** @var \Awyiss\Model\Entity $ao_record */
					$ao_record->clean();

					$ao_record->set('systemOrder', $ai_index + 1);
				});
			});

			$la_items = $la_groupedItems->unfold()->toList();
		}
		else {
			$lo_records->each(function (array $aa_items): void {
				//Increase the system order of all records
				array_walk($aa_items, function (EntityInterface $ao_record, int $ai_index): void {
					/*
					 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
					 * This might happen if the fetch records have no attribute association but available attributes.
					 * In this case, a default attribute entity gets set but this could be invalid.
					 */

					/** @var \Awyiss\Model\Entity $ao_record */
					$ao_record->clean();

					$ao_record->set('systemOrder', $ai_index + 1);
				});
			});

			$la_items = $lo_records->toList();

			dd($la_items, __LINE__, __FILE__);
		}

		//Save all found records, but skip the rules check, the audit and the system order behavior on those to avoid recursion.
		$lo_table->saveMany($la_items, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'nest' => ['skip' => true],
			'systemOrder' => ['skip' => true],
		]);
	}


	/**
	 * This method moves all elements to the back, depending on the position of the newly created resp. changed entity
	 *
	 * @param EntityInterface $ao_entity
	 * @throws \Exception
	 */
	protected function updateAfterInsert(EntityInterface $ao_entity): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		//Retreive all records in the same scope of the entity
		$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);
		//that are not the entity itself and have a systemOrder larger than or equal the entity's.
		$lo_query->where([
			$ls_tableAlias . '.id !=' => $ao_entity->get('id'),
			$ls_tableAlias . '.system_order >=' => $ao_entity->get('systemOrder'),
		]);

		$lo_records = $lo_query->all();

		if (!$lo_records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$la_records = $lo_records->toArray();
		//Increase the system order of all records
		array_walk($la_records, function (EntityInterface $ao_record): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */
			$ao_record->clean();

			/** @var \Awyiss\Model\Entity $ao_record */
			$ao_record->set('systemOrder', $ao_record->get('systemOrder') + 1);
		});

		//Save all found records, but skip the rules check, the audit and the system order behavior on those to avoid recursion.
		$lo_table->saveMany($la_records, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'nest' => ['skip' => true],
			'systemOrder' => ['skip' => true],
		]);
	}


	/**
	 * This method moves all elements to the front, depending on the position of the modified entity
	 *
	 * @param EntityInterface $ao_entity
	 * @param array $aa_originalData
	 * @throws \Exception
	 */
	protected function updateAfterRemove(EntityInterface $ao_entity, array $aa_originalData = []): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_entity = $ao_entity;

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
		 * to retreive the records of the old scope.
		 */
		if (
			$aa_originalData &&
			$lo_attributes &&
			array_filter($this->getConfig('relatedColumns'), fn ($as_field) => str_starts_with($as_field, 'attributes.'))
		) {
			$lo_entity = clone $lo_entity;

			$lo_attributes = clone $lo_attributes;
			$lo_entity->set('attributes', $lo_attributes);

			$lo_entity->set($aa_originalData, [
				'asOriginal' => true,
				'guard' => false,
				'setter' => false,
			]);

			$lo_entity->clean();
			if ($lo_attributes) {
				$lo_attributes->clean();
			}
		}

		//Retreive all records in the same scope of the entity
		$lo_query = $this->addQueryConditions($lo_table->find(), $lo_entity, true);
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
		array_walk($la_records, function (EntityInterface $ao_record): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */

			/** @var \Awyiss\Model\Entity $ao_record */
			$ao_record->clean();

			$ao_record->set('systemOrder', $ao_record->get('systemOrder') - 1);
		});

		//Save all found records, but skip the rules check, the audit and the system order behavior on those to avoid recursion.
		$lo_table->saveMany($la_records, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'nest' => ['skip' => true],
			'systemOrder' => ['skip' => true],
		]);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param string $as_field
	 * @return void
	 */
	protected function setSystemOrderByField(EntityInterface $ao_entity, string $as_field): void {
		$lo_table = $this->table();
		$ls_fieldType = $lo_table->getSchema()->getColumnType($as_field);
		$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);

		$ls_field = $as_field;
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
			/** @var \Awyiss\Model\Entity $ao_entity */
			$ls_field = $ao_entity::unmapField($ls_field);
		}

		$lo_query->select(['id', $ls_field]);

		if (!$ao_entity->isNew()) {
			$lo_query->where(['id !=' => $ao_entity->get('id')]);
		}

		$la_records = $lo_query->all()->append([$ao_entity])->sortBy(
			$as_field,
			$this->getConfig('direction'),
			in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
		)->toList();

		foreach ($la_records as $li_key => $lo_entity) {
			if ($ao_entity->isNew()) {
				if (!$lo_entity->id) {
					$ao_entity->set('systemOrder', $li_key + 1);
					break;
				}

				continue;
			}

			if ($lo_entity->id === $ao_entity->get('id')) {
				$ao_entity->set('systemOrder', $li_key + 1);
				break;
			}
		}

		if (
			!$ao_entity->isNew() &&
			(
				!$ao_entity->hasOriginal('systemOrder') ||
				$ao_entity->get('systemOrder') === $ao_entity->getOriginal('systemOrder')
			)
		) {
			$ao_entity->setDirty('systemOrder', false);
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param int $ai_hightesSystemOrder
	 * @return void
	 */
	protected function setSystemOrderForNewEntity(EntityInterface $ao_entity, int $ai_hightesSystemOrder): void {
		$li_systemOrder = $ao_entity->get('systemOrder');

		//Make sure the systemOrder is set and not higher than the max. allowed value plus 1
		if (is_null($li_systemOrder) || $li_systemOrder > $ai_hightesSystemOrder) {
			$ao_entity->set('systemOrder', $ai_hightesSystemOrder + 1);
		}
		//The position is never allowed to be below 1, because cool orders start at 1, not 0.
		elseif ($li_systemOrder < 1) {
			$ao_entity->set('systemOrder', 1);
		}
	}
}
