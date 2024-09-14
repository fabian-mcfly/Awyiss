<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Core\App;
use Awyiss\Datasource\Paging\NumericPaginator;
use Awyiss\Model\Behavior\TranslateBehavior;
use Awyiss\Model\Table;
use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Inflector;


/**
 * Class PaginateComponent
 *
 * @method \Awyiss\Controller\AppController getController()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class PaginateComponent extends Component {
	/**
	 * @var array
	 */
	protected array $aliasedFields = [];
	/**
	 * @var array
	 */
	protected array $baseFields = [];
	/**
	 * @var bool
	 */
	protected bool $enabled = true;
	/**
	 * @var array
	 */
	protected array $defaultSortableFields = [];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		$this->defaultSortableFields = $config['defaultSortableFields'] ?? [];
		$this->setConfig('defaultSortableFields');

		$this->enabled = $config['enabled'] ?? true;
		$this->setConfig('enabled');
	}


	/**
	 * @return void
	 */
	public function enable(): void {
		$this->enabled = true;
	}


	/**
	 * @return void
	 */
	public function disable(): void {
		$this->enabled = false;
	}


	/**
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object
	 * @param array $settings
	 * @return
	 */
	public function paginate(
		RepositoryInterface|QueryInterface|string|null $object = null,
		array $settings = []
	): PaginatedInterface {
		if (!$this->enabled) {
			throw new BadMethodCallException('PaginateComponent is disabled');
		}

		$lo_object = $object;
		if (!is_object($object)) {
			$lo_object = $this->getController()->fetchTable($object);
		}

		$la_settings = $settings;
		$la_settings += $this->getConfig();
		$la_settings += [
			'order' => [
				'title' => 'asc',
			],
		];

		if (isset($la_settings['defaultSortableFields'])) {
			$this->defaultSortableFields = $la_settings['defaultSortableFields'];
		}

		/** @var class-string<\Awyiss\Datasource\Paging\NumericPaginator> $lo_paginator */
		$ls_paginatorClass = App::className(
			$la_settings['className'] ?? NumericPaginator::class,
			'Datasource/Paging',
			'Paginator'
		);
		$lo_paginator = new $ls_paginatorClass();

		$la_params = $this->getController()->getRequest()->getQueryParams();

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $object->getRepository();

		$this->baseFields = $lo_table->getSchema()->columns();

		$this->checkCategoriesParam($object, $la_params, $lo_table);
		$this->modifyPaginateParams($la_params, $la_settings, $lo_table, $lo_object);
		unset($la_settings['className'], $la_settings['defaultSortableFields']);

		if (isset($la_params['sort'])) {
			if (is_array($la_params['sort'])) {
				$la_params['sort'] = array_map(function ($field) {
					if (!str_contains($field, '.')) {
						return Inflector::underscore($field);
					}

					$la_parts = explode('.', $field);

					return $la_parts[0] . '.' . Inflector::underscore($la_parts[1]);
				}, $la_params['sort']);
			}
			else {
				$la_params['sort'] = Inflector::underscore($la_params['sort']);
			}
		}

		try {
			$lo_results = $lo_paginator->paginate(
				$lo_object,
				$la_params,
				$la_settings
			);
		}
		catch (PageOutOfBoundsException $ex) {
			throw new NotFoundException(null, null, $ex);
		}

		return $lo_results;
	}


	/**
	 * @return void
	 */
	public function beforeRender(): void {
		if (!$this->enabled) {
			return;
		}

		$this->getController()->set('paginate', array_merge(
			$this->getConfig(),
			[
				'aliasedFields' => $this->aliasedFields,
				'defaultSortableFields' => $this->defaultSortableFields,
			]
		));
	}


	/**
	 * Calculates the page position of the entity in the paginated list.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return int|null
	 */
	public function calculateEntityPagePosition(EntityInterface $entity): ?int {
		if (!$this->enabled) {
			return null;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_records = $this->paginate($this->getController()->getOverviewQuery(), [
			'limit' => 999999,
			'maxLimit' => 999999,
		]);

		$li_key = 1;
		foreach ($lo_records as $lo_record) {
			if ($lo_record->id === $entity->id) {
				break;
			}
			$li_key++;
		}

		$li_page = (int)ceil($li_key / $this->getConfig('limit'));

		return $li_page > 1 ? $li_page : null;
	}


	/**
	 * Checks if the sort field is the categories field and modifies the query accordingly.
	 * This is done to make sure that the categories are sorted by their title, not by their id.
	 *
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface $object
	 * @param array $params
	 * @param \Awyiss\Model\Table $table
	 * @return void
	 */
	protected function checkCategoriesParam(RepositoryInterface|QueryInterface $object, array &$params, Table $table): void {
		if (
			!$object instanceof QueryInterface ||
			!$table->hasBehavior('Categories') ||
			$table->getBehavior('Categories')->getConfig('enabled') === false ||
			empty($params['sort'])
		) {
			return;
		}

		$ls_categoriesField = $table->getBehavior('Categories')->getConfig('field');
		if (Inflector::underscore($ls_categoriesField) !== $params['sort']) {
			return;
		}

		$object->contain([$table->getBehavior('Categories')->getConfig('associationName')]);

		/** @noinspection PhpVariableNamingConventionInspection */
		$params['sort'] = $table->getBehavior('Categories')->getConfig('associationName') . '.title';
	}


	/**
	 * Modifies the paginate-params and settings before calling the paginate method.
	 *
	 * @param array $params
	 * @param array $settings
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object
	 * @param bool $isContain
	 * @return void
	 */
	protected function modifyPaginateParams(
		array &$params,
		array &$settings,
		Table $table,
		RepositoryInterface|QueryInterface|string|null $object,
		bool $isContain = false
	): void {
		$ls_tableAlias = $isContain ? $table->getAlias() : null;

		// Make sure the sortableFields are set
		if (empty($settings['sortableFields'])) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$settings['sortableFields'] = array_merge($this->defaultSortableFields,	$table->getSchema()->columns());

			/** @noinspection PhpVariableNamingConventionInspection */
			$settings['sortableFields'] = array_map(
				fn($field) => Inflector::underscore($field),
				$settings['sortableFields']
			);

			/** @noinspection PhpVariableNamingConventionInspection */
			$settings['sortableFields'] = array_unique($settings['sortableFields']);
		}

		if ($isContain) {
			$ls_singularAlias = Inflector::underscore(Inflector::singularize($ls_tableAlias));
			// Prefix all fields with the table alias
			foreach ($table->getSchema()->columns() as $ls_field) {
				$ls_underscoredAlias = $ls_singularAlias . '_' . $ls_field;
				/** @noinspection PhpVariableNamingConventionInspection */
				$settings['sortableFields'][] = $ls_tableAlias . '.' . $ls_field;

				if (isset($params['sort']) && $params['sort'] === $ls_underscoredAlias) {
					/** @noinspection PhpVariableNamingConventionInspection */
					$params['sort'] = $ls_tableAlias . '.' . $ls_field;
				}
			}
		}

		// Add the attributes of the table to the sortableFields
		foreach ($table->getAttributes() as $ls_attribute => $lo_attribute) {
			if ($ls_tableAlias) {
				$ls_attribute = $ls_tableAlias . 'Attributes.' . $ls_attribute;
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$settings['sortableFields'][] = $ls_attribute;
		}

		// If the table has a behavior for translating, modify the params and/or settings to match the translated field names
		if ($table->hasBehavior('Translate')) {
			/**
			 * @noinspection PhpParamsInspection
			 * @noinspection PhpVariableNamingConventionInspection
			 */
			$this->modifyTranslatedPaginateParams($params, $settings, $table->getBehavior('Translate'), $ls_tableAlias);
		}


		if ($table->hasAttributes() && $table->getAttributesTable()->hasBehavior('Translate')) {
			/**
			 * @noinspection PhpParamsInspection
			 * @noinspection PhpArgumentWithoutNamedIdentifierInspection
			 * @noinspection PhpVariableNamingConventionInspection
			 */
			$this->modifyTranslatedPaginateParams($params, $settings, $table->getAttributesTable()->getBehavior('Translate'), $ls_tableAlias);
		}

		// Traverse the contain array and modify the params and settings for each table
		if (!$isContain && $object instanceof QueryInterface) {
			foreach ($object->getContain() as $ls_tableName => $la_containOptions) {
				/** @var \Awyiss\Model\Table $lo_table */
				$lo_table = $table->getAssociation($ls_tableName)->getTarget();
				/** @noinspection PhpVariableNamingConventionInspection */
				$this->modifyPaginateParams($params, $settings, $lo_table, null, true);
			}
		}
	}


	/**
	 * @param array $params
	 * @param array $settings
	 * @param \Awyiss\Model\Behavior\TranslateBehavior $behavior
	 * @param ?string $tableAlias
	 * @return void
	 */
	protected function modifyTranslatedPaginateParams(
		array &$params,
		array &$settings,
		TranslateBehavior $behavior,
		?string $tableAlias = null,
	): void {
		$la_translatableFields = $behavior->getConfig('fields');

		// Modify the sort field if it is set so that it matches the translated field name
		if (isset($params['sort']) && !is_array($params['sort'])) {
			if ($tableAlias && str_starts_with($params['sort'], Inflector::variable(Inflector::singularize($tableAlias)) . '.')) {
				// Strip the alias from the sort field
				$ls_field = substr($params['sort'], strlen(Inflector::singularize($tableAlias)) + 1);
				if (in_array($ls_field, $la_translatableFields)) {
					$ls_translationField = $behavior->translationField($ls_field);

					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $ls_translationField ] = $params['sort'];

					/** @noinspection PhpVariableNamingConventionInspection */
					$params['sort'] = [$ls_translationField, $tableAlias . '.' . $ls_field];
					/** @noinspection PhpVariableNamingConventionInspection */
					$settings['sortableFields'][] = $ls_translationField;
				}
			}

			if (in_array($params['sort'], $la_translatableFields)) {
				$ls_field = $params['sort'];

				if (!$tableAlias || !in_array($ls_field, $this->baseFields)) {
					$ls_translationField = $behavior->translationField($ls_field);
					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $ls_translationField ] = $ls_field;

					/** @noinspection PhpVariableNamingConventionInspection */
					$params['sort'] = [$ls_translationField, $ls_field];
					/** @noinspection PhpVariableNamingConventionInspection */
					$settings['sortableFields'][] = $ls_translationField;
				}
			}
		}

		// Modify the default order fields if it is set so that it matches the translated field names
		if (!$tableAlias && !empty($settings['order'])) {
			$la_order = [];

			// Traverse the order array
			foreach ($settings['order'] as $ls_field => $ls_direction) {
				$ls_key = $ls_field;

				if (in_array($ls_field, $la_translatableFields)) {
					$ls_key = $behavior->translationField($ls_field);

					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $ls_key ] = $ls_field;

					// If the sort field is not set, set it to the translated field, coalesce with the original field
					/** @noinspection PhpVariableNamingConventionInspection */
					$params['sort'] ??= [$ls_key, $ls_field];

					// Add the translated field to the sortableFields
					/** @noinspection PhpVariableNamingConventionInspection */
					$settings['sortableFields'][] = $ls_key;
				}

				// Set the direction for the translated and the original field to the current direction
				// if the sort field is set and matches the current field
				/** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
				if (($params['direction'] ?? null) && $ls_key === $params['sort']) {
					$la_order[ $ls_key ] = $params['direction'];
					$la_order[ $ls_field ] = $params['direction'];
				}
				else {
					$la_order[ $ls_key ] = $ls_direction;
					$la_order[ $ls_field ] = $ls_direction;
				}
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$settings['order'] = $la_order;
		}
	}
}
