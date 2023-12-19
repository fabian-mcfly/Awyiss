<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;


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
		'implementedMethods' => [
			'addSystemOrderQueryConditions' => 'addQueryConditions',
		],
		'relatedColumns' => [],
		'skipSystemOrderBehavior' => FALSE,
	];


	/** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		if ( ! $this->table()->getSchema()->getColumn('system_order')) {
			$this->setConfig('enabled', FALSE);
		}
	}


	public function implementedEvents (): array {
		return parent::implementedEvents() + [
			'Model.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query $ao_query
	 * @param \ArrayObject $ao_options
	 * @param $ab_primary
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, Query $ao_query, \ArrayObject $ao_options, $ab_primary): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$ao_query->orderAsc($this->table()->getAlias() . '.system_order');

		return TRUE;
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
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$la_options = \Cake\Utility\Hash::merge($this->getConfig(), $ao_options);

		if ($la_options['skipSystemOrderBehavior'] === TRUE) {
			return TRUE;
		}

		$li_systemOrderOld = $ao_entity->getOriginal('system_order');
		$li_hightesSystemOrder = $this->highestSystemOrder($ao_entity);

		if ($ao_entity->isNew()) {
			if (is_null($ao_entity->system_order) || $ao_entity->system_order > $li_hightesSystemOrder) {
				$ao_entity->system_order = $li_hightesSystemOrder + 1;
			}
			elseif ($ao_entity->system_order < 1) {
				$ao_entity->system_order = 1;
			}
		}
		else {
			if ($ao_entity->system_order < 1) {
				$ao_entity->system_order = 1;
			}

			if ($ao_entity->system_order >= $li_hightesSystemOrder) {
				$ao_entity->system_order = $li_hightesSystemOrder;
			}

			if ($li_systemOrderOld === $ao_entity->system_order) {
				$ao_entity->setDirty('system_order', FALSE);
			}
		}

		return TRUE;
	}


	/**
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function highestSystemOrder (EntityInterface $ao_entity): bool|int {
		if ( ! $this->getConfig('enabled')) {
			return FALSE;
		}

		$lo_table = $this->table();

		$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

		$lo_record = $lo_query->select('system_order')->orderDesc('system_order')->first();

		return $lo_record ? $lo_record->system_order : 1;
	}


	public function addQueryConditions (?Query $ao_query, EntityInterface $ao_entity): Query|false {
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
			$lx_value = $ao_entity->get($ls_column);
			$ls_isNullCondition = is_null($lx_value) ? ' IS' : NULL;
			$lo_query->where([$ls_tableAlias . '.' . $ls_column . $ls_isNullCondition => $lx_value]);
		}

		return $lo_query;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$la_options = \Cake\Utility\Hash::merge($this->getConfig(), $ao_options);

		if ($la_options['skipSystemOrderBehavior'] === TRUE) {
			return TRUE;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		if ($ao_entity->isNew()) {
			$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

			$lo_query->where([
				$ls_tableAlias . '.id !=' => $ao_entity->id,
				$ls_tableAlias . '.system_order >=' => $ao_entity->system_order,
			]);

			$lo_records = $lo_query->all();

			if ( ! $lo_records->count()) {
				return TRUE;
			}

			$lo_records->each(function(EntityInterface $ao_record) {
				$ao_record->system_order++;
			});

			$lo_table->saveMany($lo_records, [
				'skipAuditBehavior' => TRUE,
				'skipSystemOrderBehavior' => TRUE,
				'skipTimeTrackerBehavior' => TRUE,
			]);
		}
		elseif ($ao_entity->isDirty('system_order')) {
			$la_systemOrders = [
				$li_systemOrderNew = $ao_entity->system_order,
				$li_systemOrderOld = $ao_entity->getOriginal('system_order'),
			];
			sort($la_systemOrders);

			$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

			$lo_query->where([
				$ls_tableAlias . '.id !=' => $ao_entity->id,
			]);

			if ($li_systemOrderNew < $li_systemOrderOld) {
				$lo_query->where([
					$ls_tableAlias . '.system_order >=' => $la_systemOrders[0],
					$ls_tableAlias . '.system_order <' => $la_systemOrders[1],
				]);
			}
			else {
				$lo_query->where([
					$ls_tableAlias . '.system_order >' => $la_systemOrders[0],
					$ls_tableAlias . '.system_order <=' => $la_systemOrders[1],
				]);
			}

			$lo_records = $lo_query->all();

			if ( ! $lo_records->count()) {
				return TRUE;
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
				'skipAuditBehavior' => TRUE,
				'skipSystemOrderBehavior' => TRUE,
				'skipTimeTrackerBehavior' => TRUE,
			]);
		}

		return TRUE;
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function beforeSoftDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		if ( ! $ao_entity->getOriginal('deleted')) {
			$ao_entity->system_order = 999999;
		}

		return TRUE;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
 	public function afterSoftDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$lo_table = $this->table();
		$ls_tableAlias = $lo_table->getAlias();

		$lo_query = $this->addQueryConditions($lo_table->find('withAttributes'), $ao_entity);

		$lo_query->where([
			$ls_tableAlias . '.id !=' => $ao_entity->id,
			$ls_tableAlias . '.system_order >=' => $ao_entity->getOriginal('system_order'),
		]);

		$lo_records = $lo_query->all();

		if ( ! $lo_records->count()) {
			return TRUE;
		}

		$lo_records->each(function(EntityInterface $ao_record) {
			$ao_record->system_order--;
		});

		$lo_table->saveMany($lo_records, [
			'skipAuditBehavior' => TRUE,
			'skipSystemOrderBehavior' => TRUE,
			'skipTimeTrackerBehavior' => TRUE,
		]);

		return TRUE;
	}
}