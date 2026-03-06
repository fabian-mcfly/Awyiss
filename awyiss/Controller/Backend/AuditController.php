<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\Audit;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Entity\MediaElement;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\Model\Table\MediaAssignmentsTable;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Routing\Router;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use InvalidArgumentException;
use RuntimeException;


/**
 * Audit Controller
 *
 * @property \Awyiss\Model\Table\AuditTable $Audit
 */
class AuditController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Audit->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Show the history of a record
	 *
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function history(): void {
		$id = (int)$this->request->getParam('id');
		$scope = $this->request->getParam('scope');
		$realScope = $scope;

		if (!$id || $scope === null) {
			if ($this->request->is('ajax')) {
				throw new InvalidArgumentException(__('error_invalid_request'));
			}

			$this->Flash->error(__('error_invalid_request'));
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
		}

		/** @var \Awyiss\Model\Table $table */
		$table = $this->fetchTable(Inflector::camelize($scope));

		/**
		 * @uses \Awyiss\Model\Table::findTranslations()
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$query = $table->findById($id);

		if ($table->hasBehavior('Translate')) {
			$query->find('translations');
		}
		if ($table->hasBehavior('MediaAssignment')) {
			$query->find('mediaAssignments');
		}

		$entity = $query->first();

		if (!$entity) {
			if ($this->request->is('ajax')) {
				throw new RuntimeException(__('error_record_not_found'));
			}

			$this->Flash->error(__('error_record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
		}

		/**
		 * Check if the scope is accessible
		 * For all scopes except 'contents', the scope must be accessible for the 'update' action
		 */
		if ($scope === 'Contents') {
			// Ensure that the user has access to the `content`-permission of the page-role of the content's page.
			$this->ensurePageRoleAccess($entity);
		}
		elseif ($scope === 'Configuration') {
			$this->Authorization->scopeIsAccessible($scope, [
				'scope' => $entity->scope,
			], 'update');
		}
		else {
			$this->Authorization->scopeIsAccessible($scope, [], 'update');
		}

		$historyFields = $table->getAuditHistoryFields();
		// Deleted field is tracked but not displayed
		$historyFields = array_diff($historyFields, ['deleted']);

		$associations = $this->getAssociations($table, $historyFields);

		if ($associations) {
			$table->loadInto($entity, array_values(array_map(fn(Association $association) => $association->getName(), $associations)));
		}

		$isPageRole = false;
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($pageRoleEnum::tryFromName($scope)) {
			$isPageRole = true;
			$realScope = 'pages';
		}

		// Get the audit history of the record
		$audits = $this->findAudits([
			'foreignKey' => $id,
			'scope' => $realScope,
		]);

		$this->addPivotTableAudits($audits, $table, $entity, $associations);

		// Check audits for changes in the associations and load the associated entities
		$this->loadOldAssociationEntities($entity, $audits, $associations);

		/** @var array<\Awyiss\Model\Entity\Attribute> $attributes */
		$attributes = $table->hasAttributes() ? $table->getAttributes() : [];
		if ($attributes) {
			/** @var \Awyiss\Attribute\AttributeOptionsCollection $attributeOptions */
			$attributeOptions = AttributeOptionsProvider::getAttributeOptionsFile($scope, true);
		}

		if (in_array($scope, ['Contents', 'GlobalContents'], true)) {
			$audits = $this->setColumnData($table, $audits);
		}

		$media = $this->getMedia($entity, $audits);
		$mediaElements = $this->getMediaElements($entity, $audits);

		$translatableFields = [];
		$languages = [];
		if ($table->hasBehavior('Translate')) {
			/** @var \Awyiss\Model\Behavior\TranslateBehavior $behavior */
			$behavior = $table->getBehavior('Translate');
			$translatableFields = $behavior->getConfig('fields');

			$realm = $behavior->getConfig('realm') ?? Awyiss::REALM_BACKEND;
			$languages = LocaleMiddleware::getLanguages($realm);

			// Filter out inactive languages
			$languages = array_filter($languages, fn(Language $language) => $language->active);
		}

		$translatableAttributes = [];
		if ($table->hasAttributes()) {
			// Get all identifiers of translatable attributes
			$translatableAttributes = array_column($attributes, null, 'identifier');
			$translatableAttributes = array_keys(array_filter($translatableAttributes, fn(Attribute $attribute) => $attribute->translatable));
		}

		$associations = array_map(fn (Association $association) => [
			'association' => $association,
			'name' => $association->getName(),
			'property' => $association->getProperty(),
			'type' => $association->type(),
		], $associations);

		$this->set([
			'entity' => $entity,
			'audits' => $audits->compile(),
			'schema' => $table->getSchema(),
			'scope' => $scope,
			'historyFields' => $historyFields,
			'attributes' => $attributes,
			'attributeOptionsCollection' => $attributeOptions ?? null,
			'attributesSchema' => $table->hasAttributes() ? $table->getAttributesTable()->getSchema() : null,
			'associations' => $associations,
			'media' => $media->toArray(),
			'mediaElements' => $mediaElements,
			'isAjax' => $this->request->is('ajax'),
			'isPageRole' => $isPageRole,
			'publicationDataEnabled' => LocalConfig::read('publicationData.enabled', null, Inflector::camelize($scope)),
			'languages' => $languages,
			'translatableFields' => $translatableFields,
			'translatableAttributes' => $translatableAttributes,
			'datatables' => $this->fetchTable('Datatables')->findAllAndCache()->indexBy('identifier')->toArray(),
			'pageRoles' => $this->fetchTable('PageRoles')->findAllAndCache()->indexBy(function (PageRole $pageRole) {
				return Inflector::pluralize($pageRole->identifier);
			})->toArray(),
		]);

		if ($this->request->is('ajax')) {
			$this->viewBuilder()->setLayout('overlay_configuration');
		}
	}


	/**
	 * This method handles the info action for the AuditController. It fetches the record
	 * based on the provided id and scope from the request parameters. If the id or scope
	 * is not provided, it redirects to the Dashboard index.
	 *
	 * If the request is an AJAX request, it returns a JSON response with the audit information
	 * of the record. The audit information includes the createdBy, createdOn, changedBy,
	 * changedOn.
	 * If the createdBy or changedBy fields are empty, they are set to 'System' if the corresponding
	 * createdBy or changedBy fields are empty, otherwise they are set to 'Unknown'.
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException|\Exception If the id or scope is not provided in the request parameters
	 */
	#[NoDirectAccess]
	public function info(): void {
		$parts = $this->request->getParam('parts');
		$id = $parts['id'] ?? null;
		$scope = $parts['scope'] ?? null;

		if ($id === null || $scope === null) {
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'index']));
		}

		/**
		 * @uses \Awyiss\Model\Behavior\AuditBehavior::findWithAuditUsers()
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$record = $this->fetchTable(Inflector::camelize($scope))->findById($id)->find('withAuditUsers')->first($id);
		if (!$record) {
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'index']));
		}

		// If the request is an AJAX request, return a JSON response
		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['createdBy', 'createdOn', 'changedBy', 'changedOn', 'created', 'changed']);

			// Get the createdBy and changedBy users
			$createdByUser = $record->get('createdByUser');
			$changedByUser = $record->get('changedByUser');

			// If createdBy is empty, set it to 'System' if createdBy is empty, otherwise to "Unknown"
			if (empty($createdByUser)) {
				$createdByUser = $record->get('createdBy') ? __('user_unknown') : __('user_system');
			}

			// If changedBy is empty, set it to 'System' if createdBy is empty, otherwise to "Unknown"
			if (empty($changedByUser)) {
				$changedByUser = $record->get('changedBy') ? __('user_unknown') : __('user_system');
			}

			$timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
			if ($timezone === 'auto') {
				$timezone = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)->timezone;
			}

			// Set the data to be serialized
			$this->set([
				'createdBy' => $createdByUser,
				'createdOn' => $record->get('createdOn')?->nice($timezone),
				'changedBy' => $changedByUser,
				'changedOn' => $record->get('changedOn')?->nice($timezone),
				'created' => __('created_info_label'),
				'changed' => __('changed_info_label'),
			]);

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');


			return;
		}

		$this->set([
			'record' => $record,
			'scope' => $scope,
		]);
	}


	/**
	 * Ensure that the user has access to the `content`-permission
	 * of the page-role of the content's page
	 *
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return void
	 * @throws \Exception
	 */
	protected function ensurePageRoleAccess(Content $entity): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = $this->fetchTable('Contents');

		try {
			$page = $table->getPage($entity->pageId);
		}
		catch (RecordNotFoundException | InvalidPrimaryKeyException) {
			if ($this->request->is('ajax')) {
				throw new RuntimeException(__('error_record_not_found'));
			}

			$this->Flash->error(__('error_record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
		}

		$table->forPageRole($page->pageRoleId);
		$this->Authorization->scopeIsAccessible($table->getForScope(), [], 'contents');

		$entity->page = $page;
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param array $historyFields
	 * @return array
	 */
	protected function getAssociations(Table $table, array $historyFields): array {
		$tableName = Inflector::camelize($table->getTable());

		$blocklistAssociations = [
			'Attributes' . $tableName,
			'Child' . $tableName,
			'Duplicating' . $tableName,
			'Parent' . $tableName,
			$tableName . 'PublicationDataStart',
			$tableName . 'PublicationDataEnd',
			'PublicationData',
		];

		$associations = [];
		foreach ($table->associations() as $association) {
			if (in_array($association->getName(), $blocklistAssociations)) {
				continue;
			}

			if ($association instanceof BelongsToMany) {
				$foreignKey = $association->getProperty();
			}
			elseif (
				$association instanceof HasMany &&
				$association->getCascadeCallbacks() &&
				$association->getDependent() &&
				!in_array($association->getTarget()->getTable(), [
					MediaAssignmentsTable::TABLE,
				])
			) {
				$foreignKey = $association->getProperty();
			}
			else {
				$foreignKey = (array)$association->getForeignKey()[0];
			}

			// If the key of the association is in the history fields, add it to the associations using the key as the key
			if (in_array($foreignKey, $historyFields)) {
				$associations[ $foreignKey ] = $association;
			}
		}

		return $associations;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMedia(Entity $entity, CollectionInterface $audits): CollectionInterface {
		$mediaIds = array_merge(
			Hash::extract($entity->mediaAssignments ?? [], '{s}.{n}.mediaId'),
			Hash::extract($entity->mediaAssignments ?? [], '{s}.{s}.mediaId'),
			Hash::extract($entity->mediaAssignments ?? [], '{s}.{s}.{n}.mediaId')
		);

		$audits = $audits->toList();
		$mediaAssignments = Hash::merge(
			Hash::extract($audits, '{n}.dataOld.mediaAssignments'),
			Hash::extract($audits, '{n}.dataNew.mediaAssignments'),
		);

		$mediaIds = array_merge($mediaIds, Hash::extract($mediaAssignments, '{n}.{s}.{*}.mediaId'), Hash::extract($mediaAssignments, '{n}.{s}.{s}.{n}.mediaId'));
		$mediaIds = array_unique($mediaIds);

		if (!$mediaIds) {
			return new Collection([]);
		}

		return $this->fetchTable('Media')->find()->where(['id IN' => $mediaIds])->all()->indexBy('id');
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMediaElements(Entity $entity, CollectionInterface $audits): CollectionInterface {
		$mediaElements = $this->fetchTable('MediaElements')->find()->contain([
			'MediaElementSelectors.MediaSelectors',
		])->all()->indexBy('identifier');

		$audits = $audits->toList();
		$oldMediaElements = Hash::merge(
			Hash::extract($audits, '{n}.dataOld.mediaAssignments'),
			Hash::extract($audits, '{n}.dataNew.mediaAssignments')
		);
		$oldMediaElements = array_unique(array_merge(...array_map(array_keys(...), $oldMediaElements)));

		$currentMediaElements = array_keys($entity->mediaAssignments ?? []);

		return $mediaElements->filter(function (MediaElement $mediaElement) use ($oldMediaElements, $currentMediaElements) {
			$identifier = Inflector::variable($mediaElement->identifier);

			return in_array($identifier, $oldMediaElements, true) || in_array($identifier, $currentMediaElements, true);
		})->indexBy('identifier')->compile();
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @param array $associations
	 * @return void
	 */
	protected function loadOldAssociationEntities(Entity $entity, CollectionInterface $audits, array $associations): void {
		$associationProperties = array_map(fn (Association $association) => [
			'association' => $association,
			'name' => $association->getName(),
			'property' => $association->getProperty(),
		], $associations);

		$audits = $audits->toList();
		$diffData =	Hash::merge(
			Hash::extract($audits, '{n}.dataOld'),
			Hash::extract($audits, '{n}.dataNew')
		);

		$oldEntities = [];

		foreach ($associationProperties as $propertyName => $association) {
			// Get the current value of the entity
			$currentValue = $entity->get($propertyName);

			$foreignKeys = array_column($diffData, $propertyName);

			if (!$foreignKeys) {
				continue;
			}

			$foreignKeys = Hash::merge(
				Hash::extract($foreignKeys, '{n}.{n}.id'),
				Hash::extract($foreignKeys, '{n}._ids'),
			);


			$foreignKeys = Hash::flatten($foreignKeys);
			$foreignKeys = array_unique(array_filter($foreignKeys, fn($value) => !empty($value)));

			// Remove the current value from the foreign keys
			if ($currentValue && !$association['association'] instanceof BelongsToMany) {
				$foreignKeys = array_diff($foreignKeys, (array)$currentValue);
			}

			if (!$foreignKeys) {
				continue;
			}

			$finder = 'all';
			if ($associations[ $propertyName ]->getTarget()->hasBehavior('SoftDelete')) {
				$finder = 'withDeleted';
			}

			/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
			$oldEntities[ $propertyName ] = $associations[ $propertyName ]->find($finder, skipPageRoleCheck: true)->where([
				$associations[ $propertyName ]->getBindingKey() . ' IN' => $foreignKeys,
			])->all()->indexBy('id')->toArray();
		}

		if (!$oldEntities) {
			return;
		}

		/** @var \Awyiss\Model\Entity\Audit $audit */
		foreach ($audits as $audit) {
			$this->populateAssociationEntities($audit, $associationProperties, $oldEntities, 'dataOld', 'old');
			$this->populateAssociationEntities($audit, $associationProperties, $oldEntities, 'dataNew', 'new');
		}
	}

	/**
	 * Populate association entities in audit data
	 *
	 * @param \Awyiss\Model\Entity\Audit $audit
	 * @param array $associationProperties
	 * @param array $oldEntities
	 * @param string $dataField The field name ('dataOld' or 'dataNew')
	 * @param string $diffField The diff field name ('old' or 'new')
	 * @return void
	 */
	protected function populateAssociationEntities(Audit $audit, array $associationProperties, array $oldEntities, string $dataField, string $diffField): void {
		$data = $audit->{ $dataField };

		foreach ($associationProperties as $foreignKey => $association) {
			$id = $data[ $foreignKey ] ?? null;

			if (is_array($id)) {
				$entitiesArray = [];
				if (isset($id['_ids'])) {
					$ids = $id['_ids'];
					foreach ($ids as $entityId) {
						if (isset($oldEntities[ $foreignKey ][ $entityId ])) {
							$entitiesArray[] = $oldEntities[ $foreignKey ][ $entityId ];
						}
					}
				}
				else {
					$entitiesArray = $this->buildEntitiesArrayFromAuditData($id, $foreignKey, $oldEntities, $association);
				}

				Arrays::naturalSort($entitiesArray, 'label');

				$audit->{$dataField}[ $association['property'] ] = $entitiesArray;
				$audit->diff[ $diffField ][ $association['property'] ] = $entitiesArray;

				continue;
			}

			if (!isset($oldEntities[ $foreignKey ][ $id ])) {
				continue;
			}

			$audit->{$dataField}[ $association['property'] ] = $oldEntities[ $foreignKey ][ $id ];
			$audit->diff[ $diffField ][ $association['property'] ] = $oldEntities[ $foreignKey ][ $id ];
		}
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function setColumnData(Table $table, CollectionInterface $audits): CollectionInterface {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$columnWidths = $table->getColumnWidths();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$columnIndents = $table->getColumnIndents();

		return $audits->map(function (Audit $audit) use ($columnWidths, $columnIndents) {
			$audit->dataOld['column'] = [
				'width' => $columnWidths[ $audit->dataOld['columnWidth'] ] ?? null,
				'indent' => $columnIndents[ $audit->dataOld['columnIndent'] ] ?? null,
			];

			$audit->dataNew['column'] = [
				'width' => $columnWidths[ $audit->dataNew['columnWidth'] ] ?? null,
				'indent' => $columnIndents[ $audit->dataNew['columnIndent'] ] ?? null,
			];

			return $audit;
		});
	}


	/**
	 * Find audits based on the given conditions and
	 * decompress and decode the old and new data
	 *
	 * @param array $where
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function findAudits(array $where): CollectionInterface {
		return $this->Audit
			->find()
			->where($where)
			->contain(['Users'])
			->orderBy(['Audit.createdOn' => 'desc'])
			->formatResults(function (ResultSetInterface $results): CollectionInterface {
				return $results->map(function (Audit $audit) {
					$audit->dataOld = $audit->dataOld ? json_decode(gzuncompress(base64_decode($audit->dataOld)), true) : null;
					$audit->dataNew = $audit->dataNew ? json_decode(gzuncompress(base64_decode($audit->dataNew)), true) : null;

					return $audit;
				});
			})
			->all()
			->compile();
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @param \Awyiss\Model\Table $table
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $associations
	 * @return void
	 */
	protected function addPivotTableAudits(CollectionInterface &$audits, Table $table, Entity $entity, array $associations): void {
		$pivotTableAudits = $this->getPivotTableAudits($table, $entity, $audits);

		if ($pivotTableAudits->isEmpty()) {
			return;
		}

		// Group pivot table audits by transaction ID
		$groupedPivotAudits = $this->groupPivotAuditsByTransaction($pivotTableAudits);

		// Combine grouped audits into single audit entries per transaction
		$combinedPivotAudits = $this->combinePivotAudits($groupedPivotAudits, $table, $entity);

		// Merge with main audits and sort
		$audits = $audits->append($combinedPivotAudits)->sortBy('createdOn', SORT_ASC)->toList();

		// Build complete state for each pivot audit including cumulative association changes
		$audits = $this->buildPivotAuditStates($audits, $table, $entity, $associations);

		$audits = new Collection($audits)->sortBy('createdOn')->compile();
	}


	/**
	 * Get pivot table audits for the given entity
	 *
	 * @param \Awyiss\Model\Table $table
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getPivotTableAudits(Table $table, Entity $entity, CollectionInterface $audits): CollectionInterface {
		$indexedAudits = $audits->indexBy('transactionId')->toArray();

		$pivotTableAudits = $this->findAudits([
			'OR' => [
				[
					'subjectLeftForeignKey' => $entity->id,
					'subjectLeftTable' => $table->getTable(),
				],
				[
					'subjectRightForeignKey' => $entity->id,
					'subjectRightTable' => $table->getTable(),
				],
			],
		]);

		// Filter out audits that are already included in the main audits
		return $pivotTableAudits->reject(fn (Audit $audit) => isset($indexedAudits[ $audit->transactionId ]));
	}


	/**
	 * Group pivot table audits by transaction ID
	 *
	 * @param \Cake\Collection\CollectionInterface $pivotTableAudits
	 * @return array
	 */
	protected function groupPivotAuditsByTransaction(CollectionInterface $pivotTableAudits): array {
		$grouped = [];

		foreach ($pivotTableAudits as $audit) {
			$txId = $audit->transactionId;

			if (!isset($grouped[ $txId ])) {
				$grouped[ $txId ] = [];
			}

			$grouped[ $txId ][] = $audit;
		}

		return $grouped;
	}


	/**
	 * Combine grouped pivot audits into single audit entries per transaction.
	 *
	 * Each pivot audit can represent an addition or removal of a single association,
	 * or an update to the association itself, if it contains additional data.
	 *
	 * @param array $groupedPivotAudits
	 * @param \Awyiss\Model\Table $table
	 * @param \Awyiss\Model\Entity $entity
	 * @return array
	 */
	protected function combinePivotAudits(array $groupedPivotAudits, Table $table, Entity $entity): array {
		$combined = [];

		foreach ($groupedPivotAudits as $audits) {
			// Collect all association changes in this transaction
			$allChanges = [];

			foreach ($audits as $audit) {
				// Skip create audits that happened at the same time as entity creation
				// as they don't represent association changes, and also skip updates for now
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				if (
					(
						$audit->type === 'c' &&
						$entity->createdOn &&
						$audit->createdOn->equals($entity->createdOn)
					) ||
					$audit->type === 'u'
				) {
					continue;
				}

				// Get association info for this specific audit
				$associationInfo = $this->getPivotAssociationInfo($audit, $table, $entity);

				if (!$associationInfo) {
					continue;
				}

				$property = $associationInfo['property'];

				// Initialize if not exists
				if (!isset($allChanges[ $property ])) {
					$allChanges[ $property ] = [
						'added' => [],
						'removed' => [],
						'associationInfo' => $associationInfo,
						'audit' => $audit,
					];
				}

				$foreignId = $this->getRelatedForeignId($audit, $table->getTable(), $entity->id);

				$key = match ($audit->type) {
					'c' => 'added',
					'd' => 'removed',
				};

				$allChanges[ $property ][ $key ][] = $foreignId;
			}

			// If all audits were skipped or no valid changes, continue
			if (empty($allChanges)) {
				continue;
			}

			// Build ONE combined audit with ALL changes from all associations
			// Use the first property's audit as the base
			$firstProperty = array_key_first($allChanges);
			$combinedAudit = clone $allChanges[ $firstProperty ]['audit'];
			$combinedAudit->dataOld = [];
			$combinedAudit->dataNew = [];
			$combinedAudit->type = 'u';

			foreach ($allChanges as $changes) {
				$combinedAudit = $this->buildCombinedPivotAudit(
					$combinedAudit,
					$changes['associationInfo'],
					$changes['added'],
					$changes['removed']
				);
			}

			$combined[] = $combinedAudit;
		}

		return $combined;
	}


	/**
	 * Get association info for a pivot table audit
	 *
	 * @param \Awyiss\Model\Entity\Audit $audit
	 * @param \Awyiss\Model\Table $table
	 * @param \Awyiss\Model\Entity $entity
	 * @return array|null
	 */
	protected function getPivotAssociationInfo(Audit $audit, Table $table, Entity $entity): ?array {
		// Determine if current entity is on left or right side
		$isLeft = $audit->subjectLeftTable === $table->getTable() && $audit->subjectLeftForeignKey === $entity->id;

		// Get the other side's table name
		$targetTable = $isLeft ? $audit->subjectRightTable : $audit->subjectLeftTable;

		// Find the association
		$targetTableCamelized = Inflector::camelize($targetTable);

		if (
			!$table->hasAssociation($targetTableCamelized) ||
			!$table->getAssociation($targetTableCamelized) instanceof BelongsToMany
		) {
			return null;
		}

		$association = $table->getAssociation($targetTableCamelized);

		return [
			'association' => $association,
			'property' => $association->getProperty(),
			'targetTable' => $targetTable,
		];
	}


	/**
	 * Get the related foreign ID from a pivot audit
	 *
	 * @param \Awyiss\Model\Entity\Audit $audit
	 * @param string $currentTable
	 * @param int $entityId
	 * @return int
	 */
	protected function getRelatedForeignId(Audit $audit, string $currentTable, int $entityId): int {
		$isLeft = $audit->subjectLeftTable === $currentTable && $audit->subjectLeftForeignKey === $entityId;

		return $isLeft ? $audit->subjectRightForeignKey : $audit->subjectLeftForeignKey;
	}


	/**
	 * Build a combined pivot audit with added/removed IDs
	 *
	 * @param \Awyiss\Model\Entity\Audit $baseAudit
	 * @param array $associationInfo
	 * @param array $addedIds
	 * @param array $removedIds
	 * @return \Awyiss\Model\Entity\Audit
	 */
	protected function buildCombinedPivotAudit(Audit $baseAudit, array $associationInfo, array $addedIds, array $removedIds): Audit {
		$property = $associationInfo['property'];

		// Store the changes using a proper nested array structure
		$baseAudit->dataOld ??= [];
		$baseAudit->dataOld['_pivotChanges'] ??= [];

		$baseAudit->dataOld['_pivotChanges'][ $property ] = [
			'added' => $addedIds,
			'removed' => $removedIds,
		];

		return $baseAudit;
	}


	/**
	 * Build complete state for each pivot audit including cumulative association changes
	 *
	 * @param array $audits
	 * @param \Awyiss\Model\Table $table
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $associations
	 * @return array
	 */
	protected function buildPivotAuditStates(array $audits, Table $table, Entity $entity, array $associations): array {
		// Find initial pivot audits before first entity audit
		$initialPivotAudits = [];
		$firstEntityAuditIndex = null;

		foreach ($audits as $index => $audit) {
			if ($audit->scope === Inflector::camelize($table->getTable())) {
				$firstEntityAuditIndex = $index;
				break;
			}

			$hasPivotChanges = isset($audit->dataOld['_pivotChanges']) && is_array($audit->dataOld['_pivotChanges']);
			if ($hasPivotChanges) {
				$initialPivotAudits[] = $audit;
			}
		}

		// Process initial pivot audits in reverse if they exist
		if (!empty($initialPivotAudits)) {
			// Get reference state from first entity audit or current entity
			if ($firstEntityAuditIndex !== null) {
				$referenceData = $audits[ $firstEntityAuditIndex ]->dataNew ?? [];
			}
			else {
				$referenceData = $this->buildReferenceDataFromEntity($entity, $table);
			}

			$this->processInitialPivotAuditsReverse($initialPivotAudits, $referenceData, $associations);
		}

		// Process remaining audits forward
		$this->processAuditsForward($audits, $table, $firstEntityAuditIndex);

		return $audits;
	}


	/**
	 * Build reference data from entity including media assignments, publication data and translations
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Model\Table $table
	 * @return array
	 */
	protected function buildReferenceDataFromEntity(Entity $entity, Table $table): array {
		$referenceData = $entity->extract();

		// Add media assignments
		if ($entity->get('mediaAssignments')) {
			$mediaData = [];
			$blocklistedFields = [
				'id',
				'foreignKey',
				'deleted',
				'createdBy',
				'createdOn',
				'changedBy',
				'changedOn',
				'deletedBy',
				'deletedOn',
				'media',
				'mediaFolder',
			];

			foreach ($entity->get('mediaAssignments') as $elementIdentifier => $elementAssignments) {
				foreach ($elementAssignments as $selectorIdentifier => $selectorAssignments) {
					if ($selectorAssignments instanceof Entity) {
						$values = $selectorAssignments->extract();
						$values = array_diff_key($values, array_flip($blocklistedFields));
						ksort($values);
						$mediaData[ $elementIdentifier ][ $selectorIdentifier ] = $values;
						continue;
					}

					foreach ($selectorAssignments as $key => $mediaAssignment) {
						$values = $mediaAssignment->extract();
						$values = array_diff_key($values, array_flip($blocklistedFields));
						ksort($values);
						$mediaData[ $elementIdentifier ][ $selectorIdentifier ][ $key ] = $values;
					}
				}

				if (is_array($mediaData[ $elementIdentifier ] ?? null)) {
					ksort($mediaData[ $elementIdentifier ]);
				}
			}

			ksort($mediaData);
			$referenceData['mediaAssignments'] = $mediaData;
		}

		// Add publication data
		if ($entity->get('_publicationData')) {
			$publicationData = ['start' => ['dateTime' => null], 'end' => ['dateTime' => null]];

			foreach ($entity->get('_publicationData') ?? [] as $data) {
				$date = $data->has('dateTime') ? $data->get('dateTime') : null;

				if ($date) {
					$date = $date->format('Y-m-d H:i:s');
				}

				$publicationData[ $data->type->value ] = [
					'dateTime' => $date ?: null,
				];
			}

			$referenceData['_publicationData'] = $publicationData;
		}

		// Add translations
		if ($entity->get('_translations')) {
			$translateFields = null;
			if ($table->hasBehavior('Translate')) {
				$translateFields = $table->getBehavior('Translate')->getConfig('fields');
			}

			if ($translateFields) {
				$translations = [];

				foreach (($entity->get('_translations') ?? []) as $languageShortcode => $translatedEntity) {
					$translations[ $languageShortcode ] = $translatedEntity?->extract($translateFields, false, false) ?? null;
				}

				$referenceData['_translations'] = $translations;
			}
		}

		return $referenceData;
	}


	/**
	 * Process initial pivot audits in reverse from a future state
	 *
	 * @param array $pivotAudits
	 * @param array $referenceData
	 * @param array $associations
	 * @return void
	 */
	protected function processInitialPivotAuditsReverse(array $pivotAudits, array $referenceData, array $associations): void {
		if (empty($pivotAudits)) {
			return;
		}

		// Normalize reference data to extract IDs from entities
		$normalizedData = [];
		foreach ($referenceData as $key => $value) {
			if (!isset($associations[ $key ])) {
				$normalizedData[ $key ] = $value;
				continue;
			}

			if (!is_array($value)) {
				$normalizedData[ $key ] = $value;
				continue;
			}

			if (isset($value['_ids'])) {
				$normalizedData[ $key ] = $value;
				continue;
			}

			if (empty($value)) {
				continue;
			}

			// Extract IDs from entity array
			$ids = array_map(function ($item) {
				if ($item instanceof Entity) {
					return $item->id;
				}

				if (is_array($item) && isset($item['id'])) {
					return $item['id'];
				}

				return null;
			}, $value);

			$ids = array_values(
				array_filter($ids, fn ($id) => $id !== null)
			);

			$normalizedData[ $key ] = [
				'_ids' => $ids,
			];
		}

		// Process in reverse order
		$reversedAudits = array_reverse($pivotAudits);
		$lastEntityData = $normalizedData;

		foreach ($reversedAudits as $audit) {
			$this->processPivotAudit($audit, $lastEntityData, false);
			// Update lastEntityData to this audit's dataOld for the next pivot audit (going backwards)
			$lastEntityData = $audit->dataOld;
		}
	}


	/**
	 * Process audits forward from entity audits
	 *
	 * @param array $audits
	 * @param \Awyiss\Model\Table $table
	 * @param int|null $startIndex
	 * @return void
	 */
	protected function processAuditsForward(array $audits, Table $table, ?int $startIndex): void {
		if ($startIndex === null) {
			return;
		}

		$lastEntityData = null;

		foreach (array_slice($audits, $startIndex) as $audit) {
			if ($audit->scope === Inflector::camelize($table->getTable())) {
				// This is an entity audit - it becomes the new baseline
				$lastEntityData = $audit->dataNew ?? [];
				continue;
			}

			// This is a pivot table audit
			$hasPivotChanges = isset($audit->dataOld['_pivotChanges']) && is_array($audit->dataOld['_pivotChanges']);

			if (!$hasPivotChanges || !$lastEntityData) {
				continue;
			}

			$this->processPivotAudit($audit, $lastEntityData);

			// Update lastEntityData to this audit's dataNew for the next pivot audit
			$lastEntityData = $audit->dataNew;
		}
	}


	/**
	 * Process a single pivot audit
	 *
	 * @param \Awyiss\Model\Entity\Audit $audit
	 * @param array $entityData
	 * @param bool $forward
	 * @return void
	 */
	protected function processPivotAudit(Audit $audit, array $entityData, bool $forward = true): void {
		$pivotChangesData = $audit->dataOld['_pivotChanges'] ?? [];

		if (empty($pivotChangesData)) {
			return;
		}

		$newDataOld = $entityData;
		$newDataNew = $entityData;
		unset($newDataOld['_pivotChanges'], $newDataNew['_pivotChanges']);

		foreach ($pivotChangesData as $property => $changes) {
			$addedIds = $changes['added'];
			$removedIds = $changes['removed'];

			$currentState = $entityData[ $property ]['_ids'] ?? [];

			if ($forward) {
				// Forward: current state is BEFORE, calculate AFTER
				$idsBefore = $currentState;
				$idsAfter = array_values(array_diff(
					array_unique(array_merge($idsBefore, $addedIds)),
					$removedIds
				));
			}
			else {
				// Reverse: current state is AFTER, calculate BEFORE
				$idsAfter = $currentState;
				$idsBefore = array_values(array_unique(array_merge(
					array_diff($idsAfter, $addedIds),
					$removedIds
				)));
			}

			$newDataOld[ $property ] = ['_ids' => $idsBefore];
			$newDataNew[ $property ] = ['_ids' => $idsAfter];
		}

		$audit->dataOld = $newDataOld;
		$audit->dataNew = $newDataNew;
	}


	/**
	 * Build an array of entities from audit data
	 *
	 * If it's a list of entities, build the array using each element's ID,
	 * and if no entity with that id can be found, create a placeholder entity
	 * from the source table using the data available in the audit.
	 *
	 * @param array $auditData
	 * @param string $foreignKey
	 * @param array $oldEntities
	 * @param array $association
	 * @return array
	 */
	protected function buildEntitiesArrayFromAuditData(array $auditData, string $foreignKey, array $oldEntities, array $association): array {
		$entitiesArray = [];

		/** @var \Awyiss\Model\Table $targetTable */
		$targetTable = $this->fetchTable($association['name']);

		foreach ($auditData as $entityData) {
			if (!is_array($entityData) || !isset($entityData['id'])) {
				continue;
			}

			$entityId = $entityData['id'];
			if (isset($oldEntities[ $foreignKey ][ $entityId ])) {
				$entity = unserialize(serialize($oldEntities[ $foreignKey ][ $entityId ]));
				// Make sure the entity has the data at the time of the audit
				$targetTable->patchEntity($entity, $entityData);
			}
			else {
				$entity = $targetTable->newDefaultEntity($entityData);
			}

			$entitiesArray[] = $entity;
		}

		return $entitiesArray;
	}
}
