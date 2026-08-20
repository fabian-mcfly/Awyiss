<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentArea;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\ContentTemplateElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnSystem\ColumnInterface;
use Awyiss\Utility\Inflector;
use Awyiss\Widget\WidgetsProvider;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


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
	 * @var string|null Session identifier for the selected page_id
	 */
	protected ?string $selectedPageIdSessionIdentifier = null;
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

		$this->selectedContentAreaIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.contentAreaId'
		;

		$this->selectedPageIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.pageId'
		;

		$this->selectedParentIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.parentId'
		;
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Contents
			->find('mediaAssignments', useMediaEntity: true)
			->where($this->getOverviewWhere())
			->contain(['ContentTemplates'])
		;
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
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

		$query = $this->getOverviewQuery();

		$contents = $query
			->formatResults(function (Collection $result): Collection {
				/** @var \Awyiss\Model\Entity\Content $content */
				foreach ($result as $content) {
					$content->class = $content->column['width']->getCssClass();

					if ($content->column['indent']) {
						$content->class .= ' ' . $content->column['indent']->getCssClass();
					}

					if ($content->columnRtl) {
						$content->class .= ' Column-RTL';
					}

					if ($content->columnLast) {
						$content->class .= ' Column-Last';
					}
				}

				return $result;
			})
			->find('threaded')
			->all()
		;

		$contents = $contents->groupBy('contentAreaId')->toArray();

		$contentAreas = array_column($this->page->pageTemplate->contentAreas, 'label', 'id');
		$unknownContentAreas = array_diff_key($contents, $contentAreas);
		foreach ($unknownContentAreas as $contentAreaId => $unknownContentAreaContents) {
			$contentAreas[ $contentAreaId ] = null;
		}

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystem\ColumnSystemInterface> $columnSystemClass */
		$columnSystemClass = $this->Contents->getColumnSystemClass();

		$contentTemplates = $this
			->getContentTemplates()
			->indexBy('id')
			->toArray()
		;
		array_map(function (ContentTemplate $contentTemplate) {
			// Build an array of assigned content elements, indexed by their identifier
			$contentTemplate->contentTemplateElements = collection($contentTemplate->contentTemplateElements)
				->indexBy('identifier')
				->toArray()
			;
			// Build an array of assigned content areas, indexed by their id
			$contentTemplate->contentAreaIds = collection($contentTemplate->contentAreas)
				->filter(function (ContentArea $contentArea) {
					return $contentArea->_joinData->pageTemplateId === $this->page->pageTemplateId;
				})
				->extract('id')
				->unique()
				->toList()
			;
		}, $contentTemplates);

		$this->set([
			'contents' => $contents,
			'contentAreas' => $contentAreas,
			'unknownContentAreas' => $unknownContentAreas,
			'page' => $this->page,
			'currentPageRole' => $this->Contents->getForScope(),
			'contentTemplates' => $contentTemplates,
			'columnWidths' => $this->Contents->getColumnWidths(),
			'columnIndents' => $this->Contents->getColumnIndents(),
			'columnSystemName' => $columnSystemClass::getName(),
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
		$pageId = (int)$this->request->getParam('pageId');
		$this->forPage($pageId, true);

		if (!$pageId) {
			throw new RedirectException(Router::url(['action' => 'add', 'pageId' => $this->page->id], true), 302);
		}

		$this->Authorization->ensure('create');

		$session = $this->request->getSession();
		$content = $this->Contents->newDefaultEntity([
			'pageId' => $this->page->id,
			'contentAreaId' => $session->read($this->selectedContentAreaIdSessionIdentifier),
			'parentId' => $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($content);
		}

		$this->setViewVars($content);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		/**
		 * @var \Awyiss\Model\Entity\Content $content
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$content = $this->Contents
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->forPage($content->pageId);

		$this->Authorization->ensure('update');

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($content, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this
				->viewBuilder()
				->setTemplate('edit_frontend_editor')
				->setLayout('frontend_editor')
			;
		}

		$this->setViewVars($content);

		$this->set('auditDataCount', $this->Contents->countAuditData($content));
		$this->set('isDuplicated', $this->Contents->exists(['duplicateOf' => $content->id]));
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

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->Contents->findById($id)->first();
		if (!$content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		//Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($content->pageId);
		$this->Authorization->ensure('delete');

		if ($this->Contents->delete($content)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));

				foreach ($content->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview', 'pageId' => $content->pageId]);
	}


	/**
	 * Save the column width of one content.
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function saveColumnWidth(): void {
		$request = Router::getRequest();

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->Contents->findById($request->getData('id'))->first();
		if (!$content) {
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
		$this->forPage($content->pageId);
		$this->Authorization->ensure('read');

		$content->set('columnWidth', $request->getData('width'));

		$this->Contents->save($content);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$content->hasErrors());
			$this->set('message', !$content->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($content->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$content->hasErrors()) {
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
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function saveSystemOrder(): void {
		$request = Router::getRequest();
		$table = $this->Contents;

		$affectedRows = $this->_saveSystemOrder($request->getData('order'), $table);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', true);
			$this->set('message', $affectedRows > 0 ? __d('System', 'system_order_saved') : __d('System', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}
		else {
			if ($affectedRows) {
				$this->Flash->success(__d('System', 'system_order_saved'));
			}
			else {
				$this->Flash->error(__d('System', 'system_order_not_saved'));
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
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function duplicateConfiguration(): void {
		$pageId = (int)$this->request->getParam('pageId');
		$this->forPage($pageId);

		$this->Authorization->ensure('read');

		$duplicateOfPage = null;

		if ($this->request->is('post') && $this->request->getData('duplicateOfPageId')) {
			$duplicateOfPage = $this->getPage((int)$this->request->getData('duplicateOfPageId'));

			$query = $this->Contents
				->find()
				->find('mediaAssignments', useMediaEntity: true)
				->where(['pageId' => $duplicateOfPage->id])
				->contain(['ContentTemplates'])
			;

			$contents = $query
				->formatResults(function (Collection $result): Collection {
					/** @var \Awyiss\Model\Entity\Content $content */
					foreach ($result as $content) {
						$content->class = $content->column['width']->getCssClass();

						if ($content->column['indent']) {
							$content->class .= ' ' . $content->column['indent']->getCssClass();
						}

						if ($content->columnRtl) {
							$content->class .= ' Column-RTL';
						}

						if ($content->columnLast) {
							$content->class .= ' Column-Last';
						}
					}

					return $result;
				})
				->find('threaded')
				->all()
			;

			$contents = $contents->groupBy('contentAreaId')->toArray();

			$contentAreas = array_column($this->page->pageTemplate->contentAreas, 'label', 'id');
			$unknownContentAreas = array_diff_key($contents, $contentAreas);
			foreach ($unknownContentAreas as $contentAreaId => $unknownContentAreasContents) {
				$contentAreas[ $contentAreaId ] = null;
			}

			$contentTemplates = $this
				->getContentTemplates()
				->indexBy('id')
				->toArray()
			;
			array_map(function (ContentTemplate $contentTemplate) {
				// Build an array of assigned content elements, indexed by their identifier
				$contentTemplate->contentTemplateElements = collection($contentTemplate->contentTemplateElements)
					->indexBy('identifier')
					->toArray()
				;
				// Build an array of assigned content areas, indexed by their id
				$contentTemplate->contentAreaIds = collection($contentTemplate->contentAreas)
					->filter(function ($contentArea) {
						return $contentArea->_joinData->pageTemplateId === $this->page->pageTemplateId;
					})
					->extract('id')
					->unique()
					->toList()
				;
			}, $contentTemplates);

			$this->set([
				'contents' => $contents,
				'contentAreas' => $contentAreas,
				'unknownContentAreas' => $unknownContentAreas,
				'attributes' => $this->Contents->getAttributes(),
			]);
		}

		$this->set([
			'page' => $this->page,
			'currentPageRole' => $this->Contents->getForScope(),
			'duplicateOfPage' => $duplicateOfPage,
			'duplicateOf' => $this->request->getData('duplicateOf'),
			'contentTemplateId' => $this->request->getData('contentTemplateId'),
		]);

		$this->viewBuilder()->setLayout('overlay_configuration');
	}


	/**
	 * Show a form to configure a widget
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function widgetConfiguration(): void {
		$this->Categories->disable();

		/** @var array<string, class-string<\Awyiss\Widget\WidgetInterface>> $widgetFiles */
		$widgetFiles = WidgetsProvider::getWidgetFiles();

		// Get the title of each widget
		$widgets = array_map(function (string $widgetClass) {
			return $widgetClass::getTitle();
		}, $widgetFiles);


		$frontendLanguage = LocaleMiddleware::getLanguage();
		$backendLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);

		$this->set([
			'widgetIdentifier' => $this->request->getData('widgetIdentifier'),
			'frontendLanguage' => $frontendLanguage,
			'userLanguage' => $backendLanguage,
			'widgets' => $widgets,
			'widgetClass' => $widgetFiles[ $this->request->getData('widgetIdentifier') ] ?? null,
			'settings' => $this->request->getData('settings') ?? [],
		]);

		$this->viewBuilder()->setLayout('overlay_configuration');
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Content $content, string $method = 'add'): void {
		$associated = [];
		if ($this->Contents->hasAttributes()) {
			$associated[] = $this->Contents->getAttributesTableName(true);
			$content->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();
		$requestData = $this->formatDataAttributes($requestData);

		$duplicateOf = null;
		if (!empty($requestData['duplicateOf'])) {
			/** @var \Awyiss\Model\Entity\Content $duplicateOf */
			$duplicateOf = $this->Contents->findById($requestData['duplicateOf'])->first();
			$requestData['contentTemplateId'] = $duplicateOf ? $duplicateOf->contentTemplateId : $this->getContentTemplates()->first()->id;
		}

		$this->Contents->patchEntity($content, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if ($duplicateOf && $duplicateOf->pageId === $content->pageId) {
			$content->duplicateOf = null;
		}

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			if ($content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($content->pageId);
				$this->Authorization->ensure($method === 'add' ? 'create' : 'update');
			}

			$this->unsetUnassignedElements($content);

			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->Contents->save($content, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedContentAreaIdSessionIdentifier, $content->contentAreaId);
				$session->write($this->selectedPageIdSessionIdentifier, $content->pageId);
				$session->write($this->selectedParentIdSessionIdentifier, $content->parentId);

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(
						Router::url(['action' => 'overview', 'lang' => $this->page->languageShortcode, 'pageId' => $content->pageId], true),
						302
					);
				}

				throw new RedirectException(
					Router::url(['action' => 'edit', 'lang' => $this->page->languageShortcode, 'id' => $content->id], true),
					302
				);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($content->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
		else {
			$this->Categories->ensurePossibleCategory($content);

			if ($content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($content->pageId);

				$this->Authorization->ensure($method === 'add' ? 'create' : 'update');
			}
		}
	}


	/**
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function requestLock(string $method = 'update'): void {
		$contentId = (int)$this->request->getData('id');

		$this->Categories->disable();

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->Contents->findById($contentId)->first();
		if (!$content) {
			$this
				->viewBuilder()
				->setClassName('Json')
				->setOption('serialize', ['data', 'status'])
			;

			// Set the response data
			$this->set([
				'data' => [],
				'status' => 'error',
			]);

			return;
		}

		$this->forPage($content->pageId);

		parent::requestLock($method);
	}


	/**
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function releaseLock(string $method = 'update'): void {
		$contentId = (int)$this->request->getData('id');

		$this->Categories->disable();

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->Contents->findById($contentId)->first();
		if (!$content) {
			$this
				->viewBuilder()
				->setClassName('Json')
				->setOption('serialize', ['data', 'status'])
			;

			// Set the response data
			$this->set([
				'data' => [],
				'status' => 'error',
			]);

			return;
		}

		$this->forPage($content->pageId);

		parent::releaseLock($method);
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 * @throws \Exception
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		// Create a flat array of all order data
		$orderData = [];
		foreach ($requestData as $itemsByContentAreaId) {
			foreach ($itemsByContentAreaId as $parentId => $items) {
				array_map(function (array $item) use (&$orderData, $parentId) {
					$orderData[] = $item + ['parentId' => $parentId ?: null];
				}, $items);
			}
		}

		// Get the page id of the first content
		$content = $table->findById($orderData[0]['id'])->first();

		// Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($content->pageId);
		$this->Authorization->ensure('read');

		// Now that we have the page id, we get all current contents
		$contents = $table
			->find()
			->where([
				'pageId' => $content->pageId,
			])
			->all()
		;

		// And make sure that all contents in the request data are part of the current contents
		$filteredOrderData = array_filter($orderData, function (array $item) use ($contents) {
			return $contents->firstMatch(['id' => $item['id']]);
		});

		// If the filtered order does not match the original order, we return 0
		if ($filteredOrderData !== $orderData) {
			return 0;
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$affectedRows = $table->updateAll(function (QueryExpression $expression) use ($orderData) {
			$contentAreaCase = $expression->case();
			$parentCase = $expression->case();
			$systemOrderCase = $expression->case();

			foreach ($orderData as $data) {
				$contentAreaCase->when(['id' => $data['id']])->then($data['contentAreaId'], 'integer');
				$parentCase->when(['id' => $data['id']])->then($data['parentId'], 'integer');
				$systemOrderCase->when(['id' => $data['id']])->then($data['systemOrder'], 'integer');
			}

			return [
				'contentAreaId' => $contentAreaCase,
				'parentId' => $parentCase,
				'systemOrder' => $systemOrderCase,
			];
		}, [
			'id IN' => array_column($orderData, 'id'),
		]);


		return $affectedRows;
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
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
			$pageTemplateId = $this->page->pageTemplateId;

			$query = $this->Contents->ContentTemplates
				->find('active')
				->select([
					'id',
					'title',
					'active',
				])
				->matching(
					'ContentAreas',
					fn(SelectQuery $query) => $query->where(['ContentTemplateContentAreas.pageTemplateId' => $pageTemplateId])
				)
				->contain([
					'ContentAreas',
					'ContentTemplateElements',
				])
			;

			$this->contentTemplates = $query->all()->indexBy('id');
		}


		return $this->contentTemplates;
	}


	/**
	 * Returns a collection of all possible parent contents for the given content
	 * to prevent circular references.
	 *
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return CollectionInterface
	 */
	protected function getPossibleParentContents(Content $content): CollectionInterface {
		if (!isset($this->threadedContents)) {
			if (empty($content->contentAreaId)) {
				return new Collection([]);
			}

			$query = $this->Contents
				->find()
				->find('mediaAssignments', useMediaEntity: true)
				->where([
					'pageId' => $content->pageId,
					'contentAreaId' => $content->contentAreaId,
				])
			;

			$this->threadedContents = $this->Contents->listNested($query);
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
	 * @param bool $allowFallback
	 * @return Page
	 * @throws \Exception
	 */
	protected function forPage(int $pageId, bool $allowFallback = false): Page {
		if (!$pageId && $allowFallback) {
			$session = $this->request->getSession();
			$pageId = $session->read($this->selectedPageIdSessionIdentifier);

			if (!$pageId) {
				// Find the first page of pagerole `page`
				$pageTable = $this->fetchTable('Pages');
				/** @var \Awyiss\Model\Entity\Page $page */
				$page = $pageTable
					->find()
					->select('id')
					->where([
						'pageRoleId' => 1,
						'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
					])
					->orderBy([
						'Pages.deleted' => 'ASC',
						'Pages.parentsActive' => 'DESC',
						'Pages.active' => 'DESC',
						'Pages.parentId' => 'ASC',
					])
					->first()
				;

				$pageId = $page?->id;
			}
		}

		if (!$pageId) {
			$this->Flash->error(__d('Pages', 'record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'dashboard', 'action' => 'overview'], true), 404);
		}

		try {
			$page = $this->getPage($pageId);
		}
		catch (RecordNotFoundException | InvalidPrimaryKeyException) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'dashboard', 'action' => 'overview'], true), 404);
		}

		$this->page = $page;

		$languageShortcode = null;
		if ($this->request->is(['patch', 'post', 'put']) && in_array($this->request->getParam('action'), ['add', 'edit'])) {
			$languageShortcode = $this->request->getData('languageShortcode');
		}

		$this->Categories->setConfig([
			'finder' => [
				/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
				'forCurrentLanguage' => [
					'languageShortcode' => $languageShortcode,
				],
			],
			'selectedCategory' => $page->id,
		]);

		$this->Contents->forPageRole($page->pageRoleId);

		if (!$this->request->is(['patch', 'post', 'put']) || $this->request->getParam('action') !== 'edit') {
			if ($page->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
				throw new RedirectException(Router::url([
					'lang' => $page->languageShortcode,
					'pageId' => $page->id,
				], true), 302);
			}
		}

		$this->Authorization->setScope($this->Contents->getForScope());

		if (!Configure::read('Awyiss.' . Inflector::camelize($this->Contents->getForScope()) . '.Backend.contents.enabled')) {
			$this->Flash->error(__d($this->Contents->getForScope(), 'contents_disabled'));
			throw new RedirectException(Router::url(['controller' => $this->Contents->getForScope(), 'action' => 'overview'], true), 404);
		}


		return $page;
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param CollectionInterface $threadedContents
	 * @param \Awyiss\Model\Entity\ContentTemplate|null $selectedContentTemplate
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function ensurePossibleParentId(
		Content $content,
		CollectionInterface $threadedContents,
		?ContentTemplate $selectedContentTemplate
	): void {
		// Extract all possible parent ids
		$possibleParentIds = $threadedContents->extract('id')->toList();

		// Build an array of assigned content elements, indexed by their identifier
		$assignedContentElements = $selectedContentTemplate
			? collection($selectedContentTemplate->contentTemplateElements)
				->indexBy('identifier')
				->toArray()
			: [];

		$content->setDirty('parentId', false);

		// If the parent_id is not in the list of possible parent ids or the parent_id is not assigned to the selected content template
		if (
			$content->parentId
			&& (
				!in_array($content->parentId, $possibleParentIds)
				|| !isset($assignedContentElements['parentId'])
			)
		) {
			// Remember the errors
			$errors = $content->getError('parentId');

			// If the parent_id is required and there are possible parent ids, set the parent_id to the first possible parent id
			if (($assignedContentElements['parentId'] ?? null)?->required === true && $possibleParentIds) {
				$content->parentId = reset($possibleParentIds);
			}
			// Otherwise, set the parent_id to null
			else {
				$content->parentId = null;
			}

			// If there were errors, set them again
			if ($errors) {
				$content->setError('parentId', $errors);
			}

			$request = $this->getRequest();
			//When parent_id is part of the request data, overwrite it since it might be outdated
			if ($request->getData('parentId') !== null) {
				$request = $request->withData('parentId', $content->parentId);
				$this->setRequest($request);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param CollectionInterface $contentTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Content $content, CollectionInterface $contentTemplates): void {
		if (!$content->contentTemplateId || !$contentTemplates->firstMatch(['id' => $content->contentTemplateId])) {
			$errors = $content->getError('contentTemplateId');

			$content->contentTemplate = $contentTemplates->first();
			$content->contentTemplateId = $content->contentTemplate?->id;

			if ($errors) {
				$content->setError('contentTemplateId', $errors);
			}
		}
		elseif (!$content->contentTemplate) {
			$content->contentTemplate = $contentTemplates->firstMatch(['id' => $content->contentTemplateId]);
		}

		$request = $this->getRequest();
		//When contentTemplateId is part of the request data, overwrite it since it might be outdated
		if ($request->getData('contentTemplateId') !== null) {
			$request = $request->withData('contentTemplateId', $content->contentTemplateId);
			$this->setRequest($request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param array $availableContentAreas
	 * @return void
	 */
	protected function ensurePossibleContentArea(Content $content, array $availableContentAreas = []): void {
		if (empty($content->contentAreaId) || !in_array($content->contentAreaId, array_column($availableContentAreas['all'], 'id'))) {
			$errors = $content->getError('contentAreaId');

			if (!$availableContentAreas['available']) {
				return;
			}

			$content->contentArea = reset($availableContentAreas['available']);
			$content->contentAreaId = $content->contentArea->id;

			if ($errors) {
				$content->setError('contentAreaId', $errors);
			}

			$request = $this->getRequest();
			//When contentAreaId is part of the request data, overwrite it since it might be outdated
			if ($request->getData('contentAreaId') !== null) {
				$request = $request->withData('contentAreaId', $content->contentAreaId);
				$this->setRequest($request);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param Page|null $page
	 * @return array
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function getContentAreas(Content $content, ?Page $page = null): array {
		$page ??= $this->getPage($content->pageId);

		/** @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate */
		$contentTemplate = $this->contentTemplates->firstMatch(['id' => $content->contentTemplateId]);
		if (!$contentTemplate) {
			return [
				'available' => [],
				'unavailable' => [],
				'all' => [],
			];
		}

		$unavailableContentAreas = $contentAreas = collection($page->pageTemplate->contentAreas)->indexBy('id')->toArray();

		$availableContentAreas = [];

		/** @var \Awyiss\Model\Entity\ContentArea $contentArea */
		foreach ($contentTemplate->contentAreas ?? [] as $contentArea) {
			if ($contentArea->_joinData->pageTemplateId != $page->pageTemplateId) {
				continue;
			}

			if (isset($unavailableContentAreas[ $contentArea->id ])) {
				$availableContentAreas[ $contentArea->id ] = $unavailableContentAreas[ $contentArea->id ];
				unset($unavailableContentAreas[ $contentArea->id ]);
			}
		}

		return [
			'available' => $availableContentAreas,
			'unavailable' => $unavailableContentAreas,
			'all' => $contentAreas,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return void
	 */
	protected function unsetUnassignedElements(Content $content): void {
		$contentTemplates = $this->getContentTemplates();
		$contentTemplate = $contentTemplates->firstMatch(['id' => $content->contentTemplateId]);

		foreach (
			array_diff(
				array_keys($this->Contents->ContentTemplates->getAvailableContentElements()),
				array_column($contentTemplate->contentTemplateElements ?? [], 'identifier')
			) as $element
		) {
			if ($element === 'columnWidth') {
				$columnWidths = $this->Contents->getColumnWidths();

				$content->set($element, key($columnWidths));

				continue;
			}

			$content->set($element);
		}

		$contentAttributes = $this->Contents->ContentTemplates->getAvailableContentAttributes(true);
		$contentAttributes = array_column($contentAttributes, 'identifier');

		foreach (
			array_diff(
				$contentAttributes,
				$this->Contents->ContentTemplates->getAssignedContentAttributes($contentTemplate)
			) as $element
		) {
			$content->set($element);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Content $content): void {
		$contentTemplates = $this->getContentTemplates();
		$this->ensurePossibleTemplate($content, $contentTemplates);

		/** @var \Awyiss\Model\Entity\ContentTemplate $selectedContentTemplate */
		$selectedContentTemplate = $contentTemplates->firstMatch(['id' => $content->contentTemplateId]);

		$contentAreas = $this->getContentAreas($content, $this->page);
		$this->ensurePossibleContentArea($content, $contentAreas);

		$possibleParentContents = $this->getPossibleParentContents($content);
		$this->ensurePossibleParentId($content, $possibleParentContents, $selectedContentTemplate);

		$assignedAttributes = $this->getAssignedAttributes($content);

		$languageShortcode = $this->request->getData('languageShortcode') ?: $this->page->languageShortcode;

		$contentElementsByFieldset = [];
		if (!empty($selectedContentTemplate->contentTemplateElements)) {
			$contentElementsByFieldset = collection($selectedContentTemplate->contentTemplateElements)->groupBy('fieldset')->toArray();

			foreach ($contentElementsByFieldset as $fieldset => $contentElements) {
				$contentElementsByFieldset[ $fieldset ] = collection($contentElements)
					->indexBy(function (ContentTemplateElement $entity) {
						return Inflector::variable($entity->identifier);
					})
					->toArray()
				;
			}
		}

		$columnWidths = $this->Contents->getColumnWidths();
		$columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnWidths);

		$columnIndents = $this->Contents->getColumnIndents();
		$columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnIndents);

		$allowedKeys = [];
		if ($content->duplicateOf) {
			$allowedKeys = $this->Contents->getAllowedKeyForDuplicating();

			$content->duplicateOfContent = $this->Contents
				->findById($content->duplicateOf)
				->find('mediaAssignments', useMediaEntity: true)
				->first()
			;

			if (!$content->duplicateOfContent || $content->duplicateOfContent->id !== $content->duplicateOf) {
				$content->duplicateOfContent = null;
			}
		}

		$content->page = $this->page;

		$this->set([
			'content' => $content,
			'contentTemplates' => $contentTemplates,
			'possibleParentContents' => $possibleParentContents,
			'page' => $this->page,
			'assignedAttributes' => $assignedAttributes,
			'contentAreas' => $contentAreas,
			'contentElementsByFieldset' => $contentElementsByFieldset,
			'currentPageRole' => $this->Contents->getForScope(),
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'languageShortcode' => $languageShortcode,
			'columnWidths' => $columnWidths,
			'columnIndents' => $columnIndents,
			/** @uses \Awyiss\Model\Table::findActive() */
			'forms' => $this->Contents->Forms
				->find('active')
				->orderByAsc('title')
				->all(),
			'linkTargets' => $this->findLinkablePages(),
			/** @uses \Awyiss\Model\Table::findActive() */
			'surveys' => $this->Contents->Surveys
				->find('active')
				->orderByAsc('title')
				->all(),
			'allowedKeys' => $allowedKeys,
			'expertMode' => $this->request->getParam('expertMode'),
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected function formatDataAttributes(array $data): array {
		if (empty($data['data'])) {
			unset($data['data']);

			return $data;
		}


		$dataSet = [];

		foreach ((array)$data['data'] as $key => $value) {
			if (!is_numeric($key)) {
				if (empty($value)) {
					continue;
				}

				$dataSet[ $key ] = $value;
				continue;
			}

			if (isset($value['key']) && $value['key'] !== '' && isset($value['value']) && $value['value'] !== '') {
				$dataSet[ $value['key'] ] = $value['value'];
			}
		}

		$data['data'] = $dataSet;

		// Update the request data
		$request = $this->request->withData('data', $dataSet);
		$this->setRequest($request);

		return $data;
	}


	/**
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function findLinkablePages(): CollectionInterface {
		// Get all page roles that can be included in the link list
		/** @uses \Awyiss\Model\Table::findActive() */
		$pageRoles = $this
			->fetchTable('PageRoles')
			->find('active')
			->where(['includeInLinklist' => true])
			->all()
			->indexBy('id')
			->toArray()
		;

		/**
		 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
		 * @uses \Awyiss\Model\Table::findActive()
		 */
		$pagesTable = $this->fetchTable('Pages');
		$query = $pagesTable
			->find('active')
			->find('forCurrentLanguage', skipPageRoleCheck: true)
			->where([
				'pageRoleId IN' => array_keys($pageRoles),
			])
		;

		return $pagesTable->listNested($query);
	}
}
