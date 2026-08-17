<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use AllowDynamicProperties;
use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\View\Exception\MissingTemplateException;
use RuntimeException;


/**
 * Pages Controller
 *
 * @property \Awyiss\Model\Table\PagesTable $Pages
 */
#[AllowDynamicProperties]
class PagesController extends Controller {
	/**
	 * @var int|null Forced root page id when categories are disabled
	 */
	protected ?int $forcedRootPageId = null;
	/**
	 * @var bool Nesting enabled
	 */
	protected bool $nestable = true;
	/**
	 * @var \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	protected PageRoleEnumInterface $pageRole;
	/**
	 * @var string Page Role name
	 */
	protected string $pageRoleName = 'page';
	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected CollectionInterface $pageTemplates;
	/**
	 * @var string|null Session identifier for the selected parentId
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;
	/**
	 * @var bool Manual sorting enabled
	 */
	protected bool $sortable = true;
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'autoload' => ['add', 'addBatch', 'edit'],
	];
	/**
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected CollectionInterface $threadedPages;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->selectedParentIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.parentId'
		;

		if (!($this->categories['enabled'] ?? false)) {
			$this->forcedRootPageId = Configure::read('Awyiss.' . $this->getName() . '.Frontend.categories.forcedRootPageId');
		}
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query = $this->Pages->find('forCurrentLanguage')->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this
			->getOverviewQuery()
			->find('mediaAssignments')
			->contain([
				'PageTemplates.ContentAreas.ContentTemplates',
			])
		;

		// Disable sorting if the current category is the aggregation category or the unassigned category
		if (
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('aggregationKey')
			|| $this->Categories->getSelectedCategory() === $this->Categories->getConfig('unassignedKey')
		) {
			$this->sortable = false;
		}

		$paginated = $this->paginate['enabled'];
		unset($this->paginate['enabled']);
		if ($paginated) {
			$pages = $this->paginate($query);
		}
		elseif ($this->nestable) {
			$pages = $query->find('threaded');
		}
		else {
			$pages = $query->all();
		}

		$pageTemplates = $this
			->getPageTemplates()
			->indexBy('id')
			->toArray()
		;

		$this->set([
			'pages' => $pages,
			//'localConfig' => LocalConfig::read(),
			'contentsEnabled' => LocalConfig::read('contents.enabled'),
			'paginated' => $paginated,
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			'attributes' => $this->Pages->getAttributes(),
			'pageTemplates' => $pageTemplates,
			'isGenericPage' => $this->pageRole->value !== 1,
			'pageRole' => $this->Pages->PageRoles->get($this->getPageRole()->value),
			'pageRoleName' => Inflector::camelize($this->pageRoleName),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$session = $this->request->getSession();
		$page = $this->Pages->newDefaultEntity([
			'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
			'pageRoleId' => $this->getPageRole(),
			'parentId' => $this->forcedRootPageId ?? $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($page);
		}

		$this->setViewVars($page);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function addBatch(): void {
		$this->Authorization->ensure('create');

		$page = $this->Pages->newDefaultEntity([
			'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
			'pageRoleId' => $this->getPageRole(),
		]);

		$requestData = $this->request->getData();
		if ($this->request->is('post')) {
			$associated = [];
			if ($this->Pages->hasAttributes()) {
				$associated[] = $this->Pages->getAttributesTableName(true);
				$page->setAccess('attributes', true);
			}

			$data = [
				'pageRoleId' => $this->getPageRole()->value,
				'slug' => 'dummy',
				'title' => 'dummy',
			];
			$data += $this->request->getData();

			$this->Pages->patchEntity($page, $data, [
				'associated' => $associated,
				'validate' => !$this->request->getData('reloadForm'),
			]);

			$this->Categories->setConfig('finder', [
				'forCurrentLanguage' => [
					'entity' => $page,
				],
			]);

			$entities = null;
			if (!empty($requestData['pages'])) {
				$entities = $this->buildEntitiesFromIndentedRows($requestData['pages'], $requestData);
			}

			if (
				//reloadForm is set when we need to reload options based on current values
				!$this->request->getData('reloadForm')
				&& $entities?->count()
			) {
				$success = false;

				if ($entities->count()) {
					$pageRole = Inflector::pluralize($this->getPageRole()->name);

					$associated = ['Child' . $pageRole];
					if ($this->Pages->hasAttributes()) {
						$associated[] = $this->Pages->getAttributesTableName(true);
					}

					if ($this->Pages->saveMany($entities, ['associated' => $associated])) {
						$success = true;
					}
					else {
						$page = $entities->first();
					}
				}

				if ($success) {
					if (!$this->request->is('ajax')) {
						$this->Flash->success(__df($this->pageRoleName, 'Pages', 'add_batch_succeeded'));
					}

					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifyCategorySelection($page);

					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $page->languageShortcode], true), 302);
				}
				else {
					if (!$this->request->is('ajax')) {
						$this->Flash->error(__df($this->pageRoleName, 'Pages', 'add_batch_failed'));
						foreach ($page->getError('_general') as $error) {
							$this->Flash->error($error);
						}
					}
				}
			}
		}

		$this->setViewVars($page);
	}


	/**
	 * Edit method
	 *
	 * @return Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\Page $page
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$page = $this->Pages
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;

		if (!$page) {
			$this->Flash->error(__df($this->pageRoleName, 'Pages', 'record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($page, 'edit');
		}
		elseif ($page->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a page in another language
			throw new RedirectException(Router::url([
				'lang' => $page->languageShortcode,
				'id' => $page->id,
			], true), 302);
		}

		$this->setViewVars($page);

		$this->set('isDuplicated', $page->id && $this->Pages->exists(['duplicateOf' => $page->id]));
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->Pages->findById($id)->first();
		if (!$page) {
			$this->Flash->error(__df($this->pageRoleName, 'Pages', 'record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Pages->delete($page)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__df($this->pageRoleName, 'Pages', 'delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__df($this->pageRoleName, 'Pages', 'delete_failed'));
				foreach ($page->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * When called, the page for the page id will be loaded,
	 * a session setting for `preview` will be set,
	 * and the user will be redirected to the page in the frontend.
	 * The frontend is expected to check for the `preview` session setting
	 * and display the page and its contents, ignoring publication and
	 * active settings.
	 *
	 * @return Response
	 */
	#[NoDirectAccess]
	public function preview(): Response {
		/** @var \Awyiss\Model\Entity\Page|null $page */
		$page = $this->Pages->findById($this->request->getParam('id'))->first();

		if (!$page) {
			$this->Flash->error(__df($this->pageRoleName, 'Pages', 'record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$session = $this->request->getSession();
		$session->write('previewMode', [
			'enabled' => true,
			'markInactiveElements' => $session->read('previewMode.markElements', true),
			'inactiveElementClass' => FrontendView::getPreviewModeElementClass(),
		]);

		return $this->redirect([
			'lang' => $page->languageShortcode,
			'slug' => $page->slug,
			'_name' => 'Frontend',
		]);
	}


	/**
	 * When called, the session setting provided in the request
	 * will be updated with the new value.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function previewSettings(): void {
		$session = $this->request->getSession();
		$previewSettings = $session->read('previewMode');

		if ($this->request->is('post')) {
			if ($this->request->getData('identifier')) {
				$previewSettings[ $this->request->getData('identifier') ] = (bool)$this->request->getData('value');
			}

			$session->write('previewMode', $previewSettings);
		}

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['previewMode']);

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}

		$this->set([
			'previewMode' => $previewSettings,
		]);
	}


	/**
	 * Return a list of pages for the currently set languageShortcode
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function linkList(): void {
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
		$query = $this->Pages
			->find('active')
			->find('forCurrentLanguage', skipPageRoleCheck: true)
			->where([
				'pageRoleId IN' => array_keys($pageRoles),
			])
		;

		$pagesByPageRole = [];
		$baseUrl = Router::url('/', true);
		/** @var array<int, \Awyiss\Model\Entity\Page> $pages */
		foreach ($query->all()->groupBy('pageRoleId') as $pageRoleId => $pages) {
			$flattenedPages = collection($pages)->nest('id', 'parentId')->listNested();

			$pages = [];
			foreach ($flattenedPages as $page) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$pages[] = [
					'title' => str_repeat('- ', $flattenedPages->getDepth()) . $page->title,
					'slug' => $page->slug,
					'languageShortcode' => $page->languageShortcode,
					'link' => $baseUrl . $page->languageShortcode . '/' . $page->slug,
				];
			}

			$pagesByPageRole[ $pageRoleId ] = [
				'pageRole' => $pageRoles[ $pageRoleId ],
				'links' => $pages,
			];
		}

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'data']);

			$this->set('success', true);
			$this->set('data', $pagesByPageRole);

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param string $method
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function save(Page $page, string $method = 'add'): void {
		$associated = [];
		if ($this->Pages->hasAttributes()) {
			$associated[] = $this->Pages->getAttributesTableName(true);
			$page->setAccess('attributes', true);
		}

		$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

		$hasDescendantsWithDifferentPageRole = false;
		if (!$page->isNew() && $saveAsCopy) {
			$hasDescendantsWithDifferentPageRole = $this->Pages->hasDescendantsWithDifferentPageRole($page);
		}

		$copyDescendantsWithDifferentPageRole = $this->request->getData('copyDescendantsWithDifferentPageRole');
		if ($copyDescendantsWithDifferentPageRole !== null && $hasDescendantsWithDifferentPageRole) {
			$copyDescendantsWithDifferentPageRole = (bool)$copyDescendantsWithDifferentPageRole;
		}

		$requestData = $this->request->getData();

		if (empty($requestData['slug'])) {
			$requestData['slug'] = $requestData['title'] ?? null;
		}

		if ($this->forcedRootPageId) {
			unset($requestData['parentId']);
		}

		$this->Pages->patchEntity($page, ['pageRoleId' => $this->getPageRole()->value] + $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		$this->Categories->setConfig('finder', [
			'forCurrentLanguage' => [
				'entity' => $page,
			],
		]);

		if (
			//reloadForm is set when we need to reload options based on current values
			!$this->request->getData('reloadForm')
			&& (
				//Only save pages if there are no descendants with different page role OR if the decision has been made
				!$hasDescendantsWithDifferentPageRole
				|| $copyDescendantsWithDifferentPageRole !== null
			)
		) {
			if (
				$this->Pages->save($page, [
					'asCopy' => $saveAsCopy,
					'copyDescendantsWithDifferentPageRole' => $copyDescendantsWithDifferentPageRole,
				])
			) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__df($this->pageRoleName, 'Pages', ($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedParentIdSessionIdentifier, $page->parentId);

				if ($this->request->getData('submitType') == 'submitClose') {
					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifyCategorySelection($page);

					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $page->languageShortcode,
						'page' => $this->Paginate->calculateEntityPagePosition($page),
					], true), 302);
				}

				throw new RedirectException(
					Router::url(['action' => 'edit', 'lang' => $page->languageShortcode, 'id' => $page->id], true),
					302
				);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__df($this->pageRoleName, 'Pages', ($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($page->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		$this->set([
			'hasDescendantsWithDifferentPageRole' => $hasDescendantsWithDifferentPageRole,
			'copyDescendantsWithDifferentPageRole' => $copyDescendantsWithDifferentPageRole,
		]);
	}


	/**
	 * Returns a ResultSet of all `\Awyiss\Model\Entity\PageTemplate` records available
	 * for the current pageRoleId, formatted as a list using `\Cake\ORM\Table::findList()`
	 *
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Awyiss\Model\Entity\PageTemplate
	 * @see \Cake\ORM\Table::findList()
	 */
	protected function getPageTemplates(): CollectionInterface {
		if (!isset($this->pageTemplates)) {
			/** @uses \Awyiss\Model\Table::findActive() */
			$this->pageTemplates = $this->Pages->PageTemplates
				->find('active')
				->where([
					'pageRoleId' => $this->getPageRole(),
				])
				->all()
				->indexBy('id')
			;
		}


		return $this->pageTemplates;
	}


	/**
	 * Return a collection of pages for the currently set languageShortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	protected function getThreadedPages(Page $page): CollectionInterface {
		if (!isset($this->threadedPages)) {
			$categoryQueryConditions = $this->Categories->getQueryConditions($this->Categories->getSelectedCategory($page));
			/*
			 * Remove parent_id from the conditions.
			 * Threaded pages are used for the parentId and duplicateOf select box.
			 *
			 * For duplicateOf, the parent_id limitation is not needed. Duplicating a page with a different parent is allowed.
			 * For the parentId select box, the limitation doesn't apply since nesting is only possible if the category behavior
			 * - is disabled or
			 * - not using the parentId field
			 */
			unset($categoryQueryConditions['parentId']);

			/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
			$query = $this->Pages
				->find('forCurrentLanguage', languageShortcode: $page->languageShortcode)
				->where($this->getOverviewWhere() + $categoryQueryConditions)
			;

			$this->threadedPages = $this->Pages->listNested($query);
		}

		return $this->threadedPages;
	}


	/**
	 * Uses this controller with another page_role_id/identifier, so we don't need to bake one for every page role.
	 * This is supposed to only handle non-existing controllers as a fallback.
	 *
	 * @param \Awyiss\Model\Enum\PageRoleEnumInterface $pageRole
	 * @param string $identifier
	 * @return \Awyiss\Controller\Backend\PagesController
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function asPageRole(PageRoleEnumInterface $pageRole, string $identifier): static {
		$this->pageRole = $pageRole;
		$this->pageRoleName = $identifier;

		$this->Pages = $this->{$identifier} = $this->fetchTable($identifier);

		$this->nestable = LocalConfig::read('nest.enabled', false);
		if ($this->nestable) {
			$this->isNestableWithCategoriesEnabled();
		}

		$this->sortable = Inflector::variable(LocalConfig::read('systemOrder.field', 'systemOrder')) === 'systemOrder';

		/** @var \Awyiss\Authorization\AuthorizationService $authorizationService */
		$authorizationService = $this->getRequest()->getAttribute('authorization');
		$policyClass = $authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));

		$this->Authorization->setScope($identifier);

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($identifier)));

		$this->set([
			'policyClass' => $policyClass,
		]);


		return $this;
	}


	/**
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	#[NoDirectAccess]
	public function getPageRole(): PageRoleEnumInterface {
		if (!isset($this->pageRole)) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');
			$this->pageRole = $pageRoleEnum::Page;
		}


		return $this->pageRole;
	}


	/**
	 * Try to render the view using the default render-method
	 * If this fails because the view template could not be found, try again with a view-template
	 * in templates/Backend/GenericPages
	 */
	#[NoDirectAccess]
	public function render(?string $template = null, ?string $layout = null): Response {
		$viewBuilder = $this->viewBuilder();

		if ($this->getName() !== 'Pages') {
			$entitiesName = Inflector::variable($this->getName());
			$entityName = Inflector::variable(Inflector::singularize($this->getName()));
			$threadedName = Inflector::variable('threaded ' . $this->getName());
			$parentName = Inflector::variable('possibleParent ' . $this->getName());

			$viewBuilder->setVars([
				$entitiesName => $viewBuilder->getVar('pages'),
				$entityName => $viewBuilder->getVar('page'),
				$threadedName => $viewBuilder->getVar('threadedPages'),
				$parentName => $viewBuilder->getVar('possibleParentPages'),
			]);
		}

		try {
			$contents = parent::render($template, $layout);
		}
		catch (MissingTemplateException) {
			$templatePathParts = explode('/', $viewBuilder->getTemplatePath());
			array_pop($templatePathParts);

			$viewBuilder->setTemplatePath(implode('/', $templatePathParts) . '/GenericPages');

			$contents = parent::render($template, $layout);
		}


		return $contents;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [
			'pageRoleId' => $this->getPageRole(),
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param \Cake\Collection\CollectionInterface $threadedPages
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function ensurePossibleParentId(Page $page, CollectionInterface $threadedPages): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('field') === 'parentId') {
			//No parent id check if categories behavior is enabled and the field is parent id
			return;
		}

		$possibleParentIds = $threadedPages->extract('id')->toList();

		if (!empty($page->parentId) && !in_array($page->parentId, $possibleParentIds)) {
			$errors = $page->getError('parentId');

			$page->parentId = null;

			if ($errors) {
				$page->setError('parentId', $errors, true);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	protected function setViewVars(Page $page): void {
		$this->Categories->ensurePossibleCategory($page);

		$threadedPages = $this->getThreadedPages($page);

		if ($this->nestable) {
			$possibleParentPages = $this->Pages->getPossibleParents($page, $threadedPages);
			$this->ensurePossibleParentId($page, $possibleParentPages);
		}
		else {
			$possibleParentPages = null;
		}

		/** @uses \Awyiss\Model\Table::findActive() */
		$menus = $this
			->fetchTable('Menus')
			->find('active')
			->all()
		;

		// Get the parent page if it exists
		if ($page->parentId) {
			$parentRecord = $this->Pages
				->find('all', skipPageRoleCheck: true)
				->where(['id' => $page->parentId])
				->first()
			;
		}

		$pageTemplates = $this->getPageTemplates();
		$this->ensurePossibleTemplate($page, $pageTemplates);

		if ($page->slug) {
			$parts = explode('/', $page->slug);
			$page->slug = end($parts);
		}

		$this->set([
			'page' => $page,
			'pageTemplates' => $pageTemplates,
			'contentsEnabled' => LocalConfig::read('contents.enabled'),
			'threadedPages' => $threadedPages,
			'possibleParentPages' => $possibleParentPages,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			//'localConfig' => LocalConfig::read(),
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			/** @uses \Awyiss\Model\Table::findActive() */
			'forms' => $this->Pages->Forms
				->find('active')
				->orderByAsc('title')
				->all(),
			'linkTargets' => $this->findLinkablePages(),
			/** @uses \Awyiss\Model\Table::findActive() */
			'surveys' => $this->Pages->Surveys
				->find('active')
				->orderByAsc('title')
				->all(),
			'menus' => $menus,
			'isGenericPage' => $this->pageRole->value !== 1,
			'parentRecord' => $parentRecord ?? null,
			'pageRole' => $this->Pages->PageRoles->get($this->getPageRole()->value),
			'pageRoleName' => Inflector::camelize($this->pageRoleName),
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function verifyCategorySelection(Page $page): void {
		if (!$this->Categories->getConfig('enabled')) {
			return;
		}

		$categories = [];

		$field = $this->Categories->getConfig('field');
		if ($page->get($field)) {
			$categories[ $page->get($field) ] = $field;

			if ($this->Categories->getConfig('allowAggregation')) {
				$categories += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
			}
		}
		elseif ($this->Categories->getConfig('allowUnassigned')) {
			$categories += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
		}

		/*
		 * Make sure the currently selected category is still part of the page.
		 * Otherwise the next redirect to the overview would show a site without the modified page, which could be a bit confusing.
		 */
		$this->Categories->verifySelection(null, $categories, true);
	}


	/**
	 * @param string $text
	 * @param array $requestData
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function buildEntitiesFromIndentedRows(string $text, array $requestData): CollectionInterface {
		$currentId = 1;
		$parentStack = []; //Stack to keep track of the parent at each level
		$sortCounter = []; // Array to keep track of the sort order at each level
		$entities = collection([]);

		$rootParentId = $requestData['parentId'] ?? null;
		$firstSystemOrder = $requestData['systemOrder'] ?? null;

		unset($requestData['parentId'], $requestData['systemOrder']);

		$associated = [];
		if ($this->Pages->hasAttributes()) {
			$associated[] = $this->Pages->getAttributesTableName(true);
		}

		foreach (explode("\n", $text) as $title) {
			$title = rtrim($title);
			$title = ltrim($title, " \n\r\v\0");

			if (empty($title)) {
				continue;
			}

			$level = substr_count($title, "\t");

			//Update parent stack for the current level
			$parentStack[ $level ] = $currentId;

			//Increment or initialize sort counter for the current level
			if (!isset($sortCounter[ $level ])) {
				$sortCounter[ $level ] = 1;
			}
			else {
				$sortCounter[ $level ]++;
			}

			//Reset sort counters for all deeper levels
			foreach (array_keys($sortCounter) as $key) {
				if ($key > $level) {
					unset($sortCounter[ $key ]);
				}
			}

			//Determine the parent ID
			$parentId = $level > 0 ? $parentStack[ $level - 1 ] : null;

			$entity = $this->Pages->newDefaultEntity();

			$data = ['pageRoleId' => $this->getPageRole()->value];
			$data += [
				'tempId' => $currentId,
				'title' => trim($title),
				'slug' => mb_strlen($title) >= 3 ? $title : 'page-' . $title,
				'level' => $level,
				'parentId' => $level === 0 ? $rootParentId : null,
				'tempParentId' => $level === 0 ? null : $parentId,
				'systemOrder' => $sortCounter[ $level ] + ($level === 0 ? $firstSystemOrder : 0),
			];
			$data += $requestData;

			$this->Pages->patchEntity(
				$entity,
				$data,
				[
					'accessibleFields' => [
						'attributes' => true,
						'tempId' => true,
						'tempParentId' => true,
					],
					'associated' => $associated,
				]
			);

			//Add the current line to the result
			$entities = $entities->append([$entity]);

			$currentId++;
		}

		$pageRole = Inflector::pluralize($this->getPageRole()->name);


		return $entities->nest('tempId', 'tempParentId', 'child' . $pageRole);
	}


	/**
	 * @param Page $page
	 * @param CollectionInterface $pageTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Page $page, CollectionInterface $pageTemplates): void {
		if (!$page->pageTemplateId || !$pageTemplates->firstMatch(['id' => $page->pageTemplateId])) {
			$errors = $page->getError('pageTemplateId');

			$page->pageTemplate = $pageTemplates->first();
			$page->pageTemplateId = $page->pageTemplate?->id;

			if ($errors) {
				$page->setError('pageTemplateId', $errors);
			}
		}
		elseif (!$page->pageTemplate) {
			$page->pageTemplate = $pageTemplates->firstMatch(['id' => $page->pageTemplateId]);
		}

		$request = $this->getRequest();
		//When pageTemplateId is part of the request data, overwrite it since it might be outdated
		if ($request->getData('pageTemplateId') !== null) {
			$request = $request->withData('pageTemplateId', $page->pageTemplateId);
			$this->setRequest($request);
		}
	}


	/**
	 * @return void
	 */
	protected function isNestableWithCategoriesEnabled(): void {
		$categoriesBehavior = $this->Pages->getBehavior('Categories');
		if (
			$categoriesBehavior->getConfig('enabled')
			&& in_array($categoriesBehavior->getConfig('field'), ['parentId', 'parentId'], true)
		) {
			throw new RuntimeException('Cannot use nesting with categories that uses `parent_id` as the foreign key.');
		}
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
