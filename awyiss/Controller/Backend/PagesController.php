<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Controller\BackendController as Controller;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Http\Response;
use Cake\Utility\Inflector;


/**
 * Pages Controller
 *
 * @property \Awyiss\Model\Table\PagesTable $Pages
 * @method \Awyiss\Model\Entity\Page[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class PagesController extends Controller {
	//protected $defaultTable = 'Pages';
	protected int $pageRoleId = PAGEROLE_PAGE;
	protected ResultSetInterface $pageTemplates;
	protected TreeIterator $threadedPages;
	/*protected array $overviewWhere = [
		'page_role_id' => static::$pageRoleId,
	];*/


	/**
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere () {
		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->getRequest()->getAttribute('locale');
		$ls_languagesShortcode = $lo_locale->getLanguageFromUrl()->shortcode;

		$this->overviewWhere = [
			'languages_shortcode' => $ls_languagesShortcode,
		];
	}


	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		/*$migrations = new \Migrations\Migrations();

		// Will return an array of all migrations and their status
		$status = $migrations->status(['source' => '../../' . CUSTOM_DIR . '/config/MyMigrationsFolder']);
		dd($status);

		$lo_migration = new \Migrations\Table('_attributes_pages');
		dd($lo_migration);*/

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
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_page = $this->Pages->newDefaultEntity([
			'languages_shortcode' => $this->getOverviewWhere('languages_shortcode'),
			'page_role_id' => $this->getPageRoleId(),
		]);

		if ($this->request->is('post')) {
			$this->Pages->patchEntity($lo_page, ['page_role_id' => $this->getPageRoleId()] + $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Pages->save($lo_page)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_page->id]);
				}

				$this->Flash->error(__('::add_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_page->getError('_general')));
			}
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
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		try {
			$li_id = $this->request->getParam('id');
			/** @var \Awyiss\Model\Entity\Page $lo_page */
			$lo_page = $this->Pages->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->Pages->patchEntity($lo_page, ['page_role_id' => $this->getPageRoleId()] + $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Pages->save($lo_page)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_page->id]);
				}

				$this->Flash->error(__('::edit_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_page->getError('_general')));
			}
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
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		try {
			$li_id = $this->request->getParam('id');
			/** @var \Awyiss\Model\Entity\Page $lo_page */
			$lo_page = $this->Pages->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
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


	public function getPageTemplates (): ResultSetInterface {
		if (!isset($this->pageTemplates)) {
			$this->pageTemplates = $this->Pages->PageTemplates->find('list')->where([
				'page_role_id' => $this->getPageRoleId(),
			])->all();
		}

		return $this->pageTemplates;
	}


	public function getThreadedPages (): TreeIterator {
		if (!isset($this->threadedPages)) {
			$this->threadedPages = $this->Pages->find('withAttributes')->where([
				'languages_shortcode' => $this->getOverviewWhere('languages_shortcode'),
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
	 * @noinspection PhpUnused
	 */
	public function setPageRoleId (int $ai_pageRoleId): self {
		$this->pageRoleId = $ai_pageRoleId;

		return $this;
	}


	/**
	 * @param int $ai_pageRoleId
	 * @param string $as_identifier
	 *
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function asPageRole (int $ai_pageRoleId, string $as_identifier): self {
		$this->setPageRoleId($ai_pageRoleId);
		$this->Pages = $this->{$as_identifier} = FactoryLocator::get('Table')->get($as_identifier);

		$ls_identifier = \Cake\Utility\Inflector::pluralize($as_identifier);
		$ls_scope = strtolower($ls_identifier);

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Access->getScope(), $this->Access->getConfig('policiesType'));
		if (!$ls_policyClass) {
			$lo_policyClass = new AnonymousPolicy($this->Access->getScope());
		}

		/** @var \Awyiss\Model\Behavior\AccessBehavior $lo_accessBehavior */
		$lo_accessBehavior = $this->Pages->getBehavior('Access');
		$lo_accessBehavior->setScope($ls_scope)->setPolicyClass($ls_policyClass ?: $lo_policyClass);

		$this->Access->setScope($ls_scope)->setPolicyClass($ls_policyClass ?: $lo_policyClass);

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($as_identifier)));

		$this->set([
			'policyClass' => $ls_policyClass ?: $lo_policyClass
		]);

		return $this;
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render (?string $as_template = NULL, ?string $as_layout = NULL): Response {
		try {
			$ls_contents = parent::render($as_template, $as_layout);
		}
		/** @noinspection PhpUnusedLocalVariableInspection */
		catch (\Cake\View\Exception\MissingTemplateException $ex) {
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

