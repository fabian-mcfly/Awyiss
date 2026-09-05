<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Model\Behavior\CategoriesBehavior;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use BackedEnum;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\Utility\Hash;


/**
 * This component provides and handles category-specific logic.
 *
 * Categories are "parent" associations, like pages for contents, usergroups for users or
 * even pages for other pages like "Newscategories" for News.
 *
 * @method \Awyiss\Controller\AppController getController()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class CategoriesComponent extends Component {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
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
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$this->table = $registry->getController()->fetchTable();

		parent::__construct($registry, $config);
	}


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if (!$this->getConfig('uriParam')) {
			$identifier = $this->getConfig('identifier');
			$this->setConfig('uriParam', Inflector::dasherize($identifier ?? 'category'));
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
	 * @param string|null $key
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function getConfig(?string $key = null, mixed $default = null): mixed {
		if ($key === null) {
			return Hash::merge(parent::getConfig(), $this->table->getBehavior('Categories')->getConfig());
		}

		if (array_key_exists($key, $this->_defaultConfig)) {
			return parent::getConfig($key, $default);
		}

		if ($this->table->hasBehavior('Categories')) {
			return $this->table->getBehavior('Categories')->getConfig($key, $default);
		}

		return $default;
	}


	/**
	 * Proxy the config
	 *
	 * @param array|string $key
	 * @param mixed|null $value
	 * @param bool $merge
	 * @return \Awyiss\Controller\Component\CategoriesComponent
	 */
	public function setConfig(array|string $key, mixed $value = null, bool $merge = true): static {
		if (is_string($key)) {
			if (array_key_exists($key, $this->_defaultConfig)) {
				parent::setConfig($key, $value, $merge);


				return $this;
			}
		}
		else {
			foreach ($key as $itemKey => $itemValue) {
				if (array_key_exists($itemKey, $this->_defaultConfig)) {
					parent::setConfig($itemKey, $itemValue, $merge);
					unset($key[ $itemKey ]);
				}
			}
		}

		if ($this->table->hasBehavior('Categories')) {
			$this->table->getBehavior('Categories')->setConfig($key, $value, $merge);
		}

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

		$startupMethods = $this->getConfig('startupMethods');

		if ($startupMethods === null) {
			return;
		}

		$identifier = $this->getConfig('identifier');
		$request = $this->getController()->getRequest();
		$session = $request->getSession();

		$bindingKey = $this->getConfig('bindingKey', 'id');

		// Remember an identifier that will be used to save the selected category in the session
		$identifierParts = ['categories'];

		$schema = $this->table->getSchema();
		if ($schema->hasColumn('languageShortcode') && $this->table->getAlias() !== 'Configuration') {
			$identifierParts[] = $request->getParam('lang') ?? 'global';
		}

		$identifierParts = array_merge($identifierParts, [
			Inflector::underscore($this->table->getAlias()),
			Inflector::underscore($identifier),
		]);

		$sessionIdentifier = implode('.', $identifierParts);
		if (!str_ends_with($sessionIdentifier, '_' . $bindingKey) && $this->getConfig('useDatasource')) {
			$sessionIdentifier .= '_' . $bindingKey;
		}

		$action = $this
			->getController()
			->getRequest()
			->getParam('action')
		;
		if (in_array($action, $startupMethods)) {
			$categories = $this->table->getCategories();

			//Is there a request parameter with the identifier
			$categoryId = $request->getParam(Inflector::variable($this->getConfig('uriParam')));
			if (is_numeric($categoryId)) {
				$categoryId = (int)$categoryId;
			}

			if (!$categoryId) {
				$categoryId = $session->started() ? $session->read($sessionIdentifier) : null;
				$categoryId ??= $this->getConfig('selectedCategory');

				if (!$categoryId) {
					//Set the category identifier to the default value from the config
					$categoryId = $this->getConfig('defaultVal');

					if ($categoryId === null) {
						/*
						 * If the default value is empty, set the category identifier to either
						 * 	- the aggregationKey, if aggregation is allowed
						 * OR
						 * 	- the unassignedKey, if filtering unassigned is allowed
						 * OR
						 * 	- the first key (identifier) of the available categories
						 */
						if ($this->getConfig('allowAggregation')) {
							$categoryId = $this->getConfig('aggregationKey');
						}
						elseif ($this->getConfig('allowUnassigned')) {
							$categoryId = $this->getConfig('unassignedKey');
						}
						else {
							$categoryId = array_key_first($categories);
						}
					}
				}
			}

			if ($this->getConfig('verifySelection')) {
				if ($this->getConfig('allowUnassigned')) {
					$categories = [$this->getConfig('unassignedKey') => $this->getConfig('unassignedKey')] + $categories;
				}

				if ($this->getConfig('allowAggregation')) {
					$categories = [$this->getConfig('aggregationKey') => $this->getConfig('aggregationKey')] + $categories;
				}

				$categoryId = $this->verifySelection($categoryId, $categories);
			}

			//Save the category identifier in the session
			$session->write($sessionIdentifier, $categoryId);
		}
		else {
			$categoryId = $session->read($sessionIdentifier);
		}

		//Add the selected category identifier to the config
		$this->setConfig('selectedCategory', $categoryId);
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

		$view = $this->getController()->viewBuilder();

		$identifier = Inflector::variable($this->getConfig('identifier'));

		$config = $this->getConfig();
		ksort($config);
		unset($config['implementedEvents'], $config['implementedMethods']);

		$parentCategories = null;
		$includeParents = $this->getConfig('includeParentCategories');
		if ($this->getConfig('includeParentCategories')) {
			$maxLevel = $includeParents === true ? PHP_INT_MAX : (int)$includeParents;
			$this->getBehavior()->assignParentCategories($maxLevel);
		}

		$categories = [
			'config' => $config,
			'parents' => $parentCategories,
			'raw' => $this->getCategories(true),
			'selected' => $this->getConfig('selectedCategory'),
			'simple' => $this->getCategories(),
		];

		$variableNamePlural = Inflector::pluralize($identifier);

		$view->setVar('_categories', [$variableNamePlural => $categories]);
		$view->setVar('_categoriesIdentifier', $config['identifier']);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function ensurePossibleCategory(EntityInterface $entity): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$fieldName = $this->getConfig('field');
		$selectedCategory = $entity->$fieldName;

		if ($selectedCategory instanceof BackedEnum) {
			$selectedCategory = $selectedCategory->value;
		}

		$possibleCategories = array_keys($this->getCategories());
		if (!in_array($selectedCategory, $possibleCategories, true)) {
			/** @var \Awyiss\Model\Entity $entity */
			$targetEntity = $this->fieldIsAttribute() && $entity->attributes ? $entity->attributes : $entity;

			$errors = $targetEntity->getError($fieldName);

			$targetEntity->set($fieldName, reset($possibleCategories));

			if ($errors) {
				$targetEntity->setError($fieldName, $errors);
			}
		}

		$request = $this->getController()->getRequest();
		$fieldName = Inflector::variable($fieldName);
		//When the field is part of the request data, overwrite it since it might be outdated
		if ($request->getData($fieldName) !== null) {
			$request = $request->withData($fieldName, $entity->$fieldName);
			$this->getController()->setRequest($request);
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
	 * @param SelectQuery $query
	 * @param mixed $selectedCategory
	 * @param bool $sortAggregation
	 * @return SelectQuery
	 */
	public function filterQuery(SelectQuery $query, mixed $selectedCategory = null, bool $sortAggregation = true): SelectQuery {
		return $this->getBehavior()->filterQuery($query, $selectedCategory, $sortAggregation);
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
	 * @param bool $returnRaw
	 * @return \Cake\Datasource\ResultSetInterface|\Cake\Collection\Iterator\TreeIterator|array|null
	 */
	public function getCategories(bool $returnRaw = false): ResultSetInterface|TreeIterator|array|null {
		if (!$this->getConfig('enabled')) {
			return $returnRaw ? new ResultSet([]) : [];
		}


		return $this->getBehavior()->getCategories($returnRaw);
	}


	/**
	 * @param mixed|null $selectedCategory
	 * @return array
	 */
	public function getQueryConditions(mixed $selectedCategory = null): array {
		return $this->getBehavior()->getQueryConditions($selectedCategory);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|null $entity
	 * @return mixed
	 */
	public function getSelectedCategory(?EntityInterface $entity = null): mixed {
		return $this->getBehavior()->getSelectedCategory($entity);
	}


	/**
	 * This method groups the result of the provided query by the column provided via `$column`.
	 * It returns the query with an attached formatResults callback, that groups the resultset by the given column
	 *
	 * @param SelectQuery $query
	 * @param string|null $column
	 * @param string|null $associationName
	 * @param bool $sortByAssociation
	 * @return SelectQuery
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

		return $this->getBehavior()->groupResult($query, $column, $associationName, $sortByAssociation);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string|null $column
	 * @param string|null $associationName
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function sortQuery(SelectQuery $query, ?string $column = null, ?string $associationName = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}


		return $this->getBehavior()->sortQuery($query, $column, $associationName);
	}


	/**
	 * Verify the selected category.
	 * If that fails, set it to the first available or initiate a redirect if either
	 * `$redirect` or `redirectOnInvalidSelection` is true
	 *
	 * @param mixed $categoryId
	 * @param array|null $categories
	 * @param bool|null $redirect
	 * @return mixed
	 */
	public function verifySelection(mixed $categoryId = null, ?array $categories = null, ?bool $redirect = null): mixed {
		if (!$this->getConfig('enabled')) {
			return null;
		}

		$categories = $categories ? array_keys($categories) : $this->getBehavior()->getValidSelectionValues();
		$verifiedSelection = $this->getBehavior()->verifySelection($categoryId, $categories);

		if (
			$verifiedSelection === false
			&& (
				$redirect === true
				|| (
					$this->getConfig('redirectOnInvalidSelection')
					&& $redirect !== false
				)
			)
		) {
			throw new RedirectException(Router::url([
				'action' => 'overview',
				$this->getConfig('uriParam') => current($categories),
				'_name' => $this
					->getController()
					->getRequest()
					->getParam('_name'),
			], true), 302);
		}


		return $verifiedSelection;
	}
}
