<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Utility\Hash;
use RuntimeException;


/**
 * This behavior intercepts calls to `delete()` on a table that contains a 'deleted'-column,
 * skips the deletion and sets the "deleted"-property of the entity/entities the `delete`-method was called with.
 *
 * It also offers a direct method `softDelete()`
 */
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
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		//If the behavior is loaded for a table without a 'deleted'-column, disable it.
		if ( ! $this->table()->getSchema()->getColumn('deleted')) {
			$this->setConfig('enabled', FALSE);
		}
	}


	/**
	 * A finder that allows retreiving deleted entities
	 *
	 * @param \Cake\ORM\Query $ao_query
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
	 *
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
	 * A finder that allows retreiving regular and deleted entities in the same query
	 *
	 * @param \Cake\ORM\Query $ao_query
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findWithDeleted (Query $ao_query, array $aa_options): Query {
		if ( ! $this->getConfig('enabled')) {
			return $ao_query;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted IN' => [FALSE, TRUE]
		])->applyOptions([
			'softDelete' => ['includeDeleted' => TRUE],
		]);

		return $ao_query;
	}


	/**
	 * When building the rules, add a rule that doesn't allow updating deleted items
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\RulesChecker $ao_rules
	 *
	 * @return \Cake\ORM\RulesChecker
	 *
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
	 * When looking for entities, add the check for `deleted` = FALSE
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query $ao_query
	 * @param \ArrayObject $ao_options
	 * @param $ab_primary
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, Query $ao_query, ArrayObject $ao_options, $ab_primary): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete'));

		//Shall we include deleted items? Do nothing
		if ($la_options['includeDeleted'] ?? FALSE) {
			return;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted' => FALSE,
		]);
	}


	/**
	 * Intercept the deletion of entities and call `softDelete()`
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @return NULL|bool
	 * @noinspection PhpUnused
	 */
	public function beforeDelete (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): ?bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete'));

		if ($la_options['skip'] === TRUE) {
			return TRUE;
		}

		//Call softDelete. If it fails, throw an exception
		if ( ! $this->softDelete($ao_entity, $ao_options, $ao_event)) {
			throw new RuntimeException();
		}

		//Stop the beforeDelete event
		$ao_event->stopPropagation();

		//Dispatch an afterDelete event
		$this->table()->dispatchEvent('Model.afterDelete', [
			'entity' => $ao_entity,
			'options' => $ao_options,
		]);

		return TRUE;
	}


	/**
	 * Set the `delete`-column to TRUE and call `Model.beforeSoftDelete` and `Model.afterSoftDelete` events
	 *
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject|array $ax_options
	 * @param NULL|\Cake\Event\EventInterface $ao_event
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnused
	 */
	public function softDelete (EntityInterface $ao_entity, ArrayObject|array $ax_options = [], ?EventInterface $ao_event = NULL): bool {
		$lo_table = $this->table();

		$lo_options = is_array($ax_options) ? new ArrayObject($ax_options) : $ax_options;

		$lo_event = $lo_table->dispatchEvent('Model.beforeSoftDelete', [
			'entity' => $ao_entity,
			'options' => $lo_options,
		]);

		//If the 'Model.beforeSoftDelete' event was stopped, stop the softDelete event as well
		if ($lo_event->isStopped() && $ao_event) {
			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());

			return FALSE;
		}

		$lo_schema = $this->table()->getSchema();
		$ls_primaryKey = $lo_schema->getPrimaryKey();

		//No primary key set? Throw an exception since we can't delete new entities
		foreach ($ls_primaryKey as $ls_field) {
			if ( ! $ao_entity->has($ls_field)) {
				throw new RuntimeException();
			}
		}

		//Traverse all associations and call a cascadeDelete for those, if they are recursable.
		foreach ($lo_table->associations() as $lo_association) {
			if ($this->isRecursable($lo_association)) {
				$lo_association->cascadeDelete($ao_entity, ['_primary' => FALSE] + $lo_options->getArrayCopy());
			}
		}

		/**
		 * Set the `deleted`-column
		 *
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$ao_entity->deleted = TRUE;

		//Save the entity but skip both the access and the system order behavior
		if ($lo_table->save($ao_entity, $lo_options->getArrayCopy() + ['_cleanOnSuccess' => FALSE, 'access' => ['skip' => TRUE], 'systemOrder' => ['skip' => TRUE]])) {
			$lo_table->dispatchEvent('Model.afterSoftDelete', [
				'entity' => $ao_entity,
				'options' => $lo_options,
			]);

			//Clean the entity because it's saved and therefore no longer dirty.
			$ao_entity->clean();

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