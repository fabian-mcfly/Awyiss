<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * SystemOrderBehavior handles records and tables that have a `system_order` column.
 * It changes the position of other records after changing the positition of one record.
 *
 * It also guarantees the `system_order`-column to have a valid value (no gaps, no duplicates)
 *
 * It's possible to limit the order to a specific scope using the option key `relatedColumns`.
 *
 * Example:
 * Using `'relatedColumns' => ['foo', 'bar']` limits this behavior to all items
 * that have the same values for the columns `foo` and `bar` the current entity has.
 *
 */
class SystemOrderBehavior extends Behavior {
	/**
	 * Placeholder in <select>-elements that mark the current position. If this one's selected, no changes were made
	 */
	public const CURRENT_VALUE_PLACEHOLDER = '__CURRENT__';


	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'enabled' => TRUE,
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
		'skip' => FALSE,
	];
	/**
	 * @var array
	 */
	protected array $rememberedFields = [];


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		//If the behavior is loaded for a table without a 'system_order'-column, disable it.
		if ( ! $this->table()->getSchema()->getColumn('system_order')) {
			$this->setConfig('enabled', FALSE);
		}
	}


	/**
	 * Before finding entities, add a default order by clause (ascending by system_order)
	 *
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @param $ab_primary
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options, $ab_primary): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ($ao_event->isStopped()) {
			return;
		}

		$ao_query->orderByAsc($this->table()->getAlias() . '.system_order');
	}


	/**
	 * When marshalling an entity, unset the `system_order`-property in case it's value equals static::CURRENT_VALUE_PLACEHOLDER.
	 * This means, no changes to the system_order column have been made.
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal (EventInterface $ao_event, ArrayObject $ao_data, ArrayObject $ao_options): void {
		if ($ao_event->isStopped()) {
			return;
		}

		if ($ao_data->offsetExists('systemOrder') && $ao_data->offsetGet('systemOrder') === static::CURRENT_VALUE_PLACEHOLDER) {
			$ao_data->offsetUnset('systemOrder');
		}
		elseif ($ao_data->offsetExists('system_order') && $ao_data->offsetGet('system_order') === static::CURRENT_VALUE_PLACEHOLDER) {
			$ao_data->offsetUnset('system_order');
		}
	}


	/**
	 * Before saving an entity, make sure the value for system_order is valid.
	 *
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ($ao_event->isStopped()) {
			return;
		}

		if ($ao_options['_primary'] !== TRUE) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		$li_systemOrderOld = $ao_entity->hasOriginal('systemOrder') ? $ao_entity->getOriginal('systemOrder') : NULL;
		if (! $li_systemOrderOld && $ao_entity->extractOriginalChanged($this->getConfig('relatedColumns'))) {
			$li_systemOrderOld = $ao_entity->get('systemOrder');
		}

		$li_hightesSystemOrder = $this->getHighestSystemOrder($ao_entity);
		if ($ao_entity->isNew()) {
			$li_systemOrder = $ao_entity->get('systemOrder');
			//Make sure the systemOrder is set and not higher than the max. allowed value plus 1
			if (is_null($li_systemOrder) || $li_systemOrder > $li_hightesSystemOrder) {
				/** @noinspection PhpDynamicFieldDeclarationInspection */
				$ao_entity->set('systemOrder', $li_hightesSystemOrder + 1);
			}
			//The position is never allowed to be below 1, because cool orders start at 1, not 0.
			elseif ($li_systemOrder < 1) {
				/** @noinspection PhpDynamicFieldDeclarationInspection */
				$ao_entity->set('systemOrder', 1);
			}

			//Return here since the rest of the method handles logic related to existing entities
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		array_walk($la_relatedColumns, function(&$as_key) {
			$as_key = Inflector::variable($as_key);
		});
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $la_relatedColumns);

		//The position is never allowed to be below 1, because cool orders start at 1, not 0.
		if ($ao_entity->get('systemOrder') < 1) {
			/** @noinspection PhpDynamicFieldDeclarationInspection */
			$ao_entity->systemOrder = 1;
		}

		//The related columns have changed
		if ($la_dirtyRelatedColumns) {
			//If the item is being moved to a new scope, it's allowed to take the highest system order plus 1
			if ($ao_entity->systemOrder > ($li_hightesSystemOrder + 1)) {
				/** @noinspection PhpDynamicFieldDeclarationInspection */
				$ao_entity->systemOrder = $li_hightesSystemOrder + 1;
			}
		}
		else {
			//If the item stays inside its current scope, it's allowed to take the highest system order
			if ($ao_entity->systemOrder > $li_hightesSystemOrder) {
				/** @noinspection PhpDynamicFieldDeclarationInspection */
				$ao_entity->systemOrder = $li_hightesSystemOrder;
			}

			/*
			 * When creating a record, the system_order is always dirty.
			 * But when updating, it's only dirty if it contains a possible value.
			 * So if we needed to reset it to a valid value and this equals the old one, it's not dirty.
			 */
			if ($li_systemOrderOld === $ao_entity->get('systemOrder')) {
				$ao_entity->setDirty('systemOrder', FALSE);
			}
		}
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ($ao_event->isStopped()) {
			return;
		}

		//If it's not the primary afterSave action, there is no need to continue, since siblings have not changed
		if ($ao_options['_primary'] !== TRUE) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		if ($ao_entity->isNew()) {
			$this->updateAfterInsert($ao_entity);

			//Return here since the rest of the method handles logic related to existing entities
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		array_walk($la_relatedColumns, function(&$as_key) {
			$as_key = Inflector::variable($as_key);
		});
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $la_relatedColumns);

		//The related columns have changed
		if ($la_dirtyRelatedColumns) {
			/**
			 * Once a field related to the current scope is dirty, it is not enough to update the system_order
			 * of the records in the scope, since it was moved out of it, and into a new scope.
			 *
			 * This results in two necessary steps:
			 * 	- update the old scope's items
			 * 	- update the new scope's items
			 *
			 * - `A B C D E		=>		A B D E`
			 * - `1 2 3 4 5		=>		1 2 3 4`
			 *
			 * ---
			 *
			 * - `a b c d e		=>		a b C c d e`
			 * - `1 2 3 4 5		=>		1 2 3 4 5 6`
			 *
			 * Moving C to the position 3 of the scope of the lowercase letters means D and E need their system_order decreased by 1,
			 * while A and B need to stay untouched.
			 * This is the same as deleting a record, so `updateAfterRemove` will be used.
			 *
			 * It also means that c, d and e need their system_order increased by 1,
			 * while a and b need to stay untouched.
			 * This is the same as creating a new record, thus calling `updateAfterInsert()` is enough.
			 *
			 */

			$this->updateAfterRemove($ao_entity);
			$this->updateAfterInsert($ao_entity);
		}
		elseif ($ao_entity->isDirty('systemOrder')) {
			//No related columns have changed. This means the item was moved inside its scope.
			$li_systemOrderNew = $ao_entity->get('systemOrder');
			$li_systemOrderOld = $ao_entity->getOriginal('systemOrder');

			//Create a new query and get all records inside the entity's scope, without the entity itself
			$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);
			$lo_query->where([
				$ls_tableAlias . '.id !=' => $ao_entity->id,
			]);

			/**
			 * The item is being moved to the front.
			 *
			 * All items between (and including) the new position and the old position need to move one to the back (+1)
			 *
			 * - `A B C D E		=>		A D B C E`
			 * - `1 2 3 4 5		=>		1 2 3 4 5`
			 *
			 * Moving D to position 2 means B and C need their system_order increased by 1,
			 * while A and E need to stay untouched.
			 *
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
			 * - `A B C D E		=>		A C D B E`
			 * - `1 2 3 4 5		=>		1 2 3 4 5`
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

			if ( ! $lo_records->count()) {
				//No records found? The item is alone in its scope. But that's okay. Not all entities are gregarious animals
				return;
			}

			//If we move an item forwards, me move all items one to the back
			$lb_forward = $li_systemOrderNew < $li_systemOrderOld;

			$la_records = $lo_records->toArray();
			//Increase/decrease the system order of all records
			array_walk($la_records, function(EntityInterface $ao_record) use ($lb_forward) {
				/** @var Entity $ao_record */
				$ao_record->systemOrder += $lb_forward ? 1 : -1;
			});

			//Save all found records, but skip the authorization check, the audit and the system order behavior on those to avoid recursion.
			$lo_table->saveMany($la_records, [
				'audit' => ['skip' => TRUE],
				'authorize' => ['skip' => TRUE],
				'checkRules' => FALSE,
				'systemOrder' => ['skip' => TRUE],
			]);
		}
	}


	/**
	 * Before a soft delete, set the system_order to 999999, so it'll no longer be part of the group.
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function beforeSoftDelete (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ($ao_event->isStopped()) {
			return;
		}

		//If there's no original `deleted`-value or if that original value is empty
		if ( ! $ao_entity->hasOriginal('deleted') || empty($ao_entity->getOriginal('deleted'))) {
			$ao_entity->set('systemOrder', 999999);
		}
	}


	/**
	 * After a soft delete, call the `updateAfterRemove`-method since soft deleting an item means it's no longer part of the scope.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
 	public function afterSoftDelete (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ($ao_event->isStopped()) {
			return;
		}

		//If it's not the primary softDelete action, there is no need to call updateAfterRemove, since all siblings will be deleted as well
		if ($ao_options['_primary'] !== TRUE) {
			return;
		}

		$lo_options = new ArrayObject(Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder')));
		if ($lo_options['skip'] === TRUE) {
			/*
			 * If the system order behavior is skipped, remember the orignal values of the related columns for the given entity
			 * and handle them in the afterSoftDeleteCommit event.
			 *
			 * This will make sure that the update will not run on entities that will be deleted inside the same transaction
			 */
			$this->rememberedFields[ $ao_entity->id ] = $ao_entity->extractOriginalChanged($this->getConfig('relatedColumns'));

			return;
		}

		$this->updateAfterRemove($ao_entity);
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
 	public function afterSoftDeleteCommit (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ($ao_event->isStopped()) {
			return;
		}

 		if (isset($this->rememberedFields[ $ao_entity->id ])) {
 			/*
 			 * Only after the soft delete got committed, update the entity's siblings
 			 * The soft delete behavior will ensure that only undeleted entties will be updated
 			 */
 			$lo_clonedEntity = new $ao_entity($this->rememberedFields[ $ao_entity->id ] + $ao_entity->getOriginalValues(), [
 				'markClean' => TRUE,
 				'markNew' => FALSE,
 				'useSetters' => FALSE
			]);
			$this->updateAfterRemove($lo_clonedEntity);
			unset($this->rememberedFields[ $ao_entity->id ]);
		}
	}


	/**
	 * Add conditions to the query that limit the results to a specific scope, specified by the 'relatedColumns' config
	 * setting.
	 *
	 * For example:
	 *
	 * Contents are ordered individually per specific page (page_id), specific template position and specific parent
	 * content (parent_id)
	 *
	 * @param null|SelectQuery $ao_query
	 * @param EntityInterface $ao_entity
	 * @param bool $ab_useOriginal
	 *
	 * @return Query|false
	 */
	public function addQueryConditions (?SelectQuery $ao_query, EntityInterface $ao_entity, bool $ab_useOriginal = FALSE): SelectQuery|false {
		if ( ! $this->getConfig('enabled')) {
			return FALSE;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $ao_query;
		if ( ! $lo_query) {
			//If no query was provided, create one
			$lo_query = $this->table()->find();
		}

		foreach ($this->getConfig('relatedColumns') as $ls_column) {
			if (in_array($ls_column, ['id', 'systemOrder', 'system_order'])) {
				continue;
			}

			//Add each related column as a where clause, with a value of the entity's current or old value for this column
			$lx_value = $ao_entity->get($ls_column);
			if ($ab_useOriginal && $ao_entity->hasOriginal($ls_column)) {
				$lx_value = $ao_entity->getOriginal($ls_column);
			}

			$ls_isNullCondition = is_null($lx_value) ? ' IS' : NULL;
			$lo_query->where([$ls_tableAlias . '.' . Inflector::underscore($ls_column) . $ls_isNullCondition => $lx_value]);
		}

		return $lo_query;
	}


	/**
	 * Retreive the current highest system order for the scope of the provided entity
	 *
	 * @param EntityInterface $ao_entity
	 *
	 * @return bool|int
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function getHighestSystemOrder (EntityInterface $ao_entity): bool|int {
		if ( ! $this->getConfig('enabled')) {
			return FALSE;
		}

		$lo_table = $this->table();

		$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);
		//$lo_query->applyOptions(['authorize' => ['skip' => TRUE]]);

		$lo_record = $lo_query->select('system_order')->orderByDesc('system_order')->first();

		return $lo_record ? $lo_record->get('systemOrder') : 0;
	}


	/**
	 * Return the columns, related to the system order. Columns with the same value form a scope.
	 *
	 * @param null|EntityInterface $ao_entity
	 *
	 * @return array
	 *
	 * @noinspection PhpUnused
	 */
	public function getRelatedColumns (?EntityInterface $ao_entity = NULL): array {
		if ( ! $this->getConfig('enabled')) {
			return [];
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');

		if ( ! $ao_entity) {
			return $la_relatedColumns;
		}

		return array_intersect($ao_entity->getDirty(), $la_relatedColumns);
	}


	/**
	 * This method moves all elements to the back, depending on the position of the newly created entity
	 *
	 * @param EntityInterface $ao_entity
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function updateAfterInsert (EntityInterface $ao_entity): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		//Retreive all records in the same scope of the entity
		$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity);
		//that are not the entity itself and have a system_order larger than or equal the entity's.
		$lo_query->where([
			$ls_tableAlias . '.id !=' => $ao_entity->id,
			$ls_tableAlias . '.system_order >=' => $ao_entity->get('systemOrder'),
		]);

		$lo_records = $lo_query->all();

		if ( ! $lo_records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$la_records = $lo_records->toArray();
		//Increase the system order of all records
		array_walk($la_records, function(EntityInterface $ao_record) {
			/** @var Entity $ao_record */
			$ao_record->systemOrder++;
		});

		//Save all found records, but skip the authorization check, the audit and the system order behavior on those to avoid recursion.
		$lo_table->saveMany($la_records, [
			'audit' => ['skip' => TRUE],
			'authorize' => ['skip' => TRUE],
			'checkRules' => FALSE,
			'systemOrder' => ['skip' => TRUE],
		]);
	}


	/**
	 * This method moves all elements to the front, depending on the position of the newly created entity
	 *
	 * @param EntityInterface $ao_entity
	 *
	 * @throws \Exception
	 */
	protected function updateAfterRemove (EntityInterface $ao_entity): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$li_systemOrder = $ao_entity->get('systemOrder');
		if ($ao_entity->hasOriginal('systemOrder')) {
			$li_systemOrder = $ao_entity->getOriginal('systemOrder');
		}

		//Retreive all records in the same scope of the entity
		$lo_query = $this->addQueryConditions($lo_table->find(), $ao_entity, TRUE);
		//that are not the entity itself and have a system_order larger than or equal the entity's old position.
		$lo_query->where([
			$ls_tableAlias . '.id !=' => $ao_entity->id,
			$ls_tableAlias . '.system_order >=' => $li_systemOrder,
		]);

		$lo_records = $lo_query->all();

		if ( ! $lo_records->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$la_records = $lo_records->toArray();
		//Increase the system order of all records
		array_walk($la_records, function(EntityInterface $ao_record) {
			/** @var Entity $ao_record */
			$ao_record->systemOrder--;
		});

		//Save all found records, but skip the authorization check, the audit and the system order behavior on those to avoid recursion.
		$lo_table->saveMany($la_records, [
			'audit' => ['skip' => TRUE],
			'authorize' => ['skip' => TRUE],
			'checkRules' => FALSE,
			'systemOrder' => ['skip' => TRUE],
		]);
	}
}
