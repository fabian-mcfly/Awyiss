<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use BackedEnum;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * Build the category-association (if necessary) and offers a method to retreive all category records
 */
class CategoriesBehavior extends Behavior implements PropertyMarshalInterface {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'aggregationKey' => 'all',
		'allowAggregation' => true,
		'allowUnassigned' => false,
		'associationName' => null,
		'bindingKey' => 'id',
		'buildRules' => true,
		'categories' => null,
		'combinator' => [
			'id',
			'label',
			null,
		],
		'defaultVal' => null,
		'implementedMethods' => [
			'filterQuery' => 'filterQuery',
			'getCategories' => 'getCategories',
			'getQueryConditions' => 'getQueryConditions',
			'groupResult' => 'groupResult',
			'sortQuery' => 'sortQuery',
		],
		'enabled' => false,
		'field' => null,
		'finder' => null,
		'foreignKey' => null,
		'includeParentCategories' => false,
		'identifier' => 'category',
		'queryConditions' => [],
		'selectedCategory' => null,
		'threaded' => true,
		'unassignedKey' => 'unassigned',
		'useDatasource' => true,
	];
	/**
	 * @var array
	 */
	protected array $categories;
	/**
	 * @var \Cake\Collection\CollectionInterface|null
	 */
	protected ?CollectionInterface $parentCategories;


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		if ($this->getConfig('associationName')) {
			$ls_associationName = $this->getConfig('associationName');
			if (!$lo_table->hasAssociation($ls_associationName)) {
				$lo_table->belongsTo($ls_associationName, [
					'bindingKey' => $this->getConfig('bindingKey'),
					'joinType' => 'INNER',
					'foreignKey' => $this->getConfig('foreignKey'),
				]);
			}

			$lo_association = $lo_table->getAssociation($ls_associationName);

			if (!$this->getConfig('foreignKey')) {
				$this->setConfig('foreignKey', $lo_association->getForeignKey());
			}

			if (!$this->getConfig('field')) {
				/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
				$ls_entityClass = $lo_table->getEntityClass();

				$this->setConfig('field', $ls_entityClass::mapField($lo_association->getForeignKey()));
			}
		}

		if (!$this->getConfig('identifier')) {
			throw new RuntimeException(sprintf('`%s` is missing the identifier attribute for table `%s`', static::class, $lo_table->getAlias()));
		}

		if (!$this->getConfig('field')) {
			$this->setConfig('field', Inflector::underscore($this->getConfig('identifier')));
		}
	}


	/**
	 * Loads and returns all category-associations, customizable with config settings:
	 * - `finder`
	 * - `queryConditions`
	 *
	 * @param bool $returnRaw
	 * @return \Cake\Datasource\ResultSetInterface|\Cake\Collection\Iterator\TreeIterator|array|null
	 */
	public function getCategories(bool $returnRaw = false): ResultSetInterface|TreeIterator|array|null {
		if (!$this->getConfig('enabled')) {
			return $returnRaw ? new ResultSet([]) : [];
		}

		if (isset($this->categories)) {
			return $this->categories[ $returnRaw ? 'raw' : 'simple' ] ?? null;
		}

		if (!$this->getConfig('useDatasource')) {
			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = $this->table();

			if (method_exists($lo_table, 'buildCategories')) {
				$la_categories = $lo_table->buildCategories();
			}
			else {
				$la_categories = $this->getConfig('categories');
			}

			if (!is_array($la_categories)) {
				throw new RuntimeException(
					sprintf(
						'You need to provide categories or a `buildCategories`-method when using `useDatasource = false` in `%s` for table `%s`.',
						static::class,
						$lo_table->getAlias()
					)
				);
			}

			$this->categories = [
				'raw' => $la_categories['raw'] ?? null,
				'simple' => $la_categories['simple'] ?? $la_categories,
			];

			//Delete the config setting from the config
			$this->setConfig('categories');
		}
		else {
			$this->buildCategories();
		}


		return $this->categories[ $returnRaw ? 'raw' : 'simple' ];
	}


	/**
	 * @return void
	 */
	public function resetCategories(): void {
		if (!$this->getConfig('useDatasource') && !method_exists($this->table(), 'buildCategories')) {
			return;
		}

		unset($this->categories);
	}


	/**
	 * Returns whether the field is one of the attributes for the attached table
	 *
	 * @return bool
	 */
	public function fieldIsAttribute(): bool {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		return $lo_table->fieldIsAttribute($this->getConfig('field') ?: $this->getConfig('identifier'));
	}


	/**
	 * Add category-related conditions to a given query
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param mixed $selectedCategory
	 * @param string|null $column
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function filterQuery(SelectQuery $query, mixed $selectedCategory = null, bool $sortAggregation = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$lx_selectedCategory = $selectedCategory ?? $this->getConfig('selectedCategory');

		//Get the first possible value in case there's no selection
		if ($lx_selectedCategory === null) {
			$la_validValues = $this->getValidSelectionValues();
			$lx_selectedCategory = current($la_validValues);
		}

		//If we shall not use the datasource, apply the query conditions and return
		if (!$this->getConfig('useDatasource')) {
			//When category is empty or equals the aggregationKey, e.g. "all", do not add query conditions
			if (!$lx_selectedCategory || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
				return $sortAggregation ? $this->sortQuery($query) : $query;
			}


			return $query->where($this->getQueryConditions($selectedCategory));
		}

		$ls_associationName = $this->getConfig('associationName');
		if (!$ls_associationName || !$query->getRepository()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Cannot filter query without an association in `%s` for table `%s`.', static::class, $this->table()->getAlias()));
		}

		$lo_association = $query->getRepository()->getAssociation($ls_associationName);

		//When category is empty or equals the aggregationKey, e.g. "all", do not add query conditions
		if (!$lx_selectedCategory || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return $sortAggregation ? $this->sortQuery($query) : $query;
		}


		if ($lo_association instanceof HasMany || $lo_association instanceof BelongsToMany) {
			if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
				/*
				 * Find records with no associated category when the selected category equals the config key "unassignedKey".
				 * This allows finding entities whose categories are missing or that never had a category
				 */
				$lo_junction = $lo_association->junction();
				$ls_column = $lo_junction->getPrimaryKey() . ' IS';
				$query->leftJoinWith($ls_associationName)->where([$lo_junction->getAlias() . '.' . $ls_column => null]);


				return $query;
			}

			//Find records whose category matches the selectedCategory
			$query->matching($ls_associationName, function (SelectQuery $query) use ($lo_association, $lx_selectedCategory) {
				$lo_junction = $lo_association->junction();
				$ls_column = $lo_junction->getAssociation($lo_association->getName())->getForeignKey();

				$query->where([$lo_junction->getAlias() . '.' . $ls_column => $lx_selectedCategory]);


				return $query;
			});
		}
		else {
			//With a belongsTo-association, the categorization is realized by a simple "column = value"-limitation
			$query->where($this->getQueryConditions($lx_selectedCategory));
		}


		return $query;
	}


	/**
	 * @param mixed|null $selectedCategory
	 * @return array
	 */
	public function getQueryConditions(mixed $selectedCategory = null): array {
		if (!$this->getConfig('enabled')) {
			return [];
		}

		$lx_selectedCategory = $selectedCategory ?? $this->getConfig('selectedCategory');

		//Get the first possible value in case there's no selection
		if ($lx_selectedCategory === null) {
			$la_validValues = $this->getValidSelectionValues();
			$lx_selectedCategory = current($la_validValues);
		}

		if (!$lx_selectedCategory || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return [];
		}

		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $this->getConfig('associationName');
			$lo_association = $this->table()->getAssociation($ls_associationName);

			if ($lo_association instanceof HasMany || $lo_association instanceof BelongsToMany) {
				return [];
			}
		}

		$ls_column = $this->getConfig('field') ?: $this->getConfig('identifier');

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		$lb_isAttribute = $this->fieldIsAttribute();
		if ($lb_isAttribute) {
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getAttributesTable()->getEntityClass();
			$ls_column = $ls_entityClass::unmapField($ls_column);
			$ls_column = $lo_table->getAttributesTableName(true) . '.' . $ls_column;
		}
		else {
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();
			$ls_column = $ls_entityClass::unmapField($ls_column);
		}

		if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
			if (!$this->getCategories()) {
				return [$ls_column . ' IS' => null];
			}

			return [
				'OR' => [
					$ls_column . ' IS' => null,
					$ls_column . ' NOT IN' => array_keys($this->getCategories()),
				],
			];
		}


		return [
			$ls_column => $lx_selectedCategory,
		];
	}


	/**
	 * This method groups the result of the provided query by the column provided via `$column`.
	 * It returns the query with an attached formatResults callback, that groups the resultset by the given column
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string|null $column
	 * @param string|null $associationName
	 * @param bool $sortByAssociation
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function groupResult(SelectQuery $query, ?string $column = null, ?string $associationName = null, bool $sortByAssociation = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$ls_column = $column;
		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $associationName ?? $this->getConfig('associationName');
			if (!$ls_associationName) {
				throw new RuntimeException(
					sprintf(
						'Cannot filter query without an association in `%s` for table `%s`.',
						static::class,
						$this->table()->getAlias()
					)
				);
			}

			$lo_association = $query->getRepository()->getAssociation($ls_associationName);

			if (empty($ls_column)) {
				$ls_column = $lo_association->getForeignKey();
				if (is_array($ls_column)) {
					$ls_column = reset($ls_column);
				}
			}

			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $query->getRepository()->getEntityClass();
		}
		else {
			$ls_entityClass = $this->table()->getEntityClass();

			if (empty($ls_column)) {
				$ls_column = $this->getConfig('identifier');
			}
		}

		$ls_column = $ls_entityClass::mapField($ls_column);

		if ($sortByAssociation) {
			$this->sortQuery($query, $column, $associationName);
		}


		return $query->formatResults(function (CollectionInterface $collection) use ($ls_column) {
			return $collection->groupBy(function (EntityInterface $entity) use ($ls_column) {
				$lx_value = $entity->get($ls_column);

				if ($lx_value instanceof BackedEnum) {
					$lx_value = $lx_value->value;
				}

				return $lx_value;
			});
		});
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|null $entity
	 * @return mixed
	 */
	public function getSelectedCategory(?EntityInterface $entity = null): mixed {
		if (!$this->getConfig('enabled')) {
			return null;
		}

		if (!$entity) {
			return $this->getConfig('selectedCategory');
		}

		$ls_field = $this->getConfig('useDatasource') ? $this->getConfig('field') : $this->getConfig('identifier');


		return $entity->get($ls_field);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string|null $column
	 * @param string|null $associationName
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function sortQuery(SelectQuery $query, ?string $column = null, ?string $associationName = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$la_categories = $this->getCategories();
		if (empty($la_categories)) {
			return $query;
		}

		$ls_column = $column;
		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $associationName ?? $this->getConfig('associationName');

			if (!$ls_associationName) {
				throw new RuntimeException(
					sprintf(
						'Cannot filter query without an association in `%s` for table `%s`.',
						static::class,
						$this->table()->getAlias()
					)
				);
			}

			$lo_association = $query->getRepository()->getAssociation($ls_associationName);

			if ($lo_association instanceof BelongsToMany) {
				return $query;
			}

			if (empty($ls_column)) {
				$ls_column = $lo_association->getForeignKey();
				if (is_array($ls_column)) {
					$ls_column = reset($ls_column);
				}
			}

			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_association->getSource()->getEntityClass();

			$la_categoryIdentifiers = array_keys($la_categories);
		}
		else {
			$ls_entityClass = $this->table()->getEntityClass();

			if (empty($ls_column)) {
				$ls_column = $this->getConfig('identifier');
			}

			$la_categoryIdentifiers = array_keys($la_categories);

			dd(2, __FILE__, __LINE__);
		}

		$ls_column = $ls_entityClass::unmapField($ls_column);

		//Remember existing orders
		$lo_order = $query->clause('order');

		$ls_prefixedColumn = $query->getRepository()->getAlias() . '.' . $ls_column;

		// If the table has a SystemOrder behavior, use the sort field to sort the records
		if ($query->getRepository()->hasBehavior('SystemOrder')) {
			$this->_sortQueryBySystemOrderField($query);
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$query->orderByAsc($query->newExpr($query->func()->FIND_IN_SET([
			$ls_prefixedColumn => 'identifier',
			implode(',', $la_categoryIdentifiers),
		])), true);

		/*
		 * Set the order by-clause but reset existing order-clauses, so records will be sorted
		 * by the system_order of category first and then in the desired order.
		 */
		if (!empty($lo_order)) {
			dd($lo_order, __FILE__, __LINE__);
			/** @noinspection PhpUnreachableStatementInspection */
			$lo_query = $query;
			//Re-add remembered orders
			$lo_order->traverse(function ($clause) use ($lo_query): void {
				$lo_query->orderBy($clause);
			});
		}


		return $query;
	}


	/**
	 * @return array
	 */
	public function getValidSelectionValues(): array {
		$la_validSelectionValues = array_keys($this->getCategories());

		if ($this->getConfig('allowUnassigned')) {
			array_unshift($la_validSelectionValues, $this->getConfig('unassignedKey'));
		}

		if ($this->getConfig('allowAggregation')) {
			array_unshift($la_validSelectionValues, $this->getConfig('aggregationKey'));
		}


		return $la_validSelectionValues;
	}


	/**
	 * @param mixed|null $categoryId
	 * @param array|null $validSelectionValues
	 * @return mixed
	 */
	public function verifySelection(mixed $categoryId = null, ?array $validSelectionValues = null): mixed {
		$lx_categoryId = $categoryId ?: $this->getConfig('selectedCategory');

		if (in_array($lx_categoryId, $validSelectionValues ?? $this->getValidSelectionValues())) {
			return $lx_categoryId;
		}


		return false;
	}


	/**
	 * @param int $level
	 * @param int $maxLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getParentCategories(bool $include = false): ?CollectionInterface {
		if (isset($this->parentCategories)) {
			if ($include) {
				$lo_categories = $this->parentCategories->append($this->getCategories(true));


				return $lo_categories->indexBy('id')->compile();
			}


			return $this->parentCategories;
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$ls_associationName = $this->getConfig('associationName');

		if (
			empty($ls_associationName) ||
			!$lo_table->hasAssociation($ls_associationName) ||
			!$lo_table->getAssociation($ls_associationName)->hasBehavior('Categories')
		) {
			$this->parentCategories = null;


			return null;
		}

		/** @var \Awyiss\Model\Behavior\CategoriesBehavior $lo_behavior */
		$lo_behavior = $lo_table->getAssociation($ls_associationName)->getBehavior('Categories');

		if (
			!$lo_behavior->getConfig('enabled') ||
			!$lo_behavior->getConfig('useDatasource')
		) {
			$this->parentCategories = null;


			return null;
		}

		$this->parentCategories = $lo_behavior->getCategories(true);


		if ($include) {
			$lo_categories = $this->parentCategories->append($this->getCategories(true));


			return $lo_categories->indexBy('id')->compile();
		}


		return $this->parentCategories;
	}


	/**
	 * @param int $maxLevel
	 * @return void
	 */
	public function assignParentCategories(int $maxLevel): void {
		$lo_parentCategories = $this->getParentCategories(true);
		if (!$lo_parentCategories) {
			return;
		}

		$lo_parentCategories = $lo_parentCategories->nest('id', 'parentId')->listNested();

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$ls_associationName = $this->getConfig('associationName');
		$lo_association = $lo_table->getAssociation($ls_associationName);
		$ls_entityClass = $lo_association->getEntityClass();

		//Keep track of the current path
		$la_currentPath = [];

		/** @var \Awyiss\Model\Entity $lo_entity */
		foreach ($lo_parentCategories as $lo_entity) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$li_currentDepth = $lo_parentCategories->getDepth();

			//Adjust the current path to reflect the current depth
			$la_currentPath = array_slice($la_currentPath, 0, $li_currentDepth);

			//Check if the entity is an instance of the special class
			if ($lo_entity instanceof $ls_entityClass) {
				$li_parentsCount = min($maxLevel, $li_currentDepth);
				$lo_entity->_parents = array_values(array_slice($la_currentPath, -$li_parentsCount, $li_parentsCount, true));
				$lo_entity->setVirtual(['_parents'], true);

				continue;
			}

			$la_currentPath[ $li_currentDepth ] = $lo_entity;
		}
	}


	/**
	 * @return void
	 */
	protected function buildCategories(): void {
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$ls_associationName = $this->getConfig('associationName');
		//No matching association? Do nothing.
		if (!$ls_associationName || !$lo_table->hasAssociation($ls_associationName)) {
			throw new RuntimeException(
				sprintf(
					'Cannot build categories without an association in `%s` for table `%s`.',
					static::class,
					$this->table()->getAlias()
				)
			);
		}

		$lo_association = $lo_table->getAssociation($ls_associationName);

		$lo_query = $lo_association->find($this->getConfig('finder'))->where($this->getConfig('queryConditions'));

		if ($this->getConfig('threaded')) {
			$lo_query->find('threaded');
		}

		// Include parent categories in the query
		$lo_parentCategories = $this->getConfig('includeParentCategories') ? $this->getParentCategories() : null;
		if ($lo_parentCategories?->count()) {
			$la_parentCategorieIds = $lo_parentCategories->extract('id')->toList();

			$ls_field = $this->getConfig('foreignKey');
			$ls_field = $lo_association->aliasField($ls_field);

			// Order by parent categories first
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_query->orderByAsc($lo_query->newExpr($lo_query->func()->FIND_IN_SET([
				$ls_field => 'identifier',
				implode(',', $la_parentCategorieIds),
			])), true);
		}

		$lo_categories = $lo_query->all();

		if ($this->getConfig('threaded')) {
			/**
			 * Create a nested list of all categories
			 *
			 * @var \Cake\Collection\Iterator\TreeIterator $lo_categories
			 */
			$lo_categories = $lo_categories->listNested();

			/** @var \Awyiss\Model\Entity $lo_category */
			foreach ($lo_categories as $lo_category) {
				$lo_category->setVirtual(['level']);
				//Add the current depth as a level-property to the entity
				$lo_category->level = $lo_categories->getDepth();
			}

			//Create an array, based on a printer set in the config. Default is [id => label]
			$ls_bindingKey = $this->getConfig('bindingKey', 'id');
			$la_categories = $lo_categories->printer(...$this->getConfig('threaded.printer', ['label', $ls_bindingKey, '– ']))->toArray();
		}
		else {
			//Create an array, based on a combinator set in the config. Default is [id => label]
			$la_categories = $lo_categories->combine(...$this->getConfig('combinator'))->toArray();
		}

		$this->categories = [
			'raw' => $lo_categories,
			'simple' => $la_categories,
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules
	 * @return \Awyiss\ORM\RulesChecker
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(Event $event, RulesChecker|BaseRulesChecker $rules): RulesChecker {
		if (!$this->getConfig('enabled') || !$this->getConfig('buildRules')) {
			return $rules;
		}

		$ls_fieldName = Inflector::camelize($this->getConfig('field'));
		$ls_ruleName = 'valid' . $ls_fieldName;

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		$rules->add(function (EntityInterface $entity, array $options) use ($lo_table): bool {
			$la_categories = $this->getCategories();
			$ls_field = $this->getConfig('useDatasource') ? $this->getConfig('field') : $this->getConfig('identifier');

			$lx_value = $entity->get($ls_field);

			if ($this->getConfig('useDatasource')) {
				$lo_association = $lo_table->getAssociation($this->getConfig('associationName'));
				if ($lo_association instanceof BelongsToMany) {
					if (!empty($lx_value)) {
						$la_possibleValues = Hash::extract($lx_value, '{n}.' . $lo_association->getBindingKey());


						return empty(array_diff($la_possibleValues, array_keys($la_categories)));
					}


					return true;
				}
			}

			if ($lx_value instanceof BackedEnum) {
				$lx_value = $lx_value->value;
			}

			return array_key_exists($lx_value, $la_categories);
		}, $ls_ruleName, [
			'errorField' => Inflector::underscore($ls_fieldName),
			'message' => __df(
				$lo_table->getI18nDomain(),
				'validation',
				'error_valid_' . Inflector::underscore($ls_fieldName)
			),
		]);


		return $rules;
	}


	/**
	 * @inheritDoc
	 * @param \Cake\ORM\Marshaller $marshaller The marhshaller of the table the behavior is attached to.
	 * @param array $map The property map being built.
	 * @param array<string, mixed> $options The options array used in the marshalling call.
	 * @return array A map of `[property => callable]` of additional properties to marshal.
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		if ($this->fieldIsAttribute()) {
			$ls_column = $this->getConfig('field') ?: $this->getConfig('identifier');

			return [
				$ls_column => function (mixed $value, EntityInterface $entity) use ($ls_column): mixed {
					$lo_attributes = $entity->get('attributes');

					$lo_attributes->set($ls_column, $value);

					return $value;
				},
			];
		}

		return [];
	}


	/**
	 * Sorts the query by the field used for the system order.
	 * This method is used to sort the query by the system order field, in case the system_order field itself
	 * is ambiguous
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return void
	 */
	protected function _sortQueryBySystemOrderField(SelectQuery $query): void {
		$lo_table = $query->getRepository();

		/** @var \Awyiss\Model\Behavior\SystemOrderBehavior $lo_behavior */
		$lo_behavior = $lo_table->getBehavior('SystemOrder');
		$ls_field = $lo_behavior->getConfig('field');
		if ($ls_field === 'system_order') {
			return;
		}

		$ls_direction = $lo_behavior->getConfig('direction');

		if (str_starts_with($ls_field, 'attributes.')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$ls_fieldType = $lo_table->getAttributesTable()->getSchema()->getColumnType(substr($ls_field, 11));
		}
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		elseif ($lo_table->fieldIsAttribute($ls_field)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$ls_fieldType = $lo_table->getAttributesTable()->getSchema()->getColumnType($ls_field);
		}
		else {
			$ls_fieldType = $lo_table->getSchema()->getColumnType($ls_field);
		}

		/*
		 * $lo_records = $lo_query->all()->sortBy(
		 *	$field,
		 *	$direction,
		 *	in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
		 * );
		 */

		 $query->formatResults(function (CollectionInterface $collection) use ($ls_field, $ls_direction, $ls_fieldType) {
			 return $collection->sortBy(
				 $ls_field,
				 $ls_direction,
				 in_array($ls_fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
			 );
		 });
	}
}
