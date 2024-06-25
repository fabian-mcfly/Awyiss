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
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
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
	 * @var bool Nesting enabled
	 */
	protected bool $nestable = true;
	/**
	 * @var \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	protected PageRoleEnumInterface $pageRole;
	/**
	 * @var string Page role name
	 */
	protected string $pageRoleName = 'page';
	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected CollectionInterface $pageTemplates;
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
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Pages->find('forCurrentLanguage')->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		// Disable sorting if the current category is the aggregation category or the unassigned category
		if (
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('aggregationKey') ||
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('unassignedKey')
		) {
			$this->sortable = false;
		}

		$lb_paginated = $this->paginate['enabled'];
		unset($this->paginate['enabled']);
		if ($lb_paginated) {
			$lo_pages = $this->paginate($lo_query);
		}
		elseif ($this->nestable) {
			$lo_pages = $lo_query->find('threaded');
		}
		else {
			$lo_pages = $lo_query->all();
		}

		$la_pageTemplates = $this->getPageTemplates()->indexBy('id')->toArray();

		$this->set([
			'pages' => $lo_pages,
			//'localConfig' => LocalConfig::read(),
			'contentsEnabled' => LocalConfig::read('contents.enabled'),
			'paginated' => $lb_paginated,
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			'attributes' => $this->Pages->getAttributes(),
			'pageTemplates' => $la_pageTemplates,
			'isGenericPage' => $this->pageRole->value !== 1,
			'pageRole' => $this->Pages->PageRoles->get($this->getPageRole()->value),
			'pageRoleName' => Inflector::underscore($this->pageRoleName),
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

		$lo_page = $this->Pages->newDefaultEntity([
			'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
			'pageRoleId' => $this->getPageRole(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_page);
		}

		$this->setViewVars($lo_page);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addBatch(): void {
		$this->Authorization->ensure('create');

		$lo_page = $this->Pages->newDefaultEntity([
			'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
			'pageRoleId' => $this->getPageRole(),
		]);

		$la_requestData = $this->request->getData();
		if ($this->request->is('post') && !empty($la_requestData['pages'])) {
			$la_associated = [];
			if ($this->Pages->hasAttributes()) {
				$la_associated[] = $this->Pages->getAttributesTableName(true);
				$lo_page->setAccess('attributes', true);
			}

			$la_data = [
				'page_role_id' => $this->getPageRole()->value,
				'slug' => 'dummy',
				'title' => 'dummy',
			];
			$la_data += $this->request->getData();

			$this->Pages->patchEntity($lo_page, $la_data, [
				'associated' => $la_associated,
				'validate' => !$this->request->getData('reload_form'),
			]);

			$this->Categories->setConfig('finder', [
				'forCurrentLanguage' => [
					'entity' => $lo_page,
				],
			]);

			$lo_entities = $this->buildEntitiesFromIndentedRows($la_requestData['pages'], $la_requestData);

			if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				$lb_success = false;

				if ($lo_entities->count()) {
					$ls_pageRole = Inflector::pluralize($this->getPageRole()->name);
					$la_entities = $lo_entities->toArray();

					if ($this->Pages->saveMany($la_entities, ['associated' => ['Child' . $ls_pageRole]])) {
						$lb_success = true;
					}
					else {
						$lo_page = $lo_entities->first();
					}
				}

				if ($lb_success) {
					if (!$this->request->is('ajax')) {
						$this->Flash->success(__df($this->pageRoleName, 'pages', 'add_batch_succeeded'));
					}

					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifyCategorySelection($lo_page);

					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $lo_page->languageShortcode], true), 302);
				}
				else {
					$this->Flash->error(__df($this->pageRoleName, 'pages', 'add_batch_failed'));
					foreach ($lo_page->getError('_general') as $ls_error) {
						$this->Flash->error($ls_error);
					}
				}
			}
		}

		$this->setViewVars($lo_page);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->Pages->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();

		if (!$lo_page) {
			$this->Flash->error(__df($this->pageRoleName, 'pages', 'record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_page, 'edit');
		}
		elseif ($lo_page->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a page in another language
			throw new RedirectException(Router::url([
				'lang' => $lo_page->languageShortcode,
				'id' => $lo_page->id,
			], true), 302);
		}

		$this->setViewVars($lo_page);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->Pages->findById($id)->first();
		if (!$lo_page) {
			$this->Flash->error(__df($this->pageRoleName, 'pages', 'record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Pages->delete($lo_page)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__df($this->pageRoleName, 'pages', 'delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__df($this->pageRoleName, 'pages', 'delete_failed'));

			foreach ($lo_page->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param string $method
	 * @return void
	 */
	protected function save(Page $page, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Pages->hasAttributes()) {
			$la_associated[] = $this->Pages->getAttributesTableName(true);
			$page->setAccess('attributes', true);
		}

		$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

		$lb_hasDescendantsWithDifferentPageRole = false;
		if (!$page->isNew() && $lb_saveAsCopy) {
			$lb_hasDescendantsWithDifferentPageRole = $this->Pages->hasDescendantsWithDifferentPageRole($page);
		}

		$lb_copyDescendantsWithDifferentPageRole = $this->request->getData('copy_descendants_with_different_page_role');
		if ($lb_copyDescendantsWithDifferentPageRole !== null && $lb_hasDescendantsWithDifferentPageRole) {
			$lb_copyDescendantsWithDifferentPageRole = (bool)$lb_copyDescendantsWithDifferentPageRole;
		}

		$this->Pages->patchEntity($page, ['page_role_id' => $this->getPageRole()->value] + $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		$this->Categories->setConfig('finder', [
			'forCurrentLanguage' => [
				'entity' => $page,
			],
		]);

		if (
			!$this->request->getData('reload_form') && //reload_form is set when we need to reload options based on current values
			(
				//Only save pages if there are no descendants with different page role OR if the decision has been made
				!$lb_hasDescendantsWithDifferentPageRole ||
				$lb_copyDescendantsWithDifferentPageRole !== null
			)
		) {
			if (
				$this->Pages->save($page, [
					'asCopy' => $lb_saveAsCopy,
					'copyDescendantsWithDifferentPageRole' => $lb_copyDescendantsWithDifferentPageRole,
				])
			) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__df($this->pageRoleName, 'pages', $method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
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

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $page->languageShortcode, 'id' => $page->id], true), 302);
			}

			$this->Flash->error(__df($this->pageRoleName, 'pages', $method . '_failed'));
			foreach ($page->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		$this->set([
			'hasDescendantsWithDifferentPageRole' => $lb_hasDescendantsWithDifferentPageRole,
			'copyDescendantsWithDifferentPageRole' => $lb_copyDescendantsWithDifferentPageRole,
		]);
	}


	/**
	 * Returns a ResultSet of all `\Awyiss\Model\Entity\PageTemplate` records available
	 * for the current page_role_id, formatted as a list using `\Cake\ORM\Table::findList()`
	 *
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Awyiss\Model\Entity\PageTemplate
	 * @see \Cake\ORM\Table::findList()
	 */
	protected function getPageTemplates(): CollectionInterface {
		if (!isset($this->pageTemplates)) {
			$this->pageTemplates = $this->Pages->PageTemplates->find('active')->where([
				'page_role_id' => $this->getPageRole(),
			])->all()->indexBy('id');
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
			$la_categoryQueryConditions = $this->Categories->getQueryConditions($this->Categories->getSelectedCategory($page));
			/*
			 * Remove parent_id from the conditions.
			 * Threaded pages are used for the parent_id and duplicate_of select box.
			 *
			 * For duplicate_of, the parent_id limitation is not needed. Duplicating a page with a different parent is allowed.
			 * For the parent_id select box, the limitation doesn't apply since nesting is only possible if the category behavior
			 * - is disabled or
			 * - not using the parent_id field
			 */
			unset($la_categoryQueryConditions['parent_id'], $la_categoryQueryConditions['parentId']);

			$lo_query = $this->Pages->find('forCurrentLanguage', languageShortcode: $page->languageShortcode)
			->where($this->getOverviewWhere() + $la_categoryQueryConditions);

			$this->threadedPages = $this->Pages->listNested($lo_query);
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
	 * @throws \ReflectionException
	 */
	#[NoDirectAccess]
	public function asPageRole(PageRoleEnumInterface $pageRole, string $identifier): static {
		$this->pageRole = $pageRole;
		$this->pageRoleName = $identifier;

		$this->Pages = $this->{$identifier} = $this->fetchTable($identifier);

		$this->nestable = LocalConfig::read('nest.enabled');
		if ($this->nestable) {
			$this->isNestableWithCategoriesEnabled();
		}

		$this->sortable = Inflector::variable(LocalConfig::read('systemOrder.field', 'systemOrder')) === 'systemOrder';

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));

		$this->Authorization->setScope($identifier);/*->setPolicyClass($ls_policyClass ?: $lo_policyClass)*/

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($identifier)));

		$this->set([
			'policyClass' => $ls_policyClass,
		]);


		return $this;
	}


	/**
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	#[NoDirectAccess]
	public function getPageRole(): PageRoleEnumInterface {
		if (!isset($this->pageRole)) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
			$this->pageRole = $ls_pageRoleEnum::Page;
		}


		return $this->pageRole;
	}


	/**
	 * Try to render the view using the default render-method
	 * If this fails because the view template could not be found, try again with a view-template
	 * in templates/Backend/GenericPages
	 */
	public function render(?string $template = null, ?string $layout = null): Response {
		$lo_viewBuilder = $this->viewBuilder();

		if ($this->getName() !== 'Pages') {
			$ls_entitiesName = Inflector::variable($this->getName());
			$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
			$ls_threadedName = Inflector::variable('threaded ' . $this->getName());
			$ls_parentName = Inflector::variable('possibleParent ' . $this->getName());

			$lo_viewBuilder->setVars([
				$ls_entitiesName => $lo_viewBuilder->getVar('pages'),
				$ls_entityName => $lo_viewBuilder->getVar('page'),
				$ls_threadedName => $lo_viewBuilder->getVar('threadedPages'),
				$ls_parentName => $lo_viewBuilder->getVar('possibleParentPages'),
			]);
		}

		try {
			$ls_contents = parent::render($template, $layout);
		}
		catch (MissingTemplateException) {
			$la_templatePathParts = explode('/', $lo_viewBuilder->getTemplatePath());
			array_pop($la_templatePathParts);

			$lo_viewBuilder->setTemplatePath(implode('/', $la_templatePathParts) . '/GenericPages');

			$ls_contents = parent::render($template, $layout);
		}


		return $ls_contents;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [
			'page_role_id' => $this->getPageRole(),
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param \Cake\Collection\CollectionInterface $threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Page $page, CollectionInterface $threadedPages): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('field') === 'parentId') {
			//No parent id check if categories behavior is enabled and the field is parent id
			return;
		}

		$la_possibleParentIds = $threadedPages->extract('id')->toList();

		if (!empty($page->parentId) && !in_array($page->parentId, $la_possibleParentIds)) {
			$la_errors = $page->getError('parentId');

			$page->parentId = reset($la_possibleParentIds);

			if ($la_errors) {
				$page->setError('parentId', $la_errors, true);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	protected function setViewVars(Page $page): void {
		$this->Categories->ensurePossibleCategory($page);

		$lo_threadedPages = $this->getThreadedPages($page);

		if ($this->nestable) {
			$lo_possibleParentPages = $this->Pages->getPossibleParents($page, $lo_threadedPages);
			$this->ensurePossibleParentId($page, $lo_possibleParentPages);
		}
		else {
			$lo_possibleParentPages = null;
		}

		$lo_menus = $this->fetchTable('Menus')->find('active')->all();

		// Get the parent page if it exists
		if ($page->parentId) {
			$lo_parentRecord = $this->Pages->find('all', skipPageRoleCheck: true)->where(['id' => $page->parentId])->first();
		}

		$lo_pageTemplates = $this->getPageTemplates();
		$this->ensurePossibleTemplate($page, $lo_pageTemplates);

		$this->set([
			'page' => $page,
			'pageTemplates' => $lo_pageTemplates,
			'contentsEnabled' => LocalConfig::read('contents.enabled'),
			'threadedPages' => $lo_threadedPages,
			'possibleParentPages' => $lo_possibleParentPages,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			//'localConfig' => LocalConfig::read(),
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			'menus' => $lo_menus,
			'isGenericPage' => $this->pageRole->value !== 1,
			'parentRecord' => $lo_parentRecord ?? null,
			'pageRole' => $this->Pages->PageRoles->get($this->getPageRole()->value),
			'pageRoleName' => Inflector::underscore($this->pageRoleName),
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

		$la_categories = [];

		$ls_field = $this->Categories->getConfig('field');
		if ($page->get($ls_field)) {
			$la_categories[ $page->get($ls_field) ] = $ls_field;

			if ($this->Categories->getConfig('allowAggregation')) {
				$la_categories += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
			}
		}
		elseif ($this->Categories->getConfig('allowUnassigned')) {
			$la_categories += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
		}

		/*
		 * Make sure the currently selected category is still part of the page.
		 * Otherwise the next redirect to the overview would show a site without the modified page, which could be a bit confusing.
		 */
		$this->Categories->verifySelection(null, $la_categories, true);
	}


	/**
	 * @param string $text
	 * @param array $requestData
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function buildEntitiesFromIndentedRows(string $text, array $requestData): CollectionInterface {
		$li_currentId = 1;
		$la_parentStack = []; //Stack to keep track of the parent at each level
		$la_sortCounter = []; // Array to keep track of the sort order at each level
		$lo_entities = collection([]);

		$li_rootParentId = $requestData['parent_id'] ?: null;
		$li_firstSystemOrder = $requestData['system_order'];

		/** @noinspection PhpVariableNamingConventionInspection */
		unset($requestData['parent_id'], $requestData['system_order']);

		$la_associated = [];
		if ($this->Pages->hasAttributes()) {
			$la_associated[] = $this->Pages->getAttributesTableName(true);
		}

		foreach (explode("\n", $text) as $ls_title) {
			$ls_title = rtrim($ls_title);
			$ls_title = ltrim($ls_title, " \n\r\v\0");
			$li_level = substr_count($ls_title, "\t");

			//Update parent stack for the current level
			$la_parentStack[ $li_level ] = $li_currentId;

			//Increment or initialize sort counter for the current level
			if (!isset($la_sortCounter[ $li_level ])) {
				$la_sortCounter[ $li_level ] = 1;
			}
			else {
				$la_sortCounter[ $li_level ]++;
			}

			//Reset sort counters for all deeper levels
			foreach (array_keys($la_sortCounter) as $li_key) {
				if ($li_key > $li_level) {
					unset($la_sortCounter[ $li_key ]);
				}
			}

			//Determine the parent ID
			$li_parentId = $li_level > 0 ? $la_parentStack[ $li_level - 1 ] : null;

			$lo_entity = $this->Pages->newDefaultEntity();

			$la_data = ['page_role_id' => $this->getPageRole()->value];
			$la_data += [
				'tempId' => $li_currentId,
				'title' => trim($ls_title),
				'slug' => $ls_title,
				'level' => $li_level,
				'parentId' => $li_level === 0 ? $li_rootParentId : null,
				'tempParentId' => $li_level === 0 ? null : $li_parentId,
				'systemOrder' => $la_sortCounter[ $li_level ] + ($li_level === 0 ? $li_firstSystemOrder : 0),
			];
			$la_data += $requestData;

			$this->Pages->patchEntity(
				$lo_entity,
				$la_data,
				[
					'accessibleFields' => [
						'attributes' => true,
						'tempId' => true,
						'tempParentId' => true,
					],
					'associated' => $la_associated,
				]
			);

			//Add the current line to the result
			$lo_entities = $lo_entities->append([ $lo_entity ]);

			$li_currentId++;
		}

		$ls_pageRole = Inflector::pluralize($this->getPageRole()->name);


		return $lo_entities->nest('tempId', 'tempParentId', 'child' . $ls_pageRole);
	}


	/**
	 * @param Page $page
	 * @param CollectionInterface $pageTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Page $page, CollectionInterface $pageTemplates): void {
		if (!$page->pageTemplateId || !$pageTemplates->firstMatch(['id' => $page->pageTemplateId])) {
			$la_errors = $page->getError('pageTemplateId');

			$page->pageTemplate = $pageTemplates->first();
			$page->pageTemplateId = $page->pageTemplate?->id;

			if ($la_errors) {
				$page->setError('pageTemplateId', $la_errors);
			}
		}
		elseif (!$page->pageTemplate) {
			$page->pageTemplate = $pageTemplates->firstMatch(['id' => $page->pageTemplateId]);
		}

		$lo_request = $this->getRequest();
		//When page_template_id is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('page_template_id') !== null) {
			$lo_request = $lo_request->withData('page_template_id', $page->pageTemplateId);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @return void
	 */
	protected function isNestableWithCategoriesEnabled(): void {
		$lo_categoriesBehavior = $this->Pages->getBehavior('Categories');
		if (
			$lo_categoriesBehavior->getConfig('enabled') &&
			$lo_categoriesBehavior->getConfig('foreignKey') === 'parent_id'
		) {
			throw new RuntimeException('Cannot use nesting with categories that uses `parent_id` as the foreign key.');
		}
	}
}
