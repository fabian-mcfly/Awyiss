<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity\Date;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Handles specific dates for entities, like publishing dates or event/news dates
 */
class DatesBehavior extends Behavior/* implements PropertyMarshalInterface*/ {
	use LocatorAwareTrait;


	/**
	 * Default config
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		/*'implementedFinders' => ['translations' => 'findTranslations'],
		'implementedMethods' => [
			'setLocale' => 'setLocale',
			'getLocale' => 'getLocale',
			'translationField' => 'translationField',
		],*/
		'datesTable' => 'Dates',
		'referenceName' => '',
		'strategy' => 'subquery',
		'tableLocator' => null,
		'types' => [],
	];
	/**
	 * Instance of Table responsible for dates
	 *
	 * @var Table
	 */
	protected Table $datesTable;


	/**
	 * @inheritDoc
	 * @param Table $ao_table
	 * @param array $aa_config
	 */
	public function __construct(Table $ao_table, array $aa_config = []) {
		$la_config = $aa_config + [
				'referenceName' => $this->getScope($ao_table),
				'tableLocator' => $ao_table->associations()->getTableLocator(),
			];

		parent::__construct($ao_table, $la_config);

		$this->_tableLocator = $this->getConfig('tableLocator');
		$this->datesTable = $this->getTableLocator()->get($this->getConfig('datesTable'), ['allowFallbackClass' => false]);

		$this->setupAssociations();
	}


	/**
	 * @param EventInterface $ao_event
	 * @param SelectQuery $query
	 * @param ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $query, ArrayObject $ao_options): void {
		if (!$this->getConfig('types')) {
			return;
		}

		$la_contain = [];
		$ls_alias = $this->_table->getAlias();
		$la_select = $query->clause('select');

		$lc_conditions = function (string $as_field, SelectQuery $ao_query, array $aa_select) {
			return function (SelectQuery $q) use ($as_field, $ao_query, $aa_select) {
				if (
					$ao_query->isAutoFieldsEnabled() ||
					in_array($as_field, $aa_select, true) ||
					in_array($this->_table->aliasField($as_field), $aa_select, true)
				) {
					$q->select(['id', 'value']);
				}


				return $q;
			};
		};

		/** @var \Awyiss\Model\Behavior\Date\DateType $le_type */
		foreach ($this->getConfig('types') as $le_type) {
			$ls_name = $ls_alias . '_' . $le_type->value . '_date';

			$la_contain[ $ls_name ]['queryBuilder'] = $lc_conditions($le_type->value, $query, $la_select);
		}

		$query->contain($la_contain);
		$query->formatResults(fn(CollectionInterface $results) => $this->rowMapper($results), $query::PREPEND);
	}


	/**
	 * Determine the reference name to use for a given table
	 *
	 * The reference name is usually derived from the class name of the table object
	 * (PostsTable -> Posts), however for autotable instances it is derived from
	 * the database table the object points at - or as a last resort, the alias
	 * of the autotable instance.
	 *
	 * @param Table $ao_table The table class to get a reference name for.
	 * @return string
	 */
	protected function getScope(Table $ao_table): string {
		$ls_name = namespaceSplit($ao_table::class);
		$ls_name = substr((string)end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $ao_table->getTable() ?: $ao_table->getAlias();
		}


		return Inflector::underscore($ls_name);
	}


	/**
	 * @param CollectionInterface $ao_results
	 * @return CollectionInterface
	 */
	protected function rowMapper(CollectionInterface $ao_results): CollectionInterface {
		return $ao_results->map(function (EntityInterface|array|null $ax_row): EntityInterface|array|null {
			if ($ax_row === null) {
				return null;
			}

			$lb_hydrated = $ax_row instanceof EntityInterface;

			$ax_row['_dates'] = [];
			/** @var \Awyiss\Model\Behavior\Date\DateType $le_type */
			foreach ($this->getConfig('types') as $le_type) {
				$ls_name = $le_type->value . '_date';
				$lo_date = $ax_row[ $ls_name ];

				$ax_row['_dates'][] = $ax_row[ $ls_name ] ? new Date($ax_row[ $ls_name ]->extract(), [
					'markNew' => false,
					'useSetters' => false,
					'markClean' => true,
				]) : null;

				if ($lo_date === null) {
					$ax_row[ $le_type->value ] = null;

					if ($lb_hydrated) {
						$ax_row->setDirty($le_type->value, false);
						$ax_row->setVirtual([$le_type->value], true);
					}

					unset($ax_row[ $ls_name ]);

					continue;
				}

				$ax_row[ $le_type->value ] = $ax_row[ $ls_name ]->value;

				if ($lb_hydrated) {
					$ax_row->setDirty($le_type->value, false);
					$ax_row->setVirtual([$le_type->value], true);
				}

				unset($ax_row[ $ls_name ]);
			}

			if ($lb_hydrated) {
				$ax_row['_dates'] = array_values(array_filter($ax_row['_dates']));
				$ax_row->setDirty('_dates', false);
			}


			return $ax_row;
		});
	}


	/**
	 * @return void
	 */
	protected function setupAssociations(): void {
		if (!$this->getConfig('types')) {
			return;
		}

		$ls_targetAlias = $this->datesTable->getAlias();
		$ls_alias = $this->_table->getAlias();
		$lo_tableLocator = $this->getTableLocator();

		/** @var \Awyiss\Model\Behavior\Date\DateType $le_type */
		foreach ($this->getConfig('types') as $le_type) {
			$ls_name = $ls_alias . '_' . $le_type->value . '_date';

			if (!$lo_tableLocator->exists($ls_name)) {
				$lo_fieldTable = $lo_tableLocator->get($ls_name, [
					'className' => $this->getConfig('datesTable'),
					'alias' => $ls_name,
					'table' => $this->datesTable->getTable(),
				]);
			}
			else {
				$lo_fieldTable = $lo_tableLocator->get($ls_name);
			}

			$la_conditions = [
				$ls_name . '.scope' => $this->getConfig('referenceName'),
				$ls_name . '.type' => $le_type->value,
			];

			/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
			$this->_table->hasOne($ls_name, [
				'targetTable' => $lo_fieldTable,
				'foreignKey' => 'foreign_id',
				'joinType' => SelectQuery::JOIN_TYPE_LEFT,
				'conditions' => $la_conditions,
				'propertyName' => $le_type->value . '_date',
			]);
		}

		$this->_table->hasMany($ls_targetAlias, [
			'className' => $this->getConfig('datesTable'),
			'foreignKey' => 'foreign_id',
			'strategy' => 'subquery',
			'conditions' => [
				$ls_targetAlias . '.scope' => $this->getConfig('referenceName'),
			],
			'propertyName' => '_dates',
			'dependent' => true,
		]);
	}
}
