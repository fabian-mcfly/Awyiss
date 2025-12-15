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
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
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
		$id = $this->request->getParam('id');
		$scope = $this->request->getParam('scope');
		$realScope = $scope;

		if ($id === null || $scope === null) {
			if ($this->request->is('ajax')) {
				throw new InvalidArgumentException(__('error_invalid_request'));
			}
			else {
				$this->Flash->error(__('error_invalid_request'));
				throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
			}
		}

		/** @var \Awyiss\Model\Table $table */
		$table = $this->fetchTable(Inflector::camelize($scope));

		/**
		 * @uses \Awyiss\Model\Table::findTranslations()
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$entity = $table->findById($id)->find('translations')->find('mediaAssignments')->first();

		if (!$entity) {
			if ($this->request->is('ajax')) {
				throw new RuntimeException(__('error_record_not_found'));
			}
			else {
				$this->Flash->error(__('error_record_not_found'));
				throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
			}
		}

		/**
		 * Check if the scope is accessible
		 * For all scopes except 'contents', the scope must be accessible for the 'update' action
		 */
		if ($scope === 'contents') {
			// Ensure that the user has access to the `content`-permission of the page-role of the content's page.
			$this->ensurePageRoleAccess($entity);
		}
		elseif ($scope === 'configuration') {
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
		$audits = $this->Audit->find()->where([
			'foreign_key' => $id,
			'scope' => $realScope,
		])->contain(['Users'])->orderBy(['Audit.created_on' => 'desc'])
		->formatResults(function (ResultSetInterface $results): CollectionInterface {
			return $results->map(function (Audit $audit) {
				$audit->dataOld = json_decode(gzuncompress(base64_decode($audit->dataOld)), true);
				$audit->dataNew = json_decode(gzuncompress(base64_decode($audit->dataNew)), true);

				return $audit;
			});
		})
		->all()
		->compile();

		// Check audits for changes in the associations and load the associated entities
		$this->loadOldAssociationEntities($entity, $audits, $associations);

		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $table->getEntityClass();

		/** @var array<\Awyiss\Model\Entity\Attribute> $attributes */
		$attributes = $table->hasAttributes() ? $table->getAttributes() : [];
		if ($attributes) {
			/** @var \Awyiss\Attribute\AttributeOptionsCollection $attributeOptions */
			$attributeOptions = AttributeOptionsProvider::getAttributeOptionsFile($scope, true);
		}

		if (in_array($scope, ['contents', 'global_contents'], true)) {
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

			$realm = $behavior->getConfig('realm');
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

		$this->set([
			'entity' => $entity,
			'audits' => $audits->compile(),
			'schema' => $table->getSchema(),
			'scope' => $scope,
			'historyFields' => $historyFields,
			'attributes' => $attributes,
			'attributeOptionsCollection' => $attributeOptions ?? null,
			'attributesSchema' => $table->hasAttributes() ? $table->getAttributesTable()->getSchema() : null,
			'associations' => array_map(fn (Association $association) => [
				'name' => $association->getName(),
				'property' => $entityClass::mapField($association->getProperty()),
			], $associations),
			'media' => $media->toArray(),
			'mediaElements' => $mediaElements,
			'isAjax' => $this->request->is('ajax'),
			'isPageRole' => $isPageRole,
			'publicationDataEnabled' => LocalConfig::read('publicationData.enabled', null, Inflector::camelize($scope)),
			'languages' => $languages,
			'translatableFields' => $translatableFields,
			'translatableAttributes' => $translatableAttributes,
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
	 * changedOn, created, and changed fields. If the createdBy or changedBy fields are empty,
	 * they are set to 'System' if the corresponding createdBy or changedBy fields are empty,
	 * otherwise they are set to 'Unknown'.
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
			else {
				$this->Flash->error(__('error_record_not_found'));
				throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
			}
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

		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = $table->getEntityClass();

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

			$foreignKey = $entityClass::mapFields((array)$association->getForeignKey())[0];

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
			Hash::extract($entity->mediaAssignments, '{s}.{n}.mediaId'),
			Hash::extract($entity->mediaAssignments, '{s}.{s}.mediaId'),
			Hash::extract($entity->mediaAssignments, '{s}.{s}.{n}.mediaId')
		);
		$mediaAssignments = Hash::extract($audits->toList(), '{n}.dataOld.mediaAssignments');

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

		$oldMediaElements = Hash::extract($audits->toList(), '{n}.diff.old.mediaAssignments');
		$oldMediaElements = array_unique(array_merge(...array_map('array_keys', $oldMediaElements)));

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
		/** @var class-string<\Awyiss\Model\Entity> $entityClass */
		$entityClass = get_class($entity);

		$associationProperties = array_map(fn (Association $association) => [
			'name' => $association->getName(),
			'property' => $entityClass::mapField($association->getProperty()),
		], $associations);

		$diffData = Hash::extract($audits->toList(), '{n}.diff.old');

		$oldEntities = [];

		foreach ($associationProperties as $foreignKey => $association) {
			$foreignKeys = array_column($diffData, $foreignKey);
			$foreignKeys = array_unique(array_filter($foreignKeys));

			// Get the current value of the entity
			$currentValue = $entity->get($foreignKey);

			// Remove the current value from the foreign keys
			if ($currentValue) {
				$foreignKeys = array_diff($foreignKeys, [$currentValue]);
			}

			if (!$foreignKeys) {
				continue;
			}

			/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
			$oldEntities[ $foreignKey ] = $associations[ $foreignKey ]->find('withDeleted', skipPageRoleCheck: true)->where([
				$associations[ $foreignKey ]->getBindingKey() . ' IN' => $foreignKeys,
			])->all()->indexBy('id')->toArray();
		}

		if (!$oldEntities) {
			return;
		}

		/** @var \Awyiss\Model\Entity\Audit $audit */
		foreach ($audits as $audit) {
			$oldData = $audit->dataOld;

			foreach ($associationProperties as $foreignKey => $association) {
				$id = $oldData[ $foreignKey ] ?? null;

				if (!isset($oldEntities[ $foreignKey ][ $id ])) {
					continue;
				}

				$audit->dataOld[ $association['property'] ] = $oldEntities[ $foreignKey ][ $id ];
				$audit->diff['old'][ $association['property'] ] = $oldEntities[ $foreignKey ][ $id ];
			}
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

			return $audit;
		});
	}
}
