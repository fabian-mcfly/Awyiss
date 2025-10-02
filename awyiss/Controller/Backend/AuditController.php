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
use Awyiss\Model\Entity\Audit;
use Awyiss\Model\Entity\Content;
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
		$lo_query = $this->Audit->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($lo_query);

		return $lo_query;
	}


	/**
	 * Show the history of a record
	 *
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function history(): void {
		$li_id = $this->request->getParam('id');
		$ls_scope = $this->request->getParam('scope');
		$ls_realScope = $ls_scope;

		if ($li_id === null || $ls_scope === null) {
			if ($this->request->is('ajax')) {
				throw new InvalidArgumentException(__('error_invalid_request'));
			}
			else {
				$this->Flash->error(__('error_invalid_request'));
				throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'overview']));
			}
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->fetchTable(Inflector::camelize($ls_scope));

		/**
		 * @uses \Awyiss\Model\Table::findTranslations()
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_entity = $lo_table->findById($li_id)->find('translations')->find('mediaAssignments')->first();

		if (!$lo_entity) {
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
		if ($ls_scope === 'contents') {
			// Ensure that the user has access to the `content`-permission of the page-role of the content's page.
			$this->ensurePageRoleAccess($lo_entity);
		}
		else {
			$this->Authorization->scopeIsAccessible($ls_scope, [], 'update');
		}

		$la_historyFields = $lo_table->getAuditHistoryFields();
		// Deleted field is tracked but not displayed
		$la_historyFields = array_diff($la_historyFields, ['deleted']);

		$la_associations = $this->getAssociations($lo_table, $la_historyFields);

		if ($la_associations) {
			$lo_table->loadInto($lo_entity, array_values(array_map(fn($lo_association) => $lo_association->getName(), $la_associations)));
		}

		$lb_isPageRole = false;
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($ls_pageRoleEnum::tryFromName($ls_scope)) {
			$lb_isPageRole = true;
			$ls_realScope = 'pages';
		}

		// Get the audit history of the record
		$lo_audits = $this->Audit->find()->where([
			'foreign_key' => $li_id,
			'scope' => $ls_realScope,
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
		$this->loadOldAssociationEntities($lo_entity, $lo_audits, $la_associations);

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();

		/** @var array<\Awyiss\Model\Entity\Attribute> $la_attributes */
		$la_attributes = $lo_table->hasAttributes() ? $lo_table->getAttributes() : [];
		if ($la_attributes) {
			/** @var \Awyiss\Attribute\AttributeOptionsCollection $lo_attributeOptions */
			$lo_attributeOptions = AttributeOptionsProvider::getAttributeOptionsFile($ls_scope, true);
		}

		if (in_array($ls_scope, ['contents', 'widgets'], true)) {
			$lo_audits = $this->setColumnData($lo_table, $lo_audits);
		}

		$lo_media = $this->getMedia($lo_entity, $lo_audits);
		$lo_mediaElements = $this->getMediaElements($lo_entity, $lo_audits);

		$la_translatableFields = [];
		$la_languages = [];
		if ($lo_table->hasBehavior('Translate')) {
			/** @var \Awyiss\Model\Behavior\TranslateBehavior $lo_behavior */
			$lo_behavior = $lo_table->getBehavior('Translate');
			$la_translatableFields = $lo_behavior->getConfig('fields');

			$ls_realm = $lo_behavior->getConfig('realm');
			$la_languages = LocaleMiddleware::getLanguages($ls_realm);

			// Filter out inactive languages
			$la_languages = array_filter($la_languages, fn($lo_language) => $lo_language->active);
		}

		$la_translatableAttributes = [];
		if ($lo_table->hasAttributes()) {
			// Get all identifiers of translatable attributes
			$la_translatableAttributes = array_column($la_attributes, null, 'identifier');
			$la_translatableAttributes = array_keys(array_filter($la_translatableAttributes, fn($lo_attribute) => $lo_attribute->translatable));
		}

		$this->set([
			'entity' => $lo_entity,
			'audits' => $lo_audits->compile(),
			'schema' => $lo_table->getSchema(),
			'scope' => $ls_scope,
			'historyFields' => $la_historyFields,
			'attributes' => $la_attributes,
			'attributeOptionsCollection' => $lo_attributeOptions ?? null,
			'attributesSchema' => $lo_table->hasAttributes() ? $lo_table->getAttributesTable()->getSchema() : null,
			'associations' => array_map(fn (Association $association) => [
				'name' => $association->getName(),
				'property' => $ls_entityClass::mapField($association->getProperty()),
			], $la_associations),
			'media' => $lo_media->toArray(),
			'mediaElements' => $lo_mediaElements,
			'isAjax' => $this->request->is('ajax'),
			'isPageRole' => $lb_isPageRole,
			'publicationDataEnabled' => LocalConfig::read('publicationData.enabled', null, Inflector::camelize($ls_scope)),
			'languages' => $la_languages,
			'translatableFields' => $la_translatableFields,
			'translatableAttributes' => $la_translatableAttributes,
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
		$la_parts = $this->request->getParam('parts');
		$li_id = $la_parts['id'] ?? null;
		$ls_scope = $la_parts['scope'] ?? null;

		if ($li_id === null || $ls_scope === null) {
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'index']));
		}

		/**
		 * @uses \Awyiss\Model\Behavior\AuditBehavior::findWithAuditUsers()
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_record = $this->fetchTable(Inflector::camelize($ls_scope))->findById($li_id)->find('withAuditUsers')->first($li_id);
		if (!$lo_record) {
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'index']));
		}

		// If the request is an AJAX request, return a JSON response
		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['createdBy', 'createdOn', 'changedBy', 'changedOn', 'created', 'changed']);

			// Get the createdBy and changedBy users
			$ls_createdByUser = $lo_record->get('createdByUser');
			$ls_changedByUser = $lo_record->get('changedByUser');

			// If createdBy is empty, set it to 'System' if createdBy is empty, otherwise to "Unknown"
			if (empty($ls_createdByUser)) {
				$ls_createdByUser = $lo_record->get('createdBy') ? __('user_unknown') : __('user_system');
			}

			// If changedBy is empty, set it to 'System' if createdBy is empty, otherwise to "Unknown"
			if (empty($ls_changedByUser)) {
				$ls_changedByUser = $lo_record->get('changedBy') ? __('user_unknown') : __('user_system');
			}

			$ls_timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
			if ($ls_timezone === 'auto') {
				$ls_timezone = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)->timezone;
			}

			// Set the data to be serialized
			$this->set([
				'createdBy' => $ls_createdByUser,
				'createdOn' => $lo_record->get('createdOn')?->nice($ls_timezone),
				'changedBy' => $ls_changedByUser,
				'changedOn' => $lo_record->get('changedOn')?->nice($ls_timezone),
				'created' => __('created_info_label'),
				'changed' => __('changed_info_label'),
			]);

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');


			return;
		}

		$this->set([
			'record' => $lo_record,
			'scope' => $ls_scope,
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
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = $this->fetchTable('Contents');

		try {
			$lo_page = $lo_table->getPage($entity->pageId);
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

		$lo_table->forPageRole($lo_page->pageRoleId);
		$this->Authorization->scopeIsAccessible($lo_table->getForScope(), [], 'contents');

		$entity->page = $lo_page;
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param array $historyFields
	 * @return array
	 */
	protected function getAssociations(Table $table, array $historyFields): array {
		$ls_tableName = Inflector::camelize($table->getTable());

		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = $table->getEntityClass();

		$la_blocklistAssociations = [
			'Attributes' . $ls_tableName,
			'Child' . $ls_tableName,
			'Duplicating' . $ls_tableName,
			'Parent' . $ls_tableName,
			$ls_tableName . 'PublicationDataStart',
			$ls_tableName . 'PublicationDataEnd',
			'PublicationData',
		];

		$la_associations = [];
		foreach ($table->associations() as $lo_association) {
			if (in_array($lo_association->getName(), $la_blocklistAssociations)) {
				continue;
			}

			$ls_foreignKey = $ls_entityClass::mapFields((array)$lo_association->getForeignKey())[0];

			// If the key of the association is in the history fields, add it to the associations using the key as the key
			if (in_array($ls_foreignKey, $historyFields)) {
				$la_associations[ $ls_foreignKey ] = $lo_association;
			}
		}

		return $la_associations;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMedia(Entity $entity, CollectionInterface $audits): CollectionInterface {
		$la_mediaIds = array_merge(Hash::extract($entity->mediaAssignments, '{s}.{s}.mediaId'), Hash::extract($entity->mediaAssignments, '{s}.{s}.{n}.mediaId'));

		$la_mediaAssignments = Hash::extract($audits->toList(), '{n}.dataOld.mediaAssignments');
		$la_mediaIds = array_merge($la_mediaIds, Hash::extract($la_mediaAssignments, '{n}.{s}.{*}.mediaId'), Hash::extract($la_mediaAssignments, '{n}.{s}.{s}.{n}.mediaId'));
		$la_mediaIds = array_unique($la_mediaIds);

		if (!$la_mediaIds) {
			return new Collection([]);
		}

		return $this->fetchTable('Media')->find()->where(['id IN' => $la_mediaIds])->all()->indexBy('id');
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMediaElements(Entity $entity, CollectionInterface $audits): CollectionInterface {
		$lo_mediaElements = $this->fetchTable('MediaElements')->find()->contain([
			'MediaElementSelectors.MediaSelectors',
		])->all()->indexBy('identifier');

		$la_oldMediaElements = Hash::extract($audits->toList(), '{n}.diff.old.mediaAssignments');
		$la_oldMediaElements = array_unique(array_merge(...array_map('array_keys', $la_oldMediaElements)));

		$la_currentMediaElements = array_keys($entity->mediaAssignments ?? []);

		return $lo_mediaElements->filter(function (MediaElement $mediaElement) use ($la_oldMediaElements, $la_currentMediaElements) {
			$ls_identifier = Inflector::variable($mediaElement->identifier);

			return in_array($ls_identifier, $la_oldMediaElements, true) || in_array($ls_identifier, $la_currentMediaElements, true);
		})->indexBy('identifier')->compile();
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $audits
	 * @param array $associations
	 * @return void
	 */
	protected function loadOldAssociationEntities(Entity $entity, CollectionInterface $audits, array $associations): void {
		/** @var class-string<\Awyiss\Model\Entity> $ls_entityClass */
		$ls_entityClass = get_class($entity);

		$la_associationProperties = array_map(fn (Association $association) => [
			'name' => $association->getName(),
			'property' => $ls_entityClass::mapField($association->getProperty()),
		], $associations);

		$la_diffData = Hash::extract($audits->toList(), '{n}.diff.old');

		$la_oldEntities = [];

		foreach ($la_associationProperties as $ls_foreignKey => $la_association) {
			$la_foreignKeys = array_column($la_diffData, $ls_foreignKey);
			$la_foreignKeys = array_unique(array_filter($la_foreignKeys));

			// Get the current value of the entity
			$lx_currentValue = $entity->get($ls_foreignKey);

			// Remove the current value from the foreign keys
			if ($lx_currentValue) {
				$la_foreignKeys = array_diff($la_foreignKeys, [$lx_currentValue]);
			}

			if (!$la_foreignKeys) {
				continue;
			}

			/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
			$la_oldEntities[ $ls_foreignKey ] = $associations[ $ls_foreignKey ]->find('withDeleted', skipPageRoleCheck: true)->where([
				$associations[ $ls_foreignKey ]->getBindingKey() . ' IN' => $la_foreignKeys,
			])->all()->indexBy('id')->toArray();
		}

		if (!$la_oldEntities) {
			return;
		}

		/** @var \Awyiss\Model\Entity\Audit $lo_audit */
		foreach ($audits as $lo_audit) {
			$la_oldData = $lo_audit->dataOld;

			foreach ($la_associationProperties as $ls_foreignKey => $la_association) {
				$li_id = $la_oldData[ $ls_foreignKey ] ?? null;

				if (!isset($la_oldEntities[ $ls_foreignKey ][ $li_id ])) {
					continue;
				}

				$lo_audit->dataOld[ $la_association['property'] ] = $la_oldEntities[ $ls_foreignKey ][ $li_id ];
				$lo_audit->diff['old'][ $la_association['property'] ] = $la_oldEntities[ $ls_foreignKey ][ $li_id ];
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
		$la_columnWidths = $table->getColumnWidths();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_columnIndents = $table->getColumnIndents();

		return $audits->map(function (Audit $audit) use ($la_columnWidths, $la_columnIndents) {
			$audit->dataOld['column'] = [
				'width' => $la_columnWidths[ $audit->dataOld['columnWidth'] ] ?? null,
				'indent' => $la_columnIndents[ $audit->dataOld['columnIndent'] ] ?? null,
			];

			return $audit;
		});
	}
}
