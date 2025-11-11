<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Core\App;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Model\Entity\MediaElement;
use Awyiss\Model\Entity\MediaElementSelector;
use Awyiss\Model\Entity\MediaSelector;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\SqliteSchemaDialect;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Exception;


/**
 * This behavior automatically prefixes database columns with the name of their table.
 * This makes writing out the table name obsolete when using joins.
 */
class MediaAssignmentBehavior extends Behavior implements PropertyMarshalInterface {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @var array<\Awyiss\Model\Entity\MediaElement>
	 */
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
			'afterSoftDelete',
			'afterDelete',
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

		$la_contain = [
			'MediaAssignments.scope' => $this->getScope($this->table()),
		];

		if ($la_contain['MediaAssignments.scope'] === 'pages') {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
			$la_scope = array_map(function ($pageRole) {
				return Inflector::underscore(Inflector::pluralize($pageRole->name));
			}, $ls_pageRoleEnum::cases());
			$la_contain = ['MediaAssignments.scope IN' => $la_scope];
		}

		$this->_table->hasMany('MediaAssignments', [
			'conditions' => $la_contain,
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'foreign_key',
			'propertyName' => 'mediaAssignments',
			'saveStrategy' => 'replace',
			'strategy' => $this->getConfig('strategy'),
		]);

		$ls_entityClass::addFieldMapping('media_assignments', 'mediaAssignments');

		$this->setConfig('implementedEvents', [
			'Configuration.' . $this->table()->getAlias() . '.Backend.splitIntoLanguages.afterSaveCommit' => 'resetHiddenMediaFolderLanguageAfterSave',
			'Configuration.' . $this->table()->getAlias() . '.Backend.splitIntoLanguages.afterDeleteCommit' => 'resetHiddenMediaFolderLanguageAfterDelete',
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function resetHiddenMediaFolderLanguageAfterSave(Event $event, Configuration $configuration): void {
		if (
			!$configuration->isNew() &&
			(
				!$configuration->hasOriginal('value') ||
				$configuration->getOriginal('value') === $configuration->value
			)
		) {
			return;
		}

		$this->updateHiddenFolderSettings((bool)$configuration->value);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function resetHiddenMediaFolderLanguageAfterDelete(Event $event, Configuration $configuration): void {
		$lo_configuration = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		$lo_configOption = $lo_configuration?->getConfigOption(Awyiss::REALM_BACKEND, $configuration->identifier);
		$lb_defaultSplit = $lo_configOption?->getDefaultValue() ?? false;

		$this->updateHiddenFolderSettings($lb_defaultSplit);
	}


	/**
	 * Find media assignments when querying a table.
	 * This finder will automatically fetch the media assignments for the entity.
	 *
	 * If `includeElementSelector` is set to true, the media selectors will be included as well.
	 *
	 * If `useMediaEntity` is set to true, the media entity will be used instead of
	 * the media assignment entity as the value of the media assignment identifier
	 * The regular media assignment entity will still be available
	 * in an underscored version of the media element identifier.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param bool $includeElementSelector Whether to include the media selectors in the result
	 * @param bool $useMediaEntity Whether to use the media entity instead of the media assignment entity as the value of the media assignment identifier
	 * @param bool $formatResult Whether to format the result so that the media assignments are nested under the media element identifier
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findMediaAssignments(SelectQuery $query, bool $includeElementSelector = false, bool $useMediaEntity = false, bool $formatResult = true): SelectQuery {
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

		$query->contain([
			'MediaAssignments' => $this->getContainConditions(...),
		]);

		if ($formatResult) {
			$query->formatResults(fn (CollectionInterface $results) => $this->rowMapper($results, $useMediaEntity), $query::PREPEND);
		}

		return $query;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|array $entity
	 * @param bool $useMediaEntity
	 * @return \Cake\Datasource\EntityInterface|array
	 */
	public function rebuildMediaAssignments(EntityInterface|array $entity, bool $useMediaEntity = false): EntityInterface|array {
		$la_mediaAssignments = [];

		if (!isset(static::$mediaElements)) {
			$this->buildElements();
		}

		/** @var \Awyiss\Model\Entity\MediaAssignment $lo_mediaAssignment */
		foreach (($entity['mediaAssignments'] ?? []) as $lo_mediaAssignment) {
			if (is_array($lo_mediaAssignment)) {
				// Seems like the media assignments have already been rebuilt
				return $entity;
			}

			$lo_element = static::$mediaElements[ $lo_mediaAssignment->mediaElementId ];
			$ls_elementIdentifier = Inflector::variable($lo_element->identifier);

			$lo_selector = $lo_element->mediaSelectors[ $lo_mediaAssignment->mediaElementSelectorIdentifier ] ?? null;
			$ls_identifier = Inflector::variable($lo_mediaAssignment->mediaElementSelectorIdentifier);

			// Treat inlineImgTag as a special case
			if (!$lo_selector || $ls_elementIdentifier === 'inlineImgTag') {
				if ($ls_elementIdentifier === 'inlineImgTag') {
					$la_mediaAssignments[ $ls_elementIdentifier ] ??= [];
					$la_mediaAssignments[ $ls_elementIdentifier ][ $lo_mediaAssignment->mediaId ] = $lo_mediaAssignment;
				}

				continue;
			}

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
		$la_options['fields'] = [
			'id',
			'mediaElementId',
			'mediaElementSelectorIdentifier',
			'mediaId',
			'mediaFolderId',
			'scope',
			'foreignKey',
			'systemOrder',
		];

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

						$la_elementData = $this->mapKeys($la_elementData);

						if (array_is_list($la_elementData)) {
							$la_mediaIds = $la_elementData;
						}
						elseif (isset($la_elementData['mediaId'])) {
							$la_mediaIds = (array)$la_elementData['mediaId'];
						}
						elseif (!empty($la_elementData['mediaFolderId'])) {
							$lb_isFolder = true;
							$la_mediaIds = (array)$la_elementData['mediaFolderId'];
						}
						else {
							continue;
						}

						foreach ($la_mediaIds as $li_mediaId) {
							if (empty($li_mediaId)) {
								continue;
							}

							/** @var \Awyiss\Model\Entity\MediaAssignment $lo_entity */
							$lo_entity = $this->assignmentsTable->newDefaultEntity();

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
							$lo_entity->unset('createdBy');
							$lo_entity->unset('createdOn');

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

		static::$mediaElements = $lo_elements->each(function (MediaElement $entity): void {
			$lo_selectors = collection($entity->mediaElementSelectors);
			$lo_selectors = $lo_selectors->indexBy(function (MediaElementSelector $selector): string {
				return $selector->identifier;
			})->map(function (MediaElementSelector $selector): MediaSelector {
				return $selector->mediaSelector;
			});

			/** @noinspection PhpUndefinedFieldInspection */
			$entity->mediaSelectors = $lo_selectors->toArray();
		})->toArray();
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (
			// If no media assignments are set, skip the processing
			!$entity->has('mediaAssignments') ||
			// If the options explicitly state to skip media assignments, skip the processing
			($options['mediaAssignments']['skip'] ?? false) === true
		) {
			return;
		}

		$lo_identity = $this->getIdentity();
		// If the user doesn't have access to the media scope, remove the media assignments from the entity
		if (
			!$lo_identity ||
			!$lo_identity->scopeIsAccessible('Media', [], 'read')
		) {
			unset($entity->mediaAssignments);
			$entity->setDirty('mediaAssignments', false);
			return;
		}

		/**
		 * Make sure media assignments in the wrong format
		 * are removed from the entity.
		 *
		 * This happens when no media element was assigned but
		 * a media assignment is part of the entity/patched data.
		 */
		$la_mediaAssignments = $entity->get('mediaAssignments') ?: [];
		foreach ($la_mediaAssignments as $lx_key => $lo_mediaAssignment) {
			if (!is_numeric($lx_key) || !$lo_mediaAssignment instanceof MediaAssignment) {
				unset($la_mediaAssignments[ $lx_key ]);
			}
		}

		$entity->set('mediaAssignments', $la_mediaAssignments);

		if (($options['isCopy'] ?? false) === true) {
			// If the entity is a copy, we need to set the media assignments as new
			foreach ($la_mediaAssignments as $lo_mediaAssignment) {
				if (!$lo_mediaAssignment instanceof MediaAssignment) {
					continue;
				}

				$lo_mediaAssignment->unset('id');
				$lo_mediaAssignment->setNew(true);
			}
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
		/** @var \Awyiss\Model\Entity\MediaAssignment $lo_existingAssignment */
		$lo_existingAssignment = $lo_mediaAssignmentsTable->find()->where([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => $entity->id,
			'scope' => $this->getConfig('referenceName'),
		])->contain(['MediaFolders'])->first();

		if ($lo_existingAssignment) {
			$lo_folder = $lo_existingAssignment->mediaFolder;
			$lb_changed = false;

			if (!empty($entity->title) && $lo_folder->title !== $entity->title) {
				$lo_folder->title = $entity->title;
				$lb_changed = true;
			}

			if (!empty($entity->slug)) {
				$lo_folder->path = $entity->slug;
				$lb_changed = true;
			}

			if ($lb_changed) {
				$lo_mediaFoldersTable = $this->fetchTable('MediaFolders');
				$lo_mediaFoldersTable->save($lo_folder);
			}

			return;
		}

		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_mediaFoldersTable */
		$lo_mediaFoldersTable = $this->fetchTable('MediaFolders');
		$lo_folder = $lo_mediaFoldersTable->newDefaultEntity([
			'hidden' => true,
			'languageShortcode' => $entity->languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode,
			'title' => $entity->title ?? 'HiddenFolder' . $entity->id,
		]);

		$li_parentMediaFolderId = Configure::read(implode('.', ['Awyiss', $entity->getSource(), 'Frontend', 'mediaFolders', 'parentFolderId']));
		if ($li_parentMediaFolderId) {
			$lo_folder->parentId = $li_parentMediaFolderId;
		}

		if (!empty($entity->slug)) {
			$lo_folder->path = $entity->slug;
		}

		if (!$lo_mediaFoldersTable->save($lo_folder, ['checkRules' => false])) {
			return;
		}

		$lo_mediaFoldersTable->dispatchEvent('Model.MediaFolders.afterSaveCommit', ['entity' => $lo_folder, 'options' => $options]);

		$lo_assignment = $lo_mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 1,
			'mediaElementSelectorIdentifier' => 'hidden_folder',
			'mediaFolderId' => $lo_folder->id,
			'foreignKey' => $entity->id,
			'scope' => $this->getConfig('referenceName'),
		]);

		$lo_mediaAssignmentsTable->save($lo_assignment);
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->deleteHiddenFolders($entity);
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $lo_mediaAssignmentsTable */
		$this->deleteHiddenFolders($entity);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function getContainConditions(SelectQuery $query): SelectQuery {
		$la_identifiers = array_unique(Hash::extract(static::$mediaElements, '{n}.mediaElementSelectors.{n}.identifier'));

		$lo_dialect = $query->getConnection()->getDriver()->schemaDialect();

		$la_aliasedField = $query->aliasField('media_element_id');
		$ls_elementField = key($la_aliasedField);

		$la_aliasedField = $query->aliasField('media_element_selector_identifier');
		$ls_selectorField = key($la_aliasedField);

		/**
		 * SQLite does not support FIND_IN_SET(),
		 * so ordering using CASE WHEN is used instead
		 */
		if ($lo_dialect instanceof SqliteSchemaDialect) {
			$query->orderBy(function (QueryExpression $exp) use ($ls_elementField) {
				$li_index = 0;

				$lo_case = $exp->case();
				foreach (static::$mediaElements as $lo_mediaElement) {
					$lo_case->when([$ls_elementField => $lo_mediaElement->id])->then($li_index, 'integer');

					$li_index++;
				}

				$lo_case->else(999, 'integer');

				return $lo_case;
			}, true);

			$query->orderBy(function (QueryExpression $exp) use ($ls_selectorField, $la_identifiers) {
				$li_index = 0;

				$lo_case = $exp->case();
				foreach ($la_identifiers as $ls_identifier) {
					$lo_case->when([$ls_selectorField => $ls_identifier])->then($li_index, 'integer');

					$li_index++;
				}

				$lo_case->else(999, 'integer');

				return $lo_case;
			});

			return $query->contain(['Media', 'MediaFolders']);
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$query->orderByAsc($query->newExpr($query->func()->FIND_IN_SET([
			$ls_elementField => 'identifier',
			implode(',', array_column(static::$mediaElements, 'id')),
		])), true);

		/** @noinspection PhpUndefinedMethodInspection */
		$query->orderByAsc($query->newExpr($query->func()->FIND_IN_SET([
			$ls_selectorField => 'identifier',
			implode(',', $la_identifiers),
		])));

		return $query->contain(['Media', 'MediaFolders']);
	}


	/**
	 * @param array $elementData
	 * @return array
	 */
	protected function mapKeys(array $elementData): array {
		$la_data = [];

		foreach ($elementData as $lx_key => $lx_value) {
			if (!is_string($lx_key)) {
				$la_data[ $lx_key ] = $lx_value;
				continue;
			}

			$lx_key = Inflector::variable($lx_key);
			$la_data[ $lx_key ] = $lx_value;
		}

		return $la_data;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	protected function deleteHiddenFolders(EntityInterface $entity): void {
		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $lo_mediaAssignmentsTable */
		$lo_mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		/** @var \Awyiss\Model\Entity\MediaAssignment $lo_existingAssignment */
		$lo_existingAssignment = $lo_mediaAssignmentsTable->find()->where([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => $entity->id,
			'scope' => $this->getConfig('referenceName'),
		])->contain(['MediaFolders'])->first();

		if ($lo_existingAssignment) {
			// Delete the folder as well
			$lo_mediaFoldersTable = $this->fetchTable('MediaFolders');
			$lo_mediaFoldersTable->delete($lo_existingAssignment->mediaFolder);
		}
	}


	/**
	 * @param bool $splitIntoLanguages
	 * @return void
	 */
	protected function updateHiddenFolderSettings(bool $splitIntoLanguages): void {
		/** @var array<\Awyiss\Model\Entity\Configuration> $la_records */
		$la_records = $this->table()->find()->all()->indexBy('id')->toArray();
		$la_configurationRecords = $this->fetchTable('Configuration')->find()->where([
			'realm' => Awyiss::REALM_FRONTEND,
			'scope' => Inflector::underscore($this->table()->getTable()),
			'identifier' => 'media_folders.parent_folder_id',
		])->all()->indexBy(function (Configuration $configuration): string {
			return $configuration->languageShortcode ?? 'global';
		})->toArray();

		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $lo_mediaAssignmentsTable */
		$lo_mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$lo_existingAssignments = $lo_mediaAssignmentsTable->find()->where([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'scope' => $this->getConfig('referenceName'),
		])->contain(['MediaFolders'])->all();

		if (!$lo_existingAssignments->count()) {
			return;
		}


		$la_mediaFolders = [];
		/** @var \Awyiss\Model\Entity\MediaAssignment $lo_mediaAssignment */
		foreach ($lo_existingAssignments as $lo_mediaAssignment) {
			$lo_folder = $lo_mediaAssignment->mediaFolder;
			$lo_record = $la_records[ $lo_mediaAssignment->foreignKey ] ?? null;

			if (!$lo_record) {
				continue;
			}

			$lo_folder->languageShortcode = $splitIntoLanguages ? $lo_record->languageShortcode : null;

			$li_mediaFolderParentId = null;
			if (isset($la_configurationRecords[ $lo_folder->languageShortcode ?? 'global' ])) {
				$li_mediaFolderParentId = (int)$la_configurationRecords[ $lo_folder->languageShortcode ?? 'global' ]->value;
			}

			$lo_folder->parentId = $li_mediaFolderParentId;

			$la_mediaFolders[] = $lo_folder;
		}

		if ($la_mediaFolders) {
			$lo_mediaFoldersTable = $this->fetchTable('MediaFolders');
			try {
				$lo_mediaFoldersTable->saveMany($la_mediaFolders, ['checkRules' => false]);
			}
			catch (Exception) {
				// Ignore errors
			}
		}
	}
}
