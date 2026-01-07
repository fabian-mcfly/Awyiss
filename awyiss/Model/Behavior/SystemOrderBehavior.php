<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use BackedEnum;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
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
	final public const string CURRENT_VALUE_PLACEHOLDER = '__CURRENT__';


	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
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
			'hasDirtySystemOrderRelatedColumns' => 'hasDirtyRelatedColumns',
		],
		'relatedColumns' => [],
		'skip' => false,
	];
	/**
	 * @var array
	 */
	protected array $rememberedData = [];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setConfig('implementedEvents', [
			'Configuration.' . $this->table()->getAlias() . '.Backend.systemOrder.direction.afterSaveCommit' => 'rebuildSystemOrderAfterDirectionSave',
			'Configuration.' . $this->table()->getAlias() . '.Backend.systemOrder.field.afterSaveCommit' => 'rebuildSystemOrderAfterFieldSave',
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function rebuildSystemOrderAfterDirectionSave(Event $event, Configuration $configuration): void {
		if (
			!$configuration->isNew() &&
			(
				!$configuration->hasOriginal('value') ||
				$configuration->getOriginal('value') === $configuration->value
			)
		) {
			return;
		}

		$field = LocalConfig::read([
			'systemOrder',
			'field',
		], 'systemOrder', $this->table()->getAlias());

		// If the field is set to 'systemOrder', we don't need to rebuild the system order
		if ($field === 'systemOrder') {
			return;
		}

		$this->rebuildSystemOrder($field, (int)$configuration->value, $event);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function rebuildSystemOrderAfterFieldSave(Event $event, Configuration $configuration): void {
		if (
			$configuration->value === 'systemOrder' ||
			(
				!$configuration->isNew() &&
				(
					!$configuration->hasOriginal('value') ||
					$configuration->getOriginal('value') === $configuration->value
				)
			)
		) {
			return;
		}

		$direction = LocalConfig::read([
			'systemOrder',
			'direction',
		], SORT_ASC, $this->table()->getAlias());


		$this->rebuildSystemOrder($configuration->value, $direction, $event);
	}


	/**
	 * Before finding entities, add a default order by clause (ascending by system_order)
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @param bool $primary
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));
		if ($queryOptions['skip'] === true) {
			return;
		}

		$query->orderByAsc($this->table()->aliasField('system_order'));
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
		if (!$this->getConfig('enabled')) {
			return;
		}

		if (isset($data['systemOrder']) && $data['systemOrder'] === static::CURRENT_VALUE_PLACEHOLDER) {
			unset($data['systemOrder']);
		}

		if (isset($data['system_order']) && $data['system_order'] === static::CURRENT_VALUE_PLACEHOLDER) {
			unset($data['system_order']);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection, PhpPossiblePolymorphicInvocationInspection
	 */
	public function beforeCopy(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		// If the system order behavior is not enabled or the entity is related, not the main context, there's no need to continue
		if ($options['_primary'] !== true || !$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		if ($entity->extractOriginalChanged($this->getConfig('relatedColumns'))) {
			return;
		}

		if ($entity->systemOrder >= ($entity->originalEntity?->systemOrder ?? $entity->systemOrder)) {
			$entity->systemOrder++;
		}
		elseif ($entity->systemOrder === 0) {
			$entity->systemOrder = 1;
		}
	}

	/**
	 * Before saving an entity, make sure the value for system_order is valid.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		// If the system order behavior is not enabled or the entity is related, not the main context, there's no need to continue
		if ($options['_primary'] !== true || !$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		$queryOptions['field'] = !empty($queryOptions['field']) ? $entity::mapField($queryOptions['field']) : 'systemOrder';

		$hightesSystemOrder = null;
		$autoOrder = $queryOptions['field'] !== 'systemOrder';
		if ($autoOrder) {
			$this->setSystemOrderByField($entity, $queryOptions['field']);
		}
		else {
			$hightesSystemOrder = $this->getHighestSystemOrder($entity);
			if ($entity->isNew()) {
				$this->setSystemOrderForNewEntity($entity, $hightesSystemOrder);

				/**
				 * Return here since the rest of the method handles logic related
				 * to existing entities or when auto-ordering is happening.
				 */
				return;
			}

			// The position is never allowed to be below 1, because cool orders start at 1, not 0.
			if ($entity->get('systemOrder') < 1) {
				$entity->set('systemOrder', 1);
			}
		}

		/**
		 * Convert related column names from database to entity name and
		 * get all original values of related columns, including attributes.
		 */
		$relatedColumns = $entity::mapFields($this->getConfig('relatedColumns'));
		$dirtyRelatedData = array_merge(
			$entity->extractOriginalChanged($relatedColumns),
			$entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($relatedColumns), true) ?? []
		);

		// Remember the dirty related fields of existing entities for later use in afterSave
		if (!$entity->isNew()) {
			$this->rememberedData[ $entity->get('id') ] = $dirtyRelatedData;
		}

		// Get the original systemOrder value, if it exists.
		$systemOrderOld = $entity->hasOriginal('systemOrder') ? $entity->getOriginal('systemOrder') : null;
		if (!$entity->isNew() && !$systemOrderOld && $dirtyRelatedData) {
			// If no original system order exists but related fields have changed,
			// use current system order as the "old" value (entity is moving to new scope)
			$systemOrderOld = $entity->get('systemOrder');
		}

		// The related columns have changed
		if ($dirtyRelatedData) {
			// If the item is being moved to a new scope, it's allowed to take the highest system order plus 1
			if (!$autoOrder && $entity->get('systemOrder') > $hightesSystemOrder + 1) {
				$entity->set('systemOrder', $hightesSystemOrder + 1);
			}
		}
		else {
			// If the item stays inside its current scope, it's allowed to take the highest system order
			if (!$autoOrder && $entity->get('systemOrder') > $hightesSystemOrder) {
				$entity->set('systemOrder', $hightesSystemOrder);
			}
			/**
			 * When updating, the system order should only be marked
			 * as dirty  if it contains a possible value.
			 * So if we needed to reset it to a valid value and
			 * equals the old one, it's not dirty.
			 */
			elseif ($entity->get('systemOrder') === $systemOrderOld) {
				$entity->setDirty('systemOrder', false);
			}
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		// If the system order behavior is not enabled or the entity is related, not the main context, there's no need to continue
		if ($options['_primary'] !== true || !$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		$table = $this->table();
		$tableAlias = $table->getAlias();

		if ($entity->isNew()) {
			$this->updateAfterInsert($event, $entity);

			// Return here since the rest of the method handles logic related to existing entities
			return;
		}

		$dirtyRelatedFields = $this->rememberedData[ $entity->get('id') ] ?? [];
		unset($this->rememberedData[ $entity->get('id') ]);

		//The related columns have changed
		if ($dirtyRelatedFields) {
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

			$this->updateAfterRemove($event, $entity, $dirtyRelatedFields);
			$this->updateAfterInsert($event, $entity);

			return;
		}

		if (!$entity->isDirty('systemOrder')) {
			return;
		}

		//No related columns have changed. This means the item was moved inside its scope.
		$systemOrderNew = $entity->get('systemOrder');
		$systemOrderOld = $entity->hasOriginal('systemOrder') ? $entity->getOriginal('systemOrder') : $systemOrderNew;

		//Create a new query and get all records inside the entity's scope, without the entity itself
		$query = $this->addQueryConditions($table->find(), $entity);
		$query->where([
			$tableAlias . '.id !=' => $entity->get('id'),
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
		if ($systemOrderNew < $systemOrderOld) {
			$query->where([
				$tableAlias . '.system_order >=' => $systemOrderNew,
				$tableAlias . '.system_order <' => $systemOrderOld,
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
			$query->where([
				$tableAlias . '.system_order >' => $systemOrderOld,
				$tableAlias . '.system_order <=' => $systemOrderNew,
			]);
		}

		$records = $query->all();

		if (!$records->count()) {
			//No records found? The item is alone in its scope. But that's okay. Not all entities are gregarious animals
			return;
		}

		//If we move an item forwards, me move all items one to the back
		$forward = $systemOrderNew < $systemOrderOld;

		$records = $records->toArray();
		//Increase/decrease the system order of all records
		array_walk($records, function (EntityInterface $record) use ($forward): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */

			/** @var \Awyiss\Model\Entity $record */
			$record->clean();

			$record->set('systemOrder', $record->get('systemOrder') + ($forward ? 1 : -1));
		});

		$this->saveMany($table, $records, $event);
	}


	/**
	 * Before a delete, set the systemOrder to 999999, so it'll no longer be part of the group.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		// If the system order behavior is not enabled, there's no need to continue
		if (!$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		//If there's no original `deleted`-value or if that original value is empty
		if (!$entity->hasOriginal('deleted') || empty($entity->getOriginal('deleted'))) {
			$entity->set('systemOrder', 999999);
		}
	}


	/**
	 * Before a soft delete, set the systemOrder to 999999, so it'll no longer be part of the group.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 */
	public function beforeSoftDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->beforeDelete($event, $entity, $options);
	}


	/**
	 * After a delete, call the `updateAfterRemove`-method since deleting an item means it's no longer part of the scope.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 */
	public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		// If the system order behavior is not enabled or the entity is related, not the main context, there's no need to continue
		if ($options['_primary'] !== true || !$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'systemOrder'));

		if ($queryOptions['skip'] === true) {
			/**
			 * If the system order behavior is skipped, remember the original values of the related columns for the given entity
			 * and handle them in the afterDeleteCommit event.
			 *
			 * This will make sure that the update will not run on entities that will be deleted inside the same transaction
			 */
			$relatedColumns = $this->getConfig('relatedColumns');
			$this->rememberedData[ $entity->get('id') ] = array_merge(
				$entity->extractOriginalChanged($relatedColumns),
				$entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($relatedColumns), true) ?? []
			);

			return;
		}

		// Update the siblings of the deleted entity
		$this->updateAfterRemove($event, $entity);
	}


	/**
	 * After a soft delete, call the `updateAfterRemove`-method since deleting an item means it's no longer part of the scope.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 */
	public function afterSoftDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->afterDelete($event, $entity, $options);
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDeleteCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (
			!$this->getConfig('enabled') ||
			!isset($this->rememberedData[ $entity->get('id') ])
		) {
			return;
		}

		// Only after the delete statement has been committed, update the siblings of the deleted entity
		$values = $this->rememberedData[ $entity->get('id') ] ?? [];
		unset($this->rememberedData[ $entity->get('id') ]);

		$this->updateAfterRemove($event, $entity, $values);
	}


	/**
	 * Add conditions to the query that limit the results to a specific scope,
	 * specified by the 'relatedColumns' config setting and based on the values
	 * of the provided entity.
	 *
	 * For example, contents are ordered individually per
	 * - page (page_id)
	 * - template position
	 * - parent id
	 *
	 * If `$preferOriginal` is set to true, the original values of the entity will be used,
	 * otherwise the current values will be used.
	 *
	 * @param \Cake\ORM\Query\SelectQuery|null $query
	 * @param \Awyiss\Model\Entity $entity
	 * @param bool $preferOriginal
	 * @return \Cake\ORM\Query\SelectQuery|false
	 */
	public function addQueryConditions(?SelectQuery $query, EntityInterface $entity, bool $preferOriginal = false): SelectQuery|false {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$table = $this->table();
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $table->getEntityClass();
		$tableAlias = $table->getAlias();

		if (!$query) {
			//If no query was provided, create one
			$query = $this->table()->find();
		}

		/** @var \Awyiss\Model\Entity $attributes */
		$attributes = $entity->get('attributes');
		foreach ($this->getConfig('relatedColumns') as $column) {
			if (in_array($column, ['id', 'systemOrder', 'system_order'])) {
				continue;
			}

			if (str_starts_with($column, 'attributes.') && $attributes) {
				$column = substr($column, 11);

				$value = $attributes->get($column);
				if ($preferOriginal && $attributes->hasOriginal($column)) {
					$value = $attributes->getOriginal($column);
				}

				$column = $attributes::unmapField($column);

				$column = $table->getAttributesTableName(true) . '.' . $column;
			}
			else {
				//Add each related column as a where clause, with a value of the entity's current or old value for this column
				$value = $entity->get($column);

				if ($preferOriginal && $entity->hasOriginal($column)) {
					$value = $entity->getOriginal($column);
				}

				$column = $entityClass::unmapField($column);
			}

			if (!str_contains($column, '.')) {
				$column = $tableAlias . '.' . $column;
			}

			$isNullCondition = is_null($value) ? ' IS' : null;
			$query->where([$column . $isNullCondition => $value]);
		}

		return $query;
	}


	/**
	 * Retrieve the current highest system order for the scope of the provided entity
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @return int
	 */
	public function getHighestSystemOrder(EntityInterface $entity): int {
		if (!$this->getConfig('enabled')) {
			return 0;
		}

		$table = $this->table();

		$query = $this->addQueryConditions($table->find(), $entity);

		$record = $query->select('system_order')->orderByDesc('system_order')->first();


		return $record ? $record->get('systemOrder') : 0;
	}


	/**
	 * Return the columns, related to the system order.
	 * Columns with the same value form a scope.
	 *
	 * @return array
	 */
	public function getRelatedColumns(): array {
		return $this->getConfig('relatedColumns');
	}


	/**
	 * Return the columns, related to the system order.
	 * Columns with the same value form a scope.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @return array
	 */
	public function getDirtyRelatedColumns(EntityInterface $entity): array {
		if (!$this->getConfig('enabled')) {
			return [];
		}

		$relatedColumns = $this->getConfig('relatedColumns');

		$dirty = $entity->getDirty();
		$attributes = $entity->get('attributes');
		if ($attributes instanceof EntityInterface) {
			$dirty = array_merge($dirty, $attributes->getDirty());
		}

		$dirtyRelated = array_intersect($dirty, $this->table()->extractAttributeFields($relatedColumns, true));

		return array_values($dirtyRelated);
	}


	/**
	 * Returns whether the entity has dirty related columns.
	 * Dirty requires the column to be an original as well.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @return bool
	 */
	public function hasDirtyRelatedColumns(EntityInterface $entity): bool {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$relatedColumns = $this->getDirtyRelatedColumns($entity);

		foreach ($relatedColumns as $column) {
			if ($entity->isDirty($column) && $entity->hasOriginal($column)) {
				return true;
			}

			if (
				$entity->get('attributes') instanceof EntityInterface &&
				$entity->get('attributes')->isDirty($column) &&
				$entity->get('attributes')->hasOriginal($column)
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
			return $this->ensureGaplessSystemOrder($direction, $event, $additionalWhere);
		}

		$table = $this->table();
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $table->getEntityClass();

		if (str_starts_with($field, 'attributes.')) {
			$field = $entityClass::unmapField(substr($field, 11));
			$fieldType = $table->getAttributesTable()->getSchema()->getColumnType($field);
		}
		else {
			$field = $entityClass::unmapField($field);

			if ($table->fieldIsAttribute($field)) {
				$fieldType = $table->getAttributesTable()->getSchema()->getColumnType($field);
			}
			else {
				$fieldType = $table->getSchema()->getColumnType($field);
			}
		}

		$query = $table->find();

		if ($additionalWhere) {
			$query->where($additionalWhere);
		}

		if (in_array($fieldType, ['string', 'text', 'char'])) {
			$records = $query->all()->toArray();

			Arrays::naturalSort($records, $field, false, $direction);

			$records = collection($records);
		}
		else {
			$records = $query->all()->sortBy(
				$field,
				$direction
			);
		}


		return $this->_rebuildSystemOrder($table, $records, $event);
	}


	/**
	 * This method moves all elements to the back, depending on the position of the newly created resp. changed entity
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @throws \Exception
	 */
	protected function updateAfterInsert(EventInterface $event, EntityInterface $entity): void {
		$table = $this->table();
		$tableAlias = $table->getAlias();

		//Retrieve all records in the same scope of the entity
		$query = $this->addQueryConditions($table->find(), $entity);
		//that are not the entity itself and have a systemOrder larger than or equal the entity's.
		$query->where([
			$tableAlias . '.id !=' => $entity->get('id'),
			$tableAlias . '.system_order >=' => $entity->get('systemOrder'),
		]);

		$records = $query->all();

		if (!$records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$records = $records->toArray();
		//Increase the system order of all records
		array_walk($records, function (EntityInterface $record): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */
			$record->clean();

			/** @var \Awyiss\Model\Entity $record */
			$record->set('systemOrder', $record->get('systemOrder') + 1);
		});

		$this->saveMany($table, $records, $event);
	}


	/**
	 * This method moves all elements to the front, depending on the position of the modified entity
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $originalData
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function updateAfterRemove(EventInterface $event, EntityInterface $entity, array $originalData = []): void {
		$table = $this->table();
		$tableAlias = $table->getAlias();

		$systemOrder = $entity->get('systemOrder');
		if ($entity->hasOriginal('systemOrder')) {
			$systemOrder = $entity->getOriginal('systemOrder');
		}

		$attributes = $entity->get('attributes');
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
			$attributes &&
			array_filter($this->getConfig('relatedColumns'), fn ($field) => str_starts_with($field, 'attributes.'))
		) {
			$entity = clone $entity;

			$attributes = clone $attributes;
			$entity->set('attributes', $attributes);

			$entity->set($originalData, [
				'asOriginal' => true,
				'guard' => false,
				'setter' => false,
			]);

			$entity->clean();
			if ($attributes) {
				$attributes->clean();
			}
		}

		//Retrieve all records in the same scope of the entity
		$query = $this->addQueryConditions($table->find(), $entity, true);
		//that are not the entity itself and have a systemOrder larger than or equal the entity's old position.
		$query->where([
			$tableAlias . '.id !=' => $entity->get('id'),
			$tableAlias . '.system_order >=' => $systemOrder,
		]);

		$records = $query->all();

		if (!$records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$records = $records->toArray();
		//Decrease the system order of all records
		array_walk($records, function (EntityInterface $record): void {
			/*
			 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
			 * This might happen if the fetch records have no attribute association but available attributes.
			 * In this case, a default attribute entity gets set but this could be invalid.
			 */

			/** @var \Awyiss\Model\Entity $record */
			$record->clean();

			$record->set('systemOrder', $record->get('systemOrder') - 1);
		});

		$this->saveMany($table, $records, $event);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $field
	 * @return void
	 */
	protected function setSystemOrderByField(EntityInterface $entity, string $field): void {
		$table = $this->table();
		$fieldType = $table->getSchema()->getColumnType($field);
		$query = $this->addQueryConditions($table->find(), $entity);

		if (str_starts_with($field, 'attributes.')) {
			$field = substr($field, 11);
		}

		if ($table->fieldIsAttribute($field)) {
			$attributesTableName = $table->getAttributesTableName(true);
			/** @var class-string<\Awyiss\Model\Entity> $entityClass */
			$entityClass = $table->$attributesTableName->getEntityClass();

			$field = $attributesTableName . '.' . $entityClass::unmapField($field);
		}
		else {
			/** @var \Awyiss\Model\Entity $entity */
			$field = $entity::unmapField($field);
		}

		$query->select(['id', $field]);

		if (!$entity->isNew()) {
			$query->where(['id !=' => $entity->get('id')]);
		}

		$records = $query->all()->append([$entity])->sortBy(
			$field,
			$this->getConfig('direction'),
			in_array($fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
		)->toList();

		foreach ($records as $key => $existingEntities) {
			if ($entity->isNew()) {
				if (!$existingEntities->id) {
					$entity->set('systemOrder', $key + 1);
					break;
				}

				continue;
			}

			if ($existingEntities->id === $entity->get('id')) {
				$entity->set('systemOrder', $key + 1);
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
	 * @param \Awyiss\Model\Entity $entity
	 * @param int $hightesSystemOrder
	 * @return void
	 */
	protected function setSystemOrderForNewEntity(EntityInterface $entity, int $hightesSystemOrder): void {
		$systemOrder = $entity->get('systemOrder');

		// Make sure the systemOrder is set and not higher than the max. allowed
		// value plus 1 and not lower than 1 because cool orders start at 1, not 0.
		if (
			is_null($systemOrder) ||
			$systemOrder === 0 ||
			$systemOrder > $hightesSystemOrder
		) {
			$entity->set('systemOrder', $hightesSystemOrder + 1);
		}
		elseif ($systemOrder < 0) {
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
				'customerGroupAssignments' => ['skip' => true],
				'mediaAssignments' => ['skip' => true],
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
				'associated' => [],
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
	 * @param int $direction
	 * @param \Cake\Event\EventInterface|null $event
	 * @param array $additionalWhere
	 * @return iterable|false
	 * @throws \Exception
	 */
	protected function ensureGaplessSystemOrder(int $direction = SORT_ASC, ?EventInterface $event = null, array $additionalWhere = []): iterable|false {
		$table = $this->table();
		$query = $table->find();

		if ($additionalWhere) {
			$query->where($additionalWhere);
		}

		$records = $query->all()->sortBy('system_order', $direction);

		return $this->_rebuildSystemOrder($table, $records, $event);
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Collection\CollectionInterface $records
	 * @param \Cake\Event\EventInterface|null $event
	 * @return iterable|false
	 * @throws \Exception
	 */
	protected function _rebuildSystemOrder(Table $table, CollectionInterface $records, ?EventInterface $event): iterable|false {
		$relatedColumns = $this->getConfig('relatedColumns');

		if ($relatedColumns) {
			$relatedColumns = $table->extractAttributeFields($relatedColumns, true);
			$groupedItems = $records->groupBy(function (EntityInterface $entity) use ($relatedColumns) {
				$values = array_map(function (string $field) use ($entity) {
					$value = $entity->get($field);

					if ($value instanceof BackedEnum) {
						$value = $value->value;
					}

					return $value ?? '-';
				}, $relatedColumns);


				return implode('_', $values);
			})->reject(function (array $items): bool {
				return count($items) === 1;
			})->each(function (array $items): void {
				// Increase the system order of all records
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

			$items = $groupedItems->unfold()->toList();
		}
		else {
			$index = 1;
			$records->each(function (EntityInterface $record) use (&$index): void {

				/**
				 * Mark all fields except systemOrder as dirty. That prevents associations getting saved.
				 * This might happen if the fetch records have no attribute association but available attributes.
				 * In this case, a default attribute entity gets set but this could be invalid.
				 *
				 * @var \Awyiss\Model\Entity $record
				 */
				$record->clean();

				$record->set('systemOrder', $index);

				$index++;
			});

			$items = $records->toList();
		}

		if (!$items) {
			return [];
		}

		return $this->saveMany($table, $items, $event);
	}
}
