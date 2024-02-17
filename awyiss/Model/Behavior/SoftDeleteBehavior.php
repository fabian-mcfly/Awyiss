<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
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
	protected array $_defaultConfig = [
		'enabled' => true,
		'implementedEvents' => [
			'buildRules',
			'beforeFind',
			'beforeDelete',
			'afterDeleteCommit',
		],
		'implementedMethods' => [
			'softDelete' => 'softDelete',
		],
		'includeDeleted' => false,
		'skip' => false,
	];


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		//If the behavior is loaded for a table without a 'deleted'-column, disable it.
		if (!$this->table()->getSchema()->getColumn('deleted')) {
			$this->setConfig('enabled', false);
		}
	}


	/**
	 * A finder that allows retreiving deleted entities
	 *
	 * @param SelectQuery $ao_query
	 * @param array $aa_options
	 * @return SelectQuery
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findDeleted(SelectQuery $ao_query, array $aa_options = []): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted' => true,
		])->applyOptions([
			'softDelete' => ['includeDeleted' => true],
		] + $aa_options);


		return $ao_query;
	}


	/**
	 * A finder that allows retreiving regular and deleted entities in the same query
	 *
	 * @param SelectQuery $ao_query
	 * @param array $aa_options
	 * @return SelectQuery
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findWithDeleted(SelectQuery $ao_query, array $aa_options = []): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted IN' => [false, true],
		])->applyOptions([
			'softDelete' => ['includeDeleted' => true],
		] + $aa_options);


		return $ao_query;
	}


	/**
	 * When building the rules, add a rule that doesn't allow updating deleted items
	 *
	 * @param EventInterface $ao_event
	 * @param RulesChecker $ao_rules
	 * @return RulesChecker
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(EventInterface $ao_event, RulesChecker $ao_rules): RulesChecker {
		if (!$this->getConfig('enabled')) {
			return $ao_rules;
		}

		$ao_rules->addUpdate(function (EntityInterface $ao_entity, array $aa_options): ?bool {
			return !$ao_entity->hasOriginal('deleted') || !$ao_entity->getOriginal('deleted');
		}, 'softDelete', [
			'errorField' => '_general',
			'message' => __d('system', 'cant_modify_deleted'),
		]);


		return $ao_rules;
	}


	/**
	 * When looking for entities, add the check for `deleted` = false
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

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete'));

		//Shall we include deleted items? Do nothing
		if ($la_options['includeDeleted'] ?? false) {
			return;
		}

		$ao_query->where([
			$this->table()->getAlias() . '.deleted' => false,
		]);
	}


	/**
	 * Intercept the deletion of entities and call `softDelete()`
	 *
	 * @param EventInterface $ao_event
	 * @param EntityInterface $ao_entity
	 * @param ArrayObject $ao_options
	 * @return null
	 * @noinspection PhpUnused
	 */
	public function beforeDelete(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$lo_options = new ArrayObject(Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete')));

		if ($lo_options['skip'] === true) {
			return;
		}

		$lo_options = $ao_options;
		if ($lo_options->offsetExists('checkRules')) {
			$lo_options->offsetUnset('checkRules');
		}

		//Call softDelete. If it fails, throw an exception
		if (!$this->softDelete($ao_entity, $lo_options, $ao_event)) {
			throw new RuntimeException(sprintf('Could not soft-delete entity of type `%s`', $ao_entity::class));
		}

		//Stop the beforeDelete event
		$ao_event->stopPropagation();
		$ao_event->setResult(true);
	}


	/**
	 * @param EventInterface $ao_event
	 * @param EntityInterface $ao_entity
	 * @param ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function afterDeleteCommit(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$lo_options = new ArrayObject(Hash::merge($this->getConfig(), Hash::get($ao_options, 'softDelete')));

		$lo_event = $this->table()->dispatchEvent('Model.afterSoftDeleteCommit', [
			'entity' => $ao_entity,
			'options' => $lo_options,
		]);

		//If the 'Model.afterSoftDeleteCommit' event was stopped, stop the afterDeleteCommit event as well
		if ($lo_event->isStopped()) {
			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());
		}
	}


	/**
	 * Set the `delete`-column to true and call `Model.beforeSoftDelete` and `Model.afterSoftDelete` events
	 *
	 * @param EntityInterface $ao_entity
	 * @param ArrayObject|array $ao_options
	 * @param EventInterface|null $ao_event
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function softDelete(EntityInterface $ao_entity, ArrayObject $ao_options, ?EventInterface $ao_event = null): bool {
		$lo_table = $this->table();

		$la_defaults = [
			'_cleanOnSuccess' => false,
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
			'_primary' => true,
		];
		$lo_options = $ao_options;

		foreach ($la_defaults as $ls_key => $lx_value) {
			if (!$lo_options->offsetExists($ls_key)) {
				$lo_options->offsetSet($ls_key, $lx_value);
			}
		}

		/**
		 * Set the `deleted`-column
		 */
		$ao_entity->set('deleted', true);

		$lo_event = $lo_table->dispatchEvent('Model.beforeSoftDelete', [
			'entity' => $ao_entity,
			'options' => $lo_options,
		]);

		//If the 'Model.beforeSoftDelete' event was stopped, stop the softDelete event as well
		if ($lo_event->isStopped() && $ao_event) {
			/**
			 * Set the `deleted`-column
			 */
			$ao_entity->set('deleted', false);
			$ao_entity->setDirty('deleted', false);

			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());


			return false;
		}

		$lo_schema = $this->table()->getSchema();
		$la_primaryKeys = $lo_schema->getPrimaryKey();

		//No primary key set? Throw an exception since we can't delete new entities
		foreach ($la_primaryKeys as $ls_field) {
			if (!$ao_entity->has($ls_field)) {
				throw new RuntimeException(sprintf('Missing property `%s` in entity `%s`.', $ls_field, $ao_entity::class));
			}
		}

		//Traverse all associations and call a cascadeDelete for those, if they are recursable.
		/** @var Association $lo_association */
		foreach ($lo_table->associations() as $lo_association) {
			if ($this->isRecursable($lo_association)) {
				$lo_association->cascadeDelete($ao_entity, ['_primary' => false] + $lo_options->getArrayCopy());
			}
		}

		//Save the entity but skip both the authorization and the system order behavior
		$la_options = ['checkRules' => false] + (array)$lo_options;
		if ($lo_table->save($ao_entity, $la_options)) {
			$lo_table->dispatchEvent('Model.afterSoftDelete', [
				'entity' => $ao_entity,
				'options' => new ArrayObject(['systemOrder' => []] + $la_options),
			]);

			//Clean the entity because it's saved and therefore no longer dirty.
			$ao_entity->clean();


			return true;
		}


		return false;
	}


	/**
	 * @param Association $ao_association
	 * @return bool
	 */
	protected function isRecursable(Association $ao_association): bool {
		$lo_table = $this->table();

		if ($ao_association->isOwningSide($lo_table) && $ao_association->getDependent() && $ao_association->getCascadeCallbacks()) {
			return true;
		}


		return false;
	}
}
