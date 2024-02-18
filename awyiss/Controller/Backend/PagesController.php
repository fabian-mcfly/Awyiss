<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use AllowDynamicProperties;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Routing\Router;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingTemplateException;


/**
 * Pages Controller
 *
 * @property \Awyiss\Model\Table\PagesTable $Pages
 */
#[AllowDynamicProperties]
class PagesController extends Controller {
	/**
	 * @var \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	protected PageRoleEnumInterface $pageRole;
	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected CollectionInterface $pageTemplates;
	/**
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected CollectionInterface $threadedPages;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_pages = $this->Pages->find('forCurrentLanguage')->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_pages);
		$lo_pages = $this->Pages->listNested($lo_pages);

		$this->set([
			'ao_pages' => $lo_pages,
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
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->Pages->findById($ai_id)->find('translations')->first();

		if (!$lo_page) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_page, 'edit');
		}
		elseif ($lo_page->language_shortcode != LocaleMiddleware::getLanguage()->shortcode) {
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
	 * @param int $ai_id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->Pages->findById($ai_id)->find('translations')->first();
		if (!$lo_page) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Pages->delete($lo_page)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_page->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $ao_page
	 * @param string $as_method
	 * @return void
	 */
	protected function save(Page $ao_page, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Pages->hasAttributes()) {
			$la_associated[] = $this->Pages->getAttributesTableName(true);
			$ao_page->setAccess('attributes', true);
		}

		$this->Pages->patchEntity($ao_page, ['page_role_id' => $this->getPageRole()->value] + $this->request->getData(), ['associated' => $la_associated]);

		$this->Categories->setConfig('finder', [
			'forCurrentLanguage' => [
				'entity' => $ao_page,
			],
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Pages->save($ao_page)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifySelection($ao_page);

					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $ao_page->languageShortcode], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $ao_page->languageShortcode, 'id' => $ao_page->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_page->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * Returns a ResultSet of all `\Awyiss\Model\Entity\PageTemplate` records available
	 * for the current page_role_id, formatted as a list using `\Cake\ORM\Table::findList()`
	 *
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Awyiss\Model\Entity\PageTemplate
	 * @see \Cake\ORM\Table::findList()
	 */
	public function getPageTemplates(): CollectionInterface {
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
	 * @param \Awyiss\Model\Entity\Page $ao_page
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	public function getThreadedPages(Page $ao_page): CollectionInterface {
		if (!isset($this->threadedPages)) {
			$lo_query = $this->Pages->find('forCurrentLanguage', languageShortcode: $ao_page->languageShortcode)
			->where(
				$this->getOverviewWhere() +
				$this->Categories->getQueryConditions(
					$this->Categories->getSelectedCategory($ao_page)
				)
			);

			$this->threadedPages = $this->Pages->listNested($lo_query);
		}

		return $this->threadedPages;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $ao_page
	 * @param \Cake\Collection\CollectionInterface $ao_threadedPages
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function getParentPages(Page $ao_page, CollectionInterface $ao_threadedPages): CollectionInterface {
		//We only want to find threaded pages for an existing entity (id equals not null)
		$li_originalId = $ao_page->get('id');
		if (!$li_originalId) {
			return $ao_threadedPages;
		}

		$li_foundAtLevel = null;
		$lo_threadedPages = new Collection($ao_threadedPages->toList());

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$lo_threadedPages = $lo_threadedPages->filter(function ($ao_page) use ($li_originalId, &$li_foundAtLevel) {
			if ($ao_page->get('id') === $li_originalId) {
				$li_foundAtLevel = $ao_page->level;
			}
			elseif (is_null($li_foundAtLevel) || $ao_page->level <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});


		return $lo_threadedPages;
	}


	/**
	 * @return \Awyiss\Database\Type\PageRoleEnumInterface
	 */
	public function getPageRole(): PageRoleEnumInterface {
		if (!isset($this->pageRole)) {
			/** @var class-string<\Awyiss\Database\Type\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
			$this->pageRole = $ls_pageRoleEnum::Page;
		}

		return $this->pageRole;
	}


	/**
	 * @param \Awyiss\Database\Type\PageRoleEnumInterface $ae_pageRole
	 * @return \Awyiss\Controller\Backend\PagesController
	 */
	public function setPageRole(PageRoleEnumInterface $ae_pageRole): static {
		$this->pageRole = $ae_pageRole;


		return $this;
	}


	/**
	 * Uses this controller with another page_role_id/identifier, so we don't need to bake one for every page role.
	 * This is supposed to only handle non-existing controllers as a fallback.
	 *
	 * @param \Awyiss\Model\Enum\PageRoleEnumInterface $ae_pageRole
	 * @param string $as_identifier
	 * @return \Awyiss\Controller\Backend\PagesController
	 * @throws \ReflectionException
	 */
	public function asPageRole(PageRoleEnumInterface $ae_pageRole, string $as_identifier): static {
		$this->setPageRole($ae_pageRole);
		$this->Pages = $this->{$as_identifier} = $this->fetchTable($as_identifier);

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));

		$this->Authorization->setScope($as_identifier);/*->setPolicyClass($ls_policyClass ?: $lo_policyClass)*/

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($as_identifier)));

		$this->set([
			'policyClass' => $ls_policyClass,
		]);


		return $this;
	}


	/**
	 * Try to render the view using the default render-method
	 * If this fails because the view template could not be found, try again with a view-template
	 * in templates/Backend/GenericPages
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render(?string $as_template = null, ?string $as_layout = null): Response {
		$lo_viewBuilder = $this->viewBuilder();

		if ($this->getName() !== 'Pages') {
			$ls_entitiesName = Inflector::variable($this->getName());
			$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
			$ls_threadedName = Inflector::variable('threaded ' . $this->getName());
			$ls_parentName = Inflector::variable('parent ' . $this->getName());

			$lo_viewBuilder->setVars([
				'ao_' . $ls_entitiesName => $lo_viewBuilder->getVar('ao_pages'),
				'ao_' . $ls_entityName => $lo_viewBuilder->getVar('ao_page'),
				'ao_' . $ls_threadedName => $lo_viewBuilder->getVar('ao_threadedPages'),
				'ao_' . $ls_parentName => $lo_viewBuilder->getVar('ao_parentPages'),
			]);
		}

		try {
			$ls_contents = parent::render($as_template, $as_layout);
		}
		/** @noinspection PhpUnusedLocalVariableInspection */
		catch (MissingTemplateException $ex) {
			$la_templatePathParts = explode('/', $lo_viewBuilder->getTemplatePath());
			array_pop($la_templatePathParts);

			$lo_viewBuilder->setTemplatePath(implode('/', $la_templatePathParts) . '/GenericPages');

			$ls_contents = parent::render($as_template, $as_layout);
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
	 * @param \Awyiss\Model\Entity\Page $ao_page
	 * @param \Cake\Collection\CollectionInterface $ao_threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Page $ao_page, CollectionInterface $ao_threadedPages): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('fieldname') === 'parentId') {
			//No parent id check if categories behavior is enabled and the field is parent id
			return;
		}

		$la_possibleParentIds = $ao_threadedPages->extract('id')->toList();

		if (!empty($ao_page->parentId) && !in_array($ao_page->parentId, $la_possibleParentIds)) {
			$la_errors = $ao_page->getError('parentId');

			$ao_page->parentId = reset($la_possibleParentIds);

			if ($la_errors) {
				$ao_page->setError('parentId', $la_errors, true);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $ao_page
	 * @return void
	 */
	protected function setViewVars(Page $ao_page): void {
		$this->Categories->ensurePossibleCategory($ao_page);

		$lo_threadedPages = $this->getThreadedPages($ao_page);

		$lo_parentPages = $this->getParentPages($ao_page, $lo_threadedPages);
		$this->ensurePossibleParentId($ao_page, $lo_parentPages);

		$this->set([
			'ao_page' => $ao_page,
			'ao_pageTemplates' => $this->getPageTemplates(),
			'ao_threadedPages' => $lo_threadedPages,
			'ao_parentPages' => $lo_parentPages,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $ao_page
	 * @return void
	 */
	protected function verifySelection(Page $ao_page): void {
		if (!$this->Categories->getConfig('enabled')) {
			return;
		}

		$la_categories = [];

		$ls_field = $this->Categories->getConfig('fieldname');
		if ($ao_page->get($ls_field)) {
			$la_categories[ $ao_page->get($ls_field) ] = $ls_field;

			if ($this->Categories->getConfig('allowAggregation')) {
				$la_categories += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
			}
		}
		else {
			if ($this->Categories->getConfig('allowUnassigned')) {
				$la_categories += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
			}
		}

		/*
		 * Make sure the currently selected category is still part of the page.
		 * Otherwise the next redirect to the overview would show a site without the modified page, which could be a bit confusing.
		 */
		$this->Categories->verifySelection(null, $la_categories, true);
	}
}
