<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Controller\Frontend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Core\Configure;
use Cake\Database\Schema\MysqlSchemaDialect;
use Cake\Http\Exception\GoneException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;
use DateTime;


/**
 * Frontend Controller that handles all page requests
 *
 * For every found, active and published page, the controller will call
 * a method with the name of the page role in camelBacked form.
 *
 * @see \Awyiss\Controller\Frontend\FrontendController::news()
 */
class FrontendController extends AppController {
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\Model\Entity\Page|null $firstPage Cache for the first page per language
	 */
	protected static ?Page $firstPage = null;
	/**
	 * @var \Awyiss\Model\Entity\Page|null $currentPage The current page being viewed
	 */
	protected static ?Page $currentPage = null;


	/**
	 * Whether the current request is in preview mode
	 * If true, the page will be rendered, even if it's not published or inactive,
	 * and the same goes for the page's contents.
	 *
	 * @var bool $previewMode
	 */
	protected readonly bool $previewMode;


	/**
	 * @throws \Exception
	 */
	public function initialize(): void {
		DebugTimer::start('FrontendController::initialize');

		AppController::initialize();

		// Load event listeners for the current controller in the "Frontend"-folder
		EventListenersProvider::loadListener($this->getName(), Awyiss::REALM_FRONTEND);

		$this->viewBuilder()->setClassName('Frontend');

		$this->previewMode = !!$this->getRequest()->getSession()->read('previewMode.enabled', false);

		DebugTimer::stop('FrontendController::initialize');
	}


	/**
	 * This method is called when a page with the page role "page" is requested
	 * and after the page has been found and checked for its status.
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	public function page(Page $page): void {
		DebugTimer::start('FrontendController::page');

		if ($page->pageTemplateId === 2) {
			$pagesTable = $this->fetchTable('Pages');

			/** @uses \Awyiss\Model\Table::findActive() */
			$query = $pagesTable->find('all');

			if (!$this->previewMode) {
				/**
				 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
				 * @uses \Awyiss\Model\Table::findActive()
				 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
				 */
				$query
					->find('accessible')
					->find('active')
					->find('published');
			}

			$children = $query->find('mediaAssignments', useMediaEntity: true)
				->where(['parent_id' => $page->id])
				->all()
				->toArray();

			$this->set('childPages', $children);
		}

		DebugTimer::stop('FrontendController::page');
	}


	/**
	 * This method is called when a page with the page role "news" is requested
	 * and after the page has been found and checked for its status.
	 *
	 * It conveniently fetches the news category and the newer and older news items,
	 * as well as the title media for the page.
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	public function news(Page $page): void {
		DebugTimer::start('FrontendController::news');

		/** @var \Awyiss\Model\Table\PagesTable $newsTable */
		$newsTable = $this->fetchTable('News');

		$newsCategory = null;
		if ($page->parentId) {
			$newCategoryQuery = $newsTable->find('all', skipPageRoleCheck: true);

			if (!$this->previewMode) {
				/**
				 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
				 * @uses \Awyiss\Model\Table::findActive()
				 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
				 */
				$newCategoryQuery
					->find('active')
					->find('published');
			}

			$newCategoryQuery->where(['id' => $page->parentId]);
			$newsCategory = $newCategoryQuery->first();
		}

		/** @uses \Awyiss\Model\Table::findActive() */
		$newerNewsQuery = $newsTable->find('all');

		if (!$this->previewMode) {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$newerNewsQuery
				->find('accessible')
				->find('active')
				->find('published');
		}

		$newerNews = $newerNewsQuery
			->find('mediaAssignments', useMediaEntity: true)
			->where([
				'parent_id' . (!$page->parentId ? ' IS' : '') => $page->parentId,
				'system_order <' => $page->systemOrder,
			])
			->orderBy(['system_order' => 'DESC'])
			->limit(1)
			->first();

		if ($newerNews) {
			ResizedImageManager::addMediaItemsFromEntity($newerNews);
		}

		/** @uses \Awyiss\Model\Table::findActive() */
		$olderNewsQuery = $newsTable->find('all');

		if (!$this->previewMode) {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$olderNewsQuery
				->find('accessible')
				->find('active')
				->find('published');
		}

		$olderNews = $olderNewsQuery
			->find('mediaAssignments', useMediaEntity: true)
			->where([
				'parent_id' . (!$page->parentId ? ' IS' : '') => $page->parentId,
				'system_order >' => $page->systemOrder,
			])
			->orderBy(['system_order' => 'ASC'])
			->limit(1)
			->first();

		if ($olderNews) {
			ResizedImageManager::addMediaItemsFromEntity($olderNews);
		}

		$this->set([
			'category' => $newsCategory,
			'newer' => $newerNews,
			'older' => $olderNews,
		]);

		DebugTimer::stop('FrontendController::news');
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function index(): void {
		$language = $this->getRequest()->getParam('lang');

		$slug = $this->getRequest()->getParam('slug');
		$slug = $slug ? rtrim($slug, '/') : null;

		if ($slug) {
			DebugTimer::start('FrontendController::index: findPageBySlug');

			// Find the page with the provided slug
			$page = $this->findPage($language, $slug);

			DebugTimer::stop('FrontendController::index: findPageBySlug');
		}
		else {
			DebugTimer::start('FrontendController::index: findFirstPage');

			// Find the first page for the current language
			$page = $this->findPage($language);

			DebugTimer::stop('FrontendController::index: findFirstPage');
		}

		$this->handlePage($page);
	}


	/**
	 * @param string|null $languageShortcode
	 * @param string|null $slug
	 * @param array $where
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function findPage(?string $languageShortcode = null, ?string $slug = null, array $where = []): ?Page {
		DebugTimer::start('FrontendController::findPage');

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $this->fetchTable('Pages');

		$query = $pagesTable
			->find('all', softDelete: ['includeDeleted' => !!$slug], skipPageRoleCheck: true)
			->find('mediaAssignments', useMediaEntity: true);

		if (!$this->previewMode) {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query
				->find('accessible')
				->find('published');
		}

		/**
		 * Add additional where conditions but don't add defaults.
		 * When we would add a default condition here, like 'active' => true,
		 * we wouldn't find deleted pages when a slug is provided.
		 */
		if ($where) {
			$query->where($where);
		}

		// Include the languages in the query, including deleted languages
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

		if ($this->previewMode) {
			// Order all by deleted, system_order
			$query->orderBy([
				'Pages.deleted' => 'ASC',
			]);
		}
		else {
			if ($slug) {
				/*
				 * Order by `deleted` only when a slug is provided (to find deleted pages with a slug), otherwise
				 * deleted pages are not fetched at all, so no need to order by deleted
				 */
				$query->orderBy([
					'Pages.deleted' => 'ASC',
				]);
			}

			// Order all by `parents_active`, `active`
			$query->orderBy([
				'Pages.parents_active' => 'DESC',
				'Pages.active' => 'DESC',
			]);
		}

		$query->orderBy([
			'PageRoles.active' => 'DESC',
			'PageRoles.system_order' => 'ASC',
		]);

		if ($slug) {
			$query->where(['slug' => $slug]);

			if ($languageShortcode) {
				$query->andWhere(['language_shortcode' => $languageShortcode]);
			}
			else {
				$query->orderBy([
					'Languages.system_order' => 'ASC',
					'Languages.deleted' => 'ASC',
				]);

				if (!$this->previewMode) {
					$query->orderBy([
						'Languages.active' => 'DESC',
					]);
				}
			}
		}
		else {
			$languageShortcode ??= LocaleMiddleware::getLanguage()->shortcode;

			$query->where(['language_shortcode' => $languageShortcode]);

			// Order by parent_id first
			$query->orderBy(['Pages.parent_id' => 'ASC']);
		}

		$query->limit(1);

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $query->first();

		if (!$page) {
			return null;
		}

		if ($page->duplicateOf) {
			// Load the duplicated Page into the entity
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

		if (!$page->language || !$page->language->deleted) {
			return $page;
		}

		// If the language is deleted, try to find an active language with the same shortcode
		$languages = LocaleMiddleware::getLanguagesByShortcode($page->languageShortcode);
		if (isset($languages[ Awyiss::REALM_FRONTEND ])) {
			$page->language = $languages[ Awyiss::REALM_FRONTEND ];
		}

		DebugTimer::stop('FrontendController::findPage');

		return $page;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return void
	 * @throws \Exception
	 */
	protected function handlePage(?Page $page): void {
		DebugTimer::start('FrontendController::handlePage');

		$errorCode = null;
		$request = $this->getRequest();

		if (!$page) {
			// Try to find an entry in the slug history
			$this->historyRedirect(trim($this->getRequest()->getPath(), '/'));
		}

		if ($page?->language && $page->languageShortcode !== LocaleMiddleware::getLanguage()->shortcode) {
			$request = $request->withParam('lang', $page->languageShortcode);
			LocaleMiddleware::useLanguage($page->language);
		}

		/*
		 * If the page or the page role or the language of the page is deleted,
		 * check if there's a history entry for the current slug.
		 *
		 * If there is, redirect to the correct page.
		 * If there isn't, find the 410 page for the current language.
		 */
		if (
			$page &&
			(
				$page->deleted ||
				$page->pageRole->deleted ||
				$page->language?->deleted
			)
		) {
			// Try to find an entry in the slug history
			$this->historyRedirect($page->languageShortcode . '/' . $page->slug);

			// If the page or the language was deleted more than 6 months ago,
			// use the 404 instead by throwing NotFoundException and letting the ErrorController handle it
			if (
				($page->deletedOn && $page->deletedOn->diff(new DateTime())->days > 180) ||
				($page->language->deletedOn && $page->language->deletedOn->diff(new DateTime())->days > 180)
			) {
				throw new NotFoundException();
			}

			// Otherwise, throw a GoneException and let the ErrorController handle it
			throw new GoneException();
		}

		/**
		 * If
		 * - no page was found
		 * - or the page is not active
		 * - or the parents are not active
		 * - or the page role is not active
		 * - or the language is not active
		 * - or the parents are not accessible (no group access, unpublished, etc.)
		 * throw a NotFoundException and let the ErrorController handle it,
		 * as long as we're not in preview mode.
		 */
		if (
			!$page ||
			(
				(
					!$page->active ||
					!$page->parentsActive ||
					!$page->pageRole->active ||
					!$page->language?->active ||
					!$this->parentsAreAccessible($page)
				) &&
				!$this->previewMode
			)
		) {
			throw new NotFoundException();
		}

		if (!$errorCode) {
			// If the page has a redirect, redirect to the target
			if ($page->redirectLink) {
				$url = $page->redirectLink;
				if (!str_contains($url, '//')) {
					$url = Router::url($url, true);
				}

				throw new RedirectException($url, 303);
			}

			DebugTimer::start('FrontendController::handlePage: url-normalization');

			// Redirect to a normalized URL if the current URL does not match the normalized URL
			if (Configure::read('Route.includeLanguageShortcode')) {
				$this->redirectIfNotNormalized($page);
			}
			else {
				// Redirect to a normalized URL if the current URL does not match the normalized URL
				$this->redirectIfNotNormalizedNoLanguage($page);
			}

			DebugTimer::stop('FrontendController::handlePage: url-normalization');
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');

		DebugTimer::start('FrontendController::handlePage: entity-type-check');

		// Make sure the page is of the correct entity class (page role specific)
		$page = $this->ensureCorrectEntityType($page, $pageRoleEnum);

		// If there is no page, it's most likely not published or inaccessible,
		// so throw a NotFoundException and let the ErrorController handle it
		if (!$page) {
			throw new NotFoundException();
		}

		DebugTimer::stop('FrontendController::handlePage: entity-type-check');

		ResizedImageManager::addMediaItemsFromEntity($page);

		$designVariables = $this->getRequest()->getAttribute('design')->getDesignVariables();
		$designPreviewVariables = $this->loadDesignPreview();

		/** @var class-string<\Awyiss\Utility\Media\MediaRenderOptions> $className */
		$className = App::className('MediaRenderOptions', 'Utility/Media');

		$mediaRenderOptions = new $className(
			baseWidth: intval($designPreviewVariables['pageWidth'] ?? $designVariables['pageWidth'] ?? 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			singleColumnBreakpoint: intval($designPreviewVariables['singleColumnBreakpoint'] ?? $designVariables['singleColumnBreakpoint'] ?? 768),
		);

		$this->set([
			'isErrorPage' => !!$errorCode,
			'page' => $page,
			'pageRoleEnum' => $pageRoleEnum,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);

		$request = $request->withAttribute('currentPage', $page);

		if ($errorCode) {
			$response = $this->getResponse()->withStatus($errorCode);
			$this->setResponse($response);
		}

		$this->setRequest($request);
		Router::setRequest($request);

		if ($this->getRequest()->getSession()->read('Backend.Auth')) {
			$this->loadFrontendEditor($page);
		}

		$this->loadFrontendPreview($page);

		$this->viewBuilder()
			->setTemplate($page->pageTemplate->fileName)
			->setTemplatePath('Frontend/page');

		// Call the page role specific method
		$methodName = Inflector::variable($page->pageRoleId->name);
		if (method_exists($this, $methodName)) {
			$this->{$methodName}($page);
		}

		static::$currentPage = $page;

		DebugTimer::stop('FrontendController::handlePage');
	}


	/**
	 * Redirects to a normalized URL if the current URL does not match the normalized URL,
	 * when the configuration option "includeLanguageShortcode" is set to true.
	 *
	 * Normalized URLs without a slug:
	 * - If the language is the default language, the language is not included in the URL
	 * - If the language is not the default language, the language is included in the URL
	 *
	 * Normalized URLs with a slug:
	 * - If the slug is the first page of the default language,
	 *  neither the language nor the slug is included in the URL
	 * - If the slug is the first page of the language, the slug is not included in the URL
	 *
	 * All URLs end with a slash
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 * @throws \Exception
	 */
	protected function redirectIfNotNormalized(Page $page): void {
		DebugTimer::start('FrontendController::redirectIfNotNormalized');

		$languageShortcode = $this->getRequest()->getParam('lang');
		$slug = $this->getRequest()->getParam('slug');
		$params = $this->getRequest()->getQueryParams();

		if ($params && $slug) {
			$slug = rtrim($slug, '/') . '/';
		}

		if ($languageShortcode) {
			if (!$slug) {
				/*
				 * If the language is given, but no slug, check if the language is the default one
				 * If it is, redirect to the domain without the language.
				 */
				if ($languageShortcode === LocaleMiddleware::getDefaultLanguage()->shortcode) {
					$url = Router::url([
						'_name' => 'FrontendRoot',
						...$this->getRequest()->getQueryParams(),
						'?' => $this->getRequest()->getQueryParams(),
					], true);

					throw new RedirectException($url, 301);
				}

				if (!str_ends_with($this->getRequest()->getPath(), '/')) {
					$url = Router::url([
						'_name' => 'FrontendLanguageRoot',
						'lang' => $languageShortcode,
						'?' => $this->getRequest()->getQueryParams(),
						...$this->getRequest()->getQueryParams(),
					], true);

					throw new RedirectException($url, 301);
				}

				static::$firstPage ??= $page;

				DebugTimer::stop('FrontendController::redirectIfNotNormalized');
				return;
			}
			else {
				static::$firstPage ??= $this->findPage($languageShortcode);

				/*
				 * If there is a slug and the slug is the first page of the language,
				 * redirect to the domain without the slug
				 */
				if (static::$firstPage->id === $page->id) {
					$redirectUrl = [
						'_name' => 'FrontendLanguageRoot',
						'lang' => $languageShortcode,
						'?' => $this->getRequest()->getQueryParams(),
						...$this->getRequest()->getQueryParams(),
					];

					// For the default language, the language is not included in the URL
					if ($languageShortcode === LocaleMiddleware::getDefaultLanguage()->shortcode) {
						$redirectUrl = [
							'?' => $this->getRequest()->getQueryParams(),
							...$this->getRequest()->getQueryParams(),
							'_name' => 'FrontendRoot',
						];
					}

					throw new RedirectException(Router::url($redirectUrl, true), 301);
				}
			}
		}

		$testUrl = $page->languageShortcode . '/' . $page->slug . '/';
		$currentUrl = $languageShortcode . '/' . $slug;

		// If the URL does not match the normalized URL, redirect to the normalized URL
		if (
			!str_ends_with($this->getRequest()->getPath(), '/') ||
			(
				$testUrl !== $currentUrl &&
				!in_array($currentUrl, ['', '/', '//'], true)
			)
		) {
			if (!trim($currentUrl, '/')) {
				$realUrl = Router::url([
					'?' => $this->getRequest()->getQueryParams(),
					...$this->getRequest()->getQueryParams(),
					'_name' => 'FrontendRoot',
				]);
			}
			else {
				$realUrl = Router::url([
					'lang' => Configure::read('Route.includeLanguageShortcode') ? $page->languageShortcode : null,
					'slug' => $page->slug,
					'?' => $this->getRequest()->getQueryParams(),
					...$this->getRequest()->getQueryParams(),
				]);
			}

			throw new RedirectException($realUrl, 301);
		}

		// Cache the first page
		if ($testUrl !== $currentUrl) {
			static::$firstPage ??= $page;
		}

		DebugTimer::stop('FrontendController::redirectIfNotNormalized');
	}


	/**
	 * Redirects to a normalized URL if the current URL does not match the normalized URL,
	 * when the configuration option "includeLanguageShortcode" is set to false.
	 *
	 * - If the slug is the first page, the slug is not included in the URL
	 *
	 * All URLs end with a slash
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 * @throws \Exception
	 */
	protected function redirectIfNotNormalizedNoLanguage(Page $page): void {
		DebugTimer::start('FrontendController::redirectIfNotNormalizedNoLanguage');

		$slug = $this->getRequest()->getParam('slug');
		$params = $this->getRequest()->getQueryParams();

		if ($params && $slug) {
			$slug = rtrim($slug, '/') . '/';
		}

		if (!$slug) {
			if (!str_ends_with($this->getRequest()->getPath(), '/')) {
				$url = Router::url([
					'_name' => 'FrontendRoot',
					...$this->getRequest()->getQueryParams(),
					'?' => $this->getRequest()->getQueryParams(),
				], true);

				throw new RedirectException($url, 301);
			}

			DebugTimer::stop('FrontendController::redirectIfNotNormalizedNoLanguage');
			return;
		}

		static::$firstPage ??= $this->findPage();

		if (static::$firstPage->id === $page->id) {
			$redirectUrl = [
				'?' => $this->getRequest()->getQueryParams(),
				...$this->getRequest()->getQueryParams(),
				'_name' => 'FrontendRoot',
			];

			throw new RedirectException(Router::url($redirectUrl, true), 301);
		}

		DebugTimer::stop('FrontendController::redirectIfNotNormalizedNoLanguage');
	}


	/**
	 * Redirects to current page if the URL is found in the slug history
	 *
	 * @param string $url
	 * @return void
	 */
	protected function historyRedirect(string $url): void {
		DebugTimer::start('FrontendController::historyRedirect');

		$historyTable = $this->fetchTable('UrlHistory');

		$url = preg_replace('/[^a-zA-Z0-9\/:\-.]/', '', $url);
		$urls = [$url];

		// Check if the URL contains parameters, remove them
		if (str_contains($url, ':')) {
			$parts = explode('/', $url);
			$url = '';
			foreach ($parts as $part) {
				if (!str_contains($part, ':')) {
					$url .= $part . '/';
				}
			}

			$urls[] = rtrim($url, '/');
		}

		$urls = array_filter($urls);
		if (!$urls) {
			DebugTimer::stop('FrontendController::historyRedirect');
			return;
		}

		$query = $historyTable->find('all')
			->where(['url IN' => $urls])
			->contain(['Media', 'Pages'])
			->limit(1);

		$dialect = $query->getConnection()->getDriver()->schemaDialect();
		// Only MySQL supports FIND_IN_SET for ordering.
		if ($dialect instanceof MysqlSchemaDialect) {
			/** @noinspection PhpUndefinedMethodInspection */
			$query->orderByAsc($query->expr($query->func()->FIND_IN_SET([
				'UrlHistory.url' => 'identifier',
				implode(',', $urls),
			])), true);
		}
		else {
			$query->orderBy(function ($exp) use ($urls) {
				$index = 0;
				$case = $exp->case();

				foreach ($urls as $url) {
					$case->when(['UrlHistory.url' => $url])->then($index, 'integer');
					$index++;
				}

				$case->else(999, 'integer');

				return $case;
			});
		}

		$query->orderByDesc('UrlHistory.created_on');

		/** @var \Awyiss\Model\Entity\UrlHistory $record */
		$record = $query->first();

		if (!$record) {
			DebugTimer::stop('FrontendController::historyRedirect');
			return;
		}

		if ($record->page) {
			$realUrl = Router::url([
				'lang' => $record->page->languageShortcode,
				'slug' => $record->page->slug,
			]);

			// If the resulting url is one of the checked urls, do not redirect to itself
			if (in_array(trim($realUrl, '/'), $urls)) {
				DebugTimer::stop('FrontendController::historyRedirect');
				return;
			}

			throw new RedirectException($realUrl, $record->status ?? 307);
		}

		if ($record->media) {
			$realUrl = Router::url($record->media->path);

			throw new RedirectException($realUrl, $record->status ?? 307);
		}

		if ($record->target) {
			$realUrl = str_contains($record->target, '//') ? $record->target : Router::url($record->target);

			throw new RedirectException($realUrl, $record->status ?? 307);
		}

		DebugTimer::stop('FrontendController::historyRedirect');
	}


	/**
	 * Check if the page has the page role "page".
	 * IF that's not the case, fetch the record again using the correct table.
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum
	 * @return \Awyiss\Model\Entity\Page|null
	 */
	protected function ensureCorrectEntityType(Page $page, string $pageRoleEnum): ?Page {
		DebugTimer::start('FrontendController::ensureCorrectEntityType');

		if ($page->pageRoleId === $pageRoleEnum::Page) {
			DebugTimer::stop('FrontendController::ensureCorrectEntityType');
			return $page;
		}

		$pageRole = Inflector::pluralize($page->pageRoleId->name);
		$pageRoleTable = $this->fetchTable($pageRole);

		$query = $pageRoleTable->find('all');

		if (!$this->previewMode) {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query
				->find('accessible')
				->find('published');
		}

		$query->find('mediaAssignments', useMediaEntity: true)->where(['id' => $page->id])->limit(1);

		$contain = [
			'Languages',
			'PageTemplates',
		];

		// Only contain the DuplicateOf relation when the page is a duplicate
		if ($page->duplicateOf) {
			$contain['DuplicateOf' . $page->pageRoleId->name ] = [
				'fields' => ['id', 'language_shortcode', 'slug'],
				'finder' => [
					'withDeleted' => [
						'translate' => ['skip' => true],
					],
				],
			];
		}

		// Include the languages in the query, including deleted languages
		$query->contain($contain);

		$page = $query->first();

		DebugTimer::stop('FrontendController::ensureCorrectEntityType');

		return $page;
	}


	/**
	 * Loads and returns the design preview data
	 *
	 * @return array
	 */
	protected function loadDesignPreview(): array {
		DebugTimer::start('FrontendController::loadDesignPreview');

		if ($this->getRequest()->getParam('designPreview')) {
			$this->getRequest()->getSession()->write('designPreviewIdentifier', $this->getRequest()->getParam('designPreview'));
		}

		$designPreviewIdentifier = $this->getRequest()->getSession()->read('designPreviewIdentifier');

		if (!$designPreviewIdentifier) {
			DebugTimer::stop('FrontendController::loadDesignPreview');
			return [];
		}

		if ($this->getRequest()->getData('awyiss_design_preview') === 'cancel') {
			$this->getRequest()->getSession()->delete('designPreviewIdentifier');

			throw new RedirectException(Router::url(['_name' => $this->getRequest()->getParam('_name')]));
		}

		$designTable = $this->fetchTable('Designs');
		$design = $designTable->find('all')->where([
			'identifier' => $designPreviewIdentifier,
			'in_use' => false,
		])->first();

		if (!$design) {
			DebugTimer::stop('FrontendController::loadDesignPreview');
			return [];
		}

		$this->set('designPreview', $design);

		$webfontData = [];
		foreach ($design->settings as $variable => $value) {
			if (!is_array($value) || !isset($value['font']['name'])) {
				continue;
			}

			$webfontData[ $variable ] = [
				'name' => $value['font']['name'],
				'variants' => $value['variants'] ?? [],
			];
		}

		$this->set('designPreviewWebfonts', $webfontData);

		$designSettings = $design->settings ?? [];

		DebugTimer::stop('FrontendController::loadDesignPreview');

		return $designSettings;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	protected function loadFrontendEditor(Page $page): void {
		DebugTimer::start('FrontendController::loadFrontendEditor');

		if (!Configure::read('Awyiss.System.Frontend.editor')) {
			DebugTimer::stop('FrontendController::loadFrontendEditor');
			return;
		}

		$identity = $this->getRequest()->getSession()->read('Backend.Auth');

		if (!$identity instanceof IdentityPermissionsInterface) {
			DebugTimer::stop('FrontendController::loadFrontendEditor');
			return;
		}

		$permissionCollection = $identity->getPermissionCollection();
		$contentsEditable = $permissionCollection->scopeIsAccessible($page->pageRoleId->name, [], 'contents');
		$formElementsEditable = $permissionCollection->scopeIsAccessible('Forms', [], 'update');
		$globalContentsEditable = $permissionCollection->scopeIsAccessible('GlobalContents', [], 'update');
		$menuEntriesEditable = $permissionCollection->scopeIsAccessible('Menus', [], 'read');

		if (
			!$contentsEditable &&
			!$formElementsEditable &&
			!$globalContentsEditable &&
			!$menuEntriesEditable
		) {
			DebugTimer::stop('FrontendController::loadFrontendEditor');
			return;
		}

		$this->set([
			'frontendEditorConfig' => [
				'contents' => ['enabled' => $contentsEditable],
				'formElements' => ['enabled' => $formElementsEditable],
				'globalContents' => ['enabled' => $globalContentsEditable],
				'menuEntries' => ['enabled' => $menuEntriesEditable],
			],
		]);

		DebugTimer::stop('FrontendController::loadFrontendEditor');
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	protected function loadFrontendPreview(Page $page): void {
		$session = $this->getRequest()->getSession();
		$this->set([
			'frontendPreviewConfig' => [
				'enabled' => $session->read('previewMode.enabled', false),
				'i18n' => [
					'disable' => __d('system', 'preview_mode_disable'),
					'label' => __d('system', 'preview_mode_label'),
					'markInactiveElements' => __d('system', 'preview_mode_mark_inactive_elements'),
				],
				'markInactiveElements' => $session->read('previewMode.markInactiveElements', false),
				'settingsUrl' => Router::url([
					'lang' => $page->languageShortcode,
					'controller' => 'Pages',
					'action' => 'preview-settings',
					'_name' => 'Backend',
				], true),
			],
		]);
	}


	/**
	 * Check if the parents of the page are accessible
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return bool
	 */
	protected function parentsAreAccessible(Page $page): bool {
		DebugTimer::start('FrontendController::parentsAreAccessible');

		if (!$page->parentId) {
			DebugTimer::stop('FrontendController::parentsAreAccessible');
			return true;
		}

		$checkAncestorPagesPublicationStatus = Configure::read('Awyiss.System.Frontend.publicationData.checkAncestorPagesPublicationStatus', true);

		$parts = explode('/', $page->slug);
		// Remove the last part
		array_pop($parts);

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $this->fetchTable('Pages');

		$query = $pagesTable->find('all', skipPageRoleCheck: true);

		if (!$this->previewMode) {
			/** @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible() */
			$query->find('accessible');

			if ($checkAncestorPagesPublicationStatus) {
				/** @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findAccessible() */
				$query->find('published');
			}
		}

		$slugs = [];
		$lastPart = '';
		// Create an array of slugs. Each item is the previous slug + the current slug
		foreach ($parts as $part) {
			$slugs[] = $lastPart . $part;
			$lastPart .= $part . '/';
		}

		$query->where([
			'slug IN' => $slugs,
			'language_shortcode' => $page->languageShortcode,
		]);

		$parents = $query->all();

		$parentsAreAccessible = $parents->count() === count($slugs);

		DebugTimer::stop('FrontendController::parentsAreAccessible');

		return $parentsAreAccessible;
	}


	/**
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 */
	public static function getCurrentPage(): ?Page {
		return static::$currentPage;
	}


	/**
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 */
	public static function getFirstPage(): ?Page {
		return static::$firstPage;
	}
}
