<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query;
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
 */
class CategoriesComponent extends Component {
	/**
	 * @inheritDoc
	 *
	 * @var array<string, mixed>
	 */
	protected $_defaultConfig = [
		'aggregationKey' => 'all',
		'allowAggregation' => TRUE,
		'allowUnassigned' => FALSE,
		'associationName' => NULL,
		'bindingKey' => 'id',
		'combinator' => [
			'id',
			'label',
			NULL,
		],
		'defaultVal' => NULL,
		'enabled' => NULL,
		'finder' => NULL,
		'foreignKey' => NULL,
		'name' => 'category',
		'paginate' => TRUE,
		'queryConditions' => [],
		'queryOptions' => [
			'access' => ['skip' => TRUE],
		],
		'selectedCategory' => NULL,
		'tableName' => NULL,
		'threaded' => FALSE,
		'unassignedKey' => 'unassigned',
		'verifySelection' => TRUE,
	];
	/**
	 * Used in `_startup()` to track if the startup logic has been executed,
	 * since this protected method is used in other methods.
	 *
	 * @var bool
	 */
	protected bool $started = FALSE;


	/**
	 * When calling this method, the whole componend will be enabled.
	 *
	 * @return void
	 */
	public function enable (): void {
		$this->setConfig('enabled', TRUE);
	}


	/**
	 * When calling this method, the whole componend will be disabled.
	 *
	 * @return void
	 */
	public function disable (): void {
		$this->setConfig('enabled', FALSE);
	}


	/**
	 * Called after Controller::beforeFilter() method, and before the controller action is called.
	 *
	 * @return void
	 */
	public function startup (): void {
		if ( ! $this->getConfig('name')) {
			throw new RuntimeException(sprintf('`%s` is missing the name attribute.', static::class));
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
	public function beforeRender (): void {
		$this->_startup();

		$lo_view = $this->getController()->viewBuilder();

		$ls_dashedName = Inflector::dasherize($this->getConfig('name'));
		$ls_variableNameSingular = Inflector::variable($ls_dashedName);
		$ls_variableNamePlural = Inflector::variable(Inflector::pluralize($ls_dashedName));

		if ( ! $lo_view->getVar('aa_' . $ls_variableNamePlural)) {
			$lo_view->setVar('aa_' . $ls_variableNamePlural, $this->getConfig('categories'));
		}

		if ( ! $lo_view->getVar('ax_selected' . ucfirst($ls_variableNameSingular))) {
			$lo_view->setVar('ax_selected' . ucfirst($ls_variableNameSingular), $this->getConfig('selectedCategory'));
		}
	}


	/**
	 * Loads and returns all category-associations, customizable with config settings:
	 * - `finder`
	 *
	 * - `queryConditions`
	 *
	 * - `queryOptions`
	 *
	 * @param NULL|string $as_tableName
	 * @param NULL|string $as_associationName
	 *
	 * @return NULL|\Cake\Datasource\ResultSetInterface
	 */
	public function getCategories (?string $as_tableName = NULL, ?string $as_associationName = NULL): ?ResultSetInterface {
		$ls_tableName = $as_tableName ?? $this->getConfig('tableName', $this->getController()->getName());
		if ($ls_tableName === NULL) {
			$ls_tableName = $this->getController()->getName();
		}

		//No table? Do nothing.
		if ( ! $ls_tableName || ! ($lo_table = $this->getController()->{$ls_tableName})) {
			return NULL;
		}

		//No matching association? Do nothing.
		$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
		if ( ! $ls_associationName || ( ! $lo_association = $lo_table->{$ls_associationName})) {
			return NULL;
		}

		if ( ! $this->getConfig('foreignKey')) {
			$this->setConfig('foreignKey', $lo_association->getForeignKey());
		}

		$lo_query = $lo_association->find($this->getConfig('finder'))
			->where($this->getConfig('queryConditions'))
			->applyOptions($this->getConfig('queryOptions'));

		if ($this->getConfig('threaded')) {
			$lo_query->find('threaded');
		}

		return $lo_query->all();
	}


	/**
	 * Add category-related conditions to a given query
	 *
	 * @param \Cake\ORM\Query $ao_query
	 * @param $ax_selectedCategory
	 * @param NULL|string $as_column
	 *
	 * @return \Cake\ORM\Query
	 */
	public function filterQuery (Query $ao_query, $ax_selectedCategory = NULL, ?string $as_column = NULL): Query {
		$this->_startup();

		$ls_associationName = $this->getConfig('associationName');
		if ( ! $ls_associationName) {
			return $ao_query;
		}

		$lo_association = $ao_query->getRepository()->getAssociation($ls_associationName);

		$ls_column = $as_column;
		if (empty($ls_column)) {
			$ls_column = $lo_association->getForeignKey();
		}

		if ($this->getConfig('categories.raw')->count() === 0) {
			$ao_query->where([$ls_column . ' IS' => NULL]);

			return $ao_query;
		}

		$lx_selectedCategory = $ax_selectedCategory ?? $this->getConfig('selectedCategory');
		//When no category is selected or when it equals the aggregationKey, e.g. "all", do not add query conditions
		if ($lx_selectedCategory === NULL || $lx_selectedCategory === $this->getConfig('aggregationKey')) {
			return $ao_query;
		}

		if ($lo_association instanceof HasMany
			|| $lo_association instanceof BelongsToMany) {

			if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
				/*
				 * Find records with no associated category when the selected category equals the config key "unassignedKey".
				 * This allows finding entities whose categories are missing or that never had a category
				 */
				$lo_junction = $lo_association->junction();
				$ls_column = $lo_junction->getPrimaryKey() . ' IS';
				$ao_query->leftJoinWith($ls_associationName)->where([$lo_junction->getAlias() . '.' . $ls_column => NULL]);
			}
			else {
				//Find records whose category matches the selectedCategory
				$ao_query->matching($ls_associationName, function(Query $ao_query) use ($lo_association, $lx_selectedCategory) {
					$lo_junction = $lo_association->junction();
					$ls_column = $lo_junction->associations()->get($lo_association->getName())->getForeignKey();

					//Skip the Acces check since we allow filtering by categories even without read or write access for those categories
					$ao_query->applyOptions($this->getConfig('queryOptions'));

					return $ao_query->where([$lo_junction->getAlias() . '.' . $ls_column => $lx_selectedCategory]);
				});
			}
		}
		else {
			//With a belongsTo-association, the categorization is realized by a simple "parent_id"-limitation
			$ao_query->where([$ls_column => $lx_selectedCategory]);
		}

		return $ao_query;
	}


	/**
	 * This method groups the result of the provided query by the column provided via `$as_column`.
	 *
	 * It returns either a Query or a Collection, depending on the `paginate` config setting.
	 *
	 * @param \Cake\ORM\Query $ao_query
	 * @param array $aa_associationOptions
	 * @param NULL|string $as_associationName
	 * @param NULL|string $as_column
	 *
	 * @return \Cake\ORM\Query|\Cake\Collection\CollectionInterface
	 */
	public function groupQuery (Query $ao_query, array $aa_associationOptions = [], ?string $as_associationName = NULL, ?string $as_column = NULL): Query|CollectionInterface {
		$this->_startup();

		$ls_column = $as_column;
		$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
		$la_associationOptions = $aa_associationOptions;

		//Get the column to group the query by, if omitted
		if (empty($ls_column) && !empty($ls_associationName) && ($lo_association = $ao_query->getRepository()->getAssociation($ls_associationName))) {
			$ls_column = $lo_association->getForeignKey();
			$lo_schema = $lo_association->getSchema();

			/*
			 * When there's a system_order column on the association table, sort the categories before grouping them.
			 * This should normally be part of the SystemOrderBehavior resp. Component but it's too tedious to move it there.
			 */
			if ($lo_schema->getColumn('system_order')) {
				if ($lo_association instanceof HasMany
				|| $lo_association instanceof BelongsToMany) {
					if ( ! array_key_exists('sort', $la_associationOptions)) {
						$la_associationOptions['sort'] = [];
					}

					//Add the sort key to the "contain" options, but don't overwrite existing sort options.
					$la_associationOptions['sort'] += [$lo_association->getAlias() . '.system_order' => 'ASC'];
				}
				//With a belongsTo-association, sorting can be part of the query
				else {
					//Remember existing orders
					$lo_order = $ao_query->clause('order');

					/*
					 * Set the order by but reset existing order clauses, so records will be sorted
					 * by the system_order of category first.
					 */
					$ao_query->orderAsc($lo_association->getAlias() . '.system_order', TRUE);

					if (!empty($lo_order)) {
						dd($lo_order, __FILE__, __LINE__);
						//Re-add remembered orders
						/** @noinspection PhpUnreachableStatementInspection */
						$lo_order->traverse(function($ao_clause) use ($ao_query) {
							$ao_query->order($ao_clause);
						});
					}
				}
			}
		}

		if ($ls_associationName) {
			//add a contain-statement for the association
			$ao_query->contain([$ls_associationName => $la_associationOptions]);
		}

		if ($this->getConfig('paginate')) {
			//If set, return a paginated resultset instead of a query
			$lo_result = $this->getController()->paginate($ao_query);
			return $lo_result->groupBy($ls_column);
		}

		/** @var CollectionInterface $ao_query */
		return $ao_query->groupBy($ls_column);
	}


	/**
	 * This method makes sure that the value of the given property, provided via `$as_property`, in `$ao_entity`
	 * holds a valid category identifier.
	 *
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param NULL|\Cake\Datasource\ResultSetInterface $ao_records
	 * @param string|NULL $as_property
	 *
	 * @return void
	 */
	public function ensurePossibleCategorySelection (EntityInterface $ao_entity, ?ResultSetInterface $ao_records = NULL, string $as_property = NULL): void {
		$this->_startup();

		$lo_records = $ao_records ?? $this->getConfig('categories.raw', $this->getCategories());
		if ( ! $lo_records) {
			throw new RuntimeException(sprintf('Method `ensurePossibleCategory` in `%s` Component requires a valid set of records to ensure a possible category selection', static::class));
		}

		$ls_property = $as_property ?? $this->getConfig('foreignKey');
		if ( ! $ls_property) {
			throw new RuntimeException(sprintf('Method `ensurePossibleCategory` in `%s` Component requires a valid column to ensure a possible category selection', static::class));
		}

		$ls_bindingKey = $this->getConfig('bindingKey', 'id');
		if (empty($ao_entity->$ls_property) || ! $lo_records->firstMatch([$ls_bindingKey => $ao_entity->$ls_property])) {
			$ao_entity->$ls_property = $lo_records->first()->$ls_bindingKey;
		}
	}


	/**
	 * Internal function to retreive categories and add them to the configuration
	 */
	protected function _startup (): void {
		if ($this->started) {
			return;
		}

		$ls_name = $this->getConfig('name');
		$ls_uriKey = Inflector::dasherize($ls_name);
		$ls_tableName = $this->getConfig('tableName');
		if ($ls_tableName === NULL) {
			//No table name set? Use the one set in the controller
			$ls_tableName = $this->getController()->getName();
			$this->setConfig('tableName', $ls_tableName);
		}

		$lo_request = $this->getController()->getRequest();
		$lo_session = $lo_request->getSession();

		$ls_bindingKey = $this->getConfig('bindingKey', 'id');
		//Remember an identifier that will be used to save the selected category in the session
		$ls_sessionIdentifier = 'categories.' . ($lo_request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($ls_tableName) . '.' . Inflector::underscore($ls_name . '_' . $ls_bindingKey);

		//Get categories and fall back to an empty resultset
		$la_categories = $this->_buildCategories($ls_tableName, $ls_bindingKey);

		//Is there a request parameter with the name
		if ($lx_category_id = $lo_request->getParam($ls_uriKey)) {
			if ($lo_session->started()) {
				//Session started? Save the category identifier that's inside the url parameter in the session
				$lo_session->write($ls_sessionIdentifier, $lx_category_id);
			}
		}
		//Session not started OR there's no category identifier saved in the session
		elseif ( ! $lo_session->started() || ! ($lx_category_id = $lo_session->read($ls_sessionIdentifier))) {
			//Set the category identifier to the default value from the config
			$lx_category_id = $this->getConfig('defaultVal');

			if ($lx_category_id === NULL) {
				/*
				 * If the default value is empty, set the category identifier to either
				 * 	- the aggretationKey, if aggregation is allowed
				 * OR
				 * 	- the first key (identifier) of the available categories
				 */
				$lx_category_id = $this->getConfig('allowAggregation') ? $this->getConfig('aggregationKey') : array_key_first($la_categories);
			}

			if ($lo_session->started()) {
				//Session started? Save the category identifier in the session
				$lo_session->write($ls_sessionIdentifier, $lx_category_id);
			}
		}

		//Must the selected category identifier be a valid one?
		if ($this->getConfig('verifySelection') && ! array_key_exists($lx_category_id, $la_categories)) {
			//The selected category does not exist as a key of all available categories

			/*
			 * If it's not allowed to filter items without an association OR
			 * if the selected category identifier is not the same as the 'unassignedKey' config value
			 * AND
			 * if the category identifier set as 'defaultVal' config value equals NULL,
			 * that means the selection is not a valid category.
			 *
			 * so set the category identifier to either
			 * 	- the aggretationKey, if aggregation is allowed
			 * OR
			 * - the first key (identifier) of the available categories
			 */
			if (( ! $this->getConfig('allowUnassigned') || $lx_category_id != $this->getConfig('unassignedKey')) && ($lx_category_id = $this->getConfig('defaultVal')) === NULL) {
				$lx_category_id = $this->getConfig('allowAggregation') ? $this->getConfig('aggregationKey') : array_key_first($la_categories);
			}

			if ($lo_session->started()) {
				//Session started? Save the category identifier in the session
				$lo_session->write($ls_sessionIdentifier, $lx_category_id);
			}

			//TODO think about redirecting to an url with a valid category
		}

		//Add the selected category identifier to the config
		$this->setConfig('selectedCategory', $lx_category_id);

		//Add the whole config of this component as an item inside the 'categorization' attribute of the request
		$la_categorization = $lo_request->getAttribute('categorization', []);
		if ( ! is_array($la_categorization)) {
			$la_categorization = [];
		}
		$la_categorization[ $ls_name ] = $this->getConfig();

		$lo_request = $lo_request->withAttribute('categorization', $la_categorization);
		//The request object is immutable, so we need to add the new one created by "withAttribute" to the controller
		$this->getController()->setRequest($lo_request);

		$this->started = TRUE;
	}


	/**
	 * @param mixed $ls_tableName
	 * @param mixed $ls_bindingKey
	 *
	 * @return array
	 */
	protected function _buildCategories (mixed $ls_tableName, mixed $ls_bindingKey): array {
		$lo_categories = $this->getCategories($ls_tableName) ?? new ResultSetDecorator([]);

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
			$la_categories = $lo_categories->printer(...($this->getConfig('threaded.printer', ['label', $ls_bindingKey, '– '])))->toArray();
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