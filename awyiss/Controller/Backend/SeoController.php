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
use Dom\HTMLDocument;


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
	 * @var array<string>
	 */
	protected array $stopWords = [];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function initialize(): void {
		parent::initialize();

		$this->initPageRoles();

		$this->selectedPageRoleSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.page_role';

		$lo_session = $this->request->getSession();

		if (!in_array($this->request->getParam('action'), ['analyze', 'analyzeRenderedPage', 'analyzeRenderedPages'])) {
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
	 * @noinspection PhpUndefinedFieldInspection
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

		$la_knownTitles = [];
		foreach ($lo_pages->listNested() as $lo_page) {
			$ls_title = $lo_page->metaTitle ?? $lo_page->title . $ls_metaAppendix;

			if (!isset($la_knownTitles[$ls_title])) {
				$la_knownTitles[ $ls_title ] = 0;
			}

			$la_knownTitles[ $ls_title ] += 1;
		}

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages->listNested() as $lo_page) {
			$this->analyzePage($lo_page, $ls_metaAppendix, $la_knownTitles);

			$ls_titleStatus = $lo_page->metaStatus['title']['length'];
			$ls_descriptionStatus = $lo_page->metaStatus['description']['length'];

			// If the title is ok but not unique, set it to warning
			if ($lo_page->metaStatus['title']['length'] === 'ok' && $lo_page->metaStatus['title']['unique'] !== 'ok') {
				$ls_titleStatus = 'warning';
			}

			$la_summary['title'][ $ls_titleStatus ][] = $lo_page;
			$la_summary['description'][ $ls_descriptionStatus ][] = $lo_page;
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
	 * @noinspection PhpUndefinedFieldInspection
	 */
	#[NoDirectAccess]
	public function analyzeRenderedPage(): void {
		$this->Authorization->ensure('analyze');

		$this->stopWords = Configure::read('Seo.stopWords.' . $this->request->getParam('lang'), []);

		$lo_query = $this->getOverviewQuery()->contain([
			'PageRoles',
			'PageTemplates',
		]);

		$li_pageId = (int)$this->request->getParam('id');
		if (!$li_pageId) {
			$this->set('status', []);

			$this->viewBuilder()->setClassName('Json');
			$this->viewBuilder()->setOption('serialize', ['status']);

			return;
		}

		$lo_query->applyOptions([
			'skipPageRoleCheck' => true,
		]);

		$lo_pages = $lo_query->find('threaded')->all()->listNested();
		$la_status = [
			'title' => [
				'error' => false,
				'warning' => false,
				'autoGenerated' => false,
				'status' => null,
			],
			'description' => [
				'error' => false,
				'warning' => false,
				'status' => null,
			],
			'headlines' => [],
			'contents' => [],
		];

		$lo_view = new FrontendView();

		$lo_view->setTemplatePath('Frontend/page');
		$lo_view->setLayout('empty');
		$lo_view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		$ls_metaAppendix = Configure::read('Awyiss.System.Frontend.meta.titleSeparator') . Configure::read('Awyiss.System.Frontend.meta.titleAppendix');

		$la_knownTitles = [];
		foreach ($lo_pages as $lo_page) {
			$ls_title = $lo_page->metaTitle ?? $lo_page->title . $ls_metaAppendix;

			if (!isset($la_knownTitles[ $ls_title ])) {
				$la_knownTitles[ $ls_title ] = 0;
			}

			$la_knownTitles[ $ls_title ] += 1;
		}


		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			if ($lo_page->id !== $li_pageId) {
				continue;
			}

			$lo_view->set('page', $lo_page);

			$ls_contents = $lo_view->render($lo_page->pageTemplate->fileName);
			$ls_body = $this->getHtmlBody($ls_contents);
			if (!$ls_body) {
				continue;
			}

			$this->analyzePage($lo_page, $ls_metaAppendix, $la_knownTitles);

			if ($lo_page->metaStatus['title']['length'] == 'ok') {
				if ($lo_page->metaStatus['title']['unique'] == 'ok') {
					$la_status['title']['status'] = __('meta_title_length_ok', strlen($lo_page->metaTitle));
				}
			}
			elseif ($lo_page->metaStatus['title']['length'] == 'warning') {
				$la_status['title']['status'] = __('meta_title_length_warning', strlen($lo_page->metaTitle));
				$la_status['title']['warning'] = true;
			}
			elseif ($lo_page->metaStatus['title']['length'] == 'empty') {
				$la_status['title']['status'] = __('meta_title_length_empty');
				$la_status['title']['error'] = true;
			}
			else {
				$la_status['title']['status'] = __('meta_title_length_error', strlen($lo_page->metaTitle));
				$la_status['title']['error'] = true;
			}

			if ($lo_page->metaStatus['title']['unique'] != 'ok') {
				if ($lo_page->metaStatus['title']['unique'] == 'ok') {
					$la_status['title']['autoGenerated'] .= ', ';
				}

				$la_status['title']['status'] .= __('meta_title_unique_warning', $lo_page->metaTitle);
				$la_status['title']['warning'] = true;
			}

			if ($lo_page->metaStatus['title']['set'] == 'auto') {
				$la_status['title']['autoGenerated'] =  __('meta_title_auto');
			}

			if ($lo_page->metaStatus['description']['length'] == 'ok') {
				$la_status['description']['status'] = __('meta_description_length_ok', strlen($lo_page->metaDescription));
			}
			elseif ($lo_page->metaStatus['description']['length'] == 'warning') {
				$la_status['description']['status'] =  __('meta_description_length_warning', strlen($lo_page->metaDescription));
				$la_status['description']['warning'] = true;
			}
			elseif ($lo_page->metaStatus['description']['length'] == 'empty') {
				$la_status['description']['status'] =  __('meta_description_length_empty');
				$la_status['description']['warning'] = true;
			}
			else {
				$la_status['description']['status'] =  __('meta_description_length_error', strlen($lo_page->metaDescription));
				$la_status['description']['error'] = true;
			}

			$la_status['headlines'] = $this->analyzePageHeadlines($ls_body);
			$la_status['contents'] = $lo_page->robotsIndex ? $this->analyzePageContents($ls_body) : null;
		}

		$this->set('status', $la_status);

		$this->viewBuilder()->setClassName('Json');
		$this->viewBuilder()->setOption('serialize', ['status']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function analyzeRenderedPages(): void {
		$this->Authorization->ensure('analyze');

		$this->stopWords = Configure::read('Seo.stopWords.' . $this->request->getParam('lang'), []);

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
		$la_contents = [];
		$la_headlines = [];

		$lo_view = new FrontendView();

		$lo_view->setTemplatePath('Frontend/page');
		$lo_view->setLayout('empty');
		$lo_view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			$lo_view->set('page', $lo_page);

			$ls_contents = $lo_view->render($lo_page->pageTemplate->fileName);
			$ls_body = $this->getHtmlBody($ls_contents);
			if (!$ls_body) {
				continue;
			}

			$la_pages[ $lo_page->id ] = [
				'headlines' => $this->analyzePageHeadlines($ls_body),
				'contents' => $lo_page->robotsIndex ? $this->analyzePageContents($ls_body) : null,
			];

			$la_contents[ $lo_page->id ] = $la_pages[ $lo_page->id ]['contents'];
			$la_headlines[ $lo_page->id ] = $la_pages[ $lo_page->id ]['headlines'];
		}

		$la_summary = [
			'contents' => [
				'errors' => '',
				'warnings' => '',
			],
			'headlines' => [
				'errors' => '',
				'warnings' => '',
			],
		];

		$la_contentErrors = array_filter($la_contents, fn ($page) => !empty($page['errors']));
		if ($la_contentErrors) {
			$la_summary['contents']['errors'] = __('text_length_summary_error', count($la_contentErrors));
		}

		$la_contentWarnings = array_filter($la_contents, fn ($page) => empty($page['errors']) && !empty($page['warnings']));
		if ($la_contentWarnings) {
			$la_summary['contents']['warnings'] = __('text_length_summary_warning', count($la_contentWarnings));
		}

		$la_headlineErrors = array_filter($la_headlines, fn ($page) => !empty($page['errors']));
		if ($la_headlineErrors) {
			$la_summary['headlines']['errors'] = __('headline_structure_summary_error', count($la_headlineErrors));
		}

		$la_headlineWarnings = array_filter($la_headlines, fn ($page) => empty($page['errors']) && !empty($page['warnings']));
		if ($la_headlineWarnings) {
			$la_summary['headlines']['warnings'] = __('headline_structure_summary_warning', count($la_headlineWarnings));
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
	 * @param string $contents
	 * @return array{errors: array<string>, warnings: array<string>}
	 */
	protected function analyzePageContents(string $contents): array {
		$ls_cleanText = str_replace(['<br>', '<br/>', '<br />'], ' ', $contents);
		$ls_cleanText = strip_tags($ls_cleanText);
		$ls_cleanText = str_replace('&nbsp;', ' ', $ls_cleanText);
		$ls_cleanText = preg_replace('/([\s\n\r\t]|\xC2\xA0|\xE2\x80\xAF)/', ' ', $ls_cleanText);
		// Reduce consecutive spaces to one
		$ls_cleanText = preg_replace('/[ ]+/', ' ', $ls_cleanText);

		$la_result = [
			'errors' => [],
			'warnings' => [],
			'status' => __('text_length_status_ok'),
		];

		$la_words = array_filter(explode(' ', $ls_cleanText));
		$la_wordCount = count($la_words);

		if ($la_wordCount < 100) {
			$la_result['errors'][] = __('text_length_error_too_few_words', $la_wordCount, 300);
		}
		elseif ($la_wordCount < 300) {
			$la_result['warnings'][] = __('text_length_warning_too_few_words', $la_wordCount, 300);
		}
		elseif ($la_wordCount > 4000) {
			$la_result['warnings'][] = __('text_length_error_too_many_words', $la_wordCount, 2000);
		}
		elseif ($la_wordCount > 2000) {
			$la_result['warnings'][] = __('text_length_warning_too_many_words', $la_wordCount, 2000);
		}

		$la_uniqueWords = array_map(function (string $word): ?string {
			if (str_starts_with($word, '{{') && str_ends_with($word, '}}') || str_contains($word, '::')) {
				return null;
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$word = preg_replace('/[^\p{L}\p{N}@\-]/u', '', $word);

			// Only numbers? Skip
			if (preg_match('/^\p{N}+$/u', $word)) {
				return null;
			}

			return $word ?: null;
		}, $la_words);
		$la_uniqueWords = array_filter($la_uniqueWords, function (?string $word) {
			return $word !== null && !in_array(mb_strtolower($word), $this->stopWords, true);
		});

		$la_result['mostlyUsedWords'] = array_count_values($la_uniqueWords);
		arsort($la_result['mostlyUsedWords']);
		$la_result['mostlyUsedWords'] = array_slice($la_result['mostlyUsedWords'], 0, 10, true) ?: null;

		if (!empty($la_result['errors'])) {
			$la_result['status'] = __('text_length_status_error');
		}
		elseif (!empty($la_result['warnings'])) {
			$la_result['status'] = __('text_length_status_warning');
		}

		return $la_result;
	}


	/**
	 * @param string $contents
	 * @return array{errors: array<string>, warnings: array<string>}
	 */
	protected function analyzePageHeadlines(string $contents): array {
		$la_headlines = [];
		$la_matches = [];
		preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $contents, $la_matches, PREG_SET_ORDER);

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


	/**
	 * @param string $contents
	 * @return string
	 */
	protected function getHtmlBody(string $contents): string {
		$lo_dom = HTMLDocument::createFromString($contents, LIBXML_NOERROR, 'UTF-8');

		$ls_html = '';

		// Remove the opening and closing `<body>`-tags
		$lo_body = $lo_dom->querySelector('body');

		// Remove unwanted nodes
		$la_unwantedNodes = [
			'.Module-Breadcrumbs', 'footer', 'header', 'nav', 'template', 'style', 'script', 'nav', 'form', 'noscript',
			'link', 'meta', 'picture', 'video', 'audio', 'img', 'input', 'select', 'textarea', 'button', 'canvas', 'iframe', 'svg',
		];
		foreach ($la_unwantedNodes as $ls_unwantedNode) {
			$lo_unwantedNodes = $lo_body->querySelectorAll($ls_unwantedNode);
			foreach ($lo_unwantedNodes as $lo_unwantedNode) {
				$lo_unwantedNode->parentNode->removeChild($lo_unwantedNode);
			}
		}

		while ($lo_body->firstChild) {
			$ls_html .= $lo_dom->saveHTML($lo_body->firstChild);
			$lo_body->removeChild($lo_body->firstChild);
		}

		return $ls_html;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param string $metaAppendix
	 * @param array $knownTitles
	 * @return void
	 */
	protected function analyzePage(Page $page, string $metaAppendix, array $knownTitles): void {
		$ls_pageTitle = $page->metaTitle ?? $page->title . $metaAppendix;

		$page->set('metaStatus', [
			'description' => [
				'length' => match (true) {
					strlen($page->metaDescription ?? '') >= 160 => 'error',
					strlen($page->metaDescription ?? '') >= 129 => 'warning',
					strlen($page->metaDescription ?? '') === 0 => 'empty',
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
				'unique' => match (true) {
					$knownTitles[ $ls_pageTitle ] > 1 => 'warning',
					default => 'ok',
				},
				'set' => match (true) {
					$page->metaTitle === null => 'auto',
					default => 'manual',
				},
			],
		]);

		$page->set('metaTitle', $ls_pageTitle);
	}
}
