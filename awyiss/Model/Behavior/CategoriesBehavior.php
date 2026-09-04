<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use BackedEnum;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Database\Schema\MysqlSchemaDialect;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Hash;
use InvalidArgumentException;
use RuntimeException;


/**
 * Build the category-association (if necessary) and
 * offers a method to retrieve all category records.
 *
 * If `useDatasource` is set to `false`, the categories
 * are expected to be provided via the `categories`-config or
 * built via a `buildCategories`-method in the table.
 */
class CategoriesBehavior extends Behavior {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
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

		$table = $this->table();

		if ($this->getConfig('associationName')) {
			$associationName = $this->getConfig('associationName');
			if (!$table->hasAssociation($associationName)) {
				$table->belongsTo($associationName, [
					'bindingKey' => $this->getConfig('bindingKey', 'id'),
					'joinType' => 'INNER',
					'foreignKey' => $this->getConfig('foreignKey'),
					'propertyName' => Inflector::variable($associationName),
				]);
			}

			$association = $table->getAssociation($associationName);

			if (!$this->getConfig('foreignKey')) {
				$this->setConfig('foreignKey', $association->getForeignKey());
			}

			if (!$this->getConfig('field')) {
				$this->setConfig('field', $association->getForeignKey());
			}
		}

		if (!$this->getConfig('identifier')) {
			throw new RuntimeException(
				sprintf('`%s` is missing the identifier attribute for table `%s`', static::class, $table->getAlias())
			);
		}

		if (!$this->getConfig('field')) {
			$this->setConfig('field', Inflector::variable($this->getConfig('identifier')));
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
			$table = $this->table();

			if (method_exists($table, 'buildCategories')) {
				$categories = $table->buildCategories();
			}
			else {
				$categories = $this->getConfig('categories');
			}

			if (!is_array($categories)) {
				throw new RuntimeException(
					sprintf(
						'You need to provide categories or a `buildCategories`-method when using `useDatasource = false`'
						. ' in `%s` for table `%s`.',
						static::class,
						$table->getAlias()
					)
				);
			}

			$this->categories = [
				'raw' => $categories['raw'] ?? null,
				'simple' => $categories['simple'] ?? $categories,
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

		return $this->table()->fieldIsAttribute($this->getConfig('field') ?: $this->getConfig('identifier'));
	}


	/**
	 * Add category-related conditions to a given query
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param mixed $selectedCategory
	 * @param bool $sortAggregation
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function filterQuery(SelectQuery $query, mixed $selectedCategory = null, bool $sortAggregation = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$selectedCategory ??= $this->getConfig('selectedCategory');

		//Get the first possible value in case there's no selection
		if ($selectedCategory === null) {
			$validValues = $this->getValidSelectionValues();
			$selectedCategory = current($validValues);
		}

		//If we shall not use the datasource, apply the query conditions and return
		if (!$this->getConfig('useDatasource')) {
			//When category is empty or equals the aggregationKey, e.g. "all", do not add query conditions
			if (!$selectedCategory || $selectedCategory === $this->getConfig('aggregationKey')) {
				return $sortAggregation ? $this->sortQuery($query) : $query;
			}


			return $query->where($this->getQueryConditions($selectedCategory));
		}

		$associationName = $this->getConfig('associationName');
		if (!$associationName || !$query->getRepository()->hasAssociation($associationName)) {
			throw new RuntimeException(
				sprintf('Cannot filter query without an association in `%s` for table `%s`.', static::class, $this->table()->getAlias())
			);
		}

		$association = $query->getRepository()->getAssociation($associationName);

		// When category is empty or equals the aggregationKey, e.g. "all", do not add query conditions
		if (!$selectedCategory || $selectedCategory === $this->getConfig('aggregationKey')) {
			return $sortAggregation ? $this->sortQuery($query) : $query;
		}

		if ($association instanceof HasMany || $association instanceof BelongsToMany) {
			if ($selectedCategory == $this->getConfig('unassignedKey')) {
				/*
				 * Find records with no associated category when the selected category equals the config key "unassignedKey".
				 * This allows finding entities whose categories are missing or that never had a category
				 */
				$junction = $association->junction();
				$column = $junction->getPrimaryKey() . ' IS';
				$query->leftJoinWith($associationName)->where([$junction->getAlias() . '.' . $column => null]);


				return $query;
			}

			// Find records whose category matches the selectedCategory
			$query->matching($associationName, function (SelectQuery $query) use ($association, $selectedCategory) {
				$junction = $association->junction();
				$column = $junction->getAssociation($association->getName())->getForeignKey();

				$query->where([$junction->getAlias() . '.' . $column => $selectedCategory]);


				return $query;
			});
		}
		else {
			//With a belongsTo-association, the categorization is realized by a simple "column = value"-limitation
			$query->where($this->getQueryConditions($selectedCategory));
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

		$selectedCategory ??= $this->getConfig('selectedCategory');

		//Get the first possible value in case there's no selection
		if ($selectedCategory === null) {
			$validValues = $this->getValidSelectionValues();
			$selectedCategory = current($validValues);
		}

		if (!$selectedCategory || $selectedCategory === $this->getConfig('aggregationKey')) {
			return [];
		}

		if ($this->getConfig('useDatasource')) {
			$associationName = $this->getConfig('associationName');
			$association = $this->table()->getAssociation($associationName);

			if ($association instanceof HasMany || $association instanceof BelongsToMany) {
				return [];
			}
		}

		$column = $this->getConfig('field') ?: $this->getConfig('identifier');

		$table = $this->table();
		$isAttribute = $this->fieldIsAttribute();
		if ($isAttribute) {
			$column = $table->getAttributesTableName(true) . '.' . $column;
		}

		if ($selectedCategory == $this->getConfig('unassignedKey')) {
			if (!$this->getCategories()) {
				return [$column . ' IS' => null];
			}

			return [
				'OR' => [
					$column . ' IS' => null,
					$column . ' NOT IN' => array_keys($this->getCategories()),
				],
			];
		}


		return [
			$column => $selectedCategory,
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
	public function groupResult(
		SelectQuery $query,
		?string $column = null,
		?string $associationName = null,
		bool $sortByAssociation = true
	): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$realColumn = $column;
		if ($this->getConfig('useDatasource')) {
			$realAssociationName = $associationName ?? $this->getConfig('associationName');
			if (!$realAssociationName) {
				throw new InvalidArgumentException(
					sprintf(
						'Cannot filter query without an association in `%s` for table `%s`.',
						static::class,
						$this->table()->getAlias()
					)
				);
			}

			if (empty($realColumn)) {
				$association = $query->getRepository()->getAssociation($realAssociationName);

				$realColumn = $association->getForeignKey();
				if (is_array($realColumn)) {
					$realColumn = reset($realColumn);
				}
			}
		}
		else {
			if (empty($realColumn)) {
				$realColumn = $this->getConfig('identifier');
			}
		}

		if ($sortByAssociation) {
			$this->sortQuery($query, $column, $associationName);
		}

		return $query->formatResults(function (CollectionInterface $collection) use ($realColumn) {
			return $collection->groupBy(function (EntityInterface $entity) use ($realColumn) {
				$value = $entity->get($realColumn);

				if ($value instanceof BackedEnum) {
					$value = $value->value;
				}

				if ($value === null) {
					$value = $this->getConfig('unassignedKey');
				}

				return $value;
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

		$field = $this->getConfig('useDatasource') ? $this->getConfig('field') : $this->getConfig('identifier');


		return $entity->get($field);
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

		$categories = $this->getCategories();
		if (empty($categories)) {
			return $query;
		}

		if ($this->getConfig('useDatasource')) {
			$associationName ??= $this->getConfig('associationName');

			if (!$associationName) {
				throw new RuntimeException(
					sprintf(
						'Cannot filter query without an association in `%s` for table `%s`.',
						static::class,
						$this->table()->getAlias()
					)
				);
			}

			$association = $query->getRepository()->getAssociation($associationName);

			if ($association instanceof BelongsToMany) {
				return $query;
			}

			if (empty($column)) {
				$column = $association->getForeignKey();
				if (is_array($column)) {
					$column = reset($column);
				}
			}
		}
		else {
			if (empty($column)) {
				$column = $this->getConfig('identifier');
			}
		}

		$categoryIdentifiers = array_keys($categories);

		//Remember existing orders
		$order = $query->clause('order');

		$prefixedColumn = $query->getRepository()->getAlias() . '.' . $column;
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if ($query->getRepository()->fieldIsAttribute($column)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$prefixedColumn = $query->getRepository()->getAttributesTableName(true) . '.' . $column;
		}

		// If the table has a SystemOrder behavior, use the sort field to sort the records
		if ($query->getRepository()->hasBehavior('SystemOrder')) {
			$this->sortQueryBySystemOrderField($query);
		}

		$dialect = $query
			->getConnection()
			->getDriver()
			->schemaDialect()
		;
		// Only MySQL supports FIND_IN_SET for ordering.
		if ($dialect instanceof MysqlSchemaDialect) {
			/** @noinspection PhpUndefinedMethodInspection */
			$query->orderByAsc(
				$query->expr(
					$query
						->func()
						->FIND_IN_SET([
							$prefixedColumn => 'identifier',
							implode(',', $categoryIdentifiers),
						])
				),
				true
			);
		}
		else {
			$query->orderBy(function ($exp) use ($prefixedColumn, $categoryIdentifiers) {
				$index = 0;

				$case = $exp->case();
				foreach ($categoryIdentifiers as $categoryIdentifier) {
					$case->when([$prefixedColumn => $categoryIdentifier])->then($index, 'integer');

					$index++;
				}

				$case->else(999, 'integer');

				return $case;
			});
		}

		/*
		 * Set the order by-clause but reset existing order-clauses, so records will be sorted
		 * by the systemOrder of category first and then in the desired order.
		 */
		if (!empty($order)) {
			dd($order, __FILE__, __LINE__);
			/** @noinspection PhpUnreachableStatementInspection */
			//Re-add remembered orders
			$order->traverse(function ($clause) use ($query): void {
				$query->orderBy($clause);
			});
		}


		return $query;
	}


	/**
	 * @return array
	 */
	public function getValidSelectionValues(): array {
		$validSelectionValues = array_keys($this->getCategories());

		if ($this->getConfig('allowUnassigned')) {
			array_unshift($validSelectionValues, $this->getConfig('unassignedKey'));
		}

		if ($this->getConfig('allowAggregation')) {
			array_unshift($validSelectionValues, $this->getConfig('aggregationKey'));
		}


		return $validSelectionValues;
	}


	/**
	 * @param mixed|null $categoryId
	 * @param array|null $validSelectionValues
	 * @return mixed
	 */
	public function verifySelection(mixed $categoryId = null, ?array $validSelectionValues = null): mixed {
		$categoryId = $categoryId ?: $this->getConfig('selectedCategory');
		if (is_string($categoryId)) {
			$categoryId = Inflector::variable($categoryId);
		}

		$validSelectionValues ??= $this->getValidSelectionValues();

		foreach ($validSelectionValues as $validSelectionValue) {
			if (
				$categoryId == $validSelectionValue
				|| (
					is_string($categoryId) && is_string($validSelectionValue)
					&& Inflector::variable($categoryId) === Inflector::variable($validSelectionValue)
				)
			) {
				return $validSelectionValue;
			}
		}

		return false;
	}


	/**
	 * @param bool $includeCurrentCategories
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getParentCategories(bool $includeCurrentCategories = false): ?CollectionInterface {
		if (isset($this->parentCategories)) {
			if ($includeCurrentCategories) {
				$categories = $this->parentCategories->append($this->getCategories(true));

				return $categories->indexBy('id')->compile();
			}

			return $this->parentCategories;
		}

		$table = $this->table();

		$associationName = $this->getConfig('associationName');

		if (
			empty($associationName) || !$table->hasAssociation($associationName)
			|| !$table
				->getAssociation($associationName)
				->hasBehavior('Categories')
		) {
			$this->parentCategories = null;

			return null;
		}

		/** @var \Awyiss\Model\Behavior\CategoriesBehavior $categoriesBehavior */
		$categoriesBehavior = $table->getAssociation($associationName)->getBehavior('Categories');

		if (
			!$categoriesBehavior->getConfig('enabled') || !$categoriesBehavior->getConfig('useDatasource')
		) {
			$this->parentCategories = null;

			return null;
		}

		$this->parentCategories = $categoriesBehavior->getCategories(true);

		if ($includeCurrentCategories) {
			$categories = $this->parentCategories->append($this->getCategories(true));

			return $categories->indexBy('id')->compile();
		}

		return $this->parentCategories;
	}


	/**
	 * @param int $maxLevel
	 * @return void
	 */
	public function assignParentCategories(int $maxLevel = PHP_INT_MAX): void {
		$parentCategories = $this->getParentCategories(true);
		if (!$parentCategories) {
			return;
		}

		$parentCategories = $parentCategories->nest('id', 'parentId')->listNested();

		$table = $this->table();

		$associationName = $this->getConfig('associationName');
		$association = $table->getAssociation($associationName);
		$entityClass = $association->getEntityClass();

		// Keep track of the current path
		$currentPath = [];

		/** @var \Awyiss\Model\Entity $entity */
		foreach ($parentCategories as $entity) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$currentDepth = $parentCategories->getDepth();

			//Adjust the current path to reflect the current depth
			$currentPath = array_slice($currentPath, 0, $currentDepth);

			//Check if the entity is an instance of the special class
			if ($entity instanceof $entityClass) {
				$parentsCount = min($maxLevel, $currentDepth);
				/** @noinspection PhpUndefinedFieldInspection */
				$entity->_parents = array_values(array_slice($currentPath, -$parentsCount, $parentsCount, true));
				$entity->setVirtual(['_parents'], true);

				continue;
			}

			$currentPath[ $currentDepth ] = $entity;
		}
	}


	/**
	 * @return void
	 */
	protected function buildCategories(): void {
		$table = $this->table();

		$associationName = $this->getConfig('associationName');
		// No matching association? Do nothing.
		if (!$associationName || !$table->hasAssociation($associationName)) {
			throw new RuntimeException(
				sprintf(
					'Cannot build categories without an association in `%s` for table `%s`.',
					static::class,
					$this->table()->getAlias()
				)
			);
		}

		$association = $table->getAssociation($associationName);

		$query = $association->find($this->getConfig('finder'))->where($this->getConfig('queryConditions'));

		// Include parent categories in the query
		$parentCategories = $this->getConfig('includeParentCategories') ? $this->getParentCategories() : null;
		if ($parentCategories?->count()) {
			$parentCategorieIds = $parentCategories->extract('id')->toList();

			$field = $this->getConfig('foreignKey');
			$field = $association->aliasField($field);

			$dialect = $query
				->getConnection()
				->getDriver()
				->schemaDialect()
			;
			// Order by parent categories first
			// Only MySQL supports FIND_IN_SET for ordering.
			if ($dialect instanceof MysqlSchemaDialect) {
				/** @noinspection PhpUndefinedMethodInspection */
				$query->orderByAsc(
					$query->expr(
						$query
							->func()
							->FIND_IN_SET([
								$field => 'identifier',
								implode(',', $parentCategorieIds),
							])
					),
					true
				);
			}
			else {
				$query->orderBy(function ($exp) use ($field, $parentCategorieIds) {
					$index = 0;

					$case = $exp->case();
					foreach ($parentCategorieIds as $parentCategoryId) {
						$case->when([$field => $parentCategoryId])->then($index, 'integer');

						$index++;
					}

					return $case;
				});
			}
		}

		if ($this->getConfig('threaded')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$categories = $association->getTarget()->listNested($query);

			//Create an array, based on a printer set in the config. Default is [id => label]
			$bindingKey = $this->getConfig('bindingKey', 'id');
			$simpleCategories = $categories->printer(...$this->getConfig('threaded.printer', ['label', $bindingKey, '– ']))->toArray();
		}
		else {
			$categories = $query->all();
			//Create an array, based on a combinator set in the config. Default is [id => label]
			$simpleCategories = $categories->combine(...$this->getConfig('combinator'))->toArray();
		}

		$this->categories = [
			'raw' => $categories,
			'simple' => $simpleCategories,
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(Event $event, RulesChecker|BaseRulesChecker $rules): void {
		if (!$this->getConfig('enabled') || !$this->getConfig('buildRules')) {
			return;
		}

		$fieldName = Inflector::camelize($this->getConfig('field'));
		$ruleName = 'valid' . $fieldName;

		$table = $this->table();
		$rules->add(function (EntityInterface $entity, array $options) use ($table): bool {
			$categories = $this->getCategories();
			$field = $this->getConfig('useDatasource') ? $this->getConfig('field') : $this->getConfig('identifier');

			$value = $entity->get($field);

			if ($this->getConfig('useDatasource')) {
				$association = $table->getAssociation($this->getConfig('associationName'));
				if ($association instanceof BelongsToMany) {
					if (!empty($value)) {
						if (is_iterable($value)) {
							$possibleValues = Hash::extract($value, '{n}.' . $association->getBindingKey());
						}
						else {
							$possibleValues = [$value];
						}

						return empty(array_diff($possibleValues, array_keys($categories)));
					}

					return true;
				}
			}

			if ($value instanceof BackedEnum) {
				$value = $value->value;
			}

			return array_key_exists($value, $categories);
		}, $ruleName, [
			'errorField' => Inflector::variable($fieldName),
			'message' => __df(
				$table->getI18nDomain(),
				'Validation',
				'error_valid_' . Inflector::underscore($fieldName)
			),
		]);
	}


	/**
	 * Sorts the query by the field used for the system order.
	 * This method is used to sort the query by the system order field, in case the systemOrder field itself
	 * is ambiguous
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return void
	 */
	protected function sortQueryBySystemOrderField(SelectQuery $query): void {
		$table = $query->getRepository();

		/** @var \Awyiss\Model\Behavior\SystemOrderBehavior $systemOrderBehavior */
		$systemOrderBehavior = $table->getBehavior('SystemOrder');
		$field = $systemOrderBehavior->getConfig('field');
		if (in_array($field, ['systemOrder', 'systemOrder'])) {
			return;
		}

		$direction = $systemOrderBehavior->getConfig('direction');

		if (str_starts_with($field, 'attributes.')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$fieldType = $table
				->getAttributesTable()
				->getSchema()
				->getColumnType(substr($field, 11))
			;
		}
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		elseif ($table->fieldIsAttribute($field)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$fieldType = $table
				->getAttributesTable()
				->getSchema()
				->getColumnType($field)
			;
		}
		else {
			$fieldType = $table->getSchema()->getColumnType($field);
		}

		$query->formatResults(function (CollectionInterface $collection) use ($field, $direction, $fieldType) {
			return $collection->sortBy(
				$field,
				$direction,
				in_array($fieldType, ['string', 'text', 'char']) ? SORT_NATURAL | SORT_FLAG_CASE : SORT_NUMERIC
			);
		});
	}
}
