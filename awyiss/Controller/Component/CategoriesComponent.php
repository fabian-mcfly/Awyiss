<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Model\Behavior\CategoriesBehavior;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * This component provides and handles category-specific logic.
 *
 * Categories are "parent" associations, like pages for contents, usergroups for users or
 * even pages for other pages like newscategories for news.
 *
 * @method \Awyiss\Controller\AppController getController()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class CategoriesComponent extends Component {
	protected array $_defaultConfig = [
		'redirectOnInvalidSelection' => true,
		'startupMethods' => ['overview'],
		'uriParam' => null,
		'verifySelection' => true,
	];
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $table;


	/**
	 * @inheritDoc
	 * @param array $aa_config
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function __construct(ComponentRegistry $ao_registry, array $aa_config) {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $ao_registry->getController()->fetchTable();

		parent::__construct($ao_registry, $aa_config);
	}


	/**
	 * @param array $aa_config
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		if (!$this->getConfig('uriParam')) {
			$ls_identifier = $this->getConfig('identifier');
			$this->setConfig('uriParam', Inflector::dasherize($ls_identifier));
		}
	}


	/**
	 * @return void
	 */
	public function enable(): void {
		$this->setConfig('enabled', true);
	}


	/**
	 * @return void
	 */
	public function disable(): void {
		$this->setConfig('enabled', false);
	}


	/**
	 * Proxy the config
	 *
	 * @param string|null $as_key
	 * @param mixed|null $ax_default
	 * @return mixed
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getConfig(?string $as_key = null, mixed $ax_default = null): mixed {
		if ($as_key === null) {
			return Hash::merge(parent::getConfig(), $this->table->getBehavior('Categories')->getConfig());
		}

		if (array_key_exists($as_key, $this->_defaultConfig)) {
			return parent::getConfig($as_key, $ax_default);
		}

		return $this->table->getBehavior('Categories')->getConfig($as_key, $ax_default);
	}


	/**
	 * Proxy the config
	 *
	 * @param array|string $ax_key
	 * @param mixed|null $ax_value
	 * @param bool $ab_merge
	 * @return \Awyiss\Controller\Component\CategoriesComponent
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function setConfig(array|string $ax_key, mixed $ax_value = null, bool $ab_merge = true): static {
		if (is_string($ax_key)) {
			if (array_key_exists($ax_key, $this->_defaultConfig)) {
				parent::setConfig($ax_key, $ax_value, $ab_merge);


				return $this;
			}
		}
		else {
			foreach ($ax_key as $ls_key => $lx_value) {
				if (array_key_exists($ls_key, $this->_defaultConfig)) {
					parent::setConfig($ls_key, $lx_value, $ab_merge);
					unset($ax_key[ $ls_key ]);
				}
			}
		}


		$this->table->getBehavior('Categories')->setConfig($ax_key, $ax_value, $ab_merge);


		return $this;
	}


	/**
	 * Autoloads the categories and sets
	 *
	 * @return void
	 */
	public function startup(): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$la_startupMethods = $this->getConfig('startupMethods');

		if ($la_startupMethods === null) {
			return;
		}

		$ls_identifier = $this->getConfig('identifier');
		$lo_request = $this->getController()->getRequest();
		$lo_session = $lo_request->getSession();

		$ls_bindingKey = $this->getConfig('bindingKey', 'id');

		//Remember an identifier that will be used to save the selected category in the session
		$la_identifierParts = ['categories'];

		$lo_schema = $this->table->getSchema();
		if ($lo_schema->hasColumn('language_shortcode') && $this->table->getAlias() !== 'Configuration') {
			$la_identifierParts[] = $lo_request->getParam('lang') ?? 'global';
		}

		$la_identifierParts = array_merge($la_identifierParts, [
			Inflector::underscore($this->table->getAlias()),
			Inflector::underscore($ls_identifier),
		]);

		$ls_sessionIdentifier = implode('.', $la_identifierParts);

		if (!str_ends_with($ls_sessionIdentifier, '_' . $ls_bindingKey) && $this->getConfig('useDatasource')) {
			$ls_sessionIdentifier .= '_' . $ls_bindingKey;
		}

		$ls_action = $this->getController()->getRequest()->getParam('action');


		if (in_array($ls_action, $la_startupMethods)) {
			$la_categories = $this->table->getCategories();

			//Is there a request parameter with the identifier
			$lx_categoryId = $lo_request->getParam(Inflector::variable($this->getConfig('uriParam')));
			if (is_numeric($lx_categoryId)) {
				$lx_categoryId = (int)$lx_categoryId;
			}

			if (!$lx_categoryId) {
				$lx_categoryId = $lo_session->started() ? $lo_session->read($ls_sessionIdentifier) : null;
				$lx_categoryId ??= $this->getConfig('selectedCategory');

				if (!$lx_categoryId) {
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
				}
			}

			if ($this->getConfig('verifySelection')) {
				if ($this->getConfig('allowUnassigned')) {
					$la_categories = [$this->getConfig('unassignedKey') => $this->getConfig('unassignedKey')] + $la_categories;
				}

				if ($this->getConfig('allowAggregation')) {
					$la_categories = [$this->getConfig('aggregationKey') => $this->getConfig('aggregationKey')] + $la_categories;
				}

				$lx_categoryId = $this->verifySelection($lx_categoryId, $la_categories);
			}

			//Save the category identifier in the session
			$lo_session->write($ls_sessionIdentifier, $lx_categoryId);
		}
		else {
			$lx_categoryId = $lo_session->read($ls_sessionIdentifier);
		}

		//Add the selected category identifier to the config
		$this->setConfig('selectedCategory', $lx_categoryId);
	}


	/**
	 * Sets view vars before rendering a view, depending on the name set in the config
	 *
	 * For `usergroups` as categories for users, the set view vars are
	 *
	 * - `usergroups`, containing an array with all usergroups
	 *
	 * - `selectedUsergroup`, containing the value (id, most of the time) of the currently selected category/usergroup
	 *
	 * @return void
	 */
	public function beforeRender(): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$lo_view = $this->getController()->viewBuilder();

		$ls_identifier = Inflector::underscore($this->getConfig('identifier'));
		/*if (str_ends_with($ls_identifier, '_id')) {
			$ls_identifier = substr($ls_identifier, 0, -3);
		}*/

		$ls_variableNamePlural = Inflector::variable(Inflector::pluralize($ls_identifier));

		$la_config = $this->getConfig();
		ksort($la_config);
		unset($la_config['implementedEvents'], $la_config['implementedMethods']);

		$lo_parentCategories = null;
		$lx_includeParents = $this->getConfig('includeParentCategories');
		if ($this->getConfig('includeParentCategories')) {
			$li_maxLevel = $lx_includeParents === true ? PHP_INT_MAX : (int)$lx_includeParents;
			$this->getBehavior()->assignParentCategories($li_maxLevel);
		}

		$lo_categories = $this->getCategories(true);

		$la_categories = [
			'config' => $la_config,
			'parents' => $lo_parentCategories,
			'raw' => $lo_categories,
			'selected' => $this->getConfig('selectedCategory'),
			'simple' => $this->getCategories(),
		];

		if (!$lo_view->getVar($ls_variableNamePlural)) {
			$lo_view->setVar($ls_variableNamePlural, $la_categories);
		}

		$lo_view->setVar('categoriesIdentifier', $la_config['identifier']);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @return void
	 */
	public function ensurePossibleCategory(EntityInterface $ao_entity): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$ls_fieldName = $this->getConfig('field');
		$lx_selectedCategory = $ao_entity->$ls_fieldName;

		$la_possibleCategories = array_keys($this->getCategories());

		if (!in_array($lx_selectedCategory, $la_possibleCategories, true)) {
			/** @var \Awyiss\Model\Entity $ao_entity */
			$lo_entity = $this->fieldIsAttribute() && $ao_entity->attributes ? $ao_entity->attributes : $ao_entity;

			$la_errors = $lo_entity->getError($ls_fieldName);

			$lo_entity->set($ls_fieldName, reset($la_possibleCategories));

			if ($la_errors) {
				$lo_entity->setError($ls_fieldName, $la_errors);
			}
		}

		$lo_request = $this->getController()->getRequest();
		$ls_fieldName = Inflector::underscore($ls_fieldName);
		//When the field is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData($ls_fieldName) !== null) {
			$lo_request = $lo_request->withData($ls_fieldName, $ao_entity->$ls_fieldName);
			$this->getController()->setRequest($lo_request);
		}
	}


	/**
	 * Returns whether the field is one of the attributes for the attached table
	 *
	 * @return bool
	 */
	public function fieldIsAttribute(): bool {
		return $this->getBehavior()->fieldIsAttribute();
	}


	/**
	 * Add category-related conditions to a given query
	 *
	 * @param SelectQuery $ao_query
	 * @param mixed $ax_selectedCategory
	 * @param string|null $as_column
	 * @return SelectQuery
	 */
	public function filterQuery(SelectQuery $ao_query, mixed $ax_selectedCategory = null, bool $ab_sortAggregation = true): SelectQuery {
		return $this->getBehavior()->filterQuery($ao_query, $ax_selectedCategory, $ab_sortAggregation);
	}


	/**
	 * @return \Awyiss\Model\Behavior\CategoriesBehavior
	 */
	protected function getBehavior(): CategoriesBehavior {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->table->getBehavior('Categories');
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


		return $this->getBehavior()->getCategories($ab_returnRaw);
	}


	/**
	 * @param mixed|null $ax_selectedCategory
	 * @return array
	 */
	public function getQueryConditions(mixed $ax_selectedCategory = null): array {
		return $this->getBehavior()->getQueryConditions($ax_selectedCategory);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|null $ao_entity
	 * @return mixed
	 */
	public function getSelectedCategory(?EntityInterface $ao_entity = null): mixed {
		return $this->getBehavior()->getSelectedCategory($ao_entity);
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
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}


		return $this->getBehavior()->groupResult($ao_query, $as_column, $as_associationName, $ab_sortByAssociation);
	}


	/**
	 * @param SelectQuery $ao_query
	 * @param string|null $as_column
	 * @param string|null $as_associationName
	 * @return SelectQuery
	 */
	public function sortQuery(SelectQuery $ao_query, ?string $as_column = null, ?string $as_associationName = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $ao_query;
		}


		return $this->getBehavior()->sortQuery($ao_query, $as_column, $as_associationName);
	}


	/**
	 * Verify the selected category.
	 * If that fails, set it to the first available or initiate a redirect if either
	 * `$ab_redirect` or `redirectOnInvalidSelection` is true
	 *
	 * @param mixed $ax_categoryId
	 * @param array|null $aa_categories
	 * @param bool|null $ab_redirect
	 * @return mixed
	 */
	public function verifySelection(mixed $ax_categoryId = null, ?array $aa_categories = null, ?bool $ab_redirect = null): mixed {
		if (!$this->getConfig('enabled')) {
			return null;
		}

		$la_categories = $aa_categories ? array_keys($aa_categories) : $this->getBehavior()->getValidSelectionValues();
		$lx_verifiedSelection = $this->getBehavior()->verifySelection($ax_categoryId, $la_categories);

		if (
			$lx_verifiedSelection === false &&
			(
				$ab_redirect === true ||
				($this->getConfig('redirectOnInvalidSelection') && $ab_redirect !== false)
			)
		) {
			throw new RedirectException(Router::url([
				'action' => 'overview',
				$this->getConfig('uriParam') => current($la_categories),
				'_name' => $this->getController()->getRequest()->getParam('_name'),
			], true), 302);
		}


		return $lx_verifiedSelection;
	}
}
