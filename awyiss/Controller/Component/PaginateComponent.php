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
 */
class PaginateComponent extends Component {
	/**
	 * @var array
	 */
	protected array $aliasedFields = [];
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
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
		$this->defaultSortableFields = $aa_config['defaultSortableFields'] ?? [];
		$this->setConfig('defaultSortableFields');

		$this->enabled = $aa_config['enabled'] ?? true;
		$this->setConfig('enabled');
	}


	/**
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $ao_object
	 * @param array $aa_settings
	 * @return
	 */
	public function paginate(
		RepositoryInterface|QueryInterface|string|null $ao_object = null,
		array $aa_settings = []
	): PaginatedInterface {
		if (!$this->enabled) {
			throw new BadMethodCallException('PaginateComponent is disabled');
		}

		$lo_object = $ao_object;
		if (!is_object($ao_object)) {
			$lo_object = $this->getController()->fetchTable($ao_object);
		}

		$la_settings = $aa_settings;
		$la_settings += $this->getConfig();
		$la_settings += [
			'order' => [
				'title' => 'asc',
			],
		];

		/** @var class-string<\Awyiss\Datasource\Paging\NumericPaginator> $lo_paginator */
		$ls_paginatorClass = App::className(
			$la_settings['className'] ?? NumericPaginator::class,
			'Datasource/Paging',
			'Paginator'
		);
		$lo_paginator = new $ls_paginatorClass();

		$la_params = $this->getController()->getRequest()->getQueryParams();

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $ao_object->getRepository();

		$this->checkCategoriesParam($ao_object, $la_params, $lo_table);

		$this->modifyPaginateParams($la_params, $la_settings, $lo_table, $lo_object);
		unset($la_settings['className'], $la_settings['defaultSortableFields']);

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
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @return int|null
	 */
	public function calculateEntityPagePosition(EntityInterface $ao_entity): ?int {
		if (!$this->enabled) {
			return null;
		}

		$lo_records = $this->paginate($this->getController()->getOverviewQuery(), [
			'limit' => 999999,
			'maxLimit' => 999999,
		]);

		$li_key = 1;
		foreach ($lo_records as $lo_record) {
			if ($lo_record->id === $ao_entity->id) {
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
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface $ao_object
	 * @param array $aa_params
	 * @param \Awyiss\Model\Table $ao_table
	 * @return void
	 */
	protected function checkCategoriesParam(RepositoryInterface|QueryInterface $ao_object, array &$aa_params, Table $ao_table): void {
		if (
			!$ao_object instanceof QueryInterface ||
			!$ao_table->hasBehavior('Categories') ||
			$ao_table->getBehavior('Categories')->getConfig('enabled') === false ||
			empty($aa_params['sort'])
		) {
			return;
		}

		$ls_categoriesField = $ao_table->getBehavior('Categories')->getConfig('field');
		if (Inflector::underscore($ls_categoriesField) !== $aa_params['sort']) {
			return;
		}

		$ao_object->contain([$ao_table->getBehavior('Categories')->getConfig('associationName')]);

		$aa_params['sort'] = $ao_table->getBehavior('Categories')->getConfig('associationName') . '.title';
	}


	/**
	 * Modifies the paginate-params and settings before calling the paginate method.
	 *
	 * @param array $aa_params
	 * @param array $aa_settings
	 * @param \Awyiss\Model\Table $ao_table
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $ao_object
	 * @param bool $ab_isContain
	 * @return void
	 */
	protected function modifyPaginateParams(
		array &$aa_params,
		array &$aa_settings,
		Table $ao_table,
		RepositoryInterface|QueryInterface|string|null $ao_object,
		bool $ab_isContain = false
	): void {
		$ls_tableAlias = $ab_isContain ? $ao_table->getAlias() : null;

		// Make sure the sortableFields are set
		if (empty($aa_settings['sortableFields'])) {
			$aa_settings['sortableFields'] = $this->defaultSortableFields;
			$aa_settings['sortableFields'] = array_merge($aa_settings['sortableFields'], $ao_table->getSchema()->columns());
			$aa_settings['sortableFields'] = array_unique($aa_settings['sortableFields']);
		}

		if ($ab_isContain) {
			$ls_singularAlias = Inflector::underscore(Inflector::singularize($ls_tableAlias));
			// Prefix all fields with the table alias
			foreach ($ao_table->getSchema()->columns() as $ls_field) {
				$ls_underscoredAlias = $ls_singularAlias . '_' . $ls_field;
				$aa_settings['sortableFields'][] = $ls_tableAlias . '.' . $ls_field;

				if (isset($aa_params['sort']) && $aa_params['sort'] === $ls_underscoredAlias) {
					$aa_params['sort'] = $ls_tableAlias . '.' . $ls_field;
				}
			}
		}

		// Add the attributes of the table to the sortableFields
		foreach ($ao_table->getAttributes() as $ls_attribute => $lo_attribute) {
			if ($ls_tableAlias) {
				$ls_attribute = $ls_tableAlias . 'Attributes.' . $ls_attribute;
			}

			$aa_settings['sortableFields'][] = $ls_attribute;
		}

		// If the table has a behavior for translating, modify the params and/or settings to match the translated field names
		if ($ao_table->hasBehavior('Translate')) {
			/** @noinspection PhpParamsInspection */
			$this->modifyTranslatedPaginateParams($aa_params, $aa_settings, $ao_table->getBehavior('Translate'), $ls_tableAlias);
		}

		if ($ao_table->hasAttributes() && $ao_table->getAttributesTable()->hasBehavior('Translate')) {
			/**
			 * @noinspection PhpParamsInspection
			 * @noinspection PhpArgumentWithoutNamedIdentifierInspection
			 */
			$this->modifyTranslatedPaginateParams($aa_params, $aa_settings, $ao_table->getAttributesTable()->getBehavior('Translate'), $ls_tableAlias);
		}

		// Traverse the contain array and modify the params and settings for each table
		if (!$ab_isContain && $ao_object instanceof QueryInterface) {
			foreach ($ao_object->getContain() as $ls_tableName => $la_containOptions) {
				/** @var \Awyiss\Model\Table $lo_table */
				$lo_table = $ao_table->getAssociation($ls_tableName)->getTarget();
				$this->modifyPaginateParams($aa_params, $aa_settings, $lo_table, null, true);
			}
		}
	}


	/**
	 * @param array $aa_params
	 * @param array $aa_settings
	 * @param \Awyiss\Model\Behavior\TranslateBehavior $ao_behavior
	 * @param ?string $as_tableAlias
	 * @return void
	 */
	protected function modifyTranslatedPaginateParams(
		array &$aa_params,
		array &$aa_settings,
		TranslateBehavior $ao_behavior,
		?string $as_tableAlias = null,
	): void {
		$la_translatableFields = $ao_behavior->getConfig('fields');

		// Modify the sort field if it is set so that it matches the translated field name
		if (isset($aa_params['sort']) && !is_array($aa_params['sort'])) {
			if ($as_tableAlias && str_starts_with($aa_params['sort'], $as_tableAlias . '.')) {
				// Strip the alias from the sort field
				$ls_field = substr($aa_params['sort'], strlen($as_tableAlias) + 1);
				if (in_array($ls_field, $la_translatableFields)) {
					$ls_translationField = $ao_behavior->translationField($ls_field);

					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $ls_translationField ] = $aa_params['sort'];

					$aa_params['sort'] = [$ls_translationField, $aa_params['sort']];
					$aa_settings['sortableFields'][] = $ls_translationField;
				}
			}

			if (in_array($aa_params['sort'], $la_translatableFields)) {
				$ls_field = $aa_params['sort'];
				$ls_translationField = $ao_behavior->translationField($ls_field);

				// Add the translated field to the aliasedFields
				$this->aliasedFields[ $ls_translationField ] = $ls_field;

				$aa_params['sort'] = [$ls_translationField, $ls_field];
				$aa_settings['sortableFields'][] = $ls_translationField;
			}
		}


		// Modify the default order fields if it is set so that it matches the translated field names
		if (!$as_tableAlias && !empty($aa_settings['order'])) {
			$la_order = [];

			// Traverse the order array
			foreach ($aa_settings['order'] as $ls_field => $ls_direction) {
				$ls_key = $ls_field;

				if (in_array($ls_field, $la_translatableFields)) {
					$ls_key = $ao_behavior->translationField($ls_field);

					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $ls_key ] = $ls_field;

					// If the sort field is not set, set it to the translated field, coalesce with the original field
					$aa_params['sort'] ??= [$ls_key, $ls_field];

					// Add the translated field to the sortableFields
					$aa_settings['sortableFields'][] = $ls_key;
				}

				// Set the direction for the translated and the original field to the current direction
				// if the sort field is set and matches the current field
				/** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
				if (($aa_params['direction'] ?? null) && $ls_key === $aa_params['sort']) {
					$la_order[ $ls_key ] = $aa_params['direction'];
					$la_order[ $ls_field ] = $aa_params['direction'];
				}
				else {
					$la_order[ $ls_key ] = $ls_direction;
					$la_order[ $ls_field ] = $ls_direction;
				}
			}

			$aa_settings['order'] = $la_order;
		}
	}
}
