<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
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
	 *     *
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
		'fieldname' => null,
		'finder' => null,
		'foreignKey' => null,
		'identifier' => 'category',
		'queryConditions' => [],
		'selectedCategory' => null,
		'threaded' => false,
		'unassignedKey' => 'unassigned',
		'useDatasource' => true,
	];
	/**
	 * @var array
	 */
	protected array $categories;


	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

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

			if (!$this->getConfig('fieldname')) {
				$lo_association = $lo_table->getAssociation($ls_associationName);

				/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
				$ls_entityClass = $lo_table->getEntityClass();

				$this->setConfig('fieldname', $ls_entityClass::mapField($lo_association->getForeignKey()));
			}
		}

		if (!$this->getConfig('identifier')) {
			throw new RuntimeException(sprintf('`%s` is missing the identifier attribute for table `%s`', static::class, $lo_table->getAlias()));
		}

		if (!$this->getConfig('fieldname')) {
			$this->setConfig('fieldname', Inflector::underscore($this->getConfig('identifier')));
		}
	}


	/**
	 * Loads and returns all category-associations, customizable with config settings:
	 * - `finder`
	 * - `queryConditions`
	 *
	 * @param bool $ab_returnRaw
	 * @return \Cake\Datasource\ResultSetInterface|\Cake\Collection\Iterator\TreeIterator|array|null
	 */
	public function getCategories(bool $ab_returnRaw = false): ResultSetInterface|TreeIterator|array|null {
		if (!$this->getConfig('enabled')) {
			return $ab_returnRaw ? new ResultSet([]) : [];
		}

		if (isset($this->categories)) {
			return $this->categories[ $ab_returnRaw ? 'raw' : 'simple' ] ?? null;
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


		return $this->categories[ $ab_returnRaw ? 'raw' : 'simple' ];
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
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		return $lo_table->fieldIsAttribute($this->getConfig('fieldname') ?: $this->getConfig('identifier'));
	}


	/**
	 * Add category-related conditions to a given query
	 *
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param mixed $ax_selectedCategory
	 * @param string|null $as_column
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function filterQuery(SelectQuery $ao_query, mixed $ax_selectedCategory = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}

		$lx_selectedCategory = $ax_selectedCategory ?? $this->getConfig('selectedCategory');

		//Get the first possible value in case there's no selection
		if ($lx_selectedCategory === null) {
			$la_validValues = $this->getValidSelectionValues();
			$lx_selectedCategory = current($la_validValues);
		}

		//When category is empty or equals the aggregationKey, e.g. "all", do not add query conditions
		if (!$lx_selectedCategory || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return $ao_query;
		}

		//If we shall not use the datasource, apply the query conditions and return
		if (!$this->getConfig('useDatasource')) {
			$ao_query->where($this->getQueryConditions($lx_selectedCategory));


			return $ao_query;
		}

		$ls_associationName = $this->getConfig('associationName');
		if (!$ls_associationName) {
			throw new RuntimeException(sprintf('Cannot filter query without an association in `%s` for table `%s`.', static::class, $this->table()->getAlias()));
		}

		$lo_association = $ao_query->getRepository()->getAssociation($ls_associationName);

		if ($lo_association instanceof HasMany || $lo_association instanceof BelongsToMany) {
			if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
				/*
				 * Find records with no associated category when the selected category equals the config key "unassignedKey".
				 * This allows finding entities whose categories are missing or that never had a category
				 */
				$lo_junction = $lo_association->junction();
				$ls_column = $lo_junction->getPrimaryKey() . ' IS';
				$ao_query->leftJoinWith($ls_associationName)->where([$lo_junction->getAlias() . '.' . $ls_column => null]);


				return $ao_query;
			}

			//Find records whose category matches the selectedCategory
			$ao_query->matching($ls_associationName, function (SelectQuery $ao_query) use ($lo_association, $lx_selectedCategory) {
				$lo_junction = $lo_association->junction();
				$ls_column = $lo_junction->getAssociation($lo_association->getName())->getForeignKey();

				$ao_query->where([$lo_junction->getAlias() . '.' . $ls_column => $lx_selectedCategory]);


				return $ao_query;
			});
		}
		else {
			//With a belongsTo-association, the categorization is realized by a simple "column = value"-limitation
			$ao_query->where($this->getQueryConditions($lx_selectedCategory));
		}


		return $ao_query;
	}


	/**
	 * @param mixed|null $ax_selectedCategory
	 * @return array
	 */
	public function getQueryConditions(mixed $ax_selectedCategory = null): array {
		if (!$this->getConfig('enabled')) {
			return [];
		}

		$lx_selectedCategory = $ax_selectedCategory ?? $this->getConfig('selectedCategory');

		//Get the first possible value in case there's no selection
		if ($lx_selectedCategory === null) {
			$la_validValues = $this->getValidSelectionValues();
			$lx_selectedCategory = current($la_validValues);
		}

		if (!$lx_selectedCategory || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return [];
		}

		$ls_column = $this->getConfig('fieldname') ?: $this->getConfig('identifier');

		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $this->getConfig('associationName');
			$lo_association = $this->table()->getAssociation($ls_associationName);

			if ($lo_association instanceof HasMany || $lo_association instanceof BelongsToMany) {
				return [];
			}

			$ls_column = $lo_association->getForeignKey();
			if (is_array($ls_column)) {
				$ls_column = reset($ls_column);
			}
		}

		$lb_isAttribute = $this->fieldIsAttribute();
		if ($lb_isAttribute) {
			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = $this->table();
			$ls_column = $lo_table->getAttributesTable(true) . '.' . $ls_column;
		}

		if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
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
	 * This method groups the result of the provided query by the column provided via `$as_column`.
	 * It returns the query with an attached formatResults callback, that groups the resultset by the given column
	 *
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param string|null $as_column
	 * @param string|null $as_associationName
	 * @param bool $ab_sortByAssociation
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function groupResult(SelectQuery $ao_query, ?string $as_column = null, ?string $as_associationName = null, bool $ab_sortByAssociation = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}

		$ls_column = $as_column;
		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
			if (!$ls_associationName) {
				throw new RuntimeException(
					sprintf(
						'Cannot filter query without an association in `%s` for table `%s`.',
						static::class,
						$this->table()->getAlias()
					)
				);
			}

			$lo_association = $ao_query->getRepository()->getAssociation($ls_associationName);

			if (empty($ls_column)) {
				$ls_column = $lo_association->getForeignKey();
				if (is_array($ls_column)) {
					$ls_column = reset($ls_column);
				}
			}

			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $ao_query->getRepository()->getEntityClass();
		}
		else {
			$ls_entityClass = $this->table()->getEntityClass();

			if (empty($ls_column)) {
				$ls_column = $this->getConfig('identifier');
			}
		}

		$ls_column = $ls_entityClass::mapField($ls_column);

		if ($ab_sortByAssociation) {
			$this->sortQuery($ao_query, $as_column, $as_associationName);
		}


		return $ao_query->formatResults(function (CollectionInterface $ao_collection) use ($ls_column) {
			return $ao_collection->groupBy($ls_column);
		});
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param string|null $as_column
	 * @param string|null $as_associationName
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function sortQuery(SelectQuery $ao_query, ?string $as_column = null, ?string $as_associationName = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}

		$la_categories = $this->getConfig('categories');
		if (empty($la_categories['raw']) && empty($la_categories['simple'])) {
			return $ao_query;
		}

		$ls_column = $as_column;
		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
			if (!$ls_associationName) {
				throw new RuntimeException(
					sprintf(
						'Cannot filter query without an association in `%s` for table `%s`.',
						static::class,
						$this->table()->getAlias()
					)
				);
			}

			$lo_association = $ao_query->getRepository()->getAssociation($ls_associationName);

			if (empty($ls_column)) {
				$ls_column = $lo_association->getForeignKey();
				if (is_array($ls_column)) {
					$ls_column = reset($ls_column);
				}
			}

			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_association->getSource()->getEntityClass();

			if (!empty($la_categories['raw'])) {
				$ls_bindingKey = $lo_association->getBindingKey();
				if (is_array($ls_bindingKey)) {
					$ls_bindingKey = reset($ls_bindingKey);
				}

				$la_categoryIdentifiers = $la_categories['raw']->extract($ls_bindingKey)->toArray();
			}
			else {
				$la_categoryIdentifiers = array_keys($la_categories['simple']);
			}
		}
		else {
			$ls_entityClass = $this->table()->getEntityClass();

			if (empty($ls_column)) {
				$ls_column = $this->getConfig('identifier');
			}

			$la_categoryIdentifiers = array_keys($la_categories['simple']);

			dd(2, __FILE__, __LINE__);
		}

		$ls_column = $ls_entityClass::unmapField($ls_column);

		//Remember existing orders
		$lo_order = $ao_query->clause('order');

		$ls_prefixedColumn = $ao_query->getRepository()->getAlias() . '.' . $ls_column;

		/** @noinspection PhpUndefinedMethodInspection */
		$ao_query->orderByAsc($ao_query->newExpr($ao_query->func()->FIND_IN_SET([
			$ls_prefixedColumn => 'identifier',
			implode(',', $la_categoryIdentifiers),
		])), true);

		/*
		 * Set the order by-clause but reset existing order-clauses, so records will be sorted
		 * by the system_order of category first and then in the desired order.
		 */
		if (!empty($lo_order)) {
			dd($lo_order, __FILE__, __LINE__);
			//Re-add remembered orders
			/** @noinspection PhpUnreachableStatementInspection */
			$lo_order->traverse(function ($ao_clause) use ($ao_query): void {
				$ao_query->orderBy($ao_clause);
			});
		}


		return $ao_query;
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
	 * @param mixed|null $ax_categoryId
	 * @param array|null $aa_validSelectionValues
	 * @return mixed
	 */
	public function verifySelection(mixed $ax_categoryId = null, ?array $aa_validSelectionValues = null): mixed {
		$lx_categoryId = $ax_categoryId ?: $this->getConfig('selectedCategory');

		if (in_array($lx_categoryId, $aa_validSelectionValues ?? $this->getValidSelectionValues())) {
			return $lx_categoryId;
		}


		return false;
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

		$lo_categories = $lo_query->all();

		if ($this->getConfig('threaded')) {
			//Create a nested list of all categories
			/**
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
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules
	 * @return \Awyiss\ORM\RulesChecker
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(Event $ao_event, RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		if (!$this->getConfig('enabled') || !$this->getConfig('buildRules')) {
			return $ao_rules;
		}

		$ls_fieldName = Inflector::camelize($this->getConfig('fieldname'));
		$ls_ruleName = 'valid' . $ls_fieldName;

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		$ao_rules->add(function (EntityInterface $ao_entity, array $aa_options) use ($ao_rules, $lo_table): bool {
			$la_categories = $this->getCategories();
			$ls_field = $this->getConfig('useDatasource') ? $this->getConfig('fieldname') : $this->getConfig('identifier');

			$lx_value = $ao_entity->get($ls_field);

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


			return array_key_exists($lx_value, $la_categories);
		}, $ls_ruleName, [
			'errorField' => Inflector::underscore($ls_fieldName),
			'message' => __dfx(
				$lo_table->getI18nDomain(),
				'validation',
				$lo_table->getI18nDomain(),
				'error_valid_' . Inflector::underscore($ls_fieldName)
			),
		]);


		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 * @param \Cake\ORM\Marshaller $ao_marshaller The marhshaller of the table the behavior is attached to.
	 * @param array $aa_map The property map being built.
	 * @param array<string, mixed> $aa_options The options array used in the marshalling call.
	 * @return array A map of `[property => callable]` of additional properties to marshal.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildMarshalMap(Marshaller $ao_marshaller, array $aa_map, array $aa_options): array {
		if ($this->fieldIsAttribute()) {
			$ls_column = $this->getConfig('fieldname') ?: $this->getConfig('identifier');

			return [
				$ls_column => function (mixed $ax_value, EntityInterface $ao_entity) use ($ls_column): mixed {
					$lo_attributes = $ao_entity->get('attributes');

					$lo_attributes->set($ls_column, $ax_value);

					return $ax_value;
				},
			];
		}

		return [];
	}
}
