<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Behavior\Date\DateType;
use Awyiss\Model\Entity\Date;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


class DateBehavior extends Behavior/* implements PropertyMarshalInterface*/ {
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
		'tableLocator' => NULL,
		'types' => [],
	];
	/**
	 * Instance of Table responsible for dates
	 *
	 * @var Table
	 */
	protected Table $datesTable;


	public function __construct(Table $ao_table, array $aa_config = []) {
		$la_config = $aa_config + [
				'referenceName' => $this->getScope($ao_table),
				'tableLocator' => $ao_table->associations()->getTableLocator(),
			];

		parent::__construct($ao_table, $la_config);

		$this->_tableLocator = $this->getConfig('tableLocator');
		$this->datesTable = $this->getTableLocator()->get($this->getConfig('datesTable'), ['allowFallbackClass' => FALSE]);

		$this->setupAssociations();
	}


	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options): void {
		if (!$this->getConfig('types')) {
			return;
		}

		$contain = [];
		$alias = $this->_table->getAlias();
		$select = $query->clause('select');

		$conditions = function (string $field, SelectQuery $query, array $select) {
			return function (SelectQuery $q) use ($field, $query, $select) {
				//$table = $q->getRepository();
				//$q->where([$table->aliasField('locale') => $locale]);

				if ($query->isAutoFieldsEnabled() || in_array($field, $select, TRUE) || in_array($this->_table->aliasField($field), $select, TRUE)) {
					$q->select(['id', 'value']);
				}


				return $q;
			};
		};

		/** @var DateType $le_type */
		foreach ($this->getConfig('types') as $le_type) {
			$name = $alias . '_' . $le_type->value . '_date';

			$contain[ $name ]['queryBuilder'] = $conditions($le_type->value, $query, $select);
		}

		$query->contain($contain);
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
	 *
	 * @return string
	 */
	protected function getScope(Table $ao_table): string {
		$ls_name = namespaceSplit($ao_table::class);
		$ls_name = substr((string) end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $ao_table->getTable() ?: $ao_table->getAlias();
		}


		return Inflector::underscore($ls_name);
	}


	protected function rowMapper(CollectionInterface $results): CollectionInterface {
		return $results->map(function (EntityInterface|array|null $row) {
			if ($row === NULL) {
				return $row;
			}

			$hydrated = $row instanceof EntityInterface;

			$row['_dates'] = [];
			/** @var DateType $le_type */
			foreach ($this->getConfig('types') as $le_type) {
				$name = $le_type->value . '_date';
				$date = $row[ $name ];

				$row['_dates'][] = $row[ $name ] ? new Date($row[ $name ]->extract(), [
					'markNew' => FALSE,
					'useSetters' => FALSE,
					'markClean' => TRUE,
				]) : NULL;

				if ($date === NULL) {
					$row[ $le_type->value ] = NULL;

					if ($hydrated) {
						$row->setDirty($le_type->value, FALSE);
						$row->setVirtual([$le_type->value], TRUE);
					}

					unset($row[ $name ]);

					continue;
				}

				$row[ $le_type->value ] = $row[ $name ]->value;

				if ($hydrated) {
					$row->setDirty($le_type->value, FALSE);
					$row->setVirtual([$le_type->value], TRUE);
				}

				unset($row[ $name ]);
			}

			if ($hydrated) {
				$row['_dates'] = array_values(array_filter($row['_dates']));
				$row->setDirty('_dates', FALSE);
			}


			return $row;
		});
	}


	/**
	 * @return void
	 */
	protected function setupAssociations(): void {
		if (!$this->getConfig('types')) {
			return;
		}

		$targetAlias = $this->datesTable->getAlias();
		$alias = $this->_table->getAlias();
		$tableLocator = $this->getTableLocator();

		/** @var DateType $le_type */
		foreach ($this->getConfig('types') as $le_type) {
			$name = $alias . '_' . $le_type->value . '_date';

			if (!$tableLocator->exists($name)) {
				$fieldTable = $tableLocator->get($name, [
					'className' => $this->getConfig('datesTable'),
					'alias' => $name,
					'table' => $this->datesTable->getTable(),
				]);
			}
			else {
				$fieldTable = $tableLocator->get($name);
			}

			$conditions = [
				$name . '.scope' => $this->getConfig('referenceName'),
				$name . '.type' => $le_type->value,
			];

			$this->_table->hasOne($name, [
				'targetTable' => $fieldTable,
				'foreignKey' => 'foreign_id',
				'joinType' => SelectQuery::JOIN_TYPE_LEFT,
				'conditions' => $conditions,
				'propertyName' => $le_type->value . '_date',
			]);
		}

		$this->_table->hasMany($targetAlias, [
			'className' => $this->getConfig('datesTable'),
			'foreignKey' => 'foreign_id',
			'strategy' => 'subquery',
			'conditions' => [
				$targetAlias . '.scope' => $this->getConfig('referenceName'),
			],
			'propertyName' => '_dates',
			'dependent' => TRUE,
		]);
	}
}
