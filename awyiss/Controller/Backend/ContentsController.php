<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\ContentTemplateElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\Module\ModulesProvider;
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
	 * @var string|null Session identifier for the selected content_area_id
	 */
	protected ?string $selectedContentAreaIdSessionIdentifier = null;
	/**
	 * @var string|null Session identifier for the selected parent_id
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedContents;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->selectedContentAreaIdSessionIdentifier = Inflector::underscore($this->getName()) . '.' . ($this->request->getParam('lang') ?? 'global') . '.content_area_id';
		$this->selectedParentIdSessionIdentifier = Inflector::underscore($this->getName()) . '.' . ($this->request->getParam('lang') ?? 'global') . '.parent_id';
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/**
		 * Using `$this->Contents->find()` instead of
		 * `$this->Contents->Pages->loadInto($lo_page, ['Contents'])->contents, $lo_contents->count();`
		 * because `nestedByContentArea()` works with a Query, not an array.
		 * This could be changed, but I fail to see any benefits
		 */
		$lo_query = $this->Contents->find()->where($this->getOverviewWhere())->contain(['ContentTemplates']);
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function overview(): void {
		$this->forPage((int)$this->request->getParam('pageId'));

		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		$lo_contents = $lo_query->find('mediaAssignments', useMediaEntity: true)->formatResults(function (Collection $result): Collection {
			/** @var \Awyiss\Model\Entity\Content $lo_content */
			foreach ($result as $lo_content) {
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


			return $result;
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
		array_map(function (ContentTemplate $contentTemplate) {
			// Build an array of assigned content elements, indexed by their identifier
			$contentTemplate->contentTemplateElements = collection($contentTemplate->contentTemplateElements)->indexBy('identifier')->toArray();
			// Build an array of assigned content areas, indexed by their id
			$contentTemplate->contentAreaIds = collection($contentTemplate->contentAreas)->filter(function ($contentArea) {
				return $contentArea->_joinData->pageTemplateId === $this->page->pageTemplateId;
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

		$lo_session = $this->request->getSession();
		$lo_content = $this->Contents->newDefaultEntity([
			'pageId' => $li_pageId,
			'contentAreaId' => $lo_session->read($this->selectedContentAreaIdSessionIdentifier),
			'parentId' => $lo_session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_content);
		}

		$this->setViewVars($lo_content);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->forPage($lo_content->pageId);

		$this->Authorization->ensure('update');

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_content, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this->viewBuilder()
			->setTemplate('edit_frontend_editor')
			->setLayout('frontend_editor');
		}

		$this->setViewVars($lo_content);

		/** @noinspection PhpUndefinedMethodInspection */
		$this->set('auditDataCount', $this->Contents->countAuditData($lo_content));
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->request->allowMethod(['get', 'delete']);

		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($id)->first();
		if (!$lo_content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		//Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($lo_content->pageId);
		$this->Authorization->ensure('delete');

		if ($this->Contents->delete($lo_content)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
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
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
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
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__('edit_succeeded'));
				}
			}
			else {
				$this->Flash->error(__('edit_failed'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function saveSystemOrder(): void {
		$lo_request = Router::getRequest();
		$lo_table = $this->Contents;

		$li_affectedRows = $this->_saveSystemOrder($lo_request->getData('order'), $lo_table);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', true);
			$this->set('message', $li_affectedRows > 0 ? __d('system', 'system_order_saved') : __d('system', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}
		else {
			if ($li_affectedRows) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__d('system', 'system_order_saved'));
				}
			}
			else {
				$this->Flash->error(__d('system', 'system_order_not_saved'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * Show a form to configure a duplicateOf field
	 *
	 * @return void
	 * @throws \ReflectionException|\Exception
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function duplicateConfiguration(): void {
		$li_pageId = (int)$this->request->getParam('pageId');
		$this->forPage($li_pageId);

		$this->Authorization->ensure('read');

		$lo_duplicateOfPage = null;

		if ($this->request->is('post') && $this->request->getData('duplicate_of_page_id')) {
			$lo_duplicateOfPage = $this->getPage((int)$this->request->getData('duplicate_of_page_id'));

			$lo_query = $this->Contents->find()->find('mediaAssignments', useMediaEntity: true)->where(['page_id' => $lo_duplicateOfPage->id])->contain(['ContentTemplates']);

			$lo_contents = $lo_query->formatResults(function (Collection $result): Collection {
				/** @var \Awyiss\Model\Entity\Content $lo_content */
				foreach ($result as $lo_content) {
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

				return $result;
			})->find('threaded')->all();

			$la_contents = $lo_contents->groupBy('contentAreaId')->toArray();

			$la_contentAreas = array_combine(array_column($this->page->pageTemplate->contentAreas, 'id'), array_column($this->page->pageTemplate->contentAreas, 'label'));
			$la_unknownContentAreas = array_diff_key($la_contents, $la_contentAreas);
			foreach ($la_unknownContentAreas as $li_contentAreaId => $lo_contents) {
				$la_contentAreas[ $li_contentAreaId ] = null;
			}

			$la_contentTemplates = $this->getContentTemplates()->indexBy('id')->toArray();
			array_map(function (ContentTemplate $contentTemplate) {
				// Build an array of assigned content elements, indexed by their identifier
				$contentTemplate->contentTemplateElements = collection($contentTemplate->contentTemplateElements)->indexBy('identifier')->toArray();
				// Build an array of assigned content areas, indexed by their id
				$contentTemplate->contentAreaIds = collection($contentTemplate->contentAreas)->filter(function ($contentArea) {
					return $contentArea->_joinData->pageTemplateId === $this->page->pageTemplateId;
				})->extract('id')->unique()->toList();
			}, $la_contentTemplates);

			$this->set([
				'contents' => $la_contents,
				'contentAreas' => $la_contentAreas,
				'unknownContentAreas' => $la_unknownContentAreas,
				'attributes' => $this->Contents->getAttributes(),
			]);
		}

		$this->set([
			'page' => $this->page,
			'forScope' => $this->Contents->getForScope(),
			'duplicateOfPage' => $lo_duplicateOfPage,
			'duplicateOf' => $this->request->getData('duplicate_of'),
			'contentTemplateId' => $this->request->getData('content_template_id'),
		]);

		$this->viewBuilder()->setLayout('overlay_configuration');
	}


	/**
	 * Show a form to configure a module
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function moduleConfiguration(): void {
		$this->Categories->disable();

		/** @var array<string, class-string<\Awyiss\Module\ModuleInterface>> $la_moduleFiles */
		$la_moduleFiles = ModulesProvider::getModuleFiles();

		// Get the title of each module
		$la_modules = array_map(function (string $moduleClass) {
			return $moduleClass::getTitle();
		}, $la_moduleFiles);


		$lo_frontendLanguage = LocaleMiddleware::getLanguage();
		$lo_backendLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);

		$this->set([
			'module_identifier' => $this->request->getData('module_identifier'),
			'frontendLanguage' => $lo_frontendLanguage,
			'userLanguage' => $lo_backendLanguage,
			'modules' => $la_modules,
			'moduleClass' => $la_moduleFiles[ $this->request->getData('module_identifier') ] ?? null,
			'settings' => $this->request->getData('settings') ?? [],
		]);

		$this->viewBuilder()->setLayout('overlay_configuration');
	}


	/**
	 * @param Content $content
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Content $content, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Contents->hasAttributes()) {
			$la_associated[] = $this->Contents->getAttributesTableName(true);
			$content->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		$la_data = $this->formatDataAttributes($la_data);

		$lo_duplicateOf = null;
		if (!empty($la_data['duplicate_of'])) {
			/** @var \Awyiss\Model\Entity\Content $lo_duplicateOf */
			$lo_duplicateOf = $this->Contents->findById($la_data['duplicate_of'])->first();
			if ($lo_duplicateOf) {
				$la_data['content_template_id'] = $lo_duplicateOf->contentTemplateId;
			}
			else {
				$la_data['content_template_id'] = $this->getContentTemplates()->first()->id;
			}
		}

		$this->Contents->patchEntity($content, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if ($lo_duplicateOf && $lo_duplicateOf->pageId === $content->pageId) {
			$content->duplicateOf = null;
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($content->pageId);
				$this->Authorization->ensure($method === 'add' ? 'create' : 'update');
			}

			$this->unsetUnassignedElements($content);

			if ($this->Contents->save($content, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$lo_session = $this->request->getSession();
				$lo_session->write($this->selectedContentAreaIdSessionIdentifier, $content->contentAreaId);
				$lo_session->write($this->selectedParentIdSessionIdentifier, $content->parentId);

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $this->page->languageShortcode, 'pageId' => $content->pageId], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $this->page->languageShortcode, 'id' => $content->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($content->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Contents->getSystemOrderRelatedColumns($content)) {
				$content->systemOrder = null;
			}
			else {
				$content->systemOrder = $content->hasOriginal('systemOrder') ? $content->getOriginal('systemOrder') : $content->get('systemOrder');
			}

			$this->Categories->ensurePossibleCategory($content);

			if ($content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($content->pageId);

				$this->Authorization->ensure($method === 'add' ? 'create' : 'update');
			}
		}
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 * @throws \Exception
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		// Create a flat array of all order data
		$la_orderData = [];
		foreach ($requestData as $la_itemsByContentAreaId) {
			foreach ($la_itemsByContentAreaId as $li_parentId => $la_items) {
				array_map(function (array $item) use (&$la_orderData, $li_parentId) {
					$la_orderData[] = $item + ['parentId' => $li_parentId ?: null];
				}, $la_items);
			}
		}

		// Get the page id of the first content
		$lo_content = $table->findById($la_orderData[0]['id'])->first();

		// Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($lo_content->pageId);
		$this->Authorization->ensure('read');

		// Now that we have the page id, we get all current contents
		$lo_contents = $table->find()->where([
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
		$li_affectedRows = $table->updateAll(function (QueryExpression $expression) use ($la_orderData) {
			$lo_contentAreaCase = $expression->case();
			$lo_parentCase = $expression->case();
			$lo_systemOrderCase = $expression->case();

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
	 * @param Content $content
	 * @return array
	 */
	protected function getAssignedAttributes(Content $content): array {
		if (empty($content->contentTemplate)) {
			return [];
		}


		return $this->Contents->ContentTemplates->getAssignedContentAttributes($content->contentTemplate);
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
			])->matching('ContentAreas', function (SelectQuery $query) use ($li_pageTemplateId) {
				return $query->where(['ContentTemplateContentAreas.page_template_id' => $li_pageTemplateId]);
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
	 * @param Content $content
	 * @return CollectionInterface
	 */
	protected function getPossibleParentContents(Content $content): CollectionInterface {
		if (!isset($this->threadedContents)) {
			if (empty($content->contentAreaId)) {
				return new Collection([]);
			}

			$lo_query = $this->Contents->find()->find('mediaAssignments', useMediaEntity: true)->where([
				'page_id' => $content->pageId,
				'content_area_id' => $content->contentAreaId,
			]);

			$this->threadedContents = $this->Contents->listNested($lo_query);
		}


		return $this->Contents->getPossibleParents($content, $this->threadedContents);
	}


	/**
	 * Returns and caches a Page object.
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 * @see Page
	 */
	protected function getPage(int $pageId): Page {
		if (!isset($this->pages[ $pageId ])) {
			$this->pages[ $pageId ] = $this->Contents->getPage($pageId);
		}


		return $this->pages[ $pageId ];
	}


	/**
	 * Sets the Contents table to use the page role of a page with the given id
	 * Requesting a page that does not exist or without having read access to the scope of the page (page role),
	 * a redirect exception is thrown.
	 *
	 * @param int $pageId
	 * @return Page
	 * @throws \Exception
	 */
	protected function forPage(int $pageId): Page {
		try {
			$lo_page = $this->getPage($pageId);
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
			'selectedCategory' => $pageId,
		]);

		$this->Contents->forPageRole($lo_page->pageRoleId);

		if (!$this->request->is(['patch', 'post', 'put']) || $this->request->getParam('action') !== 'edit') {
			if ($lo_page->language_shortcode != LocaleMiddleware::getLanguage()->shortcode) {
				throw new RedirectException(Router::url([
					'lang' => $lo_page->languageShortcode,
					'pageId' => $pageId,
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
	 * @param Content $content
	 * @param CollectionInterface $threadedContents
	 * @param \Awyiss\Model\Entity\ContentTemplate|null $selectedContentTemplate
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function ensurePossibleParentId(Content $content, CollectionInterface $threadedContents, ?ContentTemplate $selectedContentTemplate): void {
		// Extract all possible parent ids
		$la_possibleParentIds = $threadedContents->extract('id')->toList();

		// Build an array of assigned content elements, indexed by their identifier
		$la_assignedContentElements = $selectedContentTemplate ? collection($selectedContentTemplate->contentTemplateElements)->indexBy('identifier')->toArray() : [];

		// If the parent_id is not in the list of possible parent ids or the parent_id is not assigned to the selected content template
		if (
			$content->parentId &&
			(
				!in_array($content->parentId, $la_possibleParentIds) ||
				!isset($la_assignedContentElements['parent_id'])
			)
		) {
			// Remember the errors
			$la_errors = $content->getError('parentId');

			// If the parent_id is required and there are possible parent ids, set the parent_id to the first possible parent id
			if (($la_assignedContentElements['parent_id'] ?? null)?->required === true && $la_possibleParentIds) {
				$content->parentId = reset($la_possibleParentIds);
			}
			// Otherwise, set the parent_id to null
			else {
				$content->parentId = null;
			}

			// If there were errors, set them again
			if ($la_errors) {
				$content->setError('parentId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When parent_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('parent_id') !== null) {
				$lo_request = $lo_request->withData('parent_id', $content->parentId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Content $content
	 * @param CollectionInterface $contentTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Content $content, CollectionInterface $contentTemplates): void {
		if (!$content->contentTemplateId || !$contentTemplates->firstMatch(['id' => $content->contentTemplateId])) {
			$la_errors = $content->getError('contentTemplateId');

			$content->contentTemplate = $contentTemplates->first();
			$content->contentTemplateId = $content->contentTemplate?->id;

			if ($la_errors) {
				$content->setError('contentTemplateId', $la_errors);
			}
		}
		elseif (!$content->contentTemplate) {
			$content->contentTemplate = $contentTemplates->firstMatch(['id' => $content->contentTemplateId]);
		}

		$lo_request = $this->getRequest();
		//When content_template_id is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('content_template_id') !== null) {
			$lo_request = $lo_request->withData('content_template_id', $content->contentTemplateId);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param Content $content
	 * @param array $availableContentAreas
	 * @return void
	 */
	protected function ensurePossibleContentArea(Content $content, array $availableContentAreas = []): void {
		if (empty($content->contentAreaId) || !in_array($content->contentAreaId, array_column($availableContentAreas['all'], 'id'))) {
			$la_errors = $content->getError('contentAreaId');

			if (!$availableContentAreas['available']) {
				return;
			}

			$content->contentArea = reset($availableContentAreas['available']);
			$content->contentAreaId = $content->contentArea->id;

			if ($la_errors) {
				$content->setError('contentAreaId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When content_area_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('content_area_id') !== null) {
				$lo_request = $lo_request->withData('content_area_id', $content->contentAreaId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Content $content
	 * @param Page|null $page
	 * @return array
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function getContentAreas(Content $content, ?Page $page = null): array {
		$lo_page = $page ?? $this->getPage($content->pageId);

		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->contentTemplates->firstMatch(['id' => $content->contentTemplateId]);
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
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return void
	 */
	protected function unsetUnassignedElements(Content $content): void {
		$lo_contentTemplates = $this->getContentTemplates();
		$lo_contentTemplate = $lo_contentTemplates->firstMatch(['id' => $content->contentTemplateId]);

		foreach (
			array_diff(
				array_keys($this->Contents->ContentTemplates->getAvailableContentElements()),
				array_column($lo_contentTemplate->contentTemplateElements ?? [], 'identifier')
			) as $ls_element
		) {
			if ($ls_element === 'column_width') {
				$la_columnWidths = $this->Contents->getColumnWidths();

				$content->set($ls_element, key($la_columnWidths));

				continue;
			}

			$content->set($ls_element);
		}

		$la_contentAttributes = $this->Contents->ContentTemplates->getAvailableContentAttributes(true);
		$la_contentAttributes = array_column($la_contentAttributes, 'identifier');

		foreach (
			array_diff(
				$la_contentAttributes,
				$this->Contents->ContentTemplates->getAssignedContentAttributes($lo_contentTemplate)
			) as $ls_element
		) {
			$content->set($ls_element);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Content $content): void {
		$lo_contentTemplates = $this->getContentTemplates();
		$this->ensurePossibleTemplate($content, $lo_contentTemplates);

		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_selectedContentTemplate */
		$lo_selectedContentTemplate = $lo_contentTemplates->firstMatch(['id' => $content->contentTemplateId]);

		$la_contentAreas = $this->getContentAreas($content, $this->page);
		$this->ensurePossibleContentArea($content, $la_contentAreas);

		$lo_possibleParentContents = $this->getPossibleParentContents($content);
		$this->ensurePossibleParentId($content, $lo_possibleParentContents, $lo_selectedContentTemplate);

		$la_assignedAttributes = $this->getAssignedAttributes($content);

		$ls_languageShortcode = $this->request->getData('language_shortcode') ?: $this->page->languageShortcode;

		$la_contentElementsByFieldset = [];
		if (!empty($lo_selectedContentTemplate->contentTemplateElements)) {
			$la_contentElementsByFieldset = collection($lo_selectedContentTemplate->contentTemplateElements)->groupBy('fieldset')->toArray();

			foreach ($la_contentElementsByFieldset as $ls_fieldset => $la_contentElements) {
				$la_contentElementsByFieldset[ $ls_fieldset ] = collection($la_contentElements)->indexBy(function (ContentTemplateElement $entity) {
					return Inflector::variable($entity->identifier);
				})->toArray();
			}
		}

		$la_columnWidths = $this->Contents->getColumnWidths();
		$la_columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnWidths);

		$la_columnIndents = $this->Contents->getColumnIndents();
		$la_columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnIndents);

		$la_allowedKeys = [];
		if ($content->duplicateOf) {
			$la_allowedKeys = $this->Contents->getAllowedKeyForDuplicating();

			$content->duplicateOfContent = $this->Contents->findById($content->duplicateOf)->find('mediaAssignments', useMediaEntity: true)->first();

			if (!$content->duplicateOfContent || $content->duplicateOfContent->id !== $content->duplicateOf) {
				$content->duplicateOfContent = null;
			}
		}

		$this->set([
			'content' => $content,
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
			'allowedKeys' => $la_allowedKeys,
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected function formatDataAttributes(array $data): array {
		$la_data = $data;

		if (empty($la_data['data'])) {
			unset($la_data['data']);

			return $la_data;
		}


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

		return $la_data;
	}
}
