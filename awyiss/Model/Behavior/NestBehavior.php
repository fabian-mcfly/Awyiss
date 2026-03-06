<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Configuration;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use RuntimeException;


/**
 * The NestBehavior exposes four methods on the table object:
 * `getNestedChildren()`, `getChildren()`, `getParent()` and `getParents()`
 *
 * For this, it uses the config options `children`/`parent` with the following options
 *    - associationName: the alias of a valid hasMany/belongsTo-association, set on the table
 *    - finder: the finder that's being used to get all children/parent(s)
 *    - maxLevel: maximum depth of items to return
 *
 * It also moves all nested children to a new scope in the `afterSave`-event, using the `relatedColumns`-option
 */
class NestBehavior extends Behavior {
	/**
	 * Fetches parents or nested children inside a loop, but only fetches
	 * those records that are required
	 *
	 * @noinspection PhpUnused
	 */
	final public const string  STRATEGY_FETCH_GRADUALLY = 'fetchGradually';
	/**
	 * Fetches all items inside the element's scope and builds a collection
	 * by filtering out siblings and records that aren't children or parents
	 */
	final public const string  STRATEGY_FETCH_ALL = 'fetchAll';

	/**
	 * Default configuration
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'alias' => null,
		'buildRules' => true,
		'children' => [
			'associationName' => null,
			'bindingKey' => 'id',
			'blocklistedColumns' => [],
			'finder' => null,
			'foreignKey' => 'parentId',
			'maxLevel' => null,
		],
		'enabled' => false,
		'implementedEvents' => [
			'buildRules',
			'beforeCopy',
			'beforeSave',
			'afterSave',
		],
		'implementedMethods' => [
			'getNestedChildren' => 'getNestedChildren',
			'getChildren' => 'getChildren',
			'getParent' => 'getParent',
			'getParents' => 'getParents',
			'getPossibleParents' => 'getPossibleParents',
			'listNested' => 'listNested',
		],
		'parent' => [
			'associationName' => null,
			'bindingKey' => 'id',
			'finder' => null,
			'foreignKey' => 'parentId',
			'maxLevel' => null,
		],
		'relatedColumns' => [],
		'strategy' => self::STRATEGY_FETCH_ALL,
		'skip' => false,
	];
	/**
	 * @var array Remembered data from existing entities in the beforeSave method
	 */
	protected array $rememberedData = [];
	/**
	 * @var array
	 */
	protected array $records = [];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if (!$this->getConfig('alias')) {
			$alias = $this->table()->getAlias();

			if (str_starts_with($alias, 'Child')) {
				$alias = substr($alias, 5);
			}
			elseif (str_starts_with($alias, 'Parent')) {
				$alias = substr($alias, 6);
			}

			$this->setConfig('alias', $alias);
		}

		$this->setConfig('implementedEvents', [
			'Configuration.' . $this->getConfig('alias') . '.Backend.nest.enabled.afterSaveCommit' => 'unnestEntriesAfterSave',
			'Configuration.' . $this->getConfig('alias') . '.Backend.nest.enabled.afterDeleteCommit' => 'unnestEntriesAfterDelete',
		]);

		$this->buildAssociations();
	}


	/**
	 * Build the association for the table
	 *
	 * @return $this
	 */
	protected function buildAssociations(): static {
		$table = $this->table();
		$schema = $table->getSchema();
		$alias = $this->getConfig('alias');

		if (
			$schema->hasColumn($this->getConfig('children.foreignKey')) &&
			(
				!$this->getConfig('children.associationName') ||
				!$table->hasAssociation($this->getConfig('children.associationName'))
			)
		) {
			$associationName = $this->getConfig('children.associationName') ?: 'Child' . Inflector::camelize($alias);

			$bindingKeys = (array)$this->getConfig('children.bindingKey');
			$bindingKeys = array_filter($bindingKeys, fn ($field) => !str_starts_with($field, 'attributes.'));

			$foreignKeys = (array)$this->getConfig('children.foreignKey');
			$foreignKeys = array_filter($foreignKeys, fn ($field) => !str_starts_with($field, 'attributes.'));

			$table->hasMany($associationName, [
				'bindingKey' => $bindingKeys,
				'cascadeCallbacks' => true,
				'className' => $alias,
				'dependent' => true,
				'foreignKey' => $foreignKeys,
				'propertyName' => Inflector::variable($associationName),
			]);

			$this->setConfig('children.associationName', $associationName);
		}

		if (
			$schema->hasColumn($this->getConfig('parent.foreignKey')) &&
			(
				!$this->getConfig('parent.associationName') ||
				!$table->hasAssociation($this->getConfig('parent.associationName'))
			)
		) {
			$associationName = $this->getConfig('parent.associationName') ?: 'Parent' . Inflector::camelize($alias);

			$bindingKeys = array_merge((array)$this->getConfig('parent.bindingKey'), $this->getConfig('relatedColumns'));
			$bindingKeys = array_filter($bindingKeys, fn ($field) => !str_starts_with($field, 'attributes.'));

			$foreignKeys = array_merge((array)$this->getConfig('parent.foreignKey'), $this->getConfig('relatedColumns'));
			$foreignKeys = array_filter($foreignKeys, fn ($field) => !str_starts_with($field, 'attributes.'));

			$table->belongsTo($associationName, [
				'bindingKey' => $bindingKeys,
				'className' => $alias,
				'foreignKey' => $foreignKeys,
				'propertyName' => Inflector::variable(Inflector::singularize($associationName)),
			]);

			$this->setConfig('parent.associationName', $associationName);
		}

		return $this;
	}


	/**
	 * Returns a collection containing all direct children of the given entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getChildren(EntityInterface $entity, array $options = []): ?CollectionInterface {
		if (($options['forceEnable'] ?? false) === false && (!$this->getConfig('enabled') || !$this->getConfig('children'))) {
			return null;
		}

		$association = $this->getAssociation('children');

		$query = $association->find();

		$finder = $options['finder'] ?? $this->getConfig('children.finder');
		if ($finder) {
			$query = $association->find($finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($query, $options);

		$this->addQueryConditions($query, $association, $entity, 'children');

		return $query->all();
	}


	/**
	 * Returns a collection containing all nested children of the given entity.
	 * The depth can be limited using the `maxLevel` option in either the `children`-config array or
	 * as an array key in the second parameter of the method call.
	 *
	 * Calling `$Comments->getNestedChildren($comment, ['maxLevel' => 2]);` returns all direct children of $comment as well as
	 * all direct children of those.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getNestedChildren(EntityInterface $entity, array $options = [], int $currentLevel = 0): ?CollectionInterface {
		if (($options['forceEnable'] ?? false) === false && (!$this->getConfig('enabled') || !$this->getConfig('children'))) {
			return null;
		}

		if ($this->getConfig('strategy') === self::STRATEGY_FETCH_ALL && empty($options['contain'])) {
			return $this->fetchAllNestedChildren($entity, $options);
		}

		$collection = new Collection([]);

		$entity->setVirtual(['level'], true);
		$entity->set('level', $currentLevel);

		foreach ($this->getChildren($entity, $options) as $child) {
			$collection = $collection->appendItem($child);

			$maxLevel = $options['maxLevel'] ?? $this->getConfig('children.maxLevel');
			if (isset($maxLevel) && $maxLevel <= $currentLevel + 1) {
				continue;
			}

			$children = $this->getNestedChildren($child, $options, $currentLevel + 1);

			if (!$children?->count()) {
				continue;
			}

			$collection = $collection->append($children);
		}

		return $collection->compile(false);
	}


	/**
	 * Returns the direct parent entity of the given entity or `null` if none exists
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	public function getParent(EntityInterface $entity, array $options = []): ?EntityInterface {
		if (($options['forceEnable'] ?? false) === false && (!$this->getConfig('enabled') || !$this->getConfig('parent'))) {
			return null;
		}

		$association = $this->getAssociation('parent');

		$query = $association->find();

		$finder = $options['finder'] ?? $this->getConfig('parent.finder');
		if ($finder) {
			$query = $association->find($finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($query, $options);

		$this->addQueryConditions($query, $association, $entity, 'parent');

		return $query->first();
	}


	/**
	 * Returns a collection containing all parents of the given entity.
	 *
	 * The depth can be limited using the `maxLevel` option in either the `parent`-config array or
	 * as an array key in the second parameter of the method call.
	 *
	 * Calling `$Comments->getParents($comment, ['maxLevel' => 2]);` returns the direct parent of $comment
	 * as well as the direct parent of this.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getParents(EntityInterface $entity, array $options = [], int $currentLevel = 0): ?CollectionInterface {
		if (($options['forceEnable'] ?? false) === false && (!$this->getConfig('enabled') || !$this->getConfig('parent'))) {
			return null;
		}

		if ($this->getConfig('strategy') === self::STRATEGY_FETCH_ALL && empty($options['contain'])) {
			return $this->fetchAllParents($entity, $options);
		}

		$collection = new Collection([]);

		$parent = $this->getParent($entity, $options);
		if (!$parent) {
			return $collection->compile(false);
		}

		$collection = $collection->appendItem($parent);

		$maxLevel = $options['maxLevel'] ?? $this->getConfig('parent.maxLevel');
		if (isset($maxLevel) && $maxLevel <= $currentLevel + 1) {
			return $collection->compile(false);
		}

		$parents = $this->getParents($parent, $options, $currentLevel + 1);
		if ($parents) {
			$collection = $collection->append($parents);
		}


		return $collection->compile(false);
	}


	/**
	 * Returns a collection of possible parents for the given entity
	 * from a set of threaded entities,
	 *
	 * Possible parents are all elements that are not children of
	 * the given entity or the entity itself.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $threadedEntities
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function getPossibleParents(Entity $entity, CollectionInterface $threadedEntities): CollectionInterface {
		//We only want to find threaded elements for an existing entity (id equals not null)
		$originalId = $entity->get('id');
		if (!$originalId) {
			return $threadedEntities;
		}

		$foundAtLevel = null;
		$threadedEntities = new Collection($threadedEntities->toList());

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$possibleParents = $threadedEntities->filter(function ($record) use ($originalId, &$foundAtLevel) {
			if ($record->get('id') === $originalId) {
				$foundAtLevel = $record->level;
			}
			elseif ($foundAtLevel === null || $record->level <= $foundAtLevel) {
				$foundAtLevel = null;

				return true;
			}

			return false;
		});

		return $possibleParents;
	}


	/**
	 * Creates a threaded list of entities from a query, adding the `level`-property to each entity and returns
	 * a collection
	 *
	 * @param \Cake\ORM\Query\SelectQuery|\Cake\Collection\Iterator\TreeIterator $query
	 * @param string $nestingKey
	 * @param string $direction
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function listNested(SelectQuery|TreeIterator $query, string $nestingKey = 'children', string $direction = 'desc'): CollectionInterface {
		$records = $query instanceof TreeIterator ? $query : $query->find('threaded', nestingKey: $nestingKey)->all()->listNested($direction, $nestingKey);

		/** @var \Awyiss\Model\Entity $entity */
		foreach ($records as $entity) {
			$entity->setVirtual(['level'], true);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity->level = $records->getDepth();
		}


		return $records;
	}


	/**
	 * @param EventInterface $event
	 * @param RulesChecker $rules
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(EventInterface $event, RulesChecker $rules): void {
		if (!$this->getConfig('enabled') || !$this->getConfig('buildRules')) {
			return;
		}

		$foreignKey = $this->getConfig('parent.foreignKey');

		$rules->add(function (EntityInterface $entity, array $options) use ($rules, $foreignKey): string|bool {
			if (!$entity->get($foreignKey)) {
				return true;
			}

			$foreignKeys = array_merge((array)$foreignKey, $this->getConfig('relatedColumns'));

			$attributeKeys = [];
			foreach ($foreignKeys as $key => $keyName) {
				if (str_starts_with($keyName, 'attributes.')) {
					$attributeKeys[] = $keyName;
					unset($foreignKeys[ $key ]);
				}
			}

			$association = $this->getAssociation('parent');

			if ($attributeKeys) {
				$finder = $association->getFinder();
				$association->setFinder([
					'withMatchingAttributes' => [
						'entity' => $entity,
						'fields' => $attributeKeys,
					],
				]);
			}

			$existsIn = $rules->existsIn($foreignKeys, $association, ['errorField' => '_dummy']);

			if ($entity->isNew()) {
				$exists = $existsIn($entity, $options);
			}
			else {
				$nestedChildren = $this->getNestedChildren($entity);

				if (!$nestedChildren?->count()) {
					return true;
				}

				$nestedChildrenIds = $nestedChildren->extract($this->getConfig('parent.bindingKey'))->toArray();

				$exists = $existsIn($entity, $options) && !in_array($entity->get($foreignKey), $nestedChildrenIds);
			}

			if ($attributeKeys) {
				$association->setFinder($finder);
			}

			if (!$exists) {
				return __df($this->table()->getI18nDomain(), 'Validation', 'error_valid_' . Inflector::underscore($this->getConfig('parent.foreignKey')));
			}

			return true;
		}, 'valid' . Inflector::camelize($foreignKey), [
			'errorField' => Inflector::variable($foreignKey),
		]);
	}


	/**
	 * Before copying, load all nested children of the entity
	 * and prepare them for copying: remove the primary key,
	 * and patch them with the values of related columns.
	 *
	 * This does, what `beforeSave` and `afterSave` do, but they're
	 * both only working with existing entities while a copy
	 * is marked and handled as a new entity.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeCopy(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled') || $options['_primary'] !== true) {
			return;
		}

		$table = $this->table();

		$finders = [];
		if ($table->hasBehavior('MediaAssignment')) {
			$finders['mediaAssignments'] = ['formatResult' => false];
		}
		$finders[] = 'translations';

		/**
		 * @var \Awyiss\Model\Entity $originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$originalEntity = $entity->originalEntity;
		if (!$originalEntity) {
			/** @var \Awyiss\Model\Entity $originalEntity */
			$originalEntity = unserialize(serialize($entity));
			$originalEntity->patch($entity->extractOriginal(), ['guard' => false]);
			$originalEntity->clean();
			$originalEntity->setNew(false);
		}
		$children = $this->getNestedChildren($originalEntity, ['finders' => $finders]);

		if (!$children?->count()) {
			return;
		}

		$alias = $this->getConfig('alias');

		$associationName = $this->getConfig('children.associationName') ?: 'Child' . Inflector::camelize($alias);
		$association = $table->{$associationName};
		$association->getBehavior('Nest')->setConfig('buildRules', false);
		if ($association->hasBehavior('Categories')) {
			$association->getBehavior('Categories')->setConfig('buildRules', false);
		}

		$propertyName = Inflector::variable($table->$associationName->getProperty());
		// Create a nested list of all items
		$entity->{$propertyName} = $children->nest('id', 'parentId', $propertyName)->toList();

		$relatedColumns = $table->getBehavior('Nest')->getConfig('relatedColumns');

		// Remove all blocklisted columns from the related columns
		$blocklistedColumns = $table->getBehavior('Nest')->getConfig('children.blocklistedColumns');
		if ($blocklistedColumns) {
			$relatedColumns = array_diff($relatedColumns, $blocklistedColumns);
		}

		$relatedColumnValues = $entity->extract($relatedColumns);

		/** @var \Awyiss\Model\Entity $child */
		foreach ($children as $child) {
			$primaryKeys = (array)$table->getPrimaryKey();
			$primaryKeyValues = $child->extract($primaryKeys);
			/** @noinspection PhpUndefinedFieldInspection */
			$child->originalPrimaryKeyValues ??= $primaryKeyValues;
			$child->unset($primaryKeys);
			$child->setNew(true);

			/**
			 * If the nesting has related columns, need to set them on the child entity with the
			 * same values as the copied entity.
			 *
			 * Copying a global content to another identifier, ort form elements lto another page:
			 * the new values of the entity have to be used for the copied children as well.
			 */
			if ($relatedColumns) {
				$child->patch($relatedColumnValues);
			}
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (
			$entity->isNew() ||
			!$this->getConfig('enabled') ||
			!$this->getConfig('relatedColumns')
		) {
			return;
		}

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'nest'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		$relatedColumns = $this->getConfig('relatedColumns');
		// Remember the original values of the related columns
		$this->rememberedData[ $entity->get('id') ] = array_merge(
			$entity->extractOriginalChanged($relatedColumns),
			$entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($relatedColumns), true) ?? []
		);
	}


	/**
	 * When the option `relatedColumns` is set and one those columns/properties of the
	 * entity are dirty, the entity was moved to a new scope.
	 * This method handles this change and also moves all nested children to the new scope.
	 *
	 * For example:
	 * - Moving a page from one language to another one results in all children pages also being moved to the new language
	 * - Moving a content from one contentAreas to another one results in all children contents also being moved to the new contentArea
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (empty($this->rememberedData[ $entity->get('id') ])) {
			return;
		}

		$originalData = $this->rememberedData[ $entity->get('id') ];
		unset($this->rememberedData[ $entity->get('id') ]);

		$originalEntity = unserialize(serialize($entity));
		/**
		 * If the entity has attributes and original data is provided, create a clone and set both,
		 * the entity and the attribute, to a clean, "old" state.
		 *
		 * This is necessary since related entities (the attribute in this case) will be saved and marked as clean
		 * before the main entity, so attributes, at this point, have no original values and cannot be used
		 * to retrieve the records of the old scope.
		 */
		if (
			$originalData &&
			$originalEntity->get('attributes') &&
			array_filter($this->getConfig('relatedColumns'), fn ($field) => str_starts_with($field, 'attributes.'))
		) {
			$attributes = $originalEntity->get('attributes');

			$originalEntity->patch($originalData, [
				'asOriginal' => true,
				'guard' => false,
				'setter' => false,
			]);

			$originalEntity->clean();

			if ($attributes) {
				$attributes->clean();
			}
		}

		// Fetch all nested children of the entity with its original data
		$children = $this->getNestedChildren($originalEntity);

		if (!$children?->count()) {
			return;
		}

		$relatedColumns = $this->getConfig('relatedColumns');

		// Remove all blocklisted columns from the related columns
		$blocklistedColumns = $this->getConfig('children.blocklistedColumns');
		if ($blocklistedColumns) {
			$relatedColumns = array_diff($relatedColumns, $blocklistedColumns);
		}

		$table = $this->table();

		$ids = $children->extract('id')->toList();

		// Extract all values of related columns in the entity
		$relatedBaseColumns = array_filter($relatedColumns, fn ($field) => !str_starts_with($field, 'attributes.'));
		if ($relatedBaseColumns) {
			$data = $entity->extract($relatedBaseColumns);

			$table->updateAll($data, ['id IN' => $ids]);
		}

		// Get all related columns that are attributes
		$attributeFields = $table->extractAttributeFields($relatedColumns);
		if (!$attributeFields) {
			return;
		}

		// Extract all values of related columns in the attribute
		$data = $entity->get('attributes')?->extract($attributeFields);
		if (!$data) {
			return;
		}

		$association = $table->getAssociation($table->getAttributesTableName(true));

		$foreignKey = $association->getForeignKey();

		// Update all attributes of the children with the new values
		$affectedRows = $association->updateAll($data, [$foreignKey . ' IN' => $ids]);

		if ($affectedRows === count($ids)) {
			return;
		}

		/**
		 * Houston, we have a problem
		 * Not all children have an attribute row.
		 */
		$existingIds = $association->find()
			->select($foreignKey)
			->where([$foreignKey . ' IN' => $ids])
			->disableHydration()
			->all()
			->extract($foreignKey)
			->toList();

		$newIds = array_diff($ids, $existingIds);

		$entities = [];
		foreach ($newIds as $id) {
			/** @var \Awyiss\Model\Table $association */
			$entities[] = $association->newDefaultEntity(
				$data + [
					$foreignKey => $id,
				]
			);
		}

		try {
			//Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
			$association->saveMany($entities, [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'customerGroupAssignments' => ['skip' => true],
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			]);
		}
		catch (PersistenceFailedException $ex) {
			$event->stopPropagation();
			$event->setResult($ex->getEntity()->getErrors());
		}
	}


	/**
	 * Add conditions to the query based on values of the entity.
	 *
	 * This method is used to add conditions to the query for fetching children or parents
	 * based on the values of the entity, for example getting elements of the same language
	 * or any other field that defines a scope.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\ORM\Association $association
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $type
	 * @return void
	 */
	protected function addQueryConditions(SelectQuery $query, Association $association, EntityInterface $entity, string $type): void {
		if ($type === 'children') {
			$bindingKeys = (array)$association->getBindingKey();
			$foreignKeys = (array)$association->getForeignKey();
		}
		else {
			$foreignKeys = (array)$association->getBindingKey();
			$bindingKeys = (array)$association->getForeignKey();
		}

		$skipFields = $options['skipFields'] ?? [];

		foreach ($foreignKeys as $key => $foreignKey) {
			if (in_array($foreignKey, $skipFields)) {
				continue;
			}

			$value = $entity->hasOriginal($bindingKeys[ $key ]) ? $entity->getOriginal($bindingKeys[ $key ]) : $entity->get($bindingKeys[ $key ]);

			if ($value === null) {
				$foreignKey .= ' IS';
			}

			$query->where([
				$association->getAlias() . '.' . $foreignKey => $value,
			]);
		}
	}


	/**
	 * @param string $type
	 * @return \Cake\ORM\Association
	 */
	protected function getAssociation(string $type): Association {
		$associationName = $this->getConfig($type . '.associationName');

		if (!$associationName || !$this->table()->hasAssociation($associationName)) {
			throw new RuntimeException(sprintf('Expected option for `%s.associationName` to be a valid assocation on table `%s`', $type, $this->table()->getAlias()));
		}

		return $this->table()->getAssociation($associationName);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return ?CollectionInterface
	 */
	protected function fetchAllNestedChildren(EntityInterface $entity, array $options): ?CollectionInterface {
		$association = $this->getAssociation('children');

		$query = $association->find();

		$finder = $options['finder'] ?? $this->getConfig('children.finder');
		if ($finder) {
			$query = $association->find($finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($query, $options);

		$nestingKey = Inflector::variable($this->getConfig('children.associationName'));
		$records = $this->listNested($query, $nestingKey);

		$records = $records->compile(false);

		$originalId = $entity->id;
		$maxLevel = $options['maxLevel'] ?? $this->getConfig('children.maxLevel');
		$foundAtLevel = null;
		$records = $records->filter(function (EntityInterface $entity) use ($originalId, &$foundAtLevel, $maxLevel) {
			/** @var \Awyiss\Model\Entity $entity */
			if ($entity->get('id') === $originalId) {
				/** @noinspection PhpUndefinedFieldInspection */
				$foundAtLevel = $entity->level;
			}
			elseif ($foundAtLevel !== null) {
				/** @noinspection PhpUndefinedFieldInspection */
				if ($entity->level > $foundAtLevel) {
					/** @noinspection PhpUndefinedFieldInspection */
					return !isset($maxLevel) || $entity->level <= $maxLevel;
				}

				$foundAtLevel = null;
			}


			return false;
		});


		return $records->compile(false);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	protected function fetchAllParents(EntityInterface $entity, array $options): ?CollectionInterface {
		$association = $this->getAssociation('parent');

		$query = $association->find();

		$finder = $options['finder'] ?? $this->getConfig('parent.finder');
		if ($finder) {
			$query = $association->find($finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($query, $options);

		$nestingKey = Inflector::variable($this->getConfig('parent.associationName'));
		$records = $this->listNested($query, $nestingKey, 'asc');
		$records = $records->compile(false);

		$originalId = $entity->id;
		$foundParentId = null;
		$maxLevel = $options['maxLevel'] ?? $this->getConfig('parent.maxLevel') ?? 0;
		$records = $records->filter(function (EntityInterface $entity) use ($originalId, &$foundParentId, $maxLevel) {
			/** @var \Awyiss\Model\Entity $entity */
			if ($entity->get('id') === $originalId) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$foundParentId = (int)$entity->parentId;
			}
			elseif ($foundParentId !== null && $entity->id === $foundParentId) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$foundParentId = (int)$entity->parentId;

				/** @noinspection PhpUndefinedFieldInspection */
				return $entity->level >= $maxLevel;
			}

			return false;
		});

		return $records->compile(false);
	}


	/**
	 * @param mixed $query
	 * @param array $options
	 * @return void
	 */
	protected function addQueryOptions(SelectQuery $query, array $options): void {
		if (!empty($options['finders']) && is_array($options['finders'])) {
			foreach ($options['finders'] as $finder => $finderOptions) {
				if (is_int($finder)) {
					$query->find($finderOptions);
				}
				else {
					$query->find($finder, ...$finderOptions);
				}
			}
		}

		if (!empty($options['where'])) {
			$query->where($options['where']);
		}

		if (!empty($options['orderBy'])) {
			$query->orderBy($options['orderBy']);
		}

		if (!empty($options['contain'])) {
			$query->contain($options['contain']);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function unnestEntriesAfterSave(Event $event, Configuration $entity): void {
		$this->unnestEntries($event, $entity);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function unnestEntriesAfterDelete(Event $event, Configuration $entity): void {
		$this->unnestEntries($event, $entity, true);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $entity
	 * @param bool $deleted
	 * @return void
	 * @throws \Exception
	 */
	protected function unnestEntries(Event $event, Configuration $entity, bool $deleted = false): void {
		$defaultNest = false;
		if ($deleted) {
			$configOptions = ConfigOptionsProvider::loadConfigOptions($entity->scope);
			$configOption = $configOptions?->getConfigOption(Awyiss::REALM_BACKEND, $entity->identifier);
			$defaultNest = $configOption?->getDefaultValue() ?? false;
		}

		if (
			(
				$deleted &&
				!$defaultNest
			) ||
			(
				!$deleted &&
				$entity->isDirty('value') &&
				!$entity->value
			)
		) {
			$table = $this->table();
			$schema = $table->getSchema();
			$column = $table->getBehavior('Nest')->getConfig('children.foreignKey');

			if (!$schema->hasColumn($column)) {
				return;
			}
			// If the column is the same as the foreign key of the Categories behavior, we don't need to unnest the entries
			if ($table->hasBehavior('Categories')) {
				$foreignKey = $table->getBehavior('Categories')->getConfig('foreignKey');
				if ($foreignKey && Inflector::underscore($foreignKey) === Inflector::underscore($column)) {
					return;
				}
			}

			$table->updateAll([
				$column => null,
			], [
				$column . ' IS NOT' => null,
			]);

			$field = LocalConfig::read([
				'systemOrder',
				'field',
			], 'systemOrder', $this->getConfig('alias'));

			$direction = LocalConfig::read([
				'systemOrder',
				'direction',
			], SORT_ASC, $this->getConfig('alias'));

			if ($table->hasBehavior('SystemOrder')) {
				$table->getBehavior('SystemOrder')->rebuildSystemOrder($field, $direction, $event);
			}
		}
	}
}
