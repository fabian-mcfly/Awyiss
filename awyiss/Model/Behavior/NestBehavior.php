<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
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
	 */
	final public const STRATEGY_FETCH_GRADUALLY = 'fetch_gradually';
	/**
	 * Fetches all items inside the element's scope and builds a collection
	 * by filtering out siblings and records that aren't children or parents
	 */
	final public const STRATEGY_FETCH_ALL = 'fetch_all';

	/**
	 * Default configuration
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'alias' => null,
		'buildRules' => true,
		'children' => [
			'associationName' => null,
			'bindingKey' => 'id',
			'finder' => null,
			'foreignKey' => 'parent_id',
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
			'foreignKey' => 'parent_id',
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
			$ls_alias = $this->table()->getAlias();

			if (str_starts_with($ls_alias, 'Child')) {
				$ls_alias = substr($ls_alias, 5);
			}
			elseif (str_starts_with($ls_alias, 'Parent')) {
				$ls_alias = substr($ls_alias, 6);
			}

			$this->setConfig('alias', $ls_alias);
		}

		$this->buildAssociations();
	}


	/**
	 * Build the association for the table
	 *
	 * @return $this
	 */
	protected function buildAssociations(): static {
		$lo_table = $this->table();
		$lo_schema = $lo_table->getSchema();
		$ls_alias = $this->getConfig('alias');

		if (
			$lo_schema->hasColumn($this->getConfig('children.foreignKey')) &&
			(
				!$this->getConfig('children.associationName') ||
				!$lo_table->hasAssociation($this->getConfig('children.associationName'))
			)
		) {
			$ls_associationName = $this->getConfig('children.associationName') ?: 'Child' . Inflector::camelize($ls_alias);
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_bindingKeys = (array)$this->getConfig('children.bindingKey');
			$la_bindingKeys = array_filter($la_bindingKeys, fn ($field) => !str_starts_with($field, 'attributes.'));
			$la_bindingKeys = $ls_entityClass::unmapFields($la_bindingKeys);

			$la_foreignKeys = (array)$this->getConfig('children.foreignKey');
			$la_foreignKeys = array_filter($la_foreignKeys, fn ($field) => !str_starts_with($field, 'attributes.'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$lo_table->hasMany($ls_associationName, [
				'bindingKey' => $la_bindingKeys,
				'cascadeCallbacks' => true,
				'className' => $ls_alias,
				'dependent' => true,
				'foreignKey' => $la_foreignKeys,
			]);

			$ls_property = $lo_table->$ls_associationName->getProperty();
			$ls_entityClass::addFieldMapping($ls_property, Inflector::variable($ls_property));

			$this->setConfig('children.associationName', $ls_associationName);
		}

		if (
			$lo_schema->hasColumn($this->getConfig('parent.foreignKey')) &&
			(
				!$this->getConfig('parent.associationName') ||
				!$lo_table->hasAssociation($this->getConfig('parent.associationName'))
			)
		) {
			$ls_associationName = $this->getConfig('parent.associationName') ?: 'Parent' . Inflector::camelize($ls_alias);
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass ??= $lo_table->getEntityClass();

			$la_bindingKeys = array_merge((array)$this->getConfig('parent.bindingKey'), $this->getConfig('relatedColumns'));
			$la_bindingKeys = array_filter($la_bindingKeys, fn ($field) => !str_starts_with($field, 'attributes.'));
			$la_bindingKeys = $ls_entityClass::unmapFields($la_bindingKeys);

			$la_foreignKeys = array_merge((array)$this->getConfig('parent.foreignKey'), $this->getConfig('relatedColumns'));
			$la_foreignKeys = array_filter($la_foreignKeys, fn ($field) => !str_starts_with($field, 'attributes.'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$lo_table->belongsTo($ls_associationName, [
				'bindingKey' => $la_bindingKeys,
				'className' => $ls_alias,
				'foreignKey' => $la_foreignKeys,
			]);

			$ls_property = $lo_table->$ls_associationName->getProperty();
			$ls_entityClass::addFieldMapping($ls_property, Inflector::variable($ls_property));

			$this->setConfig('parent.associationName', $ls_associationName);
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

		$lo_association = $this->getAssociation('children');

		$lo_query = $lo_association->find();

		$lx_finder = $options['finder'] ?? $this->getConfig('children.finder');
		if ($lx_finder) {
			$lo_query = $lo_association->find($lx_finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($lo_query, $options);

		$this->addQueryConditions($lo_query, $lo_association, $entity, 'children');

		return $lo_query->all();
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

		$lo_collection = new Collection([]);

		$entity->setVirtual(['level'], true);
		$entity->set('level', $currentLevel);

		foreach ($this->getChildren($entity, $options) as $lo_entity) {
			$lo_collection = $lo_collection->appendItem($lo_entity);

			$li_maxLevel = $options['maxLevel'] ?? $this->getConfig('children.maxLevel');
			if (isset($li_maxLevel) && $li_maxLevel <= $currentLevel + 1) {
				continue;
			}

			$lo_children = $this->getNestedChildren($lo_entity, $options, $currentLevel + 1);

			if (!$lo_children?->count()) {
				continue;
			}

			$lo_collection = $lo_collection->append($lo_children);
		}

		return $lo_collection->compile(false);
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

		$lo_association = $this->getAssociation('parent');

		$lo_query = $lo_association->find();

		$lx_finder = $options['finder'] ?? $this->getConfig('parent.finder');
		if ($lx_finder) {
			$lo_query = $lo_association->find($lx_finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($lo_query, $options);

		$this->addQueryConditions($lo_query, $lo_association, $entity, 'parent');

		return $lo_query->first();
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

		$lo_collection = new Collection([]);

		$lo_entity = $this->getParent($entity, $options);
		if (!$lo_entity) {
			return $lo_collection->compile(false);
		}

		$lo_collection = $lo_collection->appendItem($lo_entity);

		$li_maxLevel = $options['maxLevel'] ?? $this->getConfig('parent.maxLevel');
		if (isset($li_maxLevel) && $li_maxLevel <= $currentLevel + 1) {
			return $lo_collection->compile(false);
		}

		$lo_parent = $this->getParents($lo_entity, $options, $currentLevel + 1);
		if ($lo_parent) {
			$lo_collection = $lo_collection->append($lo_parent);
		}


		return $lo_collection->compile(false);
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
		$li_originalId = $entity->get('id');
		if (!$li_originalId) {
			return $threadedEntities;
		}

		$li_foundAtLevel = null;
		$lo_threadedEntities = new Collection($threadedEntities->toList());

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$lo_possibleParents = $lo_threadedEntities->filter(function ($record) use ($li_originalId, &$li_foundAtLevel) {
			if ($record->get('id') === $li_originalId) {
				$li_foundAtLevel = $record->level;
			}
			elseif ($li_foundAtLevel === null || $record->level <= $li_foundAtLevel) {
				$li_foundAtLevel = null;

				return true;
			}

			return false;
		});

		return $lo_possibleParents;
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
		$lo_records = $query instanceof TreeIterator ? $query : $query->find('threaded', nestingKey: $nestingKey)->all()->listNested($direction, $nestingKey);

		/** @var \Awyiss\Model\Entity $lo_page */
		foreach ($lo_records as $lo_entity) {
			$lo_entity->setVirtual(['level'], true);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_entity->level = $lo_records->getDepth();
		}


		return $lo_records;
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

		$ls_foreignKey = $this->getConfig('parent.foreignKey');

		$lo_rules = $rules;
		$rules->add(function (EntityInterface $entity, array $options) use ($lo_rules, $ls_foreignKey): string|bool {
			if (!$entity->get($ls_foreignKey)) {
				return true;
			}

			$lo_table = $this->table();
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_foreignKeys = array_merge((array)$ls_foreignKey, $this->getConfig('relatedColumns'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$la_attributeKeys = [];
			foreach ($la_foreignKeys as $li_key => $ls_key) {
				if (str_starts_with($ls_key, 'attributes.')) {
					$la_attributeKeys[] = $ls_key;
					unset($la_foreignKeys[ $li_key ]);
				}
			}

			$lo_association = $this->getAssociation('parent');

			if ($la_attributeKeys) {
				$lx_finder = $lo_association->getFinder();
				$lo_association->setFinder([
					'withMatchingAttributes' => [
						'entity' => $entity,
						'keys' => $la_attributeKeys,
					],
				]);
			}

			$lo_existsIn = $lo_rules->existsIn($la_foreignKeys, $lo_association, ['errorField' => '_dummy']);

			if ($entity->isNew()) {
				$lb_exists = $lo_existsIn($entity, $options);
			}
			else {
				$lo_nestedChildren = $this->getNestedChildren($entity);

				if (!$lo_nestedChildren?->count()) {
					return true;
				}

				$la_nestedChildrenIds = $lo_nestedChildren->extract($this->getConfig('parent.bindingKey'))->toArray();

				$lb_exists = $lo_existsIn($entity, $options) && !in_array($entity->get($ls_foreignKey), $la_nestedChildrenIds);
			}

			if ($la_attributeKeys) {
				$lo_association->setFinder($lx_finder);
			}

			if (!$lb_exists) {
				return __df($this->table()->getI18nDomain(), 'validation', 'error_valid_' . Inflector::underscore($this->getConfig('parent.foreignKey')));
			}

			return true;
		}, 'valid' . Inflector::camelize($ls_foreignKey), [
			'errorField' => Inflector::variable($ls_foreignKey),
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

		$lo_table = $this->table();

		$la_finders = [];
		if ($lo_table->hasBehavior('MediaAssignment')) {
			$la_finders['mediaAssignments'] = ['formatResult' => false];
		}
		$la_finders[] = 'translations';

		/**
		 * @var \Awyiss\Model\Entity $lo_originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$lo_originalEntity = $entity->originalEntity;
		if (!$lo_originalEntity) {
			/** @var \Awyiss\Model\Entity $lo_originalEntity */
			$lo_originalEntity = unserialize(serialize($entity));
			$lo_originalEntity->patch($entity->extractOriginal(), ['guard' => false]);
			$lo_originalEntity->clean();
			$lo_originalEntity->setNew(false);
		}
		$lo_children = $this->getNestedChildren($lo_originalEntity, ['finders' => $la_finders]);

		if (!$lo_children?->count()) {
			return;
		}

		$ls_alias = $this->getConfig('alias');

		$ls_associationName = $this->getConfig('children.associationName') ?: 'Child' . Inflector::camelize($ls_alias);
		$lo_association = $lo_table->{$ls_associationName};
		$lo_association->getBehavior('Nest')->setConfig('buildRules', false);
		if ($lo_association->hasBehavior('Categories')) {
			$lo_association->getBehavior('Categories')->setConfig('buildRules', false);
		}

		$ls_propertyName = Inflector::variable($lo_table->$ls_associationName->getProperty());
		// Create a nested list of all items
		$entity->{$ls_propertyName} = $lo_children->nest('id', 'parentId', $ls_propertyName)->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');
		$la_relatedColumnValues = $entity->extract($this->getConfig('relatedColumns'));

		/** @var \Awyiss\Model\Entity $lo_child */
		foreach ($lo_children as $lo_child) {
			$la_primaryKeys = (array)$lo_table->getPrimaryKey();
			$la_primaryKeyValues = $lo_child->extract($la_primaryKeys);
			/** @noinspection PhpUndefinedFieldInspection */
			$lo_child->originalPrimaryKeyValues = $la_primaryKeyValues;
			$lo_child->unset($la_primaryKeys);
			$lo_child->setNew(true);

			/**
			 * If the nesting has related columns, need to set them on the child entity with the
			 * same values as the copied entity.
			 *
			 * Copying a widget to another identifier, ort form elements lto another page:
			 * the new values of the entity have to be used for the copied children as well.
			 */
			if ($la_relatedColumns) {
				$lo_child->patch($la_relatedColumnValues);
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

		$la_options = Hash::merge($this->getConfig(), Hash::get($options, 'nest'));

		if ($la_options['skip'] === true) {
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		// Remember the original values of the related columns
		$this->rememberedData[ $entity->get('id') ] = array_merge(
			$entity->extractOriginalChanged($la_relatedColumns),
			$entity->get('attributes')?->extractOriginalChanged($this->table()->extractAttributeFields($la_relatedColumns), true) ?? []
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

		$la_originalData = $this->rememberedData[ $entity->get('id') ];
		unset($this->rememberedData[ $entity->get('id') ]);

		$lo_originalEntity = unserialize(serialize($entity));
		/**
		 * If the entity has attributes and original data is provided, create a clone and set both,
		 * the entity and the attribute, to a clean, "old" state.
		 *
		 * This is necessary since related entities (the attribute in this case) will be saved and marked as clean
		 * before the main entity, so attributes, at this point, have no original values and cannot be used
		 * to retrieve the records of the old scope.
		 */
		if (
			$la_originalData &&
			$lo_originalEntity->get('attributes') &&
			array_filter($this->getConfig('relatedColumns'), fn ($field) => str_starts_with($field, 'attributes.'))
		) {
			$lo_attributes = $lo_originalEntity->get('attributes');

			$lo_originalEntity->patch($la_originalData, [
				'asOriginal' => true,
				'guard' => false,
				'setter' => false,
			]);

			$lo_originalEntity->clean();

			if ($lo_attributes) {
				$lo_attributes->clean();
			}
		}

		// Fetch all nested children of the entity with its original data
		$lo_children = $this->getNestedChildren($lo_originalEntity);

		if (!$lo_children?->count()) {
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');

		// Extract all values of related columns in the entity
		$la_data = $entity->extract(array_filter($la_relatedColumns, fn ($field) => !str_starts_with($field, 'attributes.')));
		$la_data = $entity::unmapFields($la_data, true);

		$lo_table = $this->table();

		$la_ids = $lo_children->extract('id')->toList();
		$lo_table->updateAll($la_data, ['id IN' => $la_ids]);

		// Get all related columns that are attributes
		$la_attributeFields = $lo_table->extractAttributeFields($la_relatedColumns);
		if (!$la_attributeFields) {
			return;
		}

		// Extract all values of related columns in the attribute
		$la_data = $entity->get('attributes')?->extract($la_attributeFields);
		if (!$la_data) {
			return;
		}

		// Unmap the keys of the attributes data
		$la_data = $entity->get('attributes')::unmapFields($la_data, true);

		$lo_association = $lo_table->getAssociation($lo_table->getAttributesTableName(true));

		$ls_foreignKey = $lo_association->getForeignKey();

		// Update all attributes of the children with the new values
		$li_affectedRows = $lo_association->updateAll($la_data, [$ls_foreignKey . ' IN' => $la_ids]);

		if ($li_affectedRows === count($la_ids)) {
			return;
		}

		/**
		 * Houston, we have a problem
		 * Not all children have an attribute row.
		 */
		$la_existingIds = $lo_association->find()
			->select($ls_foreignKey)
			->where([$ls_foreignKey . ' IN' => $la_ids])
			->disableHydration()
			->all()
			->extract($ls_foreignKey)
			->toList();

		$la_newIds = array_diff($la_ids, $la_existingIds);

		$la_entities = [];
		foreach ($la_newIds as $li_id) {
			/** @var \Awyiss\Model\Table $lo_association */
			$la_entities[] = $lo_association->newDefaultEntity(
				$la_data + [
					$ls_foreignKey => $li_id,
				]
			);
		}

		try {
			//Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
			$lo_association->saveMany($la_entities, [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
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
			$la_bindingKeys = (array)$association->getBindingKey();
			$la_foreignKeys = (array)$association->getForeignKey();
		}
		else {
			$la_foreignKeys = (array)$association->getBindingKey();
			$la_bindingKeys = (array)$association->getForeignKey();
		}

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $association->getSource()->getEntityClass();

		$la_skipFields = $ls_entityClass::unmapFields($options['skipFields'] ?? []);

		foreach ($la_foreignKeys as $li_key => $ls_field) {
			$ls_field = $ls_entityClass::unmapField($ls_field);

			if (in_array($ls_field, $la_skipFields)) {
				continue;
			}

			$lx_value = $entity->hasOriginal($la_bindingKeys[ $li_key ]) ? $entity->getOriginal($la_bindingKeys[ $li_key ]) : $entity->get($la_bindingKeys[ $li_key ]);

			if ($lx_value === null) {
				$ls_field .= ' IS';
			}

			$query->where([
				$association->getAlias() . '.' . $ls_field => $lx_value,
			]);
		}
	}


	/**
	 * @param string $type
	 * @return \Cake\ORM\Association
	 */
	protected function getAssociation(string $type): Association {
		$ls_associationName = $this->getConfig($type . '.associationName');

		if (!$ls_associationName || !$this->table()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Expected option for `%s.associationName` to be a valid assocation on table `%s`', $type, $this->table()->getAlias()));
		}

		return $this->table()->getAssociation($ls_associationName);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return ?CollectionInterface
	 */
	protected function fetchAllNestedChildren(EntityInterface $entity, array $options): ?CollectionInterface {
		$lo_association = $this->getAssociation('children');

		$lo_query = $lo_association->find();

		$lx_finder = $options['finder'] ?? $this->getConfig('children.finder');
		if ($lx_finder) {
			$lo_query = $lo_association->find($lx_finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($lo_query, $options);

		$ls_nestingKey = Inflector::variable($this->getConfig('children.associationName'));
		$lo_records = $this->listNested($lo_query, $ls_nestingKey);

		$lo_records = $lo_records->compile(false);

		$li_originalId = $entity->id;
		$li_maxLevel = $options['maxLevel'] ?? $this->getConfig('children.maxLevel');
		$li_foundAtLevel = null;
		$lo_records = $lo_records->filter(function (EntityInterface $entity) use ($li_originalId, &$li_foundAtLevel, $li_maxLevel) {
			/** @var \Awyiss\Model\Entity $entity */
			if ($entity->get('id') === $li_originalId) {
				/** @noinspection PhpUndefinedFieldInspection */
				$li_foundAtLevel = $entity->level;
			}
			elseif ($li_foundAtLevel !== null) {
				/** @noinspection PhpUndefinedFieldInspection */
				if ($entity->level > $li_foundAtLevel) {
					/** @noinspection PhpUndefinedFieldInspection */
					return !isset($li_maxLevel) || $entity->level <= $li_maxLevel;
				}

				$li_foundAtLevel = null;
			}


			return false;
		});


		return $lo_records->compile(false);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	protected function fetchAllParents(EntityInterface $entity, array $options): ?CollectionInterface {
		$lo_association = $this->getAssociation('parent');

		$lo_query = $lo_association->find();

		$lx_finder = $options['finder'] ?? $this->getConfig('parent.finder');
		if ($lx_finder) {
			$lo_query = $lo_association->find($lx_finder, entity: $entity, relatedColumns: $this->getConfig('relatedColumns'));
		}

		$this->addQueryOptions($lo_query, $options);

		$ls_nestingKey = Inflector::variable($this->getConfig('parent.associationName'));
		$lo_records = $this->listNested($lo_query, $ls_nestingKey, 'asc');
		$lo_records = $lo_records->compile(false);

		$li_originalId = $entity->id;
		$li_foundParentId = null;
		$li_maxLevel = $options['maxLevel'] ?? $this->getConfig('parent.maxLevel') ?? 0;
		$lo_records = $lo_records->filter(function (EntityInterface $entity) use ($li_originalId, &$li_foundParentId, $li_maxLevel) {
			/** @var \Awyiss\Model\Entity $entity */
			if ($entity->get('id') === $li_originalId) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$li_foundParentId = (int)$entity->parentId;
			}
			elseif ($li_foundParentId !== null && $entity->id === $li_foundParentId) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$li_foundParentId = (int)$entity->parentId;

				/** @noinspection PhpUndefinedFieldInspection */
				return $entity->level >= $li_maxLevel;
			}

			return false;
		});

		return $lo_records->compile(false);
	}


	/**
	 * @param mixed $query
	 * @param array $options
	 * @return void
	 */
	protected function addQueryOptions(SelectQuery $query, array $options): void {
		if (!empty($options['finders']) && is_array($options['finders'])) {
			foreach ($options['finders'] as $lx_finder => $lx_options) {
				if (is_int($lx_finder)) {
					$query->find($lx_options);
				}
				else {
					$query->find($lx_finder, ...$lx_options);
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
}
