<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\Utility\Hash;


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
	public const CURRENT_VALUE_PLACEHOLDER = '__CURRENT__';
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
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
			'Model.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.afterSoftDelete' => 'afterSoftDelete',
		],
		'implementedMethods' => [
			'addSystemOrderQueryConditions' => 'addQueryConditions',
			'getHighestSystemOrder' => 'getHighestSystemOrder',
			'getSystemOrderRelatedColumns' => 'getSystemOrderRelatedColumns',
		],
		'relatedColumns' => [],
		'skip' => FALSE,
	];


	/** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		if ( ! $this->table()->getSchema()->getColumn('system_order')) {
			$this->setConfig('enabled', FALSE);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query $ao_query
	 * @param \ArrayObject $ao_options
	 * @param $ab_primary
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, Query $ao_query, \ArrayObject $ao_options, $ab_primary): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$ao_query->orderAsc($this->table()->getAlias() . '.system_order');
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal (EventInterface $ao_event, \ArrayObject $ao_data, \ArrayObject $ao_options) {
		if ($ao_data->offsetExists('system_order') && $ao_data->offsetGet('system_order') === static::CURRENT_VALUE_PLACEHOLDER) {
			$ao_data->offsetUnset('system_order');
		}
	}


	/**
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'systemOrder'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		$li_systemOrderOld = $ao_entity->getOriginal('system_order');
		$li_hightesSystemOrder = $this->getHighestSystemOrder($ao_entity);

		if ($ao_entity->isNew()) {
			if (is_null($ao_entity->system_order) || $ao_entity->system_order > $li_hightesSystemOrder) {
				$ao_entity->system_order = $li_hightesSystemOrder + 1;
			}
			elseif ($ao_entity->system_order < 1) {
				$ao_entity->system_order = 1;
			}

			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $la_relatedColumns);

		//The position is never allowed to be below 1
		if ($ao_entity->system_order < 1) {
			$ao_entity->system_order = 1;
		}

		if ($la_dirtyRelatedColumns) {
			//If the item is being moved to a new scope, it's allowed to take the highest system order plus 1
			if ($ao_entity->system_order > ($li_hightesSystemOrder + 1)) {
				$ao_entity->system_order = $li_hightesSystemOrder + 1;
			}
		}
		else {
			//If the item stays inside it's current scope, it's allowed to take the highest system order
			if ($ao_entity->system_order > $li_hightesSystemOrder) {
				$ao_entity->system_order = $li_hightesSystemOrder;
			}

			/**
			 * When creating a record, the systemOrder is always dirty
			 * But when updating, it's only dirty if it contains a possible value
			 */
			if ($li_systemOrderOld === $ao_entity->system_order) {
				$ao_entity->setDirty('system_order', FALSE);
			}
		}
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
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
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $la_relatedColumns);

		if ($la_dirtyRelatedColumns) {
			/**
			 * Once a field related to the current scope is dirty, it is not enough to update the system_order
			 * of the records in the scope since it was moved out of it, into a new scope.
			 *
			 * This results in two necessary steps:
			 * 	- update the old scope's items
			 * 	- update the new scope's items
			 *
			 * The first one
			 * The second steps
			 *
			 * A B C D E		=>		A B D E
			 * 1 2 3 4 5		=>		1 2 3 4
			 *
			 * a b c d e		=>		a b C c d e
			 * 1 2 3 4 5		=>		1 2 3 4 5 6
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
		elseif ($ao_entity->isDirty('system_order')) {
			$li_systemOrderNew = $ao_entity->system_order;
			$li_systemOrderOld = $ao_entity->getOriginal('system_order');

			$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

			$lo_query->where([
				$ls_tableAlias . '.id !=' => $ao_entity->id,
			]);

			/**
			 * The item is being moved to the front.
			 * All items between (and including) the new position and the old position need to move one to the back (+1)
			 *
			 * A B C D E		=>		A D B C E
			 * 1 2 3 4 5		=>		1 2 3 4 5
			 *
			 * Moving D to position #2 means B and C need their system_order increased by 1,
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
			 * A B C D E		=>		A C D B E
			 * 1 2 3 4 5		=>		1 2 3 4 5
			 *
			 * Moving B to position #4 means C and D need their system_order decreased by 1,
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
				return;
			}

			//If we move an item forwards, me move all items one to the back
			if ($li_systemOrderNew < $li_systemOrderOld) {
				$lo_records->each(function(EntityInterface $ao_record) {
					$ao_record->system_order++;
				});
			}
			//And if we move an item backwards, me move all items one to the front
			else {
				$lo_records->each(function(EntityInterface $ao_record) {
					$ao_record->system_order--;
				});
			}

			$lo_table->saveMany($lo_records, [
				'access' => ['skip' => TRUE],
				'audit' => ['skip' => TRUE],
				'systemOrder' => ['skip' => TRUE],
			]);
		}
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function beforeSoftDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		if ( ! $ao_entity->getOriginal('deleted')) {
			$ao_entity->system_order = 999999;
		}
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
 	public function afterSoftDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$this->updateAfterRemove($ao_entity);
	}


	public function addQueryConditions (?Query $ao_query, EntityInterface $ao_entity, bool $ab_useOriginal = FALSE): Query|false {
		if ( ! $this->getConfig('enabled')) {
			return FALSE;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $ao_query;
		if ( ! $lo_query) {
			$lo_query = $this->table()->find('withAttributes');
		}

		foreach ($this->getConfig('relatedColumns') as $ls_column) {
			if (in_array($ls_column, ['id', 'system_order'])) {
				continue;
			}

			$lx_value = $ab_useOriginal ? $ao_entity->getOriginal($ls_column) : $ao_entity->get($ls_column);
			$ls_isNullCondition = is_null($lx_value) ? ' IS' : NULL;
			$lo_query->where([$ls_tableAlias . '.' . $ls_column . $ls_isNullCondition => $lx_value]);
		}

		return $lo_query;
	}


	/**
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function getHighestSystemOrder (EntityInterface $ao_entity): bool|int {
		if ( ! $this->getConfig('enabled')) {
			return FALSE;
		}

		$lo_table = $this->table();

		$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

		$lo_record = $lo_query->select('system_order')->orderDesc('system_order')->first();

		return $lo_record ? $lo_record->system_order : 1;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getSystemOrderRelatedColumns (?EntityInterface $ao_entity = NULL): array {
		if ( ! $this->getConfig('enabled')) {
			return [];
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');

		if (!$ao_entity) {
			return $la_relatedColumns;
		}

		return array_intersect($ao_entity->getDirty(), $la_relatedColumns);
	}


	/**
	 * This method moves all elements to the back, depending on the position of the newly created entity
	 *
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function updateAfterInsert (EntityInterface $ao_entity): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

		$lo_query->where([
			$ls_tableAlias . '.id !=' => $ao_entity->id,
			$ls_tableAlias . '.system_order >=' => $ao_entity->system_order,
		]);

		$lo_records = $lo_query->all();

		if ( ! $lo_records->count()) {
			return;
		}

		$lo_records->each(function(EntityInterface $ao_record) {
			$ao_record->system_order++;
		});

		$lo_table->saveMany($lo_records, [
			'access' => ['skip' => TRUE],
			'audit' => ['skip' => TRUE],
			'systemOrder' => ['skip' => TRUE],
		]);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 *
	 * @throws \Exception
	 */
	protected function updateAfterRemove (EntityInterface $ao_entity): void {
		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity, TRUE);

		$lo_query->where([
			$ls_tableAlias . '.id !=' => $ao_entity->id,
			$ls_tableAlias . '.system_order >=' => $ao_entity->getOriginal('system_order'),
		]);

		$lo_records = $lo_query->all();

		if ( ! $lo_records->count()) {
			return;
		}

		$lo_records->each(function(EntityInterface $ao_record) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$ao_record->system_order--;
		});

		$lo_table->saveMany($lo_records, [
			'access' => ['skip' => TRUE],
			'audit' => ['skip' => TRUE],
			'systemOrder' => ['skip' => TRUE],
		]);
	}
}