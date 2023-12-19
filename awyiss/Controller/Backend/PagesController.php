<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Page;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingTemplateException;


/**
 * Pages Controller
 *
 * @property \Awyiss\Model\Table\PagesTable $Pages
 * @method Page[]|ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class PagesController extends Controller {
	/**
	 * @var int
	 */
	protected int $pageRoleId = PAGEROLE_PAGE;
	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected ResultSetInterface $pageTemplates;
	/**
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected TreeIterator $threadedPages;


	/**
	 * Overview method

	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Access->ensure('read');

		$lo_pages = $this->Pages->find('withAttributes')->where($this->getOverviewWhere());
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
	 *
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Access->ensure('create');

		$lo_page = $this->Pages->newDefaultEntity([
			'language_shortcode' => $this->getOverviewWhere('language_shortcode'),
			'page_role_id' => $this->getPageRoleId(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_page);
		}

		$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
		$ls_threadedName = Inflector::variable('threaded ' . $this->getName());

		$this->set([
			'ao_' . $ls_entityName => $lo_page,
			'ao_pageTemplates' => $this->getPageTemplates(),
			'ao_' . $ls_threadedName => $this->getThreadedPages(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Access->ensure('update');

		/** @var Page $lo_page */
		$lo_page = $this->Pages->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_page) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_page, 'edit');
		}

		$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
		$ls_threadedName = Inflector::variable('threaded ' . $this->getName());

		$this->set([
			'ao_' . $ls_entityName => $lo_page,
			'ao_pageTemplates' => $this->getPageTemplates(),
			'ao_' . $ls_threadedName => $this->getThreadedPages(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Page $lo_page */
		$lo_page = $this->Pages->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_page) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Pages->delete($lo_page)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Page $ao_page
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (Page $ao_page, string $as_method = 'add'): void {
		$this->Pages->patchEntity($ao_page, ['page_role_id' => $this->getPageRoleId()] + $this->request->getData());

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Pages->save($ao_page)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_page->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method . '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_page->getError('_general')));
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere (): void {
		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->getRequest()->getAttribute('locale');
		$ls_languageShortcode = $lo_locale->getLanguageFromUrl()->shortcode;

		$this->overviewWhere = [
			'language_shortcode' => $ls_languageShortcode,
		];
	}


	/**
	 * Returns a ResultSet of all `\Awyiss\Model\Entity\PageTemplate` records available
	 * for the current page_role_id, formatted as a list using `\Cake\ORM\Table::findList()`
	 *
	 * @return \Cake\Datasource\ResultSetInterface
	 *
	 * @see \Awyiss\Model\Entity\PageTemplate
	 * @see \Cake\ORM\Table::findList()
	 */
	public function getPageTemplates (): ResultSetInterface {
		if (!isset($this->pageTemplates)) {
			$this->pageTemplates = $this->Pages->PageTemplates->find('list', ['access' => ['skip' => TRUE]])->where([
				'page_role_id' => $this->getPageRoleId(),
			])->all();
		}

		return $this->pageTemplates;
	}


	/**
	 * Return a collection of pages for the currently set language_shortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @return \Cake\Collection\Iterator\TreeIterator
	 *
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	public function getThreadedPages (): TreeIterator {
		if (!isset($this->threadedPages)) {
			$this->threadedPages = $this->Pages->find('withAttributes')->where([
				'language_shortcode' => $this->getOverviewWhere('language_shortcode'),
			])->find('threaded')->all()->listNested();
		}

		return $this->threadedPages;
	}


	/**
	 * @return int
	 */
	public function getPageRoleId (): int {
		return $this->pageRoleId;
	}


	/**
	 * @param int $ai_pageRoleId
	 *
	 * @return \Awyiss\Controller\Backend\PagesController
	 *
	 * @noinspection PhpUnused
	 */
	public function setPageRoleId (int $ai_pageRoleId): static {
		$this->pageRoleId = $ai_pageRoleId;

		return $this;
	}


	/**
	 * Uses this controller with another page_role_id/identifier, so we don't need to bake one for every page role.
	 * This is supposed to only handle non-existing controllers as a fallback.
	 *
	 * @param int $ai_pageRoleId
	 * @param string $as_identifier
	 *
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function asPageRole (int $ai_pageRoleId, string $as_identifier): static {
		$this->setPageRoleId($ai_pageRoleId);
		$this->Pages = $this->{$as_identifier} = $this->fetchTable($as_identifier);

		$ls_identifier = Inflector::pluralize($as_identifier);
		$ls_scope = strtolower($ls_identifier);

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Access->getScope(), $this->Access->getConfig('policiesType'));
		if ( ! $ls_policyClass) {
			$lo_policyClass = new AnonymousPolicy($this->Access->getScope());
		}

		if ($this->Pages->hasBehavior('Access')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$this->Pages->getBehavior('Access')->setPolicyClass($ls_policyClass ?: $lo_policyClass);
		}

		$this->Access->setScope($ls_scope)->setPolicyClass($ls_policyClass ?: $lo_policyClass);

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($as_identifier)));

		$this->set([
			'policyClass' => $ls_policyClass ?: $lo_policyClass
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
	public function render (?string $as_template = NULL, ?string $as_layout = NULL): Response {
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
}

