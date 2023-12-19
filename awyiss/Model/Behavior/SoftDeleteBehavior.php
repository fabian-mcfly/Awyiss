<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Utility\Hash;


class SoftDeleteBehavior extends Behavior {
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
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeDelete' => 'beforeDelete',
		],
		'implementedMethods' => [
			'softDelete' => 'softDelete'
		],
		'includeDeleted' => FALSE,
		'skip' => FALSE,
	];


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		if ( ! $this->table()->getSchema()->getColumn('deleted')) {
			$this->setConfig('enabled', FALSE);
		}
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findDeleted (Query $ao_query, array $aa_options): Query {
		if ( ! $this->getConfig('enabled')) {
			return $ao_query;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted' => TRUE
		])->applyOptions([
			'softDelete' => ['includeDeleted' => TRUE],
		]);

		return $ao_query;
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findWithDeleted (Query $ao_query, array $aa_options): Query {
		if ( ! $this->getConfig('enabled')) {
			return $ao_query;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted IN' => [0, 1]
		])->applyOptions([
			'softDelete' => ['includeDeleted' => TRUE],
		]);

		return $ao_query;
	}


	/**
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules (EventInterface $ao_event, RulesChecker $ao_rules): RulesChecker {
		if ( ! $this->getConfig('enabled')) {
			return $ao_rules;
		}

		$ao_rules->addUpdate(function(EntityInterface $ao_entity, array $aa_options): ?bool {
			return !$ao_entity->getOriginal('deleted');
		}, 'softDelete', [
			'errorField' => '_general',
			'message' => __('::cant_modify_deleted'),
		]);

		return $ao_rules;
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

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete'));

		if (($la_options['includeDeleted'] ?? FALSE) !== TRUE) {
			$ao_query->where([
				$this->table()->getAlias() . '.deleted' => 0,
			]);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @return null|bool
	 * @noinspection PhpUnused
	 */
	public function beforeDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): ?bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete'));

		if ($la_options['skip'] === TRUE) {
			return TRUE;
		}

		$lo_table = $this->table();

		/**
		 * @noinspection PhpUndefinedMethodInspection
		 */
		if ( ! $lo_table->softDelete($ao_entity, $ao_options, $ao_event)) {
			throw new \RuntimeException();
		}

		$ao_event->stopPropagation();

		$lo_table->dispatchEvent('Model.afterDelete', [
			'entity' => $ao_entity,
			'options' => $ao_options,
		]);

		return TRUE;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject|array $ax_options
	 * @param null|\Cake\Event\EventInterface $ao_event
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnused
	 */
	public function softDelete (EntityInterface $ao_entity, \ArrayObject|array $ax_options = [], ?EventInterface $ao_event = NULL): bool {
		$lo_table = $this->table();

		$lo_options = is_array($ax_options) ? new \ArrayObject($ax_options) : $ax_options;

		$lo_event = $lo_table->dispatchEvent('Model.beforeSoftDelete', [
			'entity' => $ao_entity,
			'options' => $lo_options,
		]);

		if ($lo_event->isStopped() && $ao_event) {
			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());

			return FALSE;
		}

		$lo_schema = $this->table()->getSchema();
		$ls_primaryKey = $lo_schema->getPrimaryKey();

		foreach ($ls_primaryKey as $ls_field) {
			if ( ! $ao_entity->has($ls_field)) {
				throw new \RuntimeException();
			}
		}

		foreach ($lo_table->associations() as $lo_association) {
			if ($this->isRecursable($lo_association)) {
				$lo_association->cascadeDelete($ao_entity, ['_primary' => FALSE] + $lo_options->getArrayCopy());
			}
		}

		/**
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$ao_entity->deleted = TRUE;

		$lo_clonedEntity = clone $ao_entity;

		if ($lo_table->save($ao_entity, $lo_options->getArrayCopy() + ['access' => ['skip' => TRUE], 'systemOrder' => ['skip' => TRUE]])) {
			$lo_table->dispatchEvent('Model.afterSoftDelete', [
				'entity' => $lo_clonedEntity,
				'options' => $lo_options,
			]);

			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @param \Cake\ORM\Association $ao_association
	 *
	 * @return bool
	 */
	protected function isRecursable (Association $ao_association): bool {
		$lo_table = $this->table();

		if ($ao_association->isOwningSide($lo_table) && $ao_association->getDependent() && $ao_association->getCascadeCallbacks()) {
			return TRUE;
		}

		return FALSE;
	}
}