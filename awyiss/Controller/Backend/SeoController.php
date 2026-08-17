<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
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

		$this->selectedPageRoleSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global')
			. '.' . Inflector::variable($this->getName()) . '.pageRole'
		;

		$session = $this->request->getSession();

		if (!in_array($this->request->getParam('action'), ['analyze', 'analyzeRenderedPage', 'analyzeRenderedPages'])) {
			$pageRole = $session->read($this->selectedPageRoleSessionIdentifier, 'all');
			if (!array_key_exists($pageRole, $this->pageRoles) && $pageRole !== 'all') {
				$pageRole = array_key_first($this->pageRoles);
			}

			$this->setTable($pageRole);

			return;
		}

		//Is there a request parameter with the name 'pageRole'?
		$pageRole = $this->request->getParam('pageRole');

		if ($pageRole) {
			$session->write($this->selectedPageRoleSessionIdentifier, $pageRole);
			$pageRole = Inflector::underscore($pageRole);
		}
		else {
			$pageRole = $session->read($this->selectedPageRoleSessionIdentifier, array_key_first($this->pageRoles));
		}

		//If the selected page role is not inside the available page roles, reset it to the first available one.
		if (!array_key_exists($pageRole, $this->pageRoles) && $pageRole !== 'all') {
			$pageRole = array_key_first($this->pageRoles);

			$session->write($this->selectedPageRoleSessionIdentifier, $pageRole);

			//Redirect to remove the invalid scope parameter from the URL
			$this->redirect(['action' => 'analyze']);
		}

		$this->setTable($pageRole);
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

		$query = $this
			->getOverviewQuery()
			->contain([
				'PageRoles',
			])
		;

		if ($this->pageRole === 'all') {
			$query->applyOptions([
				'skipPageRoleCheck' => true,
			]);
		}

		$pages = $query->find('threaded')->all();

		$summary = [
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

		$metaAppendix = Configure::read('Awyiss.System.Frontend.meta.titleSeparator')
			. Configure::read('Awyiss.System.Frontend.meta.titleAppendix')
		;

		$knownTitles = [];
		foreach ($pages->listNested() as $page) {
			$title = $page->metaTitle ?? $page->title . $metaAppendix;

			if (!isset($knownTitles[ $title ])) {
				$knownTitles[ $title ] = 0;
			}

			$knownTitles[ $title ] += 1;
		}

		/** @var \Awyiss\Model\Entity\Page $page */
		foreach ($pages->listNested() as $page) {
			$this->analyzePage($page, $metaAppendix, $knownTitles);

			$titleStatus = $page->metaStatus['title']['length'];
			$descriptionStatus = $page->metaStatus['description']['length'];

			// If the title is ok but not unique, set it to warning
			if ($page->metaStatus['title']['length'] === 'ok' && $page->metaStatus['title']['unique'] !== 'ok') {
				$titleStatus = 'warning';
			}

			$summary['title'][ $titleStatus ][] = $page;
			$summary['description'][ $descriptionStatus ][] = $page;
		}

		$this->set([
			'pages' => $pages,
			'pageRole' => $this->pageRole,
			'pageRoles' => $this->pageRoles,
			'summary' => $summary,
			'attributes' => $this->table->getAttributes(),
		]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUndefinedFieldInspection
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function analyzeRenderedPage(): void {
		$this->Authorization->ensure('analyze');

		$this->stopWords = Configure::read('Seo.stopWords.' . $this->request->getParam('lang'), []);

		$query = $this
			->getOverviewQuery()
			->contain([
				'PageRoles',
				'PageTemplates',
			])
		;

		$pageId = (int)$this->request->getParam('id');
		if (!$pageId) {
			$this->set('status', []);

			$this->viewBuilder()->setClassName('Json');
			$this->viewBuilder()->setOption('serialize', ['status']);

			return;
		}

		$query->applyOptions([
			'skipPageRoleCheck' => true,
		]);

		$pages = $query
			->find('threaded')
			->all()
			->listNested()
		;
		$status = [
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

		$view = new FrontendView();

		$view->setTemplatePath('Frontend/page');
		$view->setLayout('empty');
		$view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		$metaAppendix = Configure::read('Awyiss.System.Frontend.meta.titleSeparator')
			. Configure::read('Awyiss.System.Frontend.meta.titleAppendix')
		;

		$knownTitles = [];
		/** @var \Awyiss\Model\Entity\Page $page */
		foreach ($pages as $page) {
			$title = $page->metaTitle ?? $page->title . $metaAppendix;

			if (!isset($knownTitles[ $title ])) {
				$knownTitles[ $title ] = 0;
			}

			$knownTitles[ $title ] += 1;
		}

		/** @var \Awyiss\Model\Entity\Page $page */
		foreach ($pages as $page) {
			if ($page->id !== $pageId) {
				continue;
			}

			$view->set('page', $page);

			Awyiss::setRealm(Awyiss::REALM_FRONTEND);

			$contents = $view->render($page->pageTemplate->fileName);
			$body = $this->getHtmlBody($contents);

			Awyiss::setRealm(Awyiss::REALM_BACKEND);

			if (!$body) {
				continue;
			}

			$this->analyzePage($page, $metaAppendix, $knownTitles);

			if ($page->metaStatus['title']['length'] == 'ok') {
				if ($page->metaStatus['title']['unique'] == 'ok') {
					$status['title']['status'] = __('meta_title_length_ok', strlen($page->metaTitle));
				}
			}
			elseif ($page->metaStatus['title']['length'] == 'warning') {
				$status['title']['status'] = __('meta_title_length_warning', strlen($page->metaTitle));
				$status['title']['warning'] = true;
			}
			elseif ($page->metaStatus['title']['length'] == 'empty') {
				$status['title']['status'] = __('meta_title_length_empty');
				$status['title']['error'] = true;
			}
			else {
				$status['title']['status'] = __('meta_title_length_error', strlen($page->metaTitle));
				$status['title']['error'] = true;
			}

			if ($page->metaStatus['title']['unique'] != 'ok') {
				if ($page->metaStatus['title']['unique'] == 'ok') {
					$status['title']['autoGenerated'] .= ', ';
				}

				$status['title']['status'] .= __('meta_title_unique_warning', $page->metaTitle);
				$status['title']['warning'] = true;
			}

			if ($page->metaStatus['title']['set'] == 'auto') {
				$status['title']['autoGenerated'] = __('meta_title_auto');
			}

			if ($page->metaStatus['description']['length'] == 'ok') {
				$status['description']['status'] = __('meta_description_length_ok', strlen($page->metaDescription));
			}
			elseif ($page->metaStatus['description']['length'] == 'warning') {
				$status['description']['status'] = __('meta_description_length_warning', strlen($page->metaDescription));
				$status['description']['warning'] = true;
			}
			elseif ($page->metaStatus['description']['length'] == 'empty') {
				$status['description']['status'] = __('meta_description_length_empty');
				$status['description']['warning'] = true;
			}
			else {
				$status['description']['status'] = __('meta_description_length_error', strlen($page->metaDescription));
				$status['description']['error'] = true;
			}

			$status['headlines'] = $this->analyzePageHeadlines($body);
			$status['contents'] = $page->robotsIndex ? $this->analyzePageContents($body) : null;
		}

		$this->set('status', $status);

		$this->viewBuilder()->setClassName('Json');
		$this->viewBuilder()->setOption('serialize', ['status']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function analyzeRenderedPages(): void {
		$this->Authorization->ensure('analyze');

		$this->stopWords = Configure::read('Seo.stopWords.' . $this->request->getParam('lang'), []);

		$query = $this
			->getOverviewQuery()
			->contain([
				'PageRoles',
				'PageTemplates',
			])
		;

		if ($this->pageRole === 'all') {
			$query->applyOptions([
				'skipPageRoleCheck' => true,
			]);
		}

		$pages = [];
		$contents = [];
		$headlines = [];

		$view = new FrontendView();

		$view->setTemplatePath('Frontend/page');
		$view->setLayout('empty');
		$view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		/** @var \Awyiss\Model\Entity\Page $page */
		foreach (
			$query
				->find('threaded')
				->all()
				->listNested() as $page
		) {
			$view->set('page', $page);

			Awyiss::setRealm(Awyiss::REALM_FRONTEND);

			$body = $this->getHtmlBody($view->render($page->pageTemplate->fileName));

			Awyiss::setRealm(Awyiss::REALM_BACKEND);

			if (!$body) {
				continue;
			}

			$pages[ $page->id ] = [
				'headlines' => $this->analyzePageHeadlines($body),
				'contents' => $page->robotsIndex ? $this->analyzePageContents($body) : null,
			];

			$contents[ $page->id ] = $pages[ $page->id ]['contents'];
			$headlines[ $page->id ] = $pages[ $page->id ]['headlines'];
		}

		$summary = [
			'contents' => [
				'errors' => '',
				'warnings' => '',
			],
			'headlines' => [
				'errors' => '',
				'warnings' => '',
			],
		];

		$contentErrors = array_filter($contents, fn($page) => !empty($page['errors']));
		if ($contentErrors) {
			$summary['contents']['errors'] = __('text_length_summary_error', count($contentErrors));
		}

		$contentWarnings = array_filter($contents, fn($page) => empty($page['errors']) && !empty($page['warnings']));
		if ($contentWarnings) {
			$summary['contents']['warnings'] = __('text_length_summary_warning', count($contentWarnings));
		}

		$headlineErrors = array_filter($headlines, fn($page) => !empty($page['errors']));
		if ($headlineErrors) {
			$summary['headlines']['errors'] = __('headline_structure_summary_error', count($headlineErrors));
		}

		$headlineWarnings = array_filter($headlines, fn($page) => empty($page['errors']) && !empty($page['warnings']));
		if ($headlineWarnings) {
			$summary['headlines']['warnings'] = __('headline_structure_summary_warning', count($headlineWarnings));
		}

		$this->set('pages', $pages);
		$this->set('summary', $summary);

		$this->viewBuilder()->setClassName('Json');
		$this->viewBuilder()->setOption('serialize', ['pages', 'summary']);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
	}


	/**
	 * @return array<string>
	 */
	#[NoDirectAccess]
	protected function initPageRoles(): array {
		/** @uses \Awyiss\Model\Table::findActive() */
		$pageRoles = $this
			->fetchTable('PageRoles')
			->find('active')
			->all()
		;

		foreach ($pageRoles as $pageRole) {
			$this->pageRoles[ Inflector::pluralize($pageRole->identifier) ] = $pageRole->title;
		}

		return $this->pageRoles;
	}


	/**
	 * @param string $pageRole
	 * @return void
	 */
	protected function setTable(string $pageRole): void {
		$this->pageRole = $pageRole;

		if ($pageRole === 'all') {
			$pageRole = 'pages';
		}

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable(Inflector::camelize($pageRole));
	}


	/**
	 * @param string $contents
	 * @return array{errors: array<string>, warnings: array<string>}
	 */
	protected function analyzePageContents(string $contents): array {
		$cleanText = str_replace(['<br>', '<br/>', '<br />'], ' ', $contents);
		$cleanText = strip_tags($cleanText);
		$cleanText = str_replace('&nbsp;', ' ', $cleanText);
		$cleanText = preg_replace('/([\s\n\r\t]|\xC2\xA0|\xE2\x80\xAF)/', ' ', $cleanText);
		// Reduce consecutive spaces to one
		$cleanText = preg_replace('/[ ]+/', ' ', $cleanText);

		$result = [
			'errors' => [],
			'warnings' => [],
			'status' => __('text_length_status_ok'),
		];

		$words = array_filter(explode(' ', $cleanText));
		$wordCount = count($words);

		if ($wordCount < 100) {
			$result['errors'][] = __('text_length_error_too_few_words', $wordCount, 300);
		}
		elseif ($wordCount < 300) {
			$result['warnings'][] = __('text_length_warning_too_few_words', $wordCount, 300);
		}
		elseif ($wordCount > 4000) {
			$result['errors'][] = __('text_length_error_too_many_words', $wordCount, 2000);
		}
		elseif ($wordCount > 2000) {
			$result['warnings'][] = __('text_length_warning_too_many_words', $wordCount, 2000);
		}

		$uniqueWords = array_map(function (string $word): ?string {
			if (str_starts_with($word, '{{') && str_ends_with($word, '}}') || str_contains($word, '::')) {
				return null;
			}

			$word = preg_replace('/[^\p{L}\p{N}@\-]/u', '', $word);

			// Only numbers? Skip
			if (preg_match('/^\p{N}+$/u', $word)) {
				return null;
			}

			return $word ?: null;
		}, $words);
		$uniqueWords = array_filter($uniqueWords, function (?string $word) {
			return $word !== null && !in_array(mb_strtolower($word), $this->stopWords, true);
		});

		$result['mostlyUsedWords'] = array_count_values($uniqueWords);
		arsort($result['mostlyUsedWords']);
		$result['mostlyUsedWords'] = array_slice($result['mostlyUsedWords'], 0, 10, true) ?: null;

		if (!empty($result['errors'])) {
			$result['status'] = __('text_length_status_error');
		}
		elseif (!empty($result['warnings'])) {
			$result['status'] = __('text_length_status_warning');
		}

		return $result;
	}


	/**
	 * @param string $contents
	 * @return array{errors: array<string>, warnings: array<string>}
	 */
	protected function analyzePageHeadlines(string $contents): array {
		$headlines = [];
		$matches = [];
		preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $contents, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$headlines[] = [
				'level' => (int)$match[1],
				'text' => strip_tags($match[2]),
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

		$result = [
			'errors' => [],
			'warnings' => [],
			'status' => __('headline_structure_status_ok'),
		];

		if (!array_filter($headlines, fn($headline) => $headline['level'] === 1)) {
			$result['errors'][] = __('headline_structure_error_h1_missing');
		}
		elseif (empty($headlines) || $headlines[0]['level'] !== 1) {
			$result['errors'][] = __('headline_structure_error_h1_not_first');
		}

		if (!array_filter($headlines, fn($headline) => $headline['level'] === 2)) {
			$result['warnings'][] = __('headline_structure_warning_h2_missing');
		}

		$previousLevel = 0;
		foreach ($headlines as $headline) {
			if ($headline['level'] > $previousLevel + 1) {
				$result['warnings'][] = __('headline_structure_warning_wrong_headline_order');
				break;
			}
			$previousLevel = $headline['level'];
		}

		$h1Count = count(array_filter($headlines, fn($headline) => $headline['level'] === 1));
		if ($h1Count > 1) {
			$result['warnings'][] = __('headline_structure_warning_multiple_h1');
		}

		$h2Count = count(array_filter($headlines, fn($headline) => $headline['level'] === 2));
		if ($h2Count > 10) {
			$result['warnings'][] = __('headline_structure_warning_too_many_h2');
		}

		if (!empty($result['errors'])) {
			$result['status'] = __('headline_structure_status_error');
		}
		elseif (!empty($result['warnings'])) {
			$result['status'] = __('headline_structure_status_warning');
		}

		return $result;
	}


	/**
	 * @param string $contents
	 * @return string
	 */
	protected function getHtmlBody(string $contents): string {
		$dom = HTMLDocument::createFromString($contents, LIBXML_NOERROR, 'UTF-8');

		$html = '';

		// Remove the opening and closing `<body>`-tags
		$body = $dom->querySelector('body');

		// Remove unwanted nodes
		$unwantedNodeNames = [
			'.Widget-Breadcrumbs',
			'footer',
			'header',
			'nav',
			'template',
			'style',
			'script',
			'nav',
			'form',
			'noscript',
			'link',
			'meta',
			'picture',
			'video',
			'audio',
			'img',
			'input',
			'select',
			'textarea',
			'button',
			'canvas',
			'iframe',
			'svg',
		];
		foreach ($unwantedNodeNames as $unwantedNodeName) {
			$unwantedNodes = $body->querySelectorAll($unwantedNodeName);
			foreach ($unwantedNodes as $unwantedNode) {
				$unwantedNode->parentNode->removeChild($unwantedNode);
			}
		}

		while ($body->firstChild) {
			$html .= $dom->saveHTML($body->firstChild);
			$body->removeChild($body->firstChild);
		}

		return $html;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param string $metaAppendix
	 * @param array $knownTitles
	 * @return void
	 */
	protected function analyzePage(Page $page, string $metaAppendix, array $knownTitles): void {
		$pageTitle = $page->metaTitle ?? $page->title . $metaAppendix;

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
					strlen($pageTitle) >= 70 => 'error',
					strlen($pageTitle) >= 56 => 'warning',
					strlen($pageTitle) === 0 => 'empty',
					default => 'ok',
				},
				'unique' => match (true) {
					$knownTitles[ $pageTitle ] > 1 => 'warning',
					default => 'ok',
				},
				'set' => match (true) {
					$page->metaTitle === null => 'auto',
					default => 'manual',
				},
			],
		]);

		$page->set('metaTitle', $pageTitle);
	}
}
