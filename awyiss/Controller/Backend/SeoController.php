<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;


/**
 * Seo Controller
 */
class SeoController extends BackendController {
	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = '';
	/**
	 * @var string
	 */
	protected string $pageRole;
	/**
	 * @var array<string>
	 */
	protected array $pageRoles;
	/**
	 * @var string|null Session identifier for the selected page role
	 */
	protected ?string $selectedPageRoleSessionIdentifier = null;
	/**
	 * @var \Awyiss\Model\Table\PagesTable
	 */
	protected PagesTable $table;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function initialize(): void {
		parent::initialize();

		$this->initPageRoles();

		$this->selectedPageRoleSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.page_role';

		$lo_session = $this->request->getSession();

		if (!in_array($this->request->getParam('action'), ['analyze', 'analyzeHeadlineStructures'])) {
			$ls_pageRole = $lo_session->read($this->selectedPageRoleSessionIdentifier, 'all');
			if (!array_key_exists($ls_pageRole, $this->pageRoles) && $ls_pageRole !== 'all') {
				$ls_pageRole = array_key_first($this->pageRoles);
			}

			$this->setTable($ls_pageRole);

			return;
		}

		//Is there a request parameter with the name 'pageRole'?
		$ls_pageRole = $this->request->getParam('pageRole');

		if ($ls_pageRole) {
			$lo_session->write($this->selectedPageRoleSessionIdentifier, $ls_pageRole);
			$ls_pageRole = Inflector::underscore($ls_pageRole);
		}
		else {
			$ls_pageRole = $lo_session->read($this->selectedPageRoleSessionIdentifier, array_key_first($this->pageRoles));
		}

		//If the selected page role is not inside the available page roles, reset it to the first available one.
		if (!array_key_exists($ls_pageRole, $this->pageRoles) && $ls_pageRole !== 'all') {
			$ls_pageRole = array_key_first($this->pageRoles);

			$lo_session->write($this->selectedPageRoleSessionIdentifier, $ls_pageRole);

			//Redirect to remove the invalid scope parameter from the URL
			$this->redirect(['action' => 'analyze']);
		}

		$this->setTable($ls_pageRole);
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		return $this->table->find('forCurrentLanguage')->where($this->getOverviewWhere());
	}


	/**
	 * Analyze method
	 *
	 * @throws \Exception
	 */
	public function analyze(): void {
		$this->Authorization->ensure('analyze');

		$lo_query = $this->getOverviewQuery()->contain([
			'PageRoles',
		]);

		if ($this->pageRole === 'all') {
			$lo_query->applyOptions([
				'skipPageRoleCheck' => true,
			]);
		}

		$lo_pages = $lo_query->find('threaded')->all();

		$la_summary = [
			'title' => [
				'empty' => [],
				'error' => [],
				'ok' => [],
				'warning' => [],
			],
			'description' => [
				'empty' => [],
				'error' => [],
				'ok' => [],
				'warning' => [],
			],
		];

		$ls_metaAppendix = Configure::read('Awyiss.System.Frontend.meta.titleSeparator') . Configure::read('Awyiss.System.Frontend.meta.titleAppendix');
		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages->listNested() as $lo_page) {
			$ls_pageTitle = $lo_page->metaTitle ?? $lo_page->title . $ls_metaAppendix;

			$lo_page->set('metaStatus', [
				'description' => [
					'length' => match (true) {
						strlen($lo_page->metaDescription ?? '') >= 160 => 'error',
						strlen($lo_page->metaDescription ?? '') >= 129 => 'warning',
						strlen($lo_page->metaDescription ?? '') === 0 => 'empty',
						default => 'ok',
					},
				],
				'title' => [
					'length' => match (true) {
						strlen($ls_pageTitle) >= 70 => 'error',
						strlen($ls_pageTitle) >= 56 => 'warning',
						strlen($ls_pageTitle) === 0 => 'empty',
						default => 'ok',
					},
					'set' => match (true) {
						$lo_page->metaTitle === null => 'auto',
						default => 'manual',
					},
				],
			]);

			$la_summary['title'][ $lo_page->metaStatus['title']['length'] ][] = $lo_page;
			$la_summary['description'][ $lo_page->metaStatus['description']['length'] ][] = $lo_page;

			$lo_page->set('metaTitle', $ls_pageTitle);
		}

		$this->set([
			'pages' => $lo_pages,
			'pageRole' => $this->pageRole,
			'pageRoles' => $this->pageRoles,
			'summary' => $la_summary,
			'attributes' => $this->table->getAttributes(),
		]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function analyzeHeadlineStructures(): void {
		$this->Authorization->ensure('analyze');

		$lo_query = $this->getOverviewQuery()->contain([
			'PageRoles',
			'PageTemplates',
		]);

		if ($this->pageRole === 'all') {
			$lo_query->applyOptions([
				'skipPageRoleCheck' => true,
			]);
		}

		$lo_pages = $lo_query->find('threaded')->all()->listNested();
		$la_pages = [];

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			$la_pages[ $lo_page->id ] = $this->analyzeHeadlineOrder($lo_page);
		}

		$la_summary = [
			'errors' => '',
			'warnings' => '',
		];

		$la_errors = array_filter($la_pages, fn ($page) => !empty($page['errors']));
		if ($la_errors) {
			$la_summary['errors'] = __('headline_structure_summary_error', count($la_errors));
		}

		$la_warnings = array_filter($la_pages, fn ($page) => empty($page['errors']) && !empty($page['warnings']));
		if ($la_warnings) {
			$la_summary['warnings'] = __('headline_structure_summary_warning', count($la_warnings));
		}

		$this->set('pages', $la_pages);
		$this->set('summary', $la_summary);

		$this->viewBuilder()->setClassName('Json');
		$this->viewBuilder()->setOption('serialize', ['pages', 'summary']);
	}


	/**
	 * @return array<string>
	 */
	#[NoDirectAccess]
	protected function initPageRoles(): array {
		/** @uses \Awyiss\Model\Table::findActive() */
		$lo_pageRoles = $this->fetchTable('PageRoles')->find('active')->all();

		foreach ($lo_pageRoles as $lo_pageRole) {
			$this->pageRoles[ Inflector::pluralize($lo_pageRole->identifier) ] = $lo_pageRole->title;
		}

		return $this->pageRoles;
	}


	/**
	 * @param mixed $ls_pageRole
	 * @return void
	 */
	protected function setTable(mixed $ls_pageRole): void {
		$this->pageRole = $ls_pageRole;

		if ($ls_pageRole === 'all') {
			$ls_pageRole = 'pages';
		}

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable(Inflector::camelize($ls_pageRole));
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return array{errors: array<string>, warnings: array<string>}
	 */
	protected function analyzeHeadlineOrder(Page $page): array {
		$lo_view = new FrontendView();

		$lo_view->setTemplatePath('Frontend/page');
		$lo_view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		$lo_view->set('page', $page);

		$ls_contents = $lo_view->render($page->pageTemplate->fileName);

		$la_headlines = [];
		$la_matches = [];
		preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $ls_contents, $la_matches, PREG_SET_ORDER);

		foreach ($la_matches as $la_match) {
			$la_headlines[] = [
				'level' => (int)$la_match[1],
				'text' => strip_tags($la_match[2]),
			];
		}

		/**
		 * Possible errors are:
		 * - H1 is not the first headline
		 * - H1 is missing
		 *
		 * Possible warnings are:
		 * - H2 is missing
		 * - A specific headline level occurs after one with
		 * a level higher than 1 (e.g. H3 after H1)
		 * - More than one H1
		 * - Too many H2 (more than 10)
		 */

		$la_result = [
			'errors' => [],
			'warnings' => [],
			'status' => __('headline_structure_status_ok'),
		];

		if (!array_filter($la_headlines, fn ($headline) => $headline['level'] === 1)) {
			$la_result['errors'][] = __('headline_structure_error_h1_missing');
		}
		elseif (empty($la_headlines) || $la_headlines[0]['level'] !== 1) {
			$la_result['errors'][] = __('headline_structure_error_h1_not_first');
		}

		if (!array_filter($la_headlines, fn ($headline) => $headline['level'] === 2)) {
			$la_result['warnings'][] = __('headline_structure_warning_h2_missing');
		}

		$li_previousLevel = 0;
		foreach ($la_headlines as $la_headline) {
			if ($la_headline['level'] > $li_previousLevel + 1) {
				$la_result['warnings'][] = __('headline_structure_warning_wrong_headline_order');
				break;
			}
			$li_previousLevel = $la_headline['level'];
		}

		$li_h1Count = count(array_filter($la_headlines, fn ($headline) => $headline['level'] === 1));
		if ($li_h1Count > 1) {
			$la_result['warnings'][] = __('headline_structure_warning_multiple_h1');
		}

		$li_h2Count = count(array_filter($la_headlines, fn ($headline) => $headline['level'] === 2));
		if ($li_h2Count > 10) {
			$la_result['warnings'][] = __('headline_structure_warning_too_many_h2');
		}

		if (!empty($la_result['errors'])) {
			$la_result['status'] = __('headline_structure_status_error');
		}
		elseif (!empty($la_result['warnings'])) {
			$la_result['status'] = __('headline_structure_status_warning');
		}

		return $la_result;
	}
}
