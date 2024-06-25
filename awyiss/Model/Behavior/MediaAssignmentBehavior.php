<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Model\Entity\MediaElement;
use Awyiss\Model\Entity\MediaElementSelector;
use Awyiss\Model\Entity\MediaSelector;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * This behavior automatically prefixes database columns with the name of their table.
 * This makes writing out the table name obsolete when using joins.
 */
class MediaAssignmentBehavior extends Behavior implements PropertyMarshalInterface {
	use LocatorAwareTrait;


	protected static array $mediaElements;


	/**
	 * Default config
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'enabled' => true,
		'implementedFinders' => [
			'mediaAssignments' => 'findMediaAssignments',
		],
		'implementedMethods' => [
			'rebuildMediaAssignments' => 'rebuildMediaAssignments',
		],
		'referenceName' => '',
		'strategy' => 'select',
		'tableLocator' => null,
	];
	/**
	 * Instance of Table responsible for dates
	 *
	 * @var \Awyiss\Model\Table
	 */
	protected Table $assignmentsTable;


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
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->_tableLocator = $this->getConfig('tableLocator');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->assignmentsTable = $this->getTableLocator()->get('MediaAssignments', ['allowFallbackClass' => false]);

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->_table->getEntityClass();

		$this->_table->hasMany('MediaAssignments', [
			'conditions' => [
				'MediaAssignments.scope' => $this->getScope($this->table()),
			],
			'cascadeDelete' => true,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => 'media_assignments',
			'saveStrategy' => 'replace',
			'strategy' => $this->getConfig('strategy'),
		]);

		$ls_entityClass::addFieldMapping('media_assignments', 'mediaAssignments');
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findMediaAssignments(SelectQuery $query, bool $includeCompositeSelector = false): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		if ($includeCompositeSelector) {
			$query->contain([
				'MediaAssignments.MediaCompositeSelectors.MediaSelectors',
			]);
		}

		return $query->contain([
			'MediaAssignments' => [
				'Media',
			],
		])->formatResults(fn (CollectionInterface $results) => $this->rowMapper($results), $query::PREPEND);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|array $entity
	 * @return \Cake\Datasource\EntityInterface|array
	 */
	public function rebuildMediaAssignments(EntityInterface|array $entity): EntityInterface|array {
		$la_mediaAssignments = [];

		foreach (($entity['mediaAssignments'] ?? []) as $lo_mediaAssignment) {
			$lo_element = static::$mediaElements[ $lo_mediaAssignment->mediaElementId ];
			$ls_elementIdentifier = $lo_element->identifier;

			$lo_selector = $lo_element->mediaSelectors[ $lo_mediaAssignment->mediaElementSelectorIdentifier ];
			if ($lo_selector->identifier === 'multi_file') {
				$la_mediaAssignments[ $ls_elementIdentifier ][ $lo_mediaAssignment->mediaElementSelectorIdentifier ][] = $lo_mediaAssignment;
			}
			else {
				$la_mediaAssignments[ $ls_elementIdentifier ][ $lo_mediaAssignment->mediaElementSelectorIdentifier ] = $lo_mediaAssignment;
			}
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$entity['mediaAssignments'] = $la_mediaAssignments;

		return $entity;
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
	 * @param \Cake\Collection\CollectionInterface $results
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function rowMapper(CollectionInterface $results): CollectionInterface {
		if (!isset(static::$mediaElements)) {
			$lo_elements = $this->fetchTable('MediaElements')->find()->contain([
				'MediaElementSelectors' => [
					'MediaSelectors',
				],
			])->all()->indexBy('id');

			static::$mediaElements = $lo_elements->each(function (MediaElement $element): void {
				$lo_selectors = collection($element->mediaElementSelectors);
				$lo_selectors = $lo_selectors->indexBy(function (MediaElementSelector $selector): string {
					return $selector->identifier;
				})->map(function (MediaElementSelector $selector): MediaSelector {
					return $selector->mediaSelector;
				});

				$element->mediaSelectors = $lo_selectors->toArray();
			})->toArray();
		}

		return $results->map(function (EntityInterface|array|null $row): EntityInterface|array|null {
			$lx_row = $row;

			if ($lx_row === null || empty($lx_row['mediaAssignments'])) {
				return $lx_row;
			}

			$lx_row = $this->rebuildMediaAssignments($lx_row);

			if ($row instanceof EntityInterface) {
				$row->setDirty('mediaAssignments', false);
			}

			return $lx_row;
		});
	}



	/**
	 * @param \Cake\ORM\Marshaller $marshaller
	 * @param array $map
	 * @param array $options
	 * @return array
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		if (!$this->getConfig('enabled') || ($options['mediaAssignments'] ?? true) === false) {
			return [];
		}

		$la_options = $options;
		unset($la_options['associated']);

		return [
			'media_assignments' => function (array $values, EntityInterface $entity) use ($la_options): array {
				/**
				 * @var array<string, \Awyiss\Model\Entity\MediaAssignment> $la_mediaAssignments
				 */
				$la_mediaAssignments = [];

				$la_errors = [];
				$lo_marshaller = $this->assignmentsTable->marshaller();

				foreach ($values as $li_mediaElementId => $la_elementData) {
					foreach ($la_elementData as $ls_mediaElementSelectorIdentifier => $la_mediaIds) {
						$li_systemOrder = 1;

						if (isset($la_mediaIds['media_id'])) {
							$la_mediaIds = [$la_mediaIds['media_id']];
						}

						foreach ($la_mediaIds as $li_mediaId) {
							/** @var \Awyiss\Model\Entity\MediaAssignment $lo_entity */
							$lo_entity = $this->assignmentsTable->newEmptyEntity();

							$la_data['mediaElementId'] = $li_mediaElementId;
							$la_data['mediaElementSelectorIdentifier'] = $ls_mediaElementSelectorIdentifier;
							$la_data['mediaId'] = (int)$li_mediaId;
							$la_data['scope'] = $this->getConfig('referenceName');
							$la_data['systemOrder'] = $li_systemOrder;

							$lo_marshaller->merge($lo_entity, $la_data, $la_options);

							$la_dataErrors = $lo_entity->getErrors();
							if ($la_dataErrors) {
								$la_errors[] = $la_dataErrors;
							}

							if (empty($lo_entity->mediaId)) {
								continue;
							}

							$la_mediaAssignments[] = $lo_entity;

							$li_systemOrder++;
						}
					}
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($la_errors) {
					$entity->setErrors(['mediaAssignments' => $la_errors]);
				}

				$entity->setDirty('mediaAssignments');

				return $la_mediaAssignments;
			},
		];
	}
}
