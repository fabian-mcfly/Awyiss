<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaElement;
use Awyiss\Model\Entity\MediaElementSelector;
use Awyiss\Model\Entity\MediaSelector;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * This behavior automatically prefixes database columns with the name of their table.
 * This makes writing out the table name obsolete when using joins.
 */
class MediaAssignmentBehavior extends Behavior implements PropertyMarshalInterface {
	use IdentityAwareTrait;
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
		'implementedEvents' => [
			'beforeSave',
			'afterSave',
		],
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

		$this->assignmentsTable = $this->getTableLocator()->get('MediaAssignments', ['allowFallbackClass' => false]);

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->_table->getEntityClass();

		$this->_table->hasMany('MediaAssignments', [
			'conditions' => [
				'MediaAssignments.scope' => $this->getScope($this->table()),
			],
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => 'media_assignments',
			'saveStrategy' => 'replace',
			'strategy' => $this->getConfig('strategy'),
		]);

		$ls_entityClass::addFieldMapping('media_assignments', 'mediaAssignments');
	}


	/**
	 * Find media assignments when querying a table.
	 * This finder will automatically fetch the media assignments for the entity.
	 * If `includeElementSelector` is set to true, the media selectors will be included as well.
	 * If `useMediaEntity` is set to true, the media entity will be used instead of
	 * the media assignment entity as the value of the media assignment identifier
	 * The regular media assignment entity will still be available
	 * in an underscored version of the media element identifier.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param bool $includeElementSelector
	 * @param bool $useMediaEntity
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findMediaAssignments(SelectQuery $query, bool $includeElementSelector = false, bool $useMediaEntity = false): SelectQuery {
		if (!$this->getConfig('enabled')) {
			return $query;
		}

		if (!isset(static::$mediaElements)) {
			$this->buildElements();
		}

		if ($includeElementSelector) {
			$query->contain([
				'MediaAssignments.MediaElementSelectors.MediaSelectors',
			]);
		}

		return $query->contain([
			'MediaAssignments' => function (SelectQuery $query) {
				/** @noinspection PhpUndefinedMethodInspection */
				$query->orderByAsc($query->newExpr($query->func()->FIND_IN_SET([
					'media_element_id' => 'identifier',
					implode(',', array_reverse(static::$mediaElements)),
				])), true);

				$la_identifiers = array_unique(Hash::extract(static::$mediaElements, '{n}.mediaElementSelectors.{n}.identifier'));

				/** @noinspection PhpUndefinedMethodInspection */
				$query->orderByAsc($query->newExpr($query->func()->FIND_IN_SET([
					'media_element_selector_identifier' => 'identifier',
					implode(',', $la_identifiers),
				])), true);

				return $query->contain(['Media', 'MediaFolders']);
			},
		])->formatResults(fn (CollectionInterface $results) => $this->rowMapper($results, $useMediaEntity), $query::PREPEND);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|array $entity
	 * @param bool $useMediaEntity
	 * @return \Cake\Datasource\EntityInterface|array
	 */
	public function rebuildMediaAssignments(EntityInterface|array $entity, bool $useMediaEntity = false): EntityInterface|array {
		$la_mediaAssignments = [];

		/** @var \Awyiss\Model\Entity\MediaAssignment $lo_mediaAssignment */
		foreach (($entity['mediaAssignments'] ?? []) as $lo_mediaAssignment) {
			if (is_array($lo_mediaAssignment)) {
				// Seems like the media assignments have already been rebuilt
				return $entity;
			}

			$lo_element = static::$mediaElements[ $lo_mediaAssignment->mediaElementId ];
			$ls_elementIdentifier = Inflector::variable($lo_element->identifier);

			$lo_selector = $lo_element->mediaSelectors[ $lo_mediaAssignment->mediaElementSelectorIdentifier ];
			$ls_identifier = Inflector::variable($lo_mediaAssignment->mediaElementSelectorIdentifier);

			if ($lo_selector->identifier === 'multi_file') {
				if ($useMediaEntity) {
					$la_mediaAssignments[ $ls_elementIdentifier ][ $ls_identifier ][] = $lo_mediaAssignment->media;
					$la_mediaAssignments[ $ls_elementIdentifier ][ '_' . $ls_identifier ][] = $lo_mediaAssignment;
				}
				else {
					$la_mediaAssignments[ $ls_elementIdentifier ][ $ls_identifier ][] = $lo_mediaAssignment;
				}
			}
			elseif ($lo_selector->identifier === 'folder') {
				if ($useMediaEntity) {
					$la_mediaAssignments[ $ls_elementIdentifier ][ $ls_identifier ] = $lo_mediaAssignment->mediaFolder;
					$la_mediaAssignments[ $ls_elementIdentifier ][ '_' . $ls_identifier ] = $lo_mediaAssignment;
				}
				else {
					$la_mediaAssignments[ $ls_elementIdentifier ][ $ls_identifier ] = $lo_mediaAssignment;
				}
			}
			else {
				if ($useMediaEntity) {
					$la_mediaAssignments[ $ls_elementIdentifier ][ $ls_identifier ] = $lo_mediaAssignment->media;
					$la_mediaAssignments[ $ls_elementIdentifier ][ '_' . $ls_identifier ] = $lo_mediaAssignment;
				}
				else {
					$la_mediaAssignments[ $ls_elementIdentifier ][ $ls_identifier ] = $lo_mediaAssignment;
				}
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
	 * @param bool $useMediaEntity
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function rowMapper(CollectionInterface $results, bool $useMediaEntity = false): CollectionInterface {
		if (!isset(static::$mediaElements)) {
			$this->buildElements();
		}

		$lb_useMediaEntity = $useMediaEntity;
		return $results->map(function (EntityInterface|array|null $row) use ($lb_useMediaEntity): EntityInterface|array|null {
			$lx_row = $row;

			if ($lx_row === null || empty($lx_row['mediaAssignments'])) {
				return $lx_row;
			}

			$lx_row = $this->rebuildMediaAssignments($lx_row, $lb_useMediaEntity);

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
	 * @throws \ReflectionException
	 */
	public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array {
		if (!$this->getConfig('enabled') || ($options['mediaAssignments'] ?? true) === false) {
			return [];
		}

		$lo_identity = $this->getIdentity();
		if (!$lo_identity || !$lo_identity->scopeIsAccessible('Media', [], 'read')) {
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

				foreach ($values as $li_mediaElementId => $la_elementsData) {
					foreach ($la_elementsData as $ls_mediaElementSelectorIdentifier => $la_elementData) {
						$li_systemOrder = 1;
						$lb_isFolder = false;

						if (array_is_list($la_elementData)) {
							$la_mediaIds = $la_elementData;
						}
						elseif (isset($la_elementData['media_id'])) {
							$la_mediaIds = [$la_elementData['media_id']];
						}
						elseif (!empty($la_elementData['media_folder_id'])) {
							$lb_isFolder = true;
							$la_mediaIds = [$la_elementData['media_folder_id']];
						}
						else {
							continue;
						}

						foreach ($la_mediaIds as $li_mediaId) {
							if (empty($li_mediaId)) {
								continue;
							}

							/** @var \Awyiss\Model\Entity\MediaAssignment $lo_entity */
							$lo_entity = $this->assignmentsTable->newEmptyEntity();

							if (!empty($la_elementData['id'])) {
								$lo_entity->id = $la_elementData['id'];
							}

							$la_data['mediaElementId'] = $li_mediaElementId;
							$la_data['mediaElementSelectorIdentifier'] = $ls_mediaElementSelectorIdentifier;
							$la_data['mediaId'] = !$lb_isFolder ? (int)$li_mediaId : null;
							$la_data['mediaFolderId'] = $lb_isFolder ? (int)$li_mediaId : null;
							$la_data['scope'] = $this->getConfig('referenceName');
							$la_data['systemOrder'] = $li_systemOrder;

							$lo_marshaller->merge($lo_entity, $la_data, $la_options);

							$la_dataErrors = $lo_entity->getErrors();
							if ($la_dataErrors) {
								$la_errors[] = $la_dataErrors;
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


	/**
	 * Build the media elements array
	 *
	 * @return void
	 */
	protected function buildElements(): void {
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


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \ReflectionException
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity/*, ArrayObject $options*/): void {
		$lo_identity = $this->getIdentity();
		// If the user doesn't have access to the media scope, remove the media assignments from the entity
		if (!$lo_identity || !$lo_identity->scopeIsAccessible('Media', [], 'read')) {
			unset($entity->mediaAssignments);
		}
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!LocalConfig::read('mediaFolders.autoCreate', false, $entity->getSource())) {
			return;
		}

		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $lo_mediaAssignmentsTable */
		$lo_mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$lo_existingAssignment = $lo_mediaAssignmentsTable->find()->where([
			'media_element_id' => 0,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => $entity->id,
			'scope' => $this->getConfig('referenceName'),
		])->contain(['MediaFolders'])->first();

		if ($lo_existingAssignment) {
			return;
		}

		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_mediaFoldersTable */
		$lo_mediaFoldersTable = $this->fetchTable('MediaFolders');
		$lo_folder = $lo_mediaFoldersTable->newDefaultEntity([
			'hidden' => true,
			'languageShortcode' => $entity->languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode,
			'title' => $entity->title ?? 'HiddenFolder' . $entity->id,
		]);

		if (!$lo_mediaFoldersTable->save($lo_folder)) {
			return;
		}

		$lo_mediaFoldersTable->dispatchEvent('Model.MediaFolders.afterSaveCommit', ['entity' => $lo_folder, 'options' => $options]);

		$lo_assignment = $lo_mediaAssignmentsTable->newDefaultEntity([
			'media_element_id' => 0,
			'media_element_selector_identifier' => 'hidden_folder',
			'media_folder_id' => $lo_folder->id,
			'foreign_key' => $entity->id,
			'scope' => $this->getConfig('referenceName'),
		]);

		$lo_mediaAssignmentsTable->save($lo_assignment);
	}
}
