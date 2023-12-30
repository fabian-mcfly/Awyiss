<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\Date\DateType;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
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
		'datesTable' => 'Dates',
		'referenceName' => '',
		'strategy' => 'subquery',
		'tableLocator' => null,
	];
	/**
	 * Instance of Table responsible for dates
	 *
	 * @var Table
	 */
	protected Table $datesTable;
	/**
	 * @var array<string, \Awyiss\Model\Behavior\Date\DateType>
	 */
	protected array $types = [];


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

		$this->setTypes($la_config['types'] ?? []);
		unset($la_config['types']);

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
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options): void {
		if (!$this->getTypes()) {
			return;
		}

		$la_contain = [];
		$ls_alias = $this->_table->getAlias();
		$la_select = $ao_query->clause('select');

		$lc_conditions = function (string $as_field, SelectQuery $ao_query, array $aa_select) {
			return function (SelectQuery $ao_q) use ($as_field, $ao_query, $aa_select) {
				if (
					$ao_query->isAutoFieldsEnabled() !== false ||
					in_array($as_field, $aa_select, true) ||
					in_array($this->_table->aliasField($as_field), $aa_select, true)
				) {
					$ao_q->select(['id', 'foreign_id', 'type', 'datetime', 'date', 'time']);
				}


				return $ao_q;
			};
		};


		$la_matching = $ao_query->getEagerLoader()->getMatching();
		foreach ($this->getTypes() as $ls_identifier => $le_dateType) {
			$ls_name = $ls_alias . '_' . $ls_identifier . '_date';

			if (isset($la_matching[ $ls_name ])) {
				continue;
			}

			$la_contain[ $ls_name ]['queryBuilder'] = $lc_conditions($ls_identifier, $ao_query, $la_select);
		}

		$ao_query->contain($la_contain);
		$ao_query->formatResults(fn(CollectionInterface $ao_results) => $this->rowMapper($ao_results), $ao_query::PREPEND);
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
	 * @return array
	 */
	public function getTypes(): array {
		return $this->types;
	}


	/**
	 * @param array $aa_types
	 * @return array
	 */
	protected function normalizeTypes(array $aa_types): array {
		$la_types = [];
		foreach ($aa_types as $lx_key => $lx_type) {
			if ($lx_type === null) {
				continue;
			}

			if (is_numeric($lx_key)) {
				$ls_identifier = Inflector::underscore($lx_type);
				$le_type = DateType::DATETIME;
			}
			else {
				$le_type = is_string($lx_type) ? DateType::tryFrom($lx_type) : $lx_type;
				$ls_identifier = Inflector::underscore($lx_key);
			}

			$la_types[ $ls_identifier ] = $le_type;
		}

		return $la_types;
	}


	/**
	 * @param array $aa_types
	 * @return $this
	 */
	public function setTypes(array $aa_types): static {
		$this->types = $this->normalizeTypes($aa_types);


		return $this;
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

			$ls_alias = $this->_table->getAlias();
			$lb_hydrated = $ax_row instanceof EntityInterface;

			$ax_row['_dates'] = [];
			foreach ($this->getTypes() as $ls_identifier => $le_dateType) {
				$ls_name = $ls_identifier . '_date';

				/** @var \Awyiss\Model\Entity\Date $lo_date */
				$lo_date = $ax_row[ $ls_name ];

				if (isset($ax_row['_matchingData'][ $ls_alias . '_' . $ls_name ])) {
					$lo_date = $ax_row['_matchingData'][ $ls_alias . '_' . $ls_name ];
					unset($ax_row['_matchingData'][ $ls_alias . '_' . $ls_name ]);
					if (!$lo_date->id) {
						unset($ax_row[ $ls_name ]);
						continue;
					}
				}

				$ax_row['_dates'][] = $lo_date;

				if ($lo_date === null) {
					$ax_row[ $ls_identifier ] = null;
				}
				else {
					$lo_value = match ($le_dateType) {
						DateType::DATE => $lo_date->date,
						DateType::TIME => $lo_date->time,
						DateType::DATETIME => $lo_date->dateTime,
					};

					$ax_row[ $ls_identifier ] = $lo_value;
				}

				if ($lb_hydrated) {
					$ax_row->setDirty($ls_identifier, false);
					$ax_row->setVirtual([$ls_identifier], true);
				}

				unset($ax_row[ $ls_name ]);
			}

			if ($lb_hydrated) {
				$ax_row['_dates'] = array_values(array_filter($ax_row['_dates']));
				$ax_row->setDirty('_dates', false);
			}

			if (empty($ax_row['_matchingData'])) {
				unset($ax_row['_matchingData']);
			}

			return $ax_row;
		});
	}


	/**
	 * @return void
	 */
	protected function setupAssociations(): void {
		if (!$this->getTypes()) {
			return;
		}

		$ls_targetAlias = $this->datesTable->getAlias();
		$ls_alias = $this->_table->getAlias();
		$lo_tableLocator = $this->getTableLocator();

		foreach ($this->getTypes() as $ls_identifier => $le_dateType) {
			$ls_name = $ls_alias . '_' . $ls_identifier . '_date';

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
				$ls_name . '.type' => $ls_identifier,
			];

			/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
			$this->_table->hasOne($ls_name, [
				'targetTable' => $lo_fieldTable,
				'foreignKey' => 'foreign_id',
				'joinType' => SelectQuery::JOIN_TYPE_LEFT,
				'conditions' => $la_conditions,
				'propertyName' => $ls_identifier . '_date',
			]);

			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $this->_table->getEntityClass();
			$ls_entityClass::addFieldMapping($ls_identifier, Inflector::variable($ls_identifier));
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


	/**
	 * Implemented finders
	 *
	 * @return array
	 */
	public function implementedFinders(): array {
		$la_finders = [];
		$la_types = $this->getTypes();
		if (!$la_types) {
			return $la_finders;
		}

		if (
			isset($la_types['publication_start']) ||
			isset($la_types['publication_end'])
		) {
			$la_finders['published'] = 'findPublished';
		}

		if (
			isset($la_types['event_start']) ||
			isset($la_types['event_end'])
		) {
			$la_finders['futureEvents'] = 'findFutureEvents';
			$la_finders['pastEvents'] = 'findPastEvents';
		}

		return $la_finders;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param mixed|null $ax_date
	 * @return \Cake\ORM\Query\SelectQuery
	 * @throws \Exception
	 */
	public function findFutureEvents(SelectQuery $ao_query, mixed $ax_date = null): SelectQuery {
		if ($ax_date) {
			dd(__LINE__, __FILE__);
		}

		$lo_date = new DateTime('now');
		$ls_timezone = LocaleMiddleware::getLanguage(null)->timezone;

		$ls_alias = $this->_table->getAlias();
		$la_types = $this->getTypes();

		if (isset($la_types['event_start'])) {
			$ls_name = $ls_alias . '_event_start_date';

			$ao_query->matching($ls_name, function (SelectQuery $ao_query) use ($la_types, $lo_date, $ls_timezone) {
				if ($la_types['event_start'] !== DateType::DATETIME) {
					$lo_date = $lo_date->setTimezone($ls_timezone);
				}

				return $ao_query->where([
					$la_types['event_start']->value . ' >= ' => $lo_date,
				]);
			});
		}

		if (isset($la_types['event_end'])) {
			$ls_name = $ls_alias . '_event_end_date';

			$ao_query->matching($ls_name, function (SelectQuery $ao_query) use ($la_types, $lo_date, $ls_timezone) {
				if ($la_types['event_end'] !== DateType::DATETIME) {
					$lo_date = $lo_date->setTimezone($ls_timezone);
				}

				return $ao_query->where([
					$la_types['event_end']->value . ' <= ' => $lo_date,
				]);
			});
		}

		return $ao_query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param mixed|null $ax_date
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublished(SelectQuery $ao_query, mixed $ax_date = null): SelectQuery {
		if ($ax_date) {
			dd(__LINE__, __FILE__);
		}

		$ls_timezone = LocaleMiddleware::getLanguage(null)->timezone;
		$lo_date = new DateTime('now');

		$ls_alias = $this->_table->getAlias();
		$la_types = $this->getTypes();

		if (isset($la_types['publication_start'])) {
			$ls_name = $ls_alias . '_publication_start_date';

			if ($ao_query->isAutoFieldsEnabled() === null) {
				$ao_query->enableAutoFields();
			}
			$ao_query->select($this->_table->$ls_name);

			$lo_startDate = $lo_date;
			if ($la_types['event_end'] !== DateType::DATETIME) {
				$lo_startDate = $lo_date->setTimezone($ls_timezone);
			}

			$ao_query->leftJoinWith($ls_name)->where([
				'OR' => [
					$ls_name . '.' . $la_types['publication_start']->value . ' <= ' => $lo_startDate,
					$ls_name . '.' . $la_types['publication_start']->value . ' IS ' => null,
				],
			]);
		}

		if (isset($la_types['publication_end'])) {
			$ls_name = $ls_alias . '_publication_end_date';

			if ($ao_query->isAutoFieldsEnabled() === null) {
				$ao_query->enableAutoFields();
			}
			$ao_query->select($this->_table->$ls_name);

			$lo_endDate = $lo_date;
			if ($la_types['event_end'] !== DateType::DATETIME) {
				$lo_endDate = $lo_date->setTimezone($ls_timezone);
			}

			$ao_query->leftJoinWith($ls_name)->where([
				'OR' => [
					$ls_name . '.' . $la_types['publication_end']->value . ' >= ' => $lo_endDate,
					$ls_name . '.' . $la_types['publication_end']->value . ' IS ' => null,
				],
			]);
		}

		return $ao_query;
	}
}
