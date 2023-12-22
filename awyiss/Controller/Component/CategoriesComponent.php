<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Routing\Router;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * This component provides and handles category-specific logic.
 *
 * It sets view vars if they don't already exist,
 * offers a convenient `getCategories()` method to retreive all categories for a given entity,
 * and with `filterQuery()`, `groupQuery()` and `ensurePossibleCategorySelection()` methods to modify queries and settings.
 *
 * Categories are "parent" associations, like pages for contents, usergroups for users or
 * even pages for other pages like newscategories for news.
 *
 * @method \Awyiss\Controller\AppController getController()
 */
class CategoriesComponent extends Component {
	/**
	 * @inheritDoc
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'aggregationKey' => 'all',
		'allowAggregation' => true,
		'allowUnassigned' => false,
		'associationName' => null,
		'bindingKey' => 'id',
		'combinator' => [
			'id',
			'label',
			null,
		],
		'defaultVal' => null,
		'enabled' => null,
		'finder' => null,
		'foreignKey' => null,
		'identifier' => 'category',
		'queryConditions' => [],
		'queryOptions' => [],
		'redirectOnInvalidSelection' => true,
		'selectedCategory' => null,
		'tableName' => null,
		'threaded' => false,
		'unassignedKey' => 'unassigned',
		'uriParam' => null,
		'useDatasource' => true,
		'verifySelection' => true,
	];
	/**
	 * Used in `_startup()` to track if the startup logic has been executed,
	 * since this protected method is used in other methods.
	 *
	 * @var bool
	 */
	protected bool $started = false;


	/**
	 * When calling this method, the whole componend will be enabled.
	 *
	 * @return void
	 */
	public function enable(): void {
		$this->setConfig('enabled', true);
	}


	/**
	 * When calling this method, the whole componend will be disabled.
	 *
	 * @return void
	 */
	public function disable(): void {
		$this->setConfig('enabled', false);
	}


	/**
	 * @inheritDoc
	 * @param array $aa_config
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
		if (!$this->getConfig('identifier')) {
			throw new RuntimeException(sprintf('`%s` is missing the identifier attribute.', static::class));
		}

		if (!$this->getConfig('uriParam')) {
			$ls_identifier = $this->getConfig('identifier');
			$this->setConfig('uriParam', Inflector::dasherize($ls_identifier));
		}

		if (!$this->getConfig('useDatasource')) {
			$la_categories = $this->getConfig('categories');
			if (!$la_categories || !is_array($la_categories)) {
				throw new RuntimeException(sprintf('You need to provide categories when using `useDatasource = false` in `%s`.', static::class));
			}
		}
	}


	/**
	 * Sets view vars before rendering a view, depending on the name set in the config
	 *
	 * For `usergroups` as categories for users, the set view vars are
	 *
	 * - `aa_usergroups`, containing an array with all usergroups
	 *
	 * - `ax_selectedUsergroup`, containing the value (id, most of the time) of the currently selected category/usergroup
	 *
	 * @return void
	 */
	public function beforeRender(): void {
		$this->_startup();

		$ls_action = $this->getController()->getRequest()->getParam('action');
		if (in_array($ls_action, ['login', 'logout'])) {
			return;
		}

		$lo_view = $this->getController()->viewBuilder();

		$ls_identifier = Inflector::underscore($this->getConfig('identifier'));
		$ls_variableNameSingular = Inflector::variable($ls_identifier);
		$ls_variableNamePlural = Inflector::variable(Inflector::pluralize($ls_identifier));

		if (!$lo_view->getVar('aa_' . $ls_variableNamePlural)) {
			$lo_view->setVar('aa_' . $ls_variableNamePlural, $this->getConfig('categories'));
		}

		if (!$lo_view->getVar('ax_selected' . ucfirst($ls_variableNameSingular))) {
			$lo_view->setVar('ax_selected' . ucfirst($ls_variableNameSingular), $this->getConfig('selectedCategory'));
		}
	}


	/**
	 * This method makes sure that the value of the given property, provided via `$as_property`, in `$ao_entity`
	 * holds a valid category identifier.
	 *
	 * @param EntityInterface $ao_entity
	 * @param ResultSetInterface|null $ao_records
	 * @param string|null $as_property
	 * @return void
	 */
	public function ensurePossibleCategorySelection(
		EntityInterface $ao_entity,
		?ResultSetInterface $ao_records = null,
		?string $as_property = null
	): void {
		$this->_startup();

		$lo_records = $ao_records ?? $this->getConfig('categories.raw', $this->getCategories());
		if (!$lo_records) {
			throw new RuntimeException(
				sprintf(
					'Method `ensurePossibleCategory` in `%s` requires a valid set of records to ensure a possible category selection',
					static::class
				)
			);
		}

		$ls_property = $as_property ?? $this->getConfig('foreignKey');
		if (!$ls_property) {
			throw new RuntimeException(
				sprintf(
					'Method `ensurePossibleCategory` in `%s` requires a valid column to ensure a possible category selection',
					static::class
				)
			);
		}

		$ls_bindingKey = $this->getConfig('bindingKey', 'id');

		if (empty($ao_entity->$ls_property) || !$lo_records->firstMatch([$ls_bindingKey => $ao_entity->$ls_property])) {
			$la_errors = $ao_entity->getError($ls_property);
			$ao_entity->$ls_property = $lo_records->first()->$ls_bindingKey;
			$ao_entity->setError($ls_property, $la_errors);
		}
	}


	/**
	 * Add category-related conditions to a given query
	 *
	 * @param SelectQuery $ao_query
	 * @param mixed $ax_selectedCategory
	 * @param string|null $as_column
	 * @return SelectQuery
	 */
	public function filterQuery(SelectQuery $ao_query, mixed $ax_selectedCategory = null): SelectQuery {
		$this->_startup();

		$lx_selectedCategory = $ax_selectedCategory ?? $this->getConfig('selectedCategory');
		//When category equals the aggregationKey, e.g. "all", do not add query conditions
		if ($lx_selectedCategory === null || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return $ao_query;
		}

		//If we shall not use the datasource, apply the query conditions and return
		if (!$this->getConfig('useDatasource')) {
			$ao_query->where($this->getQueryConditions($lx_selectedCategory));


			return $ao_query;
		}

		$ls_associationName = $this->getConfig('associationName');
		if (!$ls_associationName) {
			throw new RuntimeException(sprintf('Cannot filter query without an association in `%s`.', static::class));
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

				//Skip the Acces check since we allow filtering by categories even without read or write access for those categories
				$ao_query->applyOptions($this->getConfig('queryOptions'));
				//dd([$lo_junction->getAlias() . '.' . $ls_column => $lx_selectedCategory]);
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
	 * Loads and returns all category-associations, customizable with config settings:
	 * - `finder`
	 * - `queryConditions`
	 * - `queryOptions`
	 *
	 * @param string|null $as_tableName
	 * @param string|null $as_associationName
	 * @return ResultSetInterface|null
	 */
	public function getCategories(?string $as_tableName = null, ?string $as_associationName = null): ?ResultSetInterface {
		if (!$this->getConfig('useDatasource')) {
			throw new RuntimeException(
				sprintf(
					'Cannot retreive categories when using `useDatasource = false` in `%s`.',
					static::class
				)
			);
		}

		$ls_tableName = $as_tableName ?? $this->getConfig('tableName', $this->getController()->getName());

		//No table? Do nothing.
		if (!$ls_tableName || !$this->getController()->{$ls_tableName}) {
			return null;
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->getController()->{$ls_tableName};

		//No matching association? Do nothing.
		$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
		if (!$ls_associationName || !$lo_table->hasAssociation($ls_associationName)) {
			return null;
		}

		$lo_association = $lo_table->getAssociation($ls_associationName);

		if (!$this->getConfig('foreignKey')) {
			$this->setConfig('foreignKey', $lo_association->getForeignKey());
		}

		$lo_query = $lo_association->find($this->getConfig('finder'))->where($this->getConfig('queryConditions'))->applyOptions($this->getConfig('queryOptions'));

		if ($this->getConfig('threaded')) {
			$lo_query->find('threaded');
		}


		return $lo_query->all();
	}


	/**
	 * @return mixed
	 * @noinspection PhpUnused
	 */
	public function getIdentifier(): mixed {
		return $this->getConfig('identifier');
	}


	/**
	 * @param mixed|null $ax_selectedCategory
	 * @return array
	 */
	public function getQueryConditions(mixed $ax_selectedCategory = null): array {
		$this->_startup();

		$lx_selectedCategory = $ax_selectedCategory ?? $this->getConfig('selectedCategory');
		if ($lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return [];
		}

		$ls_column = $this->getConfig('foreignKey') ?: $this->getConfig('identifier');

		$ls_tableName = $as_tableName ?? $this->getConfig('tableName', $this->getController()->getName());
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->getController()->{$ls_tableName};

		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $this->getConfig('associationName');
			$lo_association = $lo_table->getAssociation($ls_associationName);

			if ($lo_association instanceof HasMany || $lo_association instanceof BelongsToMany) {
				return [];
			}

			$ls_column = $lo_association->getForeignKey();
			if (is_array($ls_column)) {
				$ls_column = reset($ls_column);
			}
			//$ls_column = $ls_associationName . '.' . $ls_column;
		}

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();
		$ls_column = $ls_entityClass::unmapField($ls_column);

		if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
			$ls_column .= ' IS';
			$lx_selectedCategory = null;
		}


		return [
			$ls_column => $lx_selectedCategory,
		];
	}


	/**
	 * @return mixed
	 * @noinspection PhpUnused
	 */
	public function getSelectedCategory(): mixed {
		//Add the selected category identifier to the config
		if (!$this->getConfig('selectedCategory')) {
			$this->_startup();
		}


		return $this->getConfig('selectedCategory');
	}


	/**
	 * This method groups the result of the provided query by the column provided via `$as_column`.
	 * It returns the query with an attached formatResults callback, that groups the resultset by the given column
	 *
	 * @param SelectQuery $ao_query
	 * @param string|null $as_column
	 * @param string|null $as_associationName
	 * @param bool $ab_sortByAssociation
	 * @return SelectQuery
	 */
	public function groupResult(SelectQuery $ao_query, ?string $as_column = null, ?string $as_associationName = null, bool $ab_sortByAssociation = true): SelectQuery {
		$this->_startup();

		$ls_column = $as_column;
		if ($this->getConfig('useDatasource')) {
			$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
			if (!$ls_associationName) {
				throw new RuntimeException(
					sprintf(
						'Cannot filter query without an association in `%s`.',
						static::class
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
			$ls_tableName = $as_tableName ?? $this->getConfig('tableName', $this->getController()->getName());
			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = $this->getController()->{$ls_tableName};
			$ls_entityClass = $lo_table->getEntityClass();

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
		/*return $ao_query->mapReduce(function(Entity $ao_entity, int $li_key, MapReduce $ao_mapReduce) use ($ls_column) {
			$ao_mapReduce->emitIntermediate($ao_entity, $ao_entity->$ls_column);
		}, function($aa_entities, $ax_column, MapReduce $ao_mapReduce) {
			$ao_mapReduce->emit($aa_entities, $ax_column);
		}, true);*/
	}


	/**
	 * @param SelectQuery $ao_query
	 * @param string|null $as_column
	 * @param string|null $as_associationName
	 * @return SelectQuery
	 */
	public function sortQuery(SelectQuery $ao_query, ?string $as_column = null, ?string $as_associationName = null): SelectQuery {
		$this->_startup();

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
						'Cannot filter query without an association in `%s`.',
						static::class
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
			$ls_tableName = $as_tableName ?? $this->getConfig('tableName', $this->getController()->getName());
			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = $this->getController()->{$ls_tableName};
			$ls_entityClass = $lo_table->getEntityClass();

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
	 * Verify the selected category identifier.
	 * If that fails, set it to the first available or initiate a redirect if
	 * `redirectOnInvalidSelection` is set to true
	 *
	 * @param mixed $ax_categoryId
	 * @param array $aa_categories
	 * @param string $as_uriKey
	 * @return mixed
	 */
	public function verifySelection(mixed $ax_categoryId = null, ?array $aa_categories = null, ?bool $ab_redirect = null): mixed {
		$lx_categoryId = $ax_categoryId ?: $this->getSelectedCategory();
		$la_categories = $aa_categories ?? $this->getConfig('categories.simple');

		if (!array_key_exists($lx_categoryId, $la_categories)) {
			$la_additionalAllowedValues = [];
			if ($this->getConfig('allowAggregation')) {
				$la_additionalAllowedValues[] = $this->getConfig('aggregationKey');
			}
			if ($this->getConfig('allowUnassigned')) {
				$la_additionalAllowedValues[] = $this->getConfig('unassignedKey');
			}

			if (!in_array($lx_categoryId, $la_additionalAllowedValues)) {
				$lx_categoryId = $this->getConfig('defaultVal');
				if ($lx_categoryId === null) {
					if ($la_additionalAllowedValues) {
						$lx_categoryId = reset($la_additionalAllowedValues);
					}
					else {
						$lx_categoryId = array_key_first($la_categories);
					}

					if ($ab_redirect === true || ($this->getConfig('redirectOnInvalidSelection') && $ab_redirect !== false)) {
						throw new RedirectException(Router::url(['action' => 'overview', $this->getConfig('uriParam') => $lx_categoryId], true), 302);
					}
				}
			}
		}


		return $lx_categoryId;
	}


	/**
	 * Internal function to retreive categories and add them to the configuration
	 */
	protected function _startup(): void {
		if ($this->started) {
			return;
		}

		$ls_identifier = $this->getConfig('identifier');
		$ls_tableName = $this->getConfig('tableName');
		if ($ls_tableName === null) {
			//No table name set? Use the one set in the controller
			$ls_tableName = $this->getController()->getName();
			$this->setConfig('tableName', $ls_tableName);
		}

		$lo_request = $this->getController()->getRequest();
		$lo_session = $lo_request->getSession();

		$ls_bindingKey = $this->getConfig('bindingKey', 'id');
		//Remember an identifier that will be used to save the selected category in the session
		$ls_sessionIdentifier = implode('.', [
			'categories',
			($lo_request->getParam('lang') ?? 'global'),
			Inflector::underscore($ls_tableName),
			Inflector::underscore($ls_identifier),
		]);
		if (!str_ends_with($ls_sessionIdentifier, '_' . $ls_bindingKey)) {
			$ls_sessionIdentifier .= '_' . $ls_bindingKey;
		}

		//Get categories and fall back to an empty resultset
		$la_categories = $this->_buildCategories($ls_tableName, $ls_bindingKey);

		//Is there a request parameter with the identifier
		$lx_categoryId = $lo_request->getParam(Inflector::variable($this->getConfig('uriParam')));
		if ($lx_categoryId) {
			if ($lo_session->started()) {
				//Session started? Save the category identifier that's inside the url parameter in the session
				$lo_session->write($ls_sessionIdentifier, $lx_categoryId);
			}
		}
		//Session not started OR there's no category identifier saved in the session
		else {
			$lx_categoryId = $lo_session->started() ? $lo_session->read($ls_sessionIdentifier) : null;

			if (!$lo_session->started() || !$lx_categoryId) {
				//Set the category identifier to the default value from the config
				$lx_categoryId = $this->getConfig('defaultVal');

				if ($lx_categoryId === null) {
					/*
					 * If the default value is empty, set the category identifier to either
					 * 	- the aggretationKey, if aggregation is allowed
					 * OR
					 * 	- the unassignedKey, if filtering unassigned is allowed
					 * OR
					 * 	- the first key (identifier) of the available categories
					 */
					if ($this->getConfig('allowAggregation')) {
						$lx_categoryId = $this->getConfig('aggregationKey');
					}
					elseif ($this->getConfig('allowUnassigned')) {
						$lx_categoryId = $this->getConfig('unassignedKey');
					}
					else {
						$lx_categoryId = array_key_first($la_categories);
					}
				}

				if ($lo_session->started()) {
					//Session started? Save the category identifier in the session
					$lo_session->write($ls_sessionIdentifier, $lx_categoryId);
				}
			}
		}

		if ($this->getConfig('verifySelection')) {
			$lx_categoryId = $this->verifySelection($lx_categoryId, $la_categories);
		}

		//Add the selected category identifier to the config
		$this->setConfig('selectedCategory', $lx_categoryId);

		//Add the whole config of this component as an item inside the 'categorization' attribute of the request
		$la_categorization = $lo_request->getAttribute('categorization', []);
		if (!is_array($la_categorization)) {
			$la_categorization = [];
		}
		$la_categorization[ $ls_identifier ] = $this->getConfig();

		$lo_request = $lo_request->withAttribute('categorization', $la_categorization);
		//The request object is immutable, so we need to add the new one created by "withAttribute" to the controller
		$this->getController()->setRequest($lo_request);

		$this->started = true;
	}


	/**
	 * @param mixed $as_tableName
	 * @param mixed $as_bindingKey
	 * @return array
	 */
	protected function _buildCategories(mixed $as_tableName, mixed $as_bindingKey): array {
		if (!$this->getConfig('useDatasource')) {
			$la_categories = $this->getConfig('categories');

			$this->setConfig('categories', [
				'raw' => $la_categories['raw'] ?? null,
				'simple' => $la_categories['simple'] ?? $la_categories,
			], false);


			return $la_categories['simple'] ?? $la_categories;
		}


		$lo_categories = $this->getCategories($as_tableName) ?? new ResultSetDecorator([]);

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
			$la_categories = $lo_categories->printer(...$this->getConfig('threaded.printer', ['label', $as_bindingKey, '– ']))->toArray();
		}
		else {
			//Create an array, based on a combinator set in the config. Default is [id => label]
			$la_categories = $lo_categories->combine(...$this->getConfig('combinator'))->toArray();
		}

		//Add the collection of raw category entities to the config
		$this->setConfig('categories.raw', $lo_categories);
		//Add the array of categories to the config
		$this->setConfig('categories.simple', $la_categories);


		return $la_categories;
	}
}
