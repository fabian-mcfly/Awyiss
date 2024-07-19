<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Entity\PublicationData;
use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Collection\CollectionInterface;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Handles specific dates for entities, like publishing dates or event/news dates
 */
class PublicationDataBehavior extends Behavior implements PropertyMarshalInterface {
	use LocatorAwareTrait;


	/**
	 * Default config
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'enabled' => false,
		'implementedFinders' => [
			'published' => 'findPublished',
		],
		'referenceName' => '',
		'strategy' => 'subquery',
		'tableLocator' => null,
	];
	/**
	 * Instance of Table responsible for dates
	 *
	 * @var \Awyiss\Model\Table
	 */
	protected Table $publicationDataTable;
	/**
	 * @var class-string<\Awyiss\Model\Enum\PublicationDataType>
	 */
	protected string $types = PublicationDataType::class;


	/**
	 * @inheritDoc
	 * @param Table $table
	 * @param array $config
	 */
	public function __construct(Table $table, array $config = []) {
		$la_config = $config + [
			'referenceName' => $this->getScope($table),
			'tableLocator' => $table->associations()->getTableLocator(),
		];

		parent::__construct($table, $la_config);
	}


	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if (!$this->getConfig('enabled')) {
			return;
		}

		$this->_tableLocator = $this->getConfig('tableLocator');

		$this->publicationDataTable = $this->getTableLocator()->get('PublicationData', ['allowFallbackClass' => false]);

		$this->setupAssociations();
	}


	/**
	 * @param Table $table The table class to get a reference name for.
	 * @return string
	 */
	protected function getScope(Table $table): string {
		$ls_name = namespaceSplit($table::class);
		$ls_name = substr((string)end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $table->getTable() ?: $table->getAlias();
		}


		return Inflector::underscore($ls_name);
	}


	/**
	 * @param CollectionInterface $results
	 * @return CollectionInterface
	 */
	protected function rowMapper(CollectionInterface $results): CollectionInterface {
		return $results->map(function (EntityInterface|array|null $row): EntityInterface|array|null {
			$lx_row = $row;

			if ($lx_row === null) {
				return null;
			}

			$ls_alias = $this->_table->getAlias();
			$lb_hydrated = $lx_row instanceof EntityInterface;

			$lx_row['_publicationData'] = [];
			foreach ($this->types::cases() as $le_dataType) {
				$ls_identifier = $le_dataType->value;

				$ls_name = 'publication_' . $ls_identifier;

				/** @var \Awyiss\Model\Entity\PublicationData $lo_publicationData */
				$lo_publicationData = $lx_row[ '_' . $ls_name ];

				$ls_matchingAlias = Inflector::camelize($ls_alias . '_' . $ls_name);
				if (isset($lx_row['_matchingData'][ $ls_matchingAlias ])) {
					$lo_publicationData = $lx_row['_matchingData'][ $ls_matchingAlias ];
					unset($lx_row['_matchingData'][ $ls_matchingAlias ]);
					if (!$lo_publicationData->id) {
						unset($lx_row[ $ls_name ]);
						continue;
					}
				}

				$lx_row['_publicationData'][] = $lo_publicationData;

				if (is_array($lo_publicationData)) {
					$lx_row[ $ls_name ] = $lo_publicationData['date_time'];
				}
				else {
					$lx_row[ $ls_name ] = $lo_publicationData?->dateTime;
				}

				if ($lb_hydrated) {
					$lx_row->setDirty($ls_name, false);
					$lx_row->setVirtual([$ls_name], true);
				}

				unset($lx_row[ '_' . $ls_name ]);
			}

			if ($lb_hydrated) {
				$lx_row['_publicationData'] = array_filter($lx_row['_publicationData']);

				$lx_row['_publicationData'] = array_combine(
					array_map(fn (PublicationData $publicationData) => $publicationData->type->value, $lx_row['_publicationData']),
					$lx_row['_publicationData']
				);
				$lx_row->setDirty('_publicationData', false);
			}

			if (empty($lx_row['_matchingData'])) {
				unset($lx_row['_matchingData']);
			}


			return $lx_row;
		});
	}


	/**
	 * @return void
	 */
	protected function setupAssociations(): void {
		$ls_targetAlias = $this->publicationDataTable->getAlias();
		$ls_alias = $this->_table->getAlias();
		$lo_tableLocator = $this->getTableLocator();

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->_table->getEntityClass();

		foreach ($this->types::cases() as $le_dataType) {
			$ls_identifier = $le_dataType->value;

			$ls_name = Inflector::camelize($ls_alias . '_publication_data_' . $ls_identifier);

			if (!$lo_tableLocator->exists($ls_name)) {
				$lo_fieldTable = $lo_tableLocator->get($ls_name, [
					'className' => $this->publicationDataTable::class,
					'alias' => $ls_name,
					'table' => $this->publicationDataTable->getTable(),
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
				'conditions' => $la_conditions,
				'foreignKey' => 'foreign_id',
				'joinType' => SelectQuery::JOIN_TYPE_LEFT,
				'propertyName' => '_publication_' . $ls_identifier,
				'targetTable' => $lo_fieldTable,
			]);

			$ls_entityClass::addFieldMapping('publication_' . $ls_identifier, Inflector::variable('publication_' . $ls_identifier));
		}

		$this->_table->hasMany($ls_targetAlias, [
			'className' => $this->publicationDataTable::class,
			'conditions' => [
				$ls_targetAlias . '.scope' => $this->getConfig('referenceName'),
			],
			'cascadeDelete' => true,
			'dependent' => true,
			'foreignKey' => 'foreign_id',
			'propertyName' => '_publication_data',
			'saveStrategy' => 'replace',
			'strategy' => 'subquery',
		]);

		$ls_entityClass::addFieldMapping('_publication_data', '_publicationData');
	}


	/**
	 * Finds records that either have no publication data or are published at the given datetimes.
	 *
	 * Possible datetimes are:
	 * - `start`: When set, the record must have its start date before this date.
	 * - `end`: When set, the record must have its end date after this date.
	 * - `at`: When set, the record must be published at this date.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $start
	 * @param \Cake\I18n\DateTime|null $end
	 * @param \Cake\I18n\DateTime|null $at
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublished(SelectQuery $query, ?DateTime $start = null, ?DateTime $end = null, ?DateTime $at = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		//$ls_timezone = LocaleMiddleware::getLanguage(null)->timezone;
		$lo_date = $at ?? new DateTime('now');

		$ls_alias = $this->_table->getAlias();

		if ($query->isAutoFieldsEnabled() === null) {
			$query->enableAutoFields();
		}

		$ls_name = Inflector::camelize($ls_alias . '_publication_data_start');
		$query->select($this->_table->$ls_name);

		$lo_startDate = $start ?? $lo_date;
		//$lo_startDate->setTimezone($ls_timezone);

		$query->leftJoinWith($ls_name)->where([
			'OR' => [
				$ls_name . '.date_time IS ' => null,
				$ls_name . '.date_time <= ' => $lo_startDate,
			],
		]);

		$ls_name = Inflector::camelize($ls_alias . '_publication_data_end');
		$query->select($this->_table->$ls_name);

		$lo_endDate = $end ?? $lo_date;
		//$lo_endDate = $lo_endDate->setTimezone($ls_timezone);

		$query->leftJoinWith($ls_name)->where([
			'OR' => [
				$ls_name . '.date_time >= ' => $lo_endDate,
				$ls_name . '.date_time IS ' => null,
			],
		]);


		return $query;
	}


	/**
	 * @param EventInterface $event
	 * @param SelectQuery $query
	 * @param ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$la_contain = [];
		$ls_alias = $this->_table->getAlias();
		$la_select = $query->clause('select');

		$lc_conditions = function (string $field, SelectQuery $query, array $select) {
			return function (SelectQuery $q) use ($field, $query, $select) {
				if (
					$query->isAutoFieldsEnabled() !== false || in_array($field, $select, true) || in_array($this->_table->aliasField($field), $select, true)
				) {
					$q->select(['id', 'foreign_id', 'type', 'date_time']);
				}


				return $q;
			};
		};

		$la_matching = $query->getEagerLoader()->getMatching();
		foreach ($this->types::cases() as $le_dataType) {
			$ls_identifier = $le_dataType->value;

			$ls_name = Inflector::camelize($ls_alias . '_' . 'publication_data_' . $ls_identifier);

			if (isset($la_matching[ $ls_name ])) {
				continue;
			}

			$la_contain[ $ls_name ]['queryBuilder'] = $lc_conditions($ls_identifier, $query, $la_select);
		}

		$query->contain($la_contain);
		$query->formatResults(fn (CollectionInterface $results) => $this->rowMapper($results), $query::PREPEND);
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$this->getConfig('enabled') || !$entity->get('_publicationData')) {
			return;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$options['associated'] = [$this->publicationDataTable->getAlias() => ['validate' => false]] + $options['associated'];

		$la_data = $entity->get('_publicationData');
		/** @var \Awyiss\Model\Entity\PublicationData $lo_publicationData */
		foreach ($la_data as $ls_key => $lo_publicationData) {
			if (!$lo_publicationData->dateTime) {
				$la_data[ $ls_key ] = false;
			}
		}

		$entity->set('_publicationData', array_values(array_filter($la_data)));
	}


	/**
	 * @param \Cake\ORM\Marshaller $marshaller
	 * @param array $map
	 * @param array $options
	 * @return array
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		if (!$this->getConfig('enabled') || ($options['publicationData'] ?? true) === false) {
			return [];
		}

		$la_options = $options;
		unset($la_options['associated']);

		return [
			'_publication_data' => function (array $values, EntityInterface $entity) use ($la_options) {
				/**
				 * @var array<string, \Awyiss\Model\Entity\PublicationData> $la_publicationData
				 */
				$la_publicationData = $entity->get('_publicationData') ?? [];

				$la_errors = [];
				$lo_marshaller = $this->publicationDataTable->marshaller();

				foreach ($values as $ls_type => $la_data) {
					if (!isset($la_publicationData[ $ls_type ])) {
						$la_publicationData[ $ls_type ] = $this->publicationDataTable->newEmptyEntity();
					}

					$la_data['type'] = $ls_type;
					$la_data['date_time'] = $la_data['date_time'] ? TypeFactory::build('datetime')->marshal($la_data['date_time']) : null;
					$la_data['scope'] = $this->getConfig('referenceName');

					$lo_marshaller->merge($la_publicationData[ $ls_type ], $la_data, $la_options);

					$la_dataErrors = $la_publicationData[ $ls_type ]->getErrors();
					if ($la_dataErrors) {
						$la_errors[ $ls_type ] = $la_dataErrors;
					}
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($la_errors) {
					$entity->setErrors(['_publicationData' => $la_errors]);
				}

				$entity->setDirty('_publicationData');

				return $la_publicationData;
			},
		];
	}
}
