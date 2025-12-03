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
		],
		'implementedFinders' => [
			'deleted' => 'findDeleted',
			'withDeleted' => 'findWithDeleted',
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
	 * @param array<string, mixed> $config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		//If the behavior is loaded for a table without a 'deleted'-column, disable it.
		if (!$this->table()->getSchema()->getColumn('deleted')) {
			$this->setConfig('enabled', false);
		}
	}


	/**
	 * A finder that allows retrieving deleted entities
	 *
	 * @param SelectQuery $query
	 * @param array $options
	 * @return SelectQuery
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findDeleted(SelectQuery $query, array $options = []): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$query->where([
			$this->table()->getAlias() . '.deleted' => true,
		])->applyOptions([
			'softDelete' => ['includeDeleted' => true],
		] + $options);


		return $query;
	}


	/**
	 * A finder that allows retrieving regular and deleted entities in the same query
	 *
	 * @param SelectQuery $query
	 * @param array $options
	 * @return SelectQuery
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findWithDeleted(SelectQuery $query, array $options = []): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$query->where([
			$this->table()->getAlias() . '.deleted IN' => [false, true],
		])->applyOptions([
			'softDelete' => ['includeDeleted' => true],
		] + $options);


		return $query;
	}


	/**
	 * When building the rules, add a rule that doesn't allow updating deleted items
	 *
	 * @param EventInterface $event
	 * @param RulesChecker $rules
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(EventInterface $event, RulesChecker $rules): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$rules->addUpdate(function (EntityInterface $entity, array $options): ?bool {
			// If the entity is deleted, we don't allow updating it.
			if (
				($entity->has('deleted') && $entity->get('deleted')) ||
				($entity->hasOriginal('deleted') && $entity->getOriginal('deleted') === true)
			) {
				return false;
			}

			return true;
		}, 'deletedNotModified', [
			'errorField' => '_general',
			'message' => __df($this->table()->getI18nDomain(), 'validation', 'error_deleted_not_modified'),
		]);
	}


	/**
	 * When looking for entities, add the check for `deleted` = false
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

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'softDelete'));

		//Shall we include deleted items? Do nothing
		if ($queryOptions['includeDeleted'] ?? false) {
			return;
		}

		$query->where([
			$this->table()->aliasField('deleted') => false,
		]);
	}


	/**
	 * Intercept the deletion of entities and call `softDelete()`
	 *
	 * @param EventInterface $event
	 * @param EntityInterface $entity
	 * @param ArrayObject $options
	 * @return void
	 */
	public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$queryOptions = new ArrayObject(Hash::merge($this->getConfig(), Hash::get($options, 'softDelete')));

		if ($queryOptions['skip'] === true) {
			return;
		}

		// At this point, rules have already been checked, so we can safely unset the checkRules option
		// to not check `update`-rules in the process of saving.
		$queryOptions = $options;
		$queryOptions['checkRules'] = false;

		//Stop the beforeDelete event
		$event->stopPropagation();
		$event->setResult(true);

		//Call softDelete. If it fails, throw an exception
		if (!$this->softDelete($entity, $queryOptions, $event)) {
			if ($event->getResult() === false) {
				return;
			}

			throw new RuntimeException(sprintf('Could not soft-delete entity of type `%s`', $entity::class));
		}

		// Dispatch the afterSoftDeleteCommit event
		$this->table()->dispatchEvent('Model.afterSoftDeleteCommit', [
			'entity' => $entity,
			'options' => $queryOptions,
		]);
	}


	/**
	 * Set the `delete`-column to true and call `Model.beforeSoftDelete` and `Model.afterSoftDelete` events
	 *
	 * @param EntityInterface $entity
	 * @param \ArrayObject $options
	 * @param EventInterface|null $event
	 * @return bool
	 */
	public function softDelete(EntityInterface $entity, ArrayObject $options, ?EventInterface $event = null): bool {
		$table = $this->table();

		$queryOptions = new ArrayObject(
			$options->getArrayCopy() + [
				'_cleanOnSuccess' => false,
				'checkRules' => false,
				'systemOrder' => ['skip' => true],
				'_primary' => true,
			]
		);

		/**
		 * Set the `deleted`-column
		 */
		$entity->set('deleted', true);

		$newEvent = $table->dispatchEvent('Model.beforeSoftDelete', [
			'entity' => $entity,
			'options' => $queryOptions,
		]);

		//If the 'Model.beforeSoftDelete' event was stopped, stop the softDelete event as well
		if ($newEvent->isStopped() && $event) {
			/**
			 * Set the `deleted`-column
			 */
			$entity->set('deleted', false);
			$entity->setDirty('deleted', false);

			$event->stopPropagation();
			$event->setResult($newEvent->getResult());

			return false;
		}

		$schema = $this->table()->getSchema();
		$primaryKeys = $schema->getPrimaryKey();

		//No primary key set? Throw an exception since we can't delete new entities
		foreach ($primaryKeys as $field) {
			if (!$entity->has($field)) {
				throw new RuntimeException(sprintf('Missing property `%s` in entity `%s`.', $field, $entity::class));
			}
		}

		// Traverse all associations and call a cascadeDelete for those, that are configured to cascade on delete.
		foreach ($table->associations() as $association) {
			if ($this->shouldCascadeDelete($association)) {
				$association->cascadeDelete($entity, ['_primary' => false] + $queryOptions->getArrayCopy());
			}
		}

		//Save the entity but skip both the authorization and the system order behavior
		$eventOptions = ['checkRules' => false] + (array)$queryOptions;
		if ($table->save($entity, $eventOptions)) {
			$table->dispatchEvent('Model.afterSoftDelete', [
				'entity' => $entity,
				'options' => new ArrayObject(['systemOrder' => []] + $eventOptions),
			]);

			//Clean the entity because it's saved and therefore no longer dirty.
			$entity->clean();

			return true;
		}

		return false;
	}


	/**
	 * @param \Cake\ORM\Association $association
	 * @return bool
	 */
	protected function shouldCascadeDelete(Association $association): bool {
		if ($association->isOwningSide($this->table()) && $association->getDependent() && $association->getCascadeCallbacks()) {
			return true;
		}

		return false;
	}
}
