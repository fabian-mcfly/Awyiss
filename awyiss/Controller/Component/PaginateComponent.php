<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Core\App;
use Awyiss\Datasource\Paging\NumericPaginator;
use Awyiss\Model\Behavior\TranslateBehavior;
use Awyiss\Utility\Inflector;
use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Orm\Table;


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
	 * @var array
	 */
	protected array $fieldTranslations = [];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		$this->defaultSortableFields = $config['defaultSortableFields'] ?? [];
		$this->setConfig('defaultSortableFields');

		$this->enabled = $config['enabled'] ?? true;
		$this->setConfig('enabled');

		$this->fieldTranslations = $config['fieldTranslations'] ?? [];
		$this->setConfig('fieldTranslations');
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
	 * @return \Cake\Datasource\Paging\PaginatedInterface
	 */
	public function paginate(
		RepositoryInterface|QueryInterface|string|null $object = null,
		array $settings = []
	): PaginatedInterface {
		if (!$this->enabled) {
			throw new BadMethodCallException('PaginateComponent is disabled');
		}

		if (!is_object($object)) {
			$object = $this->getController()->fetchTable($object);
		}

		$settings += $this->getConfig();
		$settings += [
			'fieldTranslations' => $this->fieldTranslations,
			'order' => [
				'title' => 'asc',
			],
		];

		if (isset($settings['defaultSortableFields'])) {
			$this->defaultSortableFields = $settings['defaultSortableFields'];
		}

		/** @var class-string<\Awyiss\Datasource\Paging\NumericPaginator> $paginator */
		$paginatorClass = App::className(
			$settings['className'] ?? NumericPaginator::class,
			'Datasource/Paging',
			'Paginator'
		);
		$paginator = new $paginatorClass();

		$params = $this->getController()->getRequest()->getQueryParams();

		/** @var \Cake\Orm\Table $table */
		$table = $object->getRepository();

		$this->baseFields = $table->getSchema()->columns();

		$this->checkCategoriesParam($object, $params, $table);
		$this->modifyPaginateParams($params, $settings, $table, $object);
		unset($settings['className'], $settings['defaultSortableFields']);

		if (isset($params['sort'])) {
			if (is_array($params['sort'])) {
				$params['sort'] = array_map(function ($field) {
					if (!str_contains($field, '.')) {
						return Inflector::underscore($field);
					}

					$parts = explode('.', $field);

					return $parts[0] . '.' . Inflector::underscore($parts[1]);
				}, $params['sort']);
			}
			else {
				$params['sort'] = Inflector::underscore($params['sort']);
			}
		}

		try {
			$results = $paginator->paginate(
				$object,
				$params,
				$settings
			);
		}
		catch (PageOutOfBoundsException $ex) {
			throw new NotFoundException(null, null, $ex);
		}

		return $results;
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
		$records = $this->paginate($this->getController()->getOverviewQuery(), [
			'limit' => 999999,
			'maxLimit' => 999999,
		]);

		$key = 1;
		foreach ($records as $record) {
			if ($record->id === $entity->id) {
				break;
			}
			$key++;
		}

		$page = (int)ceil($key / $this->getConfig('limit', 20));

		return $page > 1 ? $page : null;
	}


	/**
	 * Checks if the sort field is the categories field and modifies the query accordingly.
	 * This is done to make sure that the categories are sorted by their title, not by their id.
	 *
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface $object
	 * @param array $params
	 * @param \Cake\Orm\Table $table
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

		$categoriesField = $table->getBehavior('Categories')->getConfig('field');
		if (Inflector::underscore($categoriesField) !== $params['sort']) {
			return;
		}

		$object->contain([$table->getBehavior('Categories')->getConfig('associationName')]);

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
		$tableAlias = $isContain ? $table->getAlias() : null;

		// Make sure the sortableFields are set
		if (empty($settings['sortableFields'])) {
			$settings['sortableFields'] = array_merge($this->defaultSortableFields,	$table->getSchema()->columns());

			$settings['sortableFields'] = array_map(
				fn($field) => Inflector::underscore($field),
				$settings['sortableFields']
			);

			$settings['sortableFields'] = array_unique($settings['sortableFields']);
		}

		if ($isContain) {
			$singularAlias = Inflector::underscore(Inflector::singularize($tableAlias));
			// Prefix all fields with the table alias
			foreach ($table->getSchema()->columns() as $field) {
				$underscoredAlias = $singularAlias . '_' . $field;
				$settings['sortableFields'][] = $tableAlias . '.' . $field;

				if (isset($params['sort']) && $params['sort'] === $underscoredAlias) {
					$params['sort'] = $tableAlias . '.' . $field;
				}
			}
		}

		if ($table->hasBehavior('Attributes')) {
			// Add the attributes of the table to the sortableFields
			foreach ($table->getAttributes() as $attributeName => $attribute) {
				if ($tableAlias) {
					$attributeName = $tableAlias . 'Attributes.' . $attributeName;
				}

				$settings['sortableFields'][] = $attributeName;
			}
		}

		// If the table has a behavior for translating, modify the params and/or settings to match the translated field names
		if ($table->hasBehavior('Translate')) {
			$this->modifyTranslatedPaginateParams($params, $settings, $table->getBehavior('Translate'), $tableAlias);
		}


		if (
			$table->hasBehavior('Attributes') &&
			$table->hasAttributes() &&
			$table->getAttributesTable()->hasBehavior('Translate')
		) {
			/** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
			$this->modifyTranslatedPaginateParams($params, $settings, $table->getAttributesTable()->getBehavior('Translate'), $tableAlias);
		}

		// Traverse the contain array and modify the params and settings for each table
		if (!$isContain && $object instanceof QueryInterface) {
			foreach ($object->getContain() as $tableName => $containOptions) {
				$association = $table->getAssociation($tableName)->getTarget();
				$this->modifyPaginateParams($params, $settings, $association, null, true);
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
		$translatableFields = $behavior->getConfig('fields', []);

		// Modify the sort field if it is set so that it matches the translated field name
		if (isset($params['sort']) && !is_array($params['sort'])) {
			if ($tableAlias && str_starts_with($params['sort'], Inflector::variable(Inflector::singularize($tableAlias)) . '.')) {
				// Strip the alias from the sort field
				$field = substr($params['sort'], strlen(Inflector::singularize($tableAlias)) + 1);
				if (in_array($field, $translatableFields)) {
					$translationField = $behavior->translationField($field);

					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $translationField ] = $params['sort'];

					$params['sort'] = [$translationField, $tableAlias . '.' . $field];
					$settings['sortableFields'][] = $translationField;
				}
			}

			if (in_array($params['sort'], $translatableFields)) {
				$field = $params['sort'];

				if (!$tableAlias || !in_array($field, $this->baseFields)) {
					$translationField = $behavior->translationField($field);
					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $translationField ] = $field;

					$params['sort'] = [$translationField, $field];
					$settings['sortableFields'][] = $translationField;
				}
			}
		}

		// Modify the default order fields if it is set so that it matches the translated field names
		if (!$tableAlias && !empty($settings['order'])) {
			$order = [];

			// Traverse the order array
			foreach ($settings['order'] as $field => $direction) {
				$translationField = $field;

				if (in_array($field, $translatableFields)) {
					$translationField = $behavior->translationField($field);

					// Add the translated field to the aliasedFields
					$this->aliasedFields[ $translationField ] = $field;

					// If the sort field is not set, set it to the translated field, coalesce with the original field
					$params['sort'] ??= [$translationField, $field];

					// Add the translated field to the sortableFields
					$settings['sortableFields'][] = $translationField;
				}

				// Set the direction for the translated and the original field to the current direction
				// if the sort field is set and matches the current field
				/** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
				if (($params['direction'] ?? null) && $translationField === $params['sort']) {
					$order[ $translationField ] = $params['direction'];
					$order[ $field ] = $params['direction'];
				}
				else {
					$order[ $translationField ] = $direction;
					$order[ $field ] = $direction;
				}
			}

			$settings['order'] = $order;
		}
	}
}
