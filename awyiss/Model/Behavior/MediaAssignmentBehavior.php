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
	protected array $_defaultConfig = [ // phpcs:ignore
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
		$config += [
			'referenceName' => $this->getScope($table),
			'tableLocator' => $table->associations()->getTableLocator(),
		];

		parent::__construct($table, $config);
	}


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->_tableLocator = $this->getConfig('tableLocator');

		$this->assignmentsTable = $this->getTableLocator()->get('MediaAssignments', ['allowFallbackClass' => false]);

		$contain = [
			'MediaAssignments.scope' => $this->getScope($this->table()),
		];

		if ($contain['MediaAssignments.scope'] === 'Pages') {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');
			$scope = array_map(function ($pageRole) {
				return Inflector::camelize(Inflector::pluralize($pageRole->name));
			}, $pageRoleEnum::cases());
			$contain = ['MediaAssignments.scope IN' => $scope];
		}

		$this->_table->hasMany('MediaAssignments', [
			'conditions' => $contain,
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'foreignKey',
			'propertyName' => 'mediaAssignments',
			'saveStrategy' => 'replace',
			'strategy' => $this->getConfig('strategy'),
		]);

		$this->setConfig('implementedEvents', [
			'Configuration.' . $this->table()->getAlias() . '.Backend.splitIntoLanguages.afterSaveCommit'
				=> 'resetHiddenMediaFolderLanguageAfterSave',
			'Configuration.' . $this->table()->getAlias() . '.Backend.splitIntoLanguages.afterDeleteCommit'
				=> 'resetHiddenMediaFolderLanguageAfterDelete',
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
			!$configuration->isNew()
			&& (
				!$configuration->hasOriginal('value')
				|| $configuration->getOriginal('value') === $configuration->value
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
		$configOptions = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		$configOption = $configOptions?->getConfigOption(Awyiss::REALM_BACKEND, $configuration->identifier);
		$defaultSplit = $configOption?->getDefaultValue() ?? false;

		$this->updateHiddenFolderSettings($defaultSplit);
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
	 * in a variableCased version of the media element identifier.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param bool $includeElementSelector Whether to include the media selectors in the result
	 * @param bool $useMediaEntity Whether to use the media entity instead of the media assignment entity as the value of the media
	 *     assignment identifier
	 * @param bool $formatResult Whether to format the result so that the media assignments are nested under the media element identifier
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findMediaAssignments(
		SelectQuery $query,
		bool $includeElementSelector = false,
		bool $useMediaEntity = false,
		bool $formatResult = true
	): SelectQuery {
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
			$query->formatResults(fn(CollectionInterface $results) => $this->rowMapper($results, $useMediaEntity), $query::PREPEND);
		}

		return $query;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface|array $entity
	 * @param bool $useMediaEntity
	 * @return \Cake\Datasource\EntityInterface|array
	 */
	public function rebuildMediaAssignments(EntityInterface|array $entity, bool $useMediaEntity = false): EntityInterface|array {
		$mediaAssignments = [];

		if (!isset(static::$mediaElements)) {
			$this->buildElements();
		}

		/** @var \Awyiss\Model\Entity\MediaAssignment $mediaAssignment */
		foreach (($entity['mediaAssignments'] ?? []) as $mediaAssignment) {
			if (is_array($mediaAssignment)) {
				// Seems like the media assignments have already been rebuilt
				return $entity;
			}

			$element = static::$mediaElements[ $mediaAssignment->mediaElementId ];
			$elementIdentifier = Inflector::variable($element->identifier);

			$selector = $element->mediaSelectors[ $mediaAssignment->mediaElementSelectorIdentifier ] ?? null;
			$selectorIdentifier = Inflector::variable($mediaAssignment->mediaElementSelectorIdentifier);

			// Treat inlineImgTag as a special case
			if (!$selector || $elementIdentifier === 'inlineImgTag') {
				if ($elementIdentifier === 'inlineImgTag') {
					$mediaAssignments[ $elementIdentifier ] ??= [];
					$mediaAssignments[ $elementIdentifier ][ $mediaAssignment->mediaId ] = $mediaAssignment;
				}

				continue;
			}

			if ($selector->identifier === 'multiFile') {
				if ($useMediaEntity) {
					$mediaAssignments[ $elementIdentifier ][ $selectorIdentifier ][] = $mediaAssignment->media;
					$mediaAssignments[ $elementIdentifier ][ '_' . $selectorIdentifier ][] = $mediaAssignment;
				}
				else {
					$mediaAssignments[ $elementIdentifier ][ $selectorIdentifier ][] = $mediaAssignment;
				}
			}
			elseif ($selector->identifier === 'folder') {
				if ($useMediaEntity) {
					$mediaAssignments[ $elementIdentifier ][ $selectorIdentifier ] = $mediaAssignment->mediaFolder;
					$mediaAssignments[ $elementIdentifier ][ '_' . $selectorIdentifier ] = $mediaAssignment;
				}
				else {
					$mediaAssignments[ $elementIdentifier ][ $selectorIdentifier ] = $mediaAssignment;
				}
			}
			else {
				if ($useMediaEntity) {
					$mediaAssignments[ $elementIdentifier ][ $selectorIdentifier ] = $mediaAssignment->media;
					$mediaAssignments[ $elementIdentifier ][ '_' . $selectorIdentifier ] = $mediaAssignment;
				}
				else {
					$mediaAssignments[ $elementIdentifier ][ $selectorIdentifier ] = $mediaAssignment;
				}
			}
		}

		$entity['mediaAssignments'] = $mediaAssignments;

		return $entity;
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


		return Inflector::camelize($name);
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

		return $results->map(function (EntityInterface|array|null $row) use ($useMediaEntity): EntityInterface|array|null {
			$originalRow = $row;

			if ($row === null || empty($row['mediaAssignments'])) {
				return $row;
			}

			$row = $this->rebuildMediaAssignments($row, $useMediaEntity);

			if ($originalRow instanceof EntityInterface) {
				$originalRow->setDirty('mediaAssignments', false);
			}

			return $row;
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
			return [
				'mediaAssignments' => function (array $values): array {
					return $values;
				},
			];
		}


		$identity = $this->getIdentity();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if (
			!$identity
			|| !method_exists($identity, 'scopeIsAccessible')
			|| !$identity->scopeIsAccessible('Media', [], 'read')
		) {
			return [
				'mediaAssignments' => function (array $values): array {
					return $values;
				},
			];
		}

		unset($options['associated']);
		$options['fields'] = [
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
			'mediaAssignments' => function (array $values, EntityInterface $entity) use ($options): array {
				/**
				 * @var array<string, \Awyiss\Model\Entity\MediaAssignment> $mediaAssignments
				 */
				$mediaAssignments = [];

				$errors = [];
				$marshaller = $this->assignmentsTable->marshaller();

				foreach ($values as $mediaElementId => $elementsData) {
					foreach ($elementsData as $mediaElementSelectorIdentifier => $elementData) {
						$systemOrder = 1;
						$isFolder = false;

						$elementData = $this->mapKeys($elementData);

						if (array_is_list($elementData)) {
							$mediaIds = $elementData;
						}
						elseif (isset($elementData['mediaId'])) {
							$mediaIds = (array)$elementData['mediaId'];
						}
						elseif (!empty($elementData['mediaFolderId'])) {
							$isFolder = true;
							$mediaIds = (array)$elementData['mediaFolderId'];
						}
						else {
							continue;
						}

						foreach ($mediaIds as $mediaId) {
							if (empty($mediaId)) {
								continue;
							}

							/** @var \Awyiss\Model\Entity\MediaAssignment $mediaAssignment */
							$mediaAssignment = $this->assignmentsTable->newDefaultEntity();

							if (!empty($elementData['id'])) {
								$mediaAssignment->id = (int)$elementData['id'];
								$mediaAssignment->setNew(false);
							}

							$mediaAssignmentData = [
								'mediaElementId' => $mediaElementId,
								'mediaElementSelectorIdentifier' => $mediaElementSelectorIdentifier,
								'mediaId' => !$isFolder ? (int)$mediaId : null,
								'mediaFolderId' => $isFolder ? (int)$mediaId : null,
								'scope' => $this->getConfig('referenceName'),
								'systemOrder' => $systemOrder,
							];

							$marshaller->merge($mediaAssignment, $mediaAssignmentData, $options);

							$dataErrors = $mediaAssignment->getErrors();
							if ($dataErrors) {
								$errors[] = $dataErrors;
							}
							$mediaAssignment->unset('createdBy');
							$mediaAssignment->unset('createdOn');

							$mediaAssignments[] = $mediaAssignment;

							$systemOrder++;
						}
					}
				}

				//Set errors into the root entity, so validation errors match the original form data position.
				if ($errors) {
					$entity->setErrors(['mediaAssignments' => $errors]);
				}

				$entity->setDirty('mediaAssignments');

				return $mediaAssignments;
			},
		];
	}


	/**
	 * Build the media elements array
	 *
	 * @return void
	 */
	protected function buildElements(): void {
		$elements = $this
			->fetchTable('MediaElements')
			->find()
			->contain([
				'MediaElementSelectors' => [
					'MediaSelectors',
				],
			])
			->all()
			->indexBy('id')
		;

		static::$mediaElements = $elements
			->each(function (MediaElement $entity): void {
				$selectors = collection($entity->mediaElementSelectors);
				$selectors = $selectors
					->indexBy(function (MediaElementSelector $selector): string {
						return $selector->identifier;
					})
					->map(function (MediaElementSelector $selector): MediaSelector {
						return $selector->mediaSelector;
					})
				;

				$entity->mediaSelectors = $selectors->toArray();
			})
			->toArray()
		;
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
			!$entity->has('mediaAssignments')
			// If the options explicitly state to skip media assignments, skip the processing
			|| ($options['mediaAssignments']['skip'] ?? false) === true
		) {
			return;
		}

		$identity = $this->getIdentity();
		// If the user doesn't have access to the media scope, remove the media assignments from the entity
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if (!$identity?->scopeIsAccessible('Media', [], 'read')) {
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
		$mediaAssignments = $entity->get('mediaAssignments') ?: [];
		foreach ($mediaAssignments as $key => $mediaAssignment) {
			if (!is_numeric($key) || !$mediaAssignment instanceof MediaAssignment) {
				unset($mediaAssignments[ $key ]);
			}
		}

		$entity->set('mediaAssignments', $mediaAssignments);

		if (($options['isCopy'] ?? false) === true) {
			// If the entity is a copy, we need to set the media assignments as new
			foreach ($mediaAssignments as $mediaAssignment) {
				if (!$mediaAssignment instanceof MediaAssignment) {
					continue;
				}

				$mediaAssignment->unset('id');
				$mediaAssignment->setNew(true);
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

		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $mediaAssignmentsTable */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		/** @var \Awyiss\Model\Entity\MediaAssignment $existingAssignment */
		$existingAssignment = $mediaAssignmentsTable
			->find()
			->where([
				'mediaElementId' => 1,
				'mediaElementSelectorIdentifier' => 'hiddenFolder',
				'foreignKey' => $entity->id,
				'scope' => $this->getConfig('referenceName'),
			])
			->contain(['MediaFolders'])
			->first()
		;

		if ($existingAssignment) {
			$folder = $existingAssignment->mediaFolder;
			$changed = false;

			if (!empty($entity->title) && $folder->title !== $entity->title) {
				$folder->title = $entity->title;
				$changed = true;
			}

			if (!empty($entity->slug)) {
				$folder->path = $entity->slug;
				$changed = true;
			}

			if ($changed) {
				$mediaFoldersTable = $this->fetchTable('MediaFolders');
				$mediaFoldersTable->save($folder);
			}

			return;
		}

		/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFoldersTable */
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$folder = $mediaFoldersTable->newDefaultEntity([
			'hidden' => true,
			'languageShortcode' => $entity->languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode,
			'title' => $entity->title ?? 'HiddenFolder' . $entity->id,
		]);

		$parentMediaFolderId = Configure::read(
			implode('.', ['Awyiss', $entity->getSource(), 'Frontend', 'mediaFolders', 'parentFolderId'])
		);
		if ($parentMediaFolderId) {
			$folder->parentId = $parentMediaFolderId;
		}

		if (!empty($entity->slug)) {
			$folder->path = $entity->slug;
		}

		if (!$mediaFoldersTable->save($folder, ['checkRules' => false])) {
			return;
		}

		$mediaFoldersTable->dispatchEvent('Model.MediaFolders.afterSaveCommit', ['entity' => $folder, 'options' => $options]);

		$assignment = $mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 1,
			'mediaElementSelectorIdentifier' => 'hiddenFolder',
			'mediaFolderId' => $folder->id,
			'foreignKey' => $entity->id,
			'scope' => $this->getConfig('referenceName'),
		]);

		$mediaAssignmentsTable->save($assignment);
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
		$this->deleteHiddenFolders($entity);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function getContainConditions(SelectQuery $query): SelectQuery {
		$identifiers = array_unique(Hash::extract(static::$mediaElements, '{n}.mediaElementSelectors.{n}.identifier'));


		$aliasedField = $query->aliasField('mediaElementId');
		$elementField = key($aliasedField);

		$aliasedField = $query->aliasField('mediaElementSelectorIdentifier');
		$selectorField = key($aliasedField);

		$query->contain(['Media', 'MediaFolders']);

		$query->orderBy(function (QueryExpression $exp) use ($elementField) {
			$index = 0;

			$case = $exp->case();
			foreach (static::$mediaElements as $mediaElement) {
				$case->when([$elementField => $mediaElement->id])->then($index, 'integer');

				$index++;
			}

			$case->else(999, 'integer');

			return $case;
		}, true);

		$query->orderBy(function (QueryExpression $exp) use ($selectorField, $identifiers) {
			$index = 0;

			$case = $exp->case();
			foreach ($identifiers as $identifier) {
				$case->when([$selectorField => $identifier])->then($index, 'integer');

				$index++;
			}

			$case->else(999, 'integer');

			return $case;
		});

		return $query;
	}


	/**
	 * @param array $elementData
	 * @return array
	 */
	protected function mapKeys(array $elementData): array {
		$data = [];

		foreach ($elementData as $key => $value) {
			if (!is_string($key)) {
				$data[ $key ] = $value;
				continue;
			}

			$key = Inflector::variable($key);
			$data[ $key ] = $value;
		}

		return $data;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	protected function deleteHiddenFolders(EntityInterface $entity): void {
		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $mediaAssignmentsTable */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		/** @var \Awyiss\Model\Entity\MediaAssignment $existingAssignment */
		$existingAssignment = $mediaAssignmentsTable
			->find()
			->where([
				'mediaElementId' => 1,
				'mediaElementSelectorIdentifier' => 'hiddenFolder',
				'foreignKey' => $entity->id,
				'scope' => $this->getConfig('referenceName'),
			])
			->contain(['MediaFolders'])
			->first()
		;

		if ($existingAssignment) {
			// Delete the folder as well
			$mediaFoldersTable = $this->fetchTable('MediaFolders');
			$mediaFoldersTable->delete($existingAssignment->mediaFolder);
		}
	}


	/**
	 * @param bool $splitIntoLanguages
	 * @return void
	 */
	protected function updateHiddenFolderSettings(bool $splitIntoLanguages): void {
		/** @var array<\Awyiss\Model\Entity\Configuration> $records */
		$records = $this
			->table()
			->find()
			->all()
			->indexBy('id')
			->toArray()
		;
		$configurationRecords = $this
			->fetchTable('Configuration')
			->find()
			->where([
				'realm' => Awyiss::REALM_FRONTEND,
				'scope' => Inflector::camelize($this->table()->getTable()),
				'identifier' => 'mediaFolders.parentFolderId',
			])
			->all()
			->indexBy(function (Configuration $configuration): string {
				return $configuration->languageShortcode ?? 'global';
			})
			->toArray()
		;

		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $mediaAssignmentsTable */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$existingAssignments = $mediaAssignmentsTable
			->find()
			->where([
				'mediaElementId' => 1,
				'mediaElementSelectorIdentifier' => 'hiddenFolder',
				'scope' => $this->getConfig('referenceName'),
			])
			->contain(['MediaFolders'])
			->all()
		;

		if (!$existingAssignments->count()) {
			return;
		}


		$mediaFolders = [];
		/** @var \Awyiss\Model\Entity\MediaAssignment $mediaAssignment */
		foreach ($existingAssignments as $mediaAssignment) {
			$folder = $mediaAssignment->mediaFolder;
			$record = $records[ $mediaAssignment->foreignKey ] ?? null;

			if (!$record) {
				continue;
			}

			$folder->languageShortcode = $splitIntoLanguages ? $record->languageShortcode : null;

			$mediaFolderParentId = null;
			if (isset($configurationRecords[ $folder->languageShortcode ?? 'global' ])) {
				$mediaFolderParentId = (int)$configurationRecords[ $folder->languageShortcode ?? 'global' ]->value;
			}

			$folder->parentId = $mediaFolderParentId;

			$mediaFolders[] = $folder;
		}

		if ($mediaFolders) {
			$mediaFoldersTable = $this->fetchTable('MediaFolders');
			try {
				$mediaFoldersTable->saveMany($mediaFolders, ['checkRules' => false]);
			}
			catch (Exception) {
				// Ignore errors
			}
		}
	}
}
