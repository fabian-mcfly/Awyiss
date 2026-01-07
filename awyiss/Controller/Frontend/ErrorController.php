<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Jaybizzle\CrawlerDetect\CrawlerDetect;


/**
 * Handles errors that occur in the frontend-scope
 */
class ErrorController extends AppController {
	use LocatorAwareTrait;


	/**
	 * @var bool
	 */
	protected bool $templatePathSet = false;
	/**
	 * @var bool
	 */
	protected bool $layoutSet = false;


	/**
	 * beforeRender callback.
	 *
	 * @param EventInterface<\Cake\Controller\Controller> $event Event.
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		parent::beforeRender($event);

		$isPreview = !!(Router::getRequest()?->getSession()->read('previewMode.enabled', false));
		if (Configure::read('debug') && !$isPreview) {
			return;
		}

		$viewBuilder = $this->viewBuilder();
		$viewBuilder->setClassName('Frontend');

		if (!$this->layoutSet) {
			$viewBuilder->setLayout('error');
		}

		if (!$this->templatePathSet) {
			$viewBuilder->setTemplatePath('Frontend/Error');
		}
	}


	/**
	 * Handles 403 - Forbidden errors
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\ForbiddenException|\Exception
	 * @noinspection PhpUnused
	 */
	public function forbidden(): void {
		$page = $this->findErrorPage('403');

		if (!$page) {
			return;
		}

		$this->handlePage($page);
	}


	/**
	 * Handles 410 - Gone errors
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\GoneException|\Exception
	 */
	public function gone(): void {
		$this->trackNotFound();

		$page = $this->findErrorPage('410');

		if (!$page) {
			return;
		}

		$this->handlePage($page);
	}


	/**
	 * Handles 404 - Not Found errors by looking up for a page with the 404 slug
	 * in the current language, or the main language as fallback.
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\NotFoundException|\Exception
	 * @noinspection PhpUnused
	 */
	public function notFound(): void {
		$this->trackNotFound();

		$page = $this->findErrorPage('404');

		if (!$page) {
			return;
		}

		$this->handlePage($page);
	}


	/**
	 * Find an error page by error code with fallbacks
	 * Tries: language-specific error page -> main language error page -> language-specific 404 -> main language 404
	 *
	 * @param string $errorCode The error code (e.g., '403', '404', '410')
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 */
	protected function findErrorPage(string $errorCode): ?Page {
		// Try language-specific error page
		$page = $this->findPage(LocaleMiddleware::getLanguage()->shortcode, $errorCode);

		if ($page) {
			return $page;
		}

		// Try main language error page
		$page = $this->findPage(null, $errorCode);

		if ($page) {
			return $page;
		}

		// If error code is 404, don't try further fallbacks
		if ($errorCode === '404') {
			return null;
		}

		// Try language-specific 404
		$page = $this->findPage(LocaleMiddleware::getLanguage()->shortcode, '404');

		if ($page) {
			return $page;
		}

		// Try main language 404
		$page = $this->findPage(null, '404');

		if ($page) {
			return $page;
		}

		return null;
	}



	/**
	 * Find a page by slug and language
	 *
	 * @param string|null $languageShortcode
	 * @param string $slug
	 * @param array $where
	 * @return \Awyiss\Model\Entity\Page|null
	 * @noinspection DuplicatedCode
	 */
	protected function findPage(?string $languageShortcode, string $slug, array $where = []): ?Page {
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $this->fetchTable('Pages');

		/**
		 * @uses \Awyiss\Model\Table::findActive()
		 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
		 */
		$query = $pagesTable
			->find('active')
			->find('published', skipPageRoleCheck: true)
			->find('mediaAssignments', useMediaEntity: true);

		if ($where) {
			$query->where($where);
		}

		// Include the languages, page roles, and page templates
		$query->contain([
			'Languages' => [
				'fields' => ['id', 'active', 'deleted'],
				'finder' => [
					$languageShortcode ? 'withDeleted' : 'all' => [
						'translate' => ['skip' => true],
					],
				],
			],
			'PageRoles' => [
				'fields' => ['id', 'active', 'deleted'],
				'finder' => [
					$languageShortcode ? 'withDeleted' : 'all' => [
						'translate' => ['skip' => true],
					],
				],
			],
			'PageTemplates' => [
				'fields' => ['id', 'file_name'],
				'finder' => [
					$languageShortcode ? 'withDeleted' : 'all' => [
						'translate' => ['skip' => true],
					],
				],
			],
		]);

		$query->orderBy([
			'Pages.parents_active' => 'DESC',
			'Pages.active' => 'DESC',
			'PageRoles.active' => 'DESC',
			'PageRoles.system_order' => 'ASC',
		]);

		$query->where(['slug' => $slug]);

		if ($languageShortcode) {
			$query->andWhere(['language_shortcode' => $languageShortcode]);
		}
		else {
			$query->orderBy([
				'Languages.system_order' => 'ASC',
				'Languages.deleted' => 'ASC',
				'Languages.active' => 'DESC',
			]);
		}

		$query->limit(1);

		/** @var \Awyiss\Model\Entity\Page|null $page */
		$page = $query->first();

		if (!$page) {
			return null;
		}

		// Handle duplicate pages
		if ($page->duplicateOf) {
			$pagesTable->loadInto($page, [
				'DuplicateOfPage' => [
					'fields' => ['id', 'language_shortcode', 'slug'],
					'finder' => [
						$languageShortcode ? 'withDeleted' : 'all' => [
							'translate' => ['skip' => true],
						],
					],
				],
			]);
		}

		// Handle deleted languages
		if (!$page->language || !$page->language->deleted) {
			return $page;
		}

		$languages = LocaleMiddleware::getLanguagesByShortcode($page->languageShortcode);
		if (isset($languages['Frontend'])) {
			$page->language = $languages['Frontend'];
		}

		return $page;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	protected function handlePage(Page $page): void {
		$this
			->viewBuilder()
			//->setLayout('default')
			->setTemplate($page->pageTemplate->fileName)
			->setTemplatePath('Frontend/page');

		$this->templatePathSet = true;
		$this->layoutSet = true;

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');

		ResizedImageManager::addMediaItemsFromEntity($page);

		$designVariables = $this->getRequest()->getAttribute('design')->getDesignVariables();

		/** @var class-string<\Awyiss\Utility\Media\MediaRenderOptions> $className */
		$className = App::className('MediaRenderOptions', 'Utility/Media');

		$mediaRenderOptions = new $className(
			baseWidth: intval($designVariables['pageWidth'] ?? 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			singleColumnBreakpoint: intval($designVariables['singleColumnBreakpoint'] ?? 768),
		);

		$this->set([
			'isErrorPage' => true,
			'page' => $page,
			'pageRoleEnum' => $pageRoleEnum,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);
	}


	/**
	 * @return void
	 */
	protected function trackNotFound(): void {
		DebugTimer::start('ErrorController::track404');

		static $tracked;

		if (!isset($tracked)) {
			$tracked = true;
		}
		else {
			DebugTimer::stop('ErrorController::track404');
			return;
		}

		$languageShortcode = $this->getRequest()->getParam('lang');
		$slug = $languageShortcode . '/' . $this->getRequest()->getParam('slug');
		$slug = '/' . trim($slug, '/');

		// Don't track resized and preview images and assets
		if (
			$slug === '/apple-touch-icon-precomposed.png' ||
			$slug === '/apple-touch-icon.png' ||
			$slug === '/favicon.png' ||
			$slug === '/backup' ||
			$slug === '/new' ||
			$slug === '/old' ||
			$slug === '/test' ||
			$slug === '/temp' ||
			str_contains($slug, '/_resized') ||
			str_contains($slug, '_preview/') ||
			str_starts_with($slug, '/.git/') ||
			str_starts_with($slug, '/assets/') ||
			str_starts_with($slug, '/awyiss/assets/') ||
			str_starts_with($slug, '/config/') ||
			str_starts_with($slug, '//google') ||
			str_starts_with($slug, '/wordpress') ||
			str_starts_with($slug, '/wp-admin')
		) {
			DebugTimer::stop('ErrorController::track404');
			return;
		}

		$blocklistedUrls = Configure::read('Awyiss.UrlsNotFound.Frontend.blocklistedUrls', []);
		foreach ($blocklistedUrls as $blocklistedUrl) {
			$pattern = preg_quote(trim($blocklistedUrl, '*/'), '/');

			if (str_starts_with($blocklistedUrl, '*')) {
				$pattern = '.*' . $pattern;
			}
			if (str_ends_with($blocklistedUrl, '*')) {
				$pattern .= '.*';
			}

			if (preg_match('/' . $pattern . '/', trim($slug, '/'))) {
				DebugTimer::stop('ErrorController::track404');
				return;
			}
		}

		/**
		 * Check if an entry for the current slug already exists within the last 5 minutes
		 * If it does, don't track it again
		 *
		 * @var \Awyiss\Model\Table\UrlsNotFoundTable $urlsNotFoundTable
		 */
		$urlsNotFoundTable = $this->fetchTable('UrlsNotFound');
		if ($urlsNotFoundTable->exists(['url' => $slug, 'created_on >' => new DateTime('-5 minutes')])) {
			DebugTimer::stop('ErrorController::track404');
			return;
		}

		$notFound = $urlsNotFoundTable->newDefaultEntity([
			'url' => $slug,
			'referrer' => $this->getRequest()->referer(),
			'isRobot' => $this->isRobot(),
		]);

		$urlsNotFoundTable->save($notFound, ['allowFrontendSave' => true]);

		DebugTimer::stop('ErrorController::track404');
	}


	/**
	 * @return bool
	 */
	protected function isRobot(): bool {
		$userAgent = $this->getRequest()->getHeaderLine('User-Agent');

		return new CrawlerDetect()->isCrawler($userAgent);
	}
}
