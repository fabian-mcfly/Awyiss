<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Core\App;
use Awyiss\Model\Entity\PublicationData;
use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use LogicException;


/**
 * Handles specific dates for entities, like publishing dates or event/news dates
 */
class PublicationDataBehavior extends Behavior implements PropertyMarshalInterface {
	use LocatorAwareTrait;


	/**
	 * @var array
	 */
	protected static array $pageRoles;


	/**
	 * Default config
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'enabled' => false,
		'implementedFinders' => [
			'published' => 'findPublished',
			'publishedStartingBefore' => 'findPublishedStartingBefore',
			'publishedStartingAfter' => 'findPublishedStartingAfter',
			'publishedEndingBefore' => 'findPublishedEndingBefore',
			'publishedEndingAfter' => 'findPublishedEndingAfter',
		],
		'referenceName' => '',
		'skip' => false,
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
		$config += [
			'referenceName' => $this->getScope($table),
			'tableLocator' => $table->associations()->getTableLocator(),
		];

		parent::__construct($table, $config);
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

		if (!isset(static::$pageRoles)) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');
			foreach ($pageRoleEnum::cases() as $pageRole) {
				static::$pageRoles[] = Inflector::pluralize(Inflector::underscore($pageRole->name));
			}
		}

		$this->setupAssociations(in_array($this->getConfig('referenceName'), static::$pageRoles, true));
	}


	/**
	 * @param Table $table The table class to get a reference name for.
	 * @return string
	 */
	protected function getScope(Table $table): string {
		$name = namespaceSplit($table::class);
		$name = substr((string)end($name), 0, -5);

		if (empty($name)) {
			$name = $table->getTable() ?: $table->getAlias();
		}


		return Inflector::underscore($name);
	}


	/**
	 * @param CollectionInterface $results
	 * @return CollectionInterface
	 */
	protected function rowMapper(CollectionInterface $results): CollectionInterface {
		return $results->map(function (EntityInterface|array|null $row): EntityInterface|array|null {
			if ($row === null) {
				return null;
			}

			$alias = $this->_table->getAlias();
			$hydrated = $row instanceof EntityInterface;

			// If the row is already hydrated, we can skip the following steps
			if (isset($row['_publicationData'])) {
				return $row;
			}

			$row['_publicationData'] = [];
			foreach ($this->types::cases() as $dataType) {
				$identifier = $dataType->value;

				$name = 'publication_' . $identifier;

				/** @var \Awyiss\Model\Entity\PublicationData $publicationData */
				$publicationData = $row[ '_' . $name ];
				$matchingAlias = Inflector::camelize($alias . '_' . 'publication_data_' . $identifier);
				if (isset($row['_matchingData'][ $matchingAlias ])) {
					$publicationData = $row['_matchingData'][ $matchingAlias ];
					unset($row['_matchingData'][ $matchingAlias ]);
					if (!$publicationData->id) {
						unset($row[ $name ]);
						continue;
					}
				}

				$row['_publicationData'][] = $publicationData;

				if (is_array($publicationData)) {
					$row[ $name ] = $publicationData['date_time'];
				}
				else {
					$row[ $name ] = $publicationData?->dateTime;
				}

				if ($hydrated) {
					$row->setDirty($name, false);
					$row->setVirtual([$name], true);
				}

				unset($row[ '_' . $name ]);
			}

			if ($hydrated) {
				$row['_publicationData'] = array_filter($row['_publicationData']);

				$row['_publicationData'] = array_combine(
					array_map(fn (PublicationData $publicationData) => $publicationData->type->value, $row['_publicationData']),
					$row['_publicationData']
				);
				$row->setDirty('_publicationData', false);
			}

			if (empty($row['_matchingData'])) {
				unset($row['_matchingData']);
			}

			return $row;
		});
	}


	/**
	 * Sets up the necessary associations on the table
	 *
	 * @param bool $forPages Whether the associations are being set up for pages.
	 * @return void
	 */
	protected function setupAssociations(bool $forPages = false): void {
		$targetAlias = $this->publicationDataTable->getAlias();
		$alias = $this->_table->getAlias();
		$tableLocator = $this->getTableLocator();

		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $this->_table->getEntityClass();

		foreach ($this->types::cases() as $dataType) {
			$identifier = $dataType->value;

			$name = Inflector::camelize($alias . '_publication_data_' . $identifier);

			if (!$tableLocator->exists($name)) {
				$fieldTable = $tableLocator->get($name, [
					'className' => $this->publicationDataTable::class,
					'alias' => $name,
					'table' => $this->publicationDataTable->getTable(),
				]);
			}
			else {
				$fieldTable = $tableLocator->get($name);
			}

			$conditions = [
				$name . '.type' => $identifier,
			];

			if ($forPages) {
				$conditions[ $name . '.scope IN' ] = static::$pageRoles;
			}
			else {
				$conditions[ $name . '.scope' ] = $this->getConfig('referenceName');
			}

			/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
			$this->_table->hasOne($name, [
				'conditions' => $conditions,
				'foreignKey' => 'foreign_key',
				'joinType' => SelectQuery::JOIN_TYPE_LEFT,
				'propertyName' => '_publication_' . $identifier,
				'targetTable' => $fieldTable,
			]);

			$entityClass::addFieldMapping('publication_' . $identifier, Inflector::variable('publication_' . $identifier));
		}

		if ($forPages) {
			$conditions = [
				$targetAlias . '.scope IN' => static::$pageRoles,
			];
		}
		else {
			$conditions = [
				$targetAlias . '.scope' => $this->getConfig('referenceName'),
			];
		}

		$this->_table->hasMany($targetAlias, [
			'className' => $this->publicationDataTable::class,
			'conditions' => $conditions,
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => '_publication_data',
			'saveStrategy' => 'replace',
			'strategy' => 'subquery',
		]);

		$entityClass::addFieldMapping('_publication_data', '_publicationData');
	}


	/**
	 * Finds records that either have no publication data or
	 * are published at a given time.
	 *
	 * If no time is given, it defaults to the current time,
	 * returning all records that are currently published.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $at
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublished(SelectQuery $query, ?DateTime $at = null): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		$date = $at ?? new DateTime('now');

		$this->findPublishedStartingBefore($query, $date);
		$this->findPublishedEndingAfter($query, $date);

		return $query;
	}


	/**
	 * Finds records that have publication data for a given type
	 * starting before or after a given date.

	 * If `includeUndefined` is true, it will also include records
	 * that have no publication data for the specified type.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $date
	 * @param \Awyiss\Model\Enum\PublicationDataType $type
	 * @param string $when
	 * @param bool $includeUndefined
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function find(SelectQuery $query, ?DateTime $date, PublicationDataType $type, string $when, bool $includeUndefined): SelectQuery {
		$alias = $this->_table->getAlias();
		$date ??= new DateTime('now');
		$operator = $when === 'before' ? '<=' : '>=';
		$name = Inflector::camelize($alias . '_publication_data_' . $type->value);

		if ($query->isAutoFieldsEnabled() === null) {
			$query->enableAutoFields();
		}

		if ($query->isAutoFieldsEnabled() !== false) {
			$query->select($this->_table->$name);
		}

		if ($query->getOptions()['_hasPublicationData' . $type->name] ?? false) {
			throw new LogicException(sprintf(
				'Cannot use the publish finder with type `%s` twice.',
				$type->name
			));
		}

		$query->applyOptions([
			'_hasPublicationData' . $type->name => true,
		]);

		if ($includeUndefined) {
			return $query->leftJoinWith($name)->where([
				'OR' => [
					$name . '.date_time ' . $operator => $date,
					$name . '.date_time IS' => null,
				],
			]);
		}

		return $query->leftJoinWith($name)->where([
			$name . '.date_time ' . $operator => $date,
		]);
	}


	/**
	 * Finds records that have publication data starting before
	 * a given date.
	 *
	 * If no date is given, it defaults to the current time.
	 *
	 * If `includeUndefined` is true, it will also include records
	 * that have no publication data for the 'start' type.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $date
	 * @param bool $includeUndefined
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublishedStartingBefore(SelectQuery $query, ?DateTime $date = null, bool $includeUndefined = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		return $this->find($query, $date, PublicationDataType::Start, 'before', $includeUndefined);
	}


	/**
	 * Finds records that have publication data starting after
	 * a given date.
	 *
	 * If no date is given, it defaults to the current time.
	 *
	 * If `includeUndefined` is true, it will also include records
	 * that have no publication data for the 'start' type.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $date
	 * @param bool $includeUndefined
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublishedStartingAfter(SelectQuery $query, ?DateTime $date = null, bool $includeUndefined = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		return $this->find($query, $date, PublicationDataType::Start, 'after', $includeUndefined);
	}


	/**
	 * Finds records that have publication data ending before
	 * a given date.
	 *
	 * If no date is given, it defaults to the current time.
	 *
	 * If `includeUndefined` is true, it will also include records
	 * that have no publication data for the 'end' type.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $date
	 * @param bool $includeUndefined
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublishedEndingBefore(SelectQuery $query, ?DateTime $date = null, bool $includeUndefined = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		return $this->find($query, $date, PublicationDataType::End, 'before', $includeUndefined);
	}



	/**
	 * Finds records that have publication data ending after
	 * a given date.
	 *
	 * If no date is given, it defaults to the current time.
	 *
	 * If `includeUndefined` is true, it will also include records
	 * that have no publication data for the 'end' type.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Cake\I18n\DateTime|null $date
	 * @param bool $includeUndefined
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findPublishedEndingAfter(SelectQuery $query, ?DateTime $date = null, bool $includeUndefined = true): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		return $this->find($query, $date, PublicationDataType::End, 'after', $includeUndefined);
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

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'publicationData'));
		if ($queryOptions['skip'] === true) {
			return;
		}

		$contain = [];
		$alias = $this->_table->getAlias();
		$select = $query->clause('select');

		$conditions = function (string $field, SelectQuery $query, array $select) {
			return function (SelectQuery $q) use ($field, $query, $select) {
				if (
					$query->isAutoFieldsEnabled() !== false ||
					in_array($field, $select, true) ||
					in_array($this->_table->aliasField($field), $select, true)
				) {
					$q->select(['id', 'scope', 'foreign_key', 'type', 'date_time']);
				}

				return $q;
			};
		};

		$matching = $query->getEagerLoader()->getMatching();
		foreach ($this->types::cases() as $dataType) {
			$identifier = $dataType->value;

			$name = Inflector::camelize($alias . '_' . 'publication_data_' . $identifier);

			if (isset($matching[ $name ])) {
				continue;
			}

			$contain[ $name ]['queryBuilder'] = $conditions($identifier, $query, $select);
		}

		$query->contain($contain);
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

		$options['associated'] = [$this->publicationDataTable->getAlias() => ['validate' => false]] + $options['associated'];

		$data = $entity->get('_publicationData');
		/** @var \Awyiss\Model\Entity\PublicationData $publicationData */
		foreach ($data as $key => $publicationData) {
			if (!$publicationData->dateTime) {
				$data[ $key ] = false;
			}

			if (($options['isCopy'] ?? false) === true) {
				$publicationData->unset(['id', 'foreignKey']);
				$publicationData->setNew(true);
			}
		}

		$entity->set('_publicationData', array_values(array_filter($data)));
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

		return [
			'_publication_data' => function (array $values, EntityInterface $entity) {
				/**
				 * @var array<string, \Awyiss\Model\Entity\PublicationData> $publicationData
				 */
				$publicationData = $entity->get('_publicationData') ?? [];

				$errors = [];
				$marshaller = $this->publicationDataTable->marshaller();

				foreach ($values as $type => $data) {
					if (!isset($publicationData[ $type ])) {
						$publicationData[ $type ] = $this->publicationDataTable->newEmptyEntity();
					}

					$data['type'] ??= $type;

					$data['date_time'] ??= null;
					if (is_string($data['date_time']) && $data['date_time'] !== '') {
						$data['date_time'] = TypeFactory::build('datetime')->marshal($data['date_time']);
					}

					$data['scope'] = $this->getConfig('referenceName');

					if (empty($data['scope'])) {
						dd($data, $this);
					}

					$marshaller->merge($publicationData[ $type ], $data, [
						'fields' => [
							'type',
							'dateTime',
							'scope',
						],
						'setter' => false,
						'validate' => false,
						'isMerge' => true,
					]);

					$dataErrors = $publicationData[ $type ]->getErrors();
					if ($dataErrors) {
						$errors[ $type ] = $dataErrors;
					}
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($errors) {
					$entity->setErrors(['_publicationData' => $errors]);
				}

				$entity->setDirty('_publicationData');

				return $publicationData;
			},
		];
	}
}
