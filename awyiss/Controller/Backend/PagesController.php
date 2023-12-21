<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use AllowDynamicProperties;
use Awyiss\Authorization\Policy\GenericPagePolicy;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
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
	 * @var int
	 */
	protected int $pageRoleId = PAGEROLE_PAGE;
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
		$this->Authorization->ensure(['read', 'create']);

		$lo_pages = $this->Pages->find()->where($this->getOverviewWhere());
		$lo_pages = $this->Pages->listNested($lo_pages);

		$ls_entitiesName = Inflector::variable($this->getName());

		$this->set([
			'ao_' . $ls_entitiesName => $lo_pages,
			'ao_pageTemplates' => $this->getPageTemplates(),
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
			'language_shortcode' => $this->getOverviewWhere('language_shortcode'),
			'page_role_id' => $this->getPageRoleId(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_page);
		}

		$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
		$ls_threadedName = Inflector::variable('threaded ' . $this->getName());

		$lo_threadedPages = $this->getThreadedPages($lo_page);
		$this->ensurePossibleParentId($lo_page, $lo_threadedPages);

		$this->set([
			'ao_' . $ls_entityName => $lo_page,
			'ao_pageTemplates' => $this->getPageTemplates(),
			'ao_' . $ls_threadedName => $lo_threadedPages,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var Page $lo_page */
		$lo_page = $this->Pages->findById($ai_id)->find('translations')->first();

		if (!$lo_page) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_page, 'edit');
		}

		$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
		$ls_threadedName = Inflector::variable('threaded ' . $this->getName());

		$lo_threadedPages = $this->getThreadedPages($lo_page);
		$this->ensurePossibleParentId($lo_page, $lo_threadedPages);

		$this->set([
			'ao_' . $ls_entityName => $lo_page,
			'ao_pageTemplates' => $this->getPageTemplates(),
			'ao_' . $ls_threadedName => $lo_threadedPages,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Page $lo_page */
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
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Returns a ResultSet of all `\Awyiss\Model\Entity\PageTemplate` records available
	 * for the current page_role_id, formatted as a list using `\Cake\ORM\Table::findList()`
	 *
	 * @return CollectionInterface
	 * @see \Awyiss\Model\Entity\PageTemplate
	 * @see \Cake\ORM\Table::findList()
	 */
	public function getPageTemplates(): CollectionInterface {
		if (!isset($this->pageTemplates)) {
			$this->pageTemplates = $this->Pages->PageTemplates->find('active', authorize: ['skip' => true])->where([
				'page_role_id' => $this->getPageRoleId(),
			])->all()->indexBy('id');
		}


		return $this->pageTemplates;
	}


	/**
	 * Return a collection of pages for the currently set language_shortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @param Page $ao_page
	 * @return CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	public function getThreadedPages(Page $ao_page): CollectionInterface {
		if (!isset($this->threadedPages)) {
			$lo_query = $this->Pages->find()->where([
				'language_shortcode' => $this->getOverviewWhere('language_shortcode'),
				'page_role_id' => $this->getPageRoleId(),
			]);

			$this->threadedPages = $this->Pages->listNested($lo_query);
		}

		//We only want to find threaded pages for an existing entity (id equals not null)
		$li_originalId = $ao_page->get('id');
		if (!$li_originalId) {
			return $this->threadedPages;
		}

		$li_foundAtLevel = null;
		$lo_threadedPages = new Collection($this->threadedPages->toList());
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

		$lo_threadedPages = $lo_threadedPages->nest('id', 'parentId');


		return $lo_threadedPages->listNested();
	}


	/**
	 * @return int
	 */
	public function getPageRoleId(): int {
		return $this->pageRoleId;
	}


	/**
	 * @param int $ai_pageRoleId
	 * @return PagesController
	 * @noinspection PhpUnused
	 */
	public function setPageRoleId(int $ai_pageRoleId): static {
		$this->pageRoleId = $ai_pageRoleId;


		return $this;
	}


	/**
	 * Uses this controller with another page_role_id/identifier, so we don't need to bake one for every page role.
	 * This is supposed to only handle non-existing controllers as a fallback.
	 *
	 * @param int $ai_pageRoleId
	 * @param string $as_identifier
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function asPageRole(int $ai_pageRoleId, string $as_identifier): static {
		$this->setPageRoleId($ai_pageRoleId);
		$this->Pages = $this->{$as_identifier} = $this->fetchTable($as_identifier);

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));
		if (!$ls_policyClass) {
			$lo_policyClass = new GenericPagePolicy($this->Authorization->getScope());
		}

		if ($this->Pages->hasBehavior('Authorize')) {
			$this->Pages->getBehavior('Authorize');/*->setPolicyClass($ls_policyClass ?: $lo_policyClass)*/
		}

		$this->Authorization->setScope($as_identifier);/*->setPolicyClass($ls_policyClass ?: $lo_policyClass)*/

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($as_identifier)));

		$this->set([
			'policyClass' => $ls_policyClass ?: $lo_policyClass,
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
		try {
			$ls_contents = parent::render($as_template, $as_layout);
		}
			/** @noinspection PhpUnusedLocalVariableInspection */
		catch (MissingTemplateException $ex) {
			$lo_viewBuilder = $this->viewBuilder();
			$la_templatePathParts = explode('/', $lo_viewBuilder->getTemplatePath());
			array_pop($la_templatePathParts);

			$lo_viewBuilder->setTemplatePath(implode('/', $la_templatePathParts) . '/GenericPages');

			$ls_entitiesName = Inflector::variable($this->getName());
			$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
			$ls_threadedName = Inflector::variable('threaded ' . $this->getName());

			$lo_viewBuilder->setVars([
				'ao_pages' => $lo_viewBuilder->getVar('ao_' . $ls_entitiesName),
				'ao_page' => $lo_viewBuilder->getVar('ao_' . $ls_entityName),
				'ao_threadedPages' => $lo_viewBuilder->getVar('ao_' . $ls_threadedName),
			]);

			$ls_contents = parent::render($as_template, $as_layout);
		}


		return $ls_contents;
	}


	/**
	 * @param Page $ao_page
	 * @param string $as_method
	 * @return void
	 */
	protected function save(Page $ao_page, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Pages->hasAttributes()) {
			$la_associated[] = $this->Pages->getAttributesTable(true);
			$ao_page->setAccess('attributes', true);
		}

		$this->Pages->patchEntity($ao_page, ['page_role_id' => $this->getPageRoleId()] + $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Pages->save($ao_page)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
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
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$ls_languageShortcode = LocaleMiddleware::getLanguage()->shortcode;

		$this->overviewWhere = [
			'language_shortcode' => $ls_languageShortcode,
			'page_role_id' => $this->getPageRoleId(),
		];
	}


	/**
	 * @param Page $ao_page
	 * @param CollectionInterface $ao_threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Page $ao_page, CollectionInterface $ao_threadedPages): void {
		$la_possibleParentIds = $ao_threadedPages->extract('id')->toArray();

		if (!empty($ao_page->parentId) && !in_array($ao_page->parentId, $la_possibleParentIds)) {
			$la_errors = $ao_page->getError('parentId');

			$ao_page->parentId = reset($la_possibleParentIds);

			if ($la_errors) {
				$ao_page->setError('parentId', $la_errors);
			}
		}
	}
}
