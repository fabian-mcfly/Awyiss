<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Contents Controller
 *
 * @property \Awyiss\Model\Table\ContentsTable $Contents
 */
class ContentsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'startupMethods' => null,
		'uriParam' => 'page-id',
	];
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $contentTemplates;
	/**
	 * @var array<Page>
	 */
	protected array $pages;
	/**
	 * @var Page
	 */
	protected Page $page;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedContents;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->forPage((int)$this->request->getParam('pageId'));

		$this->Authorization->ensure('read');

		/**
		 * Using `$this->Contents->find()` instead of
		 * `$this->Contents->Pages->loadInto($lo_page, ['Contents'])->contents, $lo_contents->count();`
		 * because `nestedByContentArea()` works with a Query, not an array.
		 * This could be changed, but I fail to see any benefits
		 */
		$lo_contents = $this->Contents->find()->where($this->getOverviewWhere())->contain(['ContentTemplates']);
		$this->Categories->filterQuery($lo_contents, null, !$this->paginate['enabled']);
		$lo_contents = $lo_contents->formatResults(function (Collection $ao_result): Collection {
			/** @var \Awyiss\Model\Entity\Content $lo_content */
			foreach ($ao_result as $lo_content) {
				$lo_content->class = $lo_content->column['width']->getCssClass();

				if ($lo_content->column['indent']) {
					$lo_content->class .= ' ' . $lo_content->column['indent']->getCssClass();
				}

				if ($lo_content->columnRtl) {
					$lo_content->class .= ' Column-RTL';
				}

				if ($lo_content->columnLast) {
					$lo_content->class .= ' Column-Last';
				}
			}


			return $ao_result;
		})->find('threaded')->all();

		$la_contents = $lo_contents->groupBy('contentAreaId')->toArray();

		$la_contentAreas = array_combine(array_column($this->page->pageTemplate->contentAreas, 'id'), array_column($this->page->pageTemplate->contentAreas, 'label'));
		$la_unknownContentAreas = array_diff_key($la_contents, $la_contentAreas);
		foreach ($la_unknownContentAreas as $li_contentAreaId => $lo_contents) {
			$la_contentAreas[ $li_contentAreaId ] = null;
		}

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $ls_columnSystemClass */
		$ls_columnSystemClass = $this->Contents->getColumnSystemClass();

		$la_contentTemplates = $this->getContentTemplates()->indexBy('id')->toArray();
		array_map(function (ContentTemplate $ao_contentTemplate) {
			// Build an array of assigned content elements, indexed by their identifier
			$ao_contentTemplate->contentTemplateElements = collection($ao_contentTemplate->contentTemplateElements)->indexBy('identifier')->toArray();
			// Build an array of assigned content areas, indexed by their id
			$ao_contentTemplate->contentAreaIds = collection($ao_contentTemplate->contentAreas)->filter(function ($ao_contentArea) {
				return $ao_contentArea->_joinData->pageTemplateId === $this->page->pageTemplateId;
			})->extract('id')->unique()->toList();
		}, $la_contentTemplates);

		$this->set([
			'contents' => $la_contents,
			'contentAreas' => $la_contentAreas,
			'unknownContentAreas' => $la_unknownContentAreas,
			'page' => $this->page,
			'forScope' => $this->Contents->getForScope(),
			'contentTemplates' => $la_contentTemplates,
			'columnWidths' => $this->Contents->getColumnWidths(),
			'columnIndents' => $this->Contents->getColumnIndents(),
			'columnSystemName' => $ls_columnSystemClass::getName(),
			'attributes' => $this->Contents->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$li_pageId = (int)$this->request->getParam('pageId');
		$this->forPage($li_pageId);

		$this->Authorization->ensure('create');

		$lo_content = $this->Contents->newDefaultEntity([
			'pageId' => $li_pageId,
		]);

		if ($this->request->is('post')) {
			$this->save($lo_content);
		}

		$this->setViewVars($lo_content);
	}


	/**
	 * Edit method
	 *
	 * @param int $ai_id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($ai_id)->find('translations')->first();
		if (!$lo_content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->forPage($lo_content->pageId);

		$this->Authorization->ensure('update');

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_content, 'edit');
		}

		$this->setViewVars($lo_content);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->request->allowMethod(['get', 'delete']);

		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($ai_id)->find('translations')->first();
		if (!$lo_content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		//Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($lo_content->pageId);
		$this->Authorization->ensure('delete');

		if ($this->Contents->delete($lo_content)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_content->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview', 'pageId' => $lo_content->pageId]);
	}


	/**
	 * Save the column width of one content.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function saveColumnWidth(): void {
		$lo_request = Router::getRequest();

		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($lo_request->getData('id'))->first();
		if (!$lo_content) {
			if ($this->request->accepts('application/json')) {
				$this->viewBuilder()->setOption('serialize', ['success', 'message']);

				$this->set('success', false);
				$this->set('message', __('record_not_found'));

				// Set the view class to JSON
				$this->viewBuilder()->setClassName('Json');

				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(404, 'Record not found');
			}
			else {
				$this->Flash->error(__('record_not_found'));

				throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
			}
		}

		//Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($lo_content->pageId);
		$this->Authorization->ensure('read');

		$lo_content->set('columnWidth', $lo_request->getData('width'));

		$this->Contents->save($lo_content);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$lo_content->hasErrors());
			$this->set('message', !$lo_content->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($lo_content->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$lo_content->hasErrors()) {
				$this->Flash->success(__('edit_succeeded'));
			}
			else {
				$this->Flash->error(__('edit_failed'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * @inheritDoc
	 */
	public function saveSystemOrder(): void {
		$lo_request = Router::getRequest();
		$lo_table = $this->Contents;

		$li_affectedRows = $this->_saveSystemOrder($lo_request->getData('order'), $lo_table);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', $li_affectedRows !== false);
			$this->set('message', $li_affectedRows > 0 ? __d('system', 'system_order_saved') : __d('system', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($li_affectedRows === false) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if ($li_affectedRows) {
				$this->Flash->success(__d('system', 'system_order_saved'));
			}
			else {
				$this->Flash->error(__d('system', 'system_order_not_saved'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * @param Content $ao_content
	 * @param string $as_method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Content $ao_content, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Contents->hasAttributes()) {
			$la_associated[] = $this->Contents->getAttributesTableName(true);
			$ao_content->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		if (isset($la_data['data'])) {
			$la_dataSet = [];

			foreach ((array)$la_data['data'] as $lx_key => $lx_value) {
				if (!is_numeric($lx_key)) {
					if (empty($lx_value)) {
						continue;
					}

					$la_dataSet[ $lx_key ] = $lx_value;
					continue;
				}

				if (isset($lx_value['key']) && $lx_value['key'] !== '' && isset($lx_value['value']) && $lx_value['value'] !== '') {
					$la_dataSet[ $lx_value['key'] ] = $lx_value['value'];
				}
			}

			$la_data['data'] = $la_dataSet;
		}

		$this->Contents->patchEntity($ao_content, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($ao_content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($ao_content->pageId);
				$this->Authorization->ensure($as_method === 'add' ? 'create' : 'update');
			}

			$this->unsetUnassignedElements($ao_content);

			if ($this->Contents->save($ao_content, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $this->page->languageShortcode, 'pageId' => $ao_content->pageId], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $this->page->languageShortcode, 'id' => $ao_content->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_content->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Contents->getSystemOrderRelatedColumns($ao_content)) {
				$ao_content->systemOrder = null;
			}
			else {
				$ao_content->systemOrder = $ao_content->hasOriginal('systemOrder') ? $ao_content->getOriginal('systemOrder') : $ao_content->get('systemOrder');
			}

			$this->Categories->ensurePossibleCategory($ao_content);

			if ($ao_content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($ao_content->pageId);

				$this->Authorization->ensure($as_method === 'add' ? 'create' : 'update');
			}
		}
	}


	/**
	 * @param array $aa_requestData
	 * @param \Awyiss\Model\Table $ao_table
	 * @return int
	 * @throws \Exception
	 */
	protected function _saveSystemOrder(array $aa_requestData, Table $ao_table): int {
		// Create a flat array of all order data
		$la_orderData = [];
		foreach ($aa_requestData as $la_itemsByContentAreaId) {
			foreach ($la_itemsByContentAreaId as $li_parentId => $la_items) {
				array_map(function (array $aa_item) use (&$la_orderData, $li_parentId) {
					$la_orderData[] = $aa_item + ['parentId' => $li_parentId ?: null];
				}, $la_items);
			}
		}

		// Get the page id of the first content
		$lo_content = $ao_table->findById($la_orderData[0]['id'])->first();

		// Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($lo_content->pageId);
		$this->Authorization->ensure('read');

		// Now that we have the page id, we get all current contents
		$lo_contents = $ao_table->find()->where([
			'page_id' => $lo_content->pageId,
		])->all();

		// And make sure that all contents in the request data are part of the current contents
		$la_filteredOrderData = array_filter($la_orderData, function ($la_item) use ($lo_contents) {
			return $lo_contents->firstMatch(['id' => $la_item['id']]);
		});

		// If the filtered order does not match the original order, we return 0
		if ($la_filteredOrderData !== $la_orderData) {
			return 0;
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $ao_table->updateAll(function (QueryExpression $ao_expression) use ($la_orderData) {
			$lo_contentAreaCase = $ao_expression->case();
			$lo_parentCase = $ao_expression->case();
			$lo_systemOrderCase = $ao_expression->case();

			foreach ($la_orderData as $la_data) {
				$lo_contentAreaCase->when(['id' => $la_data['id']])->then($la_data['contentAreaId'], 'integer');
				$lo_parentCase->when(['id' => $la_data['id']])->then($la_data['parentId'], 'integer');
				$lo_systemOrderCase->when(['id' => $la_data['id']])->then($la_data['systemOrder'], 'integer');
			}


			return [
				'content_area_id' => $lo_contentAreaCase,
				'parent_id' => $lo_parentCase,
				'system_order' => $lo_systemOrderCase,
			];
		}, [
			'id IN' => array_column($la_orderData, 'id'),
		]);


		return $li_affectedRows;
	}


	/**
	 * @param Content $ao_content
	 * @return array
	 */
	protected function getAssignedAttributes(Content $ao_content): array {
		if (empty($ao_content->contentTemplate)) {
			return [];
		}


		return $this->Contents->ContentTemplates->getAssignedContentAttributes($ao_content->contentTemplate);
	}


	/**
	 * Returns a collection of all available ContentTemplates
	 *
	 * @return CollectionInterface
	 */
	protected function getContentTemplates(): CollectionInterface {
		if (!isset($this->contentTemplates)) {
			$li_pageTemplateId = $this->page->pageTemplateId;

			$lo_query = $this->Contents->ContentTemplates->find(
				'active',
			)->select([
				'id',
				'title',
				'active',
			])->matching('ContentAreas', function (SelectQuery $ao_query) use ($li_pageTemplateId) {
				return $ao_query->where(['ContentTemplateContentAreas.page_template_id' => $li_pageTemplateId]);
			})->contain([
				'ContentAreas',
				'ContentTemplateElements',
			]);

			$this->contentTemplates = $lo_query->all()->indexBy('id');
		}


		return $this->contentTemplates;
	}


	/**
	 * Returns a collection of all possible parent contents for the given content
	 * to prevent circular references.
	 *
	 * @param Content $ao_content
	 * @return CollectionInterface
	 */
	protected function getPossibleParentContents(Content $ao_content): CollectionInterface {
		if (!isset($this->threadedContents)) {
			if (empty($ao_content->contentAreaId)) {
				return new Collection([]);
			}

			$lo_query = $this->Contents->find()->where([
				'page_id' => $ao_content->pageId,
				'content_area_id' => $ao_content->contentAreaId,
			]);

			$this->threadedContents = $this->Contents->listNested($lo_query);
		}


		return $this->Contents->getPossibleParents($ao_content, $this->threadedContents);
	}


	/**
	 * Returns and caches a Page object.
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 * @see Page
	 */
	protected function getPage(int $ai_pageId): Page {
		if (!isset($this->pages[ $ai_pageId ])) {
			$this->pages[ $ai_pageId ] = $this->Contents->getPage($ai_pageId);
		}


		return $this->pages[ $ai_pageId ];
	}


	/**
	 * Sets the Contents table to use the page role of a page with the given id
	 * Requesting a page that does not exist or without having read access to the scope of the page (page role),
	 * a redirect exception is thrown.
	 *
	 * @param int $ai_pageId
	 * @return Page
	 * @throws \Exception
	 */
	protected function forPage(int $ai_pageId): Page {
		try {
			$lo_page = $this->getPage($ai_pageId);
		}
		catch (RecordNotFoundException | InvalidPrimaryKeyException) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'dashboard', 'action' => 'overview'], true), 404);
		}

		$this->page = $lo_page;

		$ls_languageShortcode = null;
		if ($this->request->is(['patch', 'post', 'put']) && in_array($this->request->getParam('action'), ['add', 'edit'])) {
			$ls_languageShortcode = $this->request->getData('language_shortcode');
		}

		$this->Categories->setConfig([
			'finder' => [
				'forCurrentLanguage' => [
					'languageShortcode' => $ls_languageShortcode,
				],
			],
			'selectedCategory' => $ai_pageId,
		]);

		$this->Contents->forPageRole($lo_page->pageRoleId);

		if (!$this->request->is(['patch', 'post', 'put']) || $this->request->getParam('action') !== 'edit') {
			if ($lo_page->language_shortcode != LocaleMiddleware::getLanguage()->shortcode) {
				throw new RedirectException(Router::url([
					'lang' => $lo_page->languageShortcode,
					'pageId' => $ai_pageId,
				], true), 302);
			}
		}

		$this->Authorization->setScope($this->Contents->getForScope());

		if (!Configure::read('Awyiss.' . Inflector::camelize($this->Contents->getForScope()) . '.Backend.contents.enabled')) {
			$this->Flash->error(__d($this->Contents->getForScope(), 'contents_disabled'));
			throw new RedirectException(Router::url(['controller' => $this->Contents->getForScope(), 'action' => 'overview'], true), 404);
		}


		return $lo_page;
	}


	/**
	 * @param Content $ao_content
	 * @param CollectionInterface $ao_threadedContents
	 * @param \Awyiss\Model\Entity\ContentTemplate|null $ao_selectedContentTemplate
	 * @return void
	 */
	protected function ensurePossibleParentId(Content $ao_content, CollectionInterface $ao_threadedContents, ?ContentTemplate $ao_selectedContentTemplate): void {
		// Extract all possible parent ids
		$la_possibleParentIds = $ao_threadedContents->extract('id')->toList();

		// Build an array of assigned content elements, indexed by their identifier
		$la_assignedContentElements = $ao_selectedContentTemplate ? collection($ao_selectedContentTemplate->contentTemplateElements)->indexBy('identifier')->toArray() : [];

		// If the parent_id is not in the list of possible parent ids or the parent_id is not assigned to the selected content template
		if (
			$ao_content->parentId &&
			(
				!in_array($ao_content->parentId, $la_possibleParentIds) ||
				!isset($la_assignedContentElements['parent_id'])
			)
		) {
			// Remember the errors
			$la_errors = $ao_content->getError('parentId');

			// If the parent_id is required and there are possible parent ids, set the parent_id to the first possible parent id
			if (($la_assignedContentElements['parent_id'] ?? null)?->required === true && $la_possibleParentIds) {
				$ao_content->parentId = reset($la_possibleParentIds);
			}
			// Otherwise, set the parent_id to null
			else {
				$ao_content->parentId = null;
			}

			// If there were errors, set them again
			if ($la_errors) {
				$ao_content->setError('parentId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When parent_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('parent_id') !== null) {
				$lo_request = $lo_request->withData('parent_id', $ao_content->parentId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Content $ao_content
	 * @param CollectionInterface $ao_contentTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Content $ao_content, CollectionInterface $ao_contentTemplates): void {
		if (!$ao_content->contentTemplateId || !$ao_contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId])) {
			$la_errors = $ao_content->getError('contentTemplateId');

			$ao_content->contentTemplate = $ao_contentTemplates->first();
			$ao_content->contentTemplateId = $ao_content->contentTemplate?->id;

			if ($la_errors) {
				$ao_content->setError('contentTemplateId', $la_errors);
			}
		}
		elseif (!$ao_content->contentTemplate) {
			$ao_content->contentTemplate = $ao_contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId]);
		}

		$lo_request = $this->getRequest();
		//When content_template_id is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('content_template_id') !== null) {
			$lo_request = $lo_request->withData('content_template_id', $ao_content->contentTemplateId);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param Content $ao_content
	 * @param array $aa_availableContentAreas
	 * @return void
	 */
	protected function ensurePossibleContentArea(Content $ao_content, array $aa_availableContentAreas = []): void {
		if (empty($ao_content->contentAreaId) || !in_array($ao_content->contentAreaId, array_column($aa_availableContentAreas['all'], 'id'))) {
			$la_errors = $ao_content->getError('contentAreaId');

			if (!$aa_availableContentAreas['available']) {
				return;
			}

			$ao_content->contentArea = reset($aa_availableContentAreas['available']);
			$ao_content->contentAreaId = $ao_content->contentArea->id;

			if ($la_errors) {
				$ao_content->setError('contentAreaId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When content_area_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('content_area_id') !== null) {
				$lo_request = $lo_request->withData('content_area_id', $ao_content->contentAreaId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Content $ao_content
	 * @param Page|null $ao_page
	 * @return array
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function getContentAreas(Content $ao_content, ?Page $ao_page = null): array {
		$lo_page = $ao_page ?? $this->getPage($ao_content->pageId);

		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId]);
		if (!$lo_contentTemplate) {
			return [
				'available' => [],
				'unavailable' => [],
				'all' => [],
			];
		}

		$la_unavailableContentAreas = $la_contentAreas = collection($lo_page->pageTemplate->contentAreas)->indexBy('id')->toArray();

		$la_availableContentAreas = [];

		foreach ($lo_contentTemplate->contentAreas ?? [] as $lo_contentArea) {
			if ($lo_contentArea->_joinData->pageTemplateId != $lo_page->pageTemplateId) {
				continue;
			}

			if (isset($la_unavailableContentAreas[ $lo_contentArea->id ])) {
				$la_availableContentAreas[ $lo_contentArea->id ] = $la_unavailableContentAreas[ $lo_contentArea->id ];
				unset($la_unavailableContentAreas[ $lo_contentArea->id ]);
			}
		}

		return [
			'available' => $la_availableContentAreas,
			'unavailable' => $la_unavailableContentAreas,
			'all' => $la_contentAreas,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $ao_content
	 * @return void
	 */
	protected function unsetUnassignedElements(Content $ao_content): void {
		$lo_contentTemplates = $this->getContentTemplates();
		$lo_contentTemplate = $lo_contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId]);

		foreach (
			array_diff(
				array_keys($this->Contents->ContentTemplates->getAvailableContentElements()),
				array_column($lo_contentTemplate->contentTemplateElements, 'identifier')
			) as $ls_element
		) {
			if ($ls_element === 'column_width') {
				$la_columnWidths = $this->Contents->getColumnWidths();

				$ao_content->set($ls_element, key($la_columnWidths));

				continue;
			}

			$ao_content->set($ls_element);
		}

		$la_contentAttributes = $this->Contents->ContentTemplates->getAvailableContentAttributes();
		$la_contentAttributes = array_column($la_contentAttributes, 'identifier');

		foreach (
			array_diff(
				$la_contentAttributes,
				$this->Contents->ContentTemplates->getAssignedContentAttributes($lo_contentTemplate)
			) as $ls_element
		) {
			$ao_content->set($ls_element);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $ao_content
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Content $ao_content): void {
		$lo_contentTemplates = $this->getContentTemplates();
		$this->ensurePossibleTemplate($ao_content, $lo_contentTemplates);
		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_selectedContentTemplate */
		$lo_selectedContentTemplate = $lo_contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId]);

		$la_contentAreas = $this->getContentAreas($ao_content, $this->page);
		$this->ensurePossibleContentArea($ao_content, $la_contentAreas);

		$lo_possibleParentContents = $this->getPossibleParentContents($ao_content);
		$this->ensurePossibleParentId($ao_content, $lo_possibleParentContents, $lo_selectedContentTemplate);

		$la_assignedAttributes = $this->getAssignedAttributes($ao_content);

		$ls_languageShortcode = $this->request->getData('language_shortcode') ?: $this->page->languageShortcode;

		$la_contentElementsByFieldset = [];
		if (!empty($lo_selectedContentTemplate->contentTemplateElements)) {
			$la_contentElementsByFieldset = collection($lo_selectedContentTemplate->contentTemplateElements)->groupBy('fieldset')->toArray();
		}

		$la_columnWidths = $this->Contents->getColumnWidths();
		$la_columnWidths = array_map(function (ColumnInterface $ao_column): string {
			return $ao_column->getLabel();
		}, $la_columnWidths);

		$la_columnIndents = $this->Contents->getColumnIndents();
		$la_columnIndents = array_map(function (ColumnInterface $ao_column): string {
			return $ao_column->getLabel();
		}, $la_columnIndents);

		$this->set([
			'content' => $ao_content,
			'contentTemplates' => $lo_contentTemplates,
			'possibleParentContents' => $lo_possibleParentContents,
			'page' => $this->page,
			'assignedAttributes' => $la_assignedAttributes,
			'contentAreas' => $la_contentAreas,
			'contentElementsByFieldset' => $la_contentElementsByFieldset,
			'forScope' => $this->Contents->getForScope(),
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'languageShortcode' => $ls_languageShortcode,
			'columnWidths' => $la_columnWidths,
			'columnIndents' => $la_columnIndents,
		]);
	}
}
