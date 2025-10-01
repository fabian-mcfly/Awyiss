<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;
use DateTime;
use Jaybizzle\CrawlerDetect\CrawlerDetect;


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
		AppController::initialize();

		//Load event listeners for the current controller in the "backend"-folder
		EventListenersProvider::loadListener($this->getName(), Awyiss::REALM_FRONTEND);

		$this->viewBuilder()->setClassName('Frontend');

		$this->previewMode = !!$this->getRequest()->getSession()->read('previewMode.enabled', false);
	}


	/**
	 * This method is called when a page with the page role "news" is requested
	 * and after the page has been found and checked for its status.
	 *
	 * It conveniently fetches the news category and the newer and older news items,
	 * as well as the title media for the page.
	 *
	 * The method also sets the og:image meta tag for the page.
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	public function news(Page $page): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_newsTable */
		$lo_newsTable = $this->fetchTable('News');

		$lo_newsCategory = null;
		if ($page->parentId) {
			$lo_query = $lo_newsTable->find($this->previewMode ? 'all' : 'active')->find(!$this->previewMode ? 'published' : 'all', skipPageRoleCheck: true);
			$lo_query->where(['id' => $page->parentId]);
			$lo_newsCategory = $lo_query->first();
		}

		$lo_newer = $lo_newsTable->find($this->previewMode ? 'all' : 'active')->find(!$this->previewMode ? 'published' : 'all')->find('mediaAssignments', useMediaEntity: true)
		->where([
			'parent_id' . (!$page->parentId ? ' IS' : '') => $page->parentId,
			'system_order <' => $page->systemOrder,
		])
		->orderBy(['system_order' => 'DESC'])
		->limit(1)->first();

		$lo_older = $lo_newsTable->find($this->previewMode ? 'all' : 'active')->find(!$this->previewMode ? 'published' : 'all')->find('mediaAssignments', useMediaEntity: true)
		->where([
			'parent_id' . (!$page->parentId ? ' IS' : '') => $page->parentId,
			'system_order >' => $page->systemOrder,
		])
		->orderBy(['system_order' => 'ASC'])
		->limit(1)->first();

		if ($lo_newer) {
			ResizedImageManager::addMediaItemsFromEntity($lo_newer);
		}

		if ($lo_older) {
			ResizedImageManager::addMediaItemsFromEntity($lo_older);
		}

		/** @var \Awyiss\Model\Entity\Media $lo_titleMedia */
		$lo_titleMedia = $page->mediaAssignments['titleAndTeaserImage']['titleMedia'] ?? null;
		if ($lo_titleMedia) {
			$this->set('ogImage', Router::url('/', true) . ($lo_titleMedia->isImage() ? $lo_titleMedia->path : $lo_titleMedia->previewPath));
		}

		$this->set([
			'category' => $lo_newsCategory,
			'newer' => $lo_newer,
			'older' => $lo_older,
		]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function index(): void {
		$ls_language = $this->getRequest()->getParam('lang');

		$ls_slug = $this->getRequest()->getParam('slug');
		$ls_slug = $ls_slug ? rtrim($ls_slug, '/') : null;

		if ($ls_slug) {
			// Find the page with the provided slug
			$lo_page = $this->findPage($ls_language, $ls_slug);
		}
		else {
			// Find the first page for the current language
			$lo_page = $this->findPage($ls_language);
		}

		$this->handlePage($lo_page);
	}


	/**
	 * @param string|null $languageShortcode
	 * @param string|null $slug
	 * @param array $where
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 */
	protected function findPage(?string $languageShortcode = null, ?string $slug = null, array $where = []): ?Page {
		/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
		$lo_pagesTable = $this->fetchTable('Pages');

		$lo_query = $lo_pagesTable->find(!$this->previewMode ? 'published' : 'all', softDelete: ['includeDeleted' => !!$slug], skipPageRoleCheck: true);

		// Add additional where conditions
		if ($where) {
			$lo_query->where($where);
		}

		// Include the languages in the query, including deleted languages
		$lo_query->contain([
			'DuplicateOfPage',
			'Languages' => [
				'finder' => $languageShortcode ? 'withDeleted' : 'all',
			],
			'PageTemplates',
		]);

		if ($this->previewMode) {
			// Order all by deleted, system_order
			$lo_query->orderBy([
				'Pages.deleted' => 'ASC',
			]);
		}
		else {
			// Order all by deleted, parents_active, active, system_order
			$lo_query->orderBy([
				'Pages.deleted' => 'ASC',
				'Pages.parents_active' => 'DESC',
				'Pages.active' => 'DESC',
			]);
		}

		if ($slug) {
			$lo_query->where(['slug' => $slug]);

			if ($languageShortcode) {
				$lo_query->andWhere(['language_shortcode' => $languageShortcode]);
			}
			else {
				$lo_query->orderBy([
					'Languages.deleted' => 'ASC',
				]);

				if (!$this->previewMode) {
					$lo_query->orderBy([
						'Languages.active' => 'DESC',
					]);
				}
			}
		}
		else {
			$ls_languageShortcode = $languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode;

			$lo_query->where(['language_shortcode' => $ls_languageShortcode]);

			// Order by parent_id first
			$lo_query->orderBy(['Pages.parent_id' => 'ASC']);
		}

		$lo_query->limit(1);

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $lo_query->first();

		if (!$lo_page) {
			return null;
		}

		if (!$lo_page->language || !$lo_page->language->deleted) {
			return $lo_page;
		}

		// If the language is deleted, try to find an active language with the same shortcode
		$la_languages = LocaleMiddleware::getLanguagesByShortcode($lo_page->languageShortcode);
		if (isset($la_languages[ Awyiss::REALM_FRONTEND ])) {
			$lo_page->language = $la_languages[ Awyiss::REALM_FRONTEND ];
		}

		return $lo_page;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return void
	 * @throws \Exception
	 */
	protected function handlePage(?Page $page): void {
		$li_errorCode = null;
		$lo_page = $page;

		$lo_request = $this->getRequest();

		if (!$lo_page) {
			// Try to find an entry in the slug history
			$this->historyRedirect(trim($this->getRequest()->getPath(), '/'));
		}

		if ($lo_page?->language && $lo_page->languageShortcode !== LocaleMiddleware::getLanguage()->shortcode) {
			$lo_request = $lo_request->withParam('lang', $lo_page->languageShortcode);
			LocaleMiddleware::useLanguage($lo_page->language);
		}

		/*
		 * If the page or the language of the page is deleted,
		 * check if there's a history entry for the current slug.
		 *
		 * If there is, redirect to the correct page.
		 * If there isn't, find the 410 page for the current language.
		 */
		if ($lo_page && ($lo_page->deleted || $lo_page->language->deleted)) {
			// Try to find an entry in the slug history
			$this->historyRedirect($lo_page->languageShortcode . '/' . $lo_page->slug);

			$this->track404();

			// Find the 410 page for the current language
			$ls_statusCode = '410';

			// If the page or the language was deleted more than 6 months ago, use the 404 instead
			if (
				($lo_page->deletedOn && $lo_page->deletedOn->diff(new DateTime())->days > 180) ||
				($lo_page->language->deletedOn && $lo_page->language->deletedOn->diff(new DateTime())->days > 180)
			) {
				$ls_statusCode = '404';
			}

			$lo_page = $this->findPage($lo_page->languageShortcode, $ls_statusCode, ['active' => true, 'deleted' => false]);
			$li_errorCode = (int)$ls_statusCode;

			if (!$lo_page) {
				throw new NotFoundException();
			}
		}

		/*
		 * If no page was found or the page is not active or the parents are not active,
		 * find the 404 page for the current language.
		 */
		if (
			!$lo_page ||
			(
				(
					!$lo_page->active ||
					!$lo_page->parentsActive ||
					!$lo_page->language->active ||
					!$this->parentsArePublished($lo_page)
				) &&
				!$this->previewMode
			)
		) {
			$this->track404();

			// Find the 404 page for the current language
			$lo_page = $this->findPage(LocaleMiddleware::getLanguage()->shortcode, '404', ['active' => true, 'deleted' => false]);
			$li_errorCode = 404;

			if (!$lo_page) {
				throw new NotFoundException();
			}
		}

		if (!$li_errorCode) {
			// If the page has a redirect, redirect to the target
			if ($lo_page->redirectLink) {
				$ls_url = $lo_page->redirectLink;
				if (!str_contains($ls_url, '//')) {
					$ls_url = Router::url($ls_url, true);
				}

				throw new RedirectException($ls_url, 303);
			}

			// Redirect to a normalized URL if the current URL does not match the normalized URL
			if (Configure::read('Route.includeLanguageShortcode')) {
				$this->redirectIfNotNormalized($lo_page);
			}
			else {
				// Redirect to a normalized URL if the current URL does not match the normalized URL
				$this->redirectIfNotNormalizedNoLanguage($lo_page);
			}
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		// Make sure the page is of the correct entity class (page role specific)
		$lo_page = $this->ensureCorrectEntityType($lo_page, $ls_pageRoleEnum);

		// If there is no page, it's most likely not published
		if (!$lo_page) {
			$this->track404();

			// Find the 404 page for the current language
			$lo_page = $this->findPage(LocaleMiddleware::getLanguage()->shortcode, '404', ['active' => true, 'deleted' => false]);

			if (!$lo_page) {
				throw new NotFoundException();
			}
		}

		ResizedImageManager::addMediaItemsFromEntity($lo_page);

		$la_designVariables = $this->getRequest()->getAttribute('design')->getDesignVariables();
		$la_designPreviewVariables = $this->loadDesignPreview();

		/** @var class-string<\Awyiss\Utility\Media\MediaRenderOptions> $ls_className */
		$ls_className = App::className('MediaRenderOptions', 'Utility/Media');

		$lo_mediaRenderOptions = new $ls_className(
			baseWidth: intval($la_designPreviewVariables['pageWidth'] ?? $la_designVariables['pageWidth'] ?? 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			singleColumnBreakpoint: intval($la_designPreviewVariables['singleColumnBreakpoint'] ?? $la_designVariables['singleColumnBreakpoint'] ?? 768),
		);

		$this->set([
			'isErrorPage' => !!$li_errorCode,
			'page' => $lo_page,
			'pageRoleEnum' => $ls_pageRoleEnum,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);

		$lo_request = $lo_request->withAttribute('currentPage', $lo_page);

		if ($li_errorCode) {
			$lo_response = $this->getResponse()->withStatus($li_errorCode);
			$this->setResponse($lo_response);
		}

		$this->setRequest($lo_request);
		Router::setRequest($lo_request);

		if ($this->getRequest()->getSession()->read('Auth')) {
			$this->loadFrontendEditor($lo_page);
		}

		$this->loadFrontendPreview($lo_page);

		$this->viewBuilder()
		->setTemplate($lo_page->pageTemplate->fileName)
		->setTemplatePath('Frontend/page');

		// Call the page role specific method
		$ls_methodName = Inflector::variable($lo_page->pageRoleId->name);
		if (method_exists($this, $ls_methodName)) {
			$this->{$ls_methodName}($lo_page);
		}
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
		$ls_languageShortcode = $this->getRequest()->getParam('lang');
		$ls_slug = $this->getRequest()->getParam('slug');
		$la_params = $this->getRequest()->getQueryParams();

		if ($la_params && $ls_slug) {
			$ls_slug = rtrim($ls_slug, '/') . '/';
		}

		if ($ls_languageShortcode) {
			if (!$ls_slug) {
				/*
				 * If the language is given, but no slug, check if the language is the default one
				 * If it is, redirect to the domain without the language.
				 */
				if ($ls_languageShortcode === LocaleMiddleware::getDefaultLanguage()->shortcode) {
					$ls_url = Router::url([
						'_name' => 'FrontendRoot',
						...$this->getRequest()->getQueryParams(),
						'?' => $this->getRequest()->getParam('?'),
					], true);

					throw new RedirectException($ls_url, 301);
				}

				if (!str_ends_with($this->getRequest()->getPath(), '/')) {
					$ls_url = Router::url([
						'_name' => 'FrontendLanguageRoot',
						'lang' => $ls_languageShortcode,
						'?' => $this->getRequest()->getParam('?'),
						...$this->getRequest()->getQueryParams(),
					], true);

					throw new RedirectException($ls_url, 301);
				}

				return;
			}
			else {
				$lo_firstPage = $this->findPage($ls_languageShortcode);
				/*
				 * If there is a slug and the slug is the first page of the language,
				 * redirect to the domain without the slug
				 */
				if ($lo_firstPage->id === $page->id) {
					$la_url = [
						'_name' => 'FrontendLanguageRoot',
						'lang' => $ls_languageShortcode,
						'?' => $this->getRequest()->getParam('?'),
						...$this->getRequest()->getQueryParams(),
					];

					// For the default language, the language is not included in the URL
					if ($ls_languageShortcode === LocaleMiddleware::getDefaultLanguage()->shortcode) {
						$la_url = [
							'?' => $this->getRequest()->getParam('?'),
							...$this->getRequest()->getQueryParams(),
							'_name' => 'FrontendRoot',
						];
					}

					throw new RedirectException(Router::url($la_url, true), 301);
				}
			}
		}

		$ls_testUrl = $page->languageShortcode . '/' . $page->slug . '/';
		$ls_currentUrl = $ls_languageShortcode . '/' . $ls_slug;

		// If the URL does not match the normalized URL, redirect to the normalized URL
		if (
			!str_ends_with($this->getRequest()->getPath(), '/') ||
			(
				$ls_testUrl !== $ls_currentUrl &&
				!in_array($ls_currentUrl, ['', '/', '//'], true)
			)
		) {
			if (!trim($ls_currentUrl, '/')) {
				$ls_realUrl = Router::url([
					'?' => $this->getRequest()->getParam('?'),
					...$this->getRequest()->getQueryParams(),
					'_name' => 'FrontendRoot',
				]);
			}
			else {
				$ls_realUrl = Router::url([
					'lang' => Configure::read('Route.includeLanguageShortcode') ? $page->languageShortcode : null,
					'slug' => $page->slug,
					'?' => $this->getRequest()->getParam('?'),
					...$this->getRequest()->getQueryParams(),
				]);
			}

			throw new RedirectException($ls_realUrl, 301);
		}
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
		$ls_slug = $this->getRequest()->getParam('slug');
		$la_params = $this->getRequest()->getQueryParams();

		if ($la_params && $ls_slug) {
			$ls_slug = rtrim($ls_slug, '/') . '/';
		}

		if (!$ls_slug) {
			if (!str_ends_with($this->getRequest()->getPath(), '/')) {
				$ls_url = Router::url([
					'_name' => 'FrontendRoot',
					...$this->getRequest()->getQueryParams(),
					'?' => $this->getRequest()->getParam('?'),
				], true);

				throw new RedirectException($ls_url, 301);
			}

			return;
		}

		$lo_firstPage = $this->findPage();

		if ($lo_firstPage->id === $page->id) {
			$la_url = [
				'?' => $this->getRequest()->getParam('?'),
				...$this->getRequest()->getQueryParams(),
				'_name' => 'FrontendRoot',
			];

			throw new RedirectException(Router::url($la_url, true), 301);
		}
	}


	/**
	 * Redirects to current page if the URL is found in the slug history
	 *
	 * @param string $url
	 * @return void
	 */
	protected function historyRedirect(string $url): void {
		$lo_historyTable = $this->fetchTable('UrlHistory');

		$ls_url = preg_replace('/[^a-zA-Z0-9\/:\-.]/', '', $url);

		$la_urls = [$ls_url];

		// Check if the URL contains parameters, remove them
		if (str_contains($ls_url, ':')) {
			$la_parts = explode('/', $ls_url);
			$ls_url = '';
			foreach ($la_parts as $ls_part) {
				if (!str_contains($ls_part, ':')) {
					$ls_url .= $ls_part . '/';
				}
			}

			$la_urls[] = rtrim($ls_url, '/');
		}

		$lo_query = $lo_historyTable->find('all')
			->where(['url IN' => $la_urls])
			->contain(['Media', 'Pages'])
			->limit(1);

		/** @noinspection PhpUndefinedMethodInspection */
		$lo_query->orderByAsc($lo_query->newExpr($lo_query->func()->FIND_IN_SET([
			'UrlHistory.url' => 'identifier',
			implode(',', $la_urls),
		])), true);

		$lo_query->orderByDesc('UrlHistory.created_on');

		/** @var \Awyiss\Model\Entity\UrlHistory $lo_record */
		$lo_record = $lo_query->first();

		if (!$lo_record) {
			return;
		}

		if ($lo_record->page) {
			$ls_realUrl = Router::url([
				'lang' => $lo_record->page->languageShortcode,
				'slug' => $lo_record->page->slug,
			]);

			throw new RedirectException($ls_realUrl, $lo_record->status ?? 307);
		}

		if ($lo_record->media) {
			$ls_realUrl = Router::url($lo_record->media->path);

			throw new RedirectException($ls_realUrl, $lo_record->status ?? 307);
		}

		if ($lo_record->target) {
			$ls_realUrl = str_contains($lo_record->target, '//') ? $lo_record->target : Router::url($lo_record->target);

			throw new RedirectException($ls_realUrl, $lo_record->status ?? 307);
		}
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
		if ($page->pageRoleId === $pageRoleEnum::Page) {
			return $page;
		}

		$ls_pageRole = Inflector::pluralize($page->pageRoleId->name);
		$lo_table = $this->fetchTable($ls_pageRole);

		$lo_query = $lo_table->find(!$this->previewMode ? 'published' : 'all')->find('mediaAssignments', useMediaEntity: true)->where(['id' => $page->id])->limit(1);

		// Include the languages in the query, including deleted languages
		$lo_query->contain([
			'DuplicateOf' . $page->pageRoleId->name,
			'Languages',
			'PageTemplates',
		]);

		return $lo_query->first();
	}


	/**
	 * Loads and returns the design preview data
	 *
	 * @return array
	 */
	protected function loadDesignPreview(): array {
		if ($this->getRequest()->getParam('designPreview')) {
			$this->getRequest()->getSession()->write('designPreviewIdentifier', $this->getRequest()->getParam('designPreview'));
		}

		$ls_designPreviewIdentifier = $this->getRequest()->getSession()->read('designPreviewIdentifier');

		if (!$ls_designPreviewIdentifier) {
			return [];
		}

		if ($this->getRequest()->getData('awyiss_design_preview') === 'cancel') {
			$this->getRequest()->getSession()->delete('designPreviewIdentifier');

			throw new RedirectException(Router::url(['_name' => $this->getRequest()->getParam('_name')]));
		}

		$lo_designTable = $this->fetchTable('Designs');
		$lo_design = $lo_designTable->find('all')->where([
			'identifier' => $ls_designPreviewIdentifier,
			'in_use' => false,
		])->first();

		if (!$lo_design) {
			return [];
		}

		$this->set('designPreview', $lo_design);

		$la_webfontData = [];
		foreach ($lo_design->settings as $ls_variable => $lx_value) {
			if (!is_array($lx_value) || !isset($lx_value['font']['name'])) {
				continue;
			}

			$la_webfontData[ $ls_variable ] = [
				'name' => $lx_value['font']['name'],
				'variants' => $lx_value['variants'] ?? [],
			];
		}

		$this->set('designPreviewWebfonts', $la_webfontData);

		return $lo_design->settings ?? [];
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function loadFrontendEditor(Page $page): void {
		if (!Configure::read('Awyiss.System.Frontend.editor')) {
			return;
		}

		$lo_identity = $this->getRequest()->getSession()->read('Auth');

		if (!$lo_identity instanceof IdentityPermissionsInterface) {
			return;
		}

		$lo_permissionCollection = $lo_identity->getPermissionCollection();
		$lb_contentsEditable = $lo_permissionCollection->scopeIsAccessible($page->pageRoleId->name, [], 'contents');
		$lb_formElementsEditable = $lo_permissionCollection->scopeIsAccessible('Forms', [], 'update');
		$lb_menuEntriesEditable = $lo_permissionCollection->scopeIsAccessible('Menus', [], 'read');
		$lb_widgetsEditable = $lo_permissionCollection->scopeIsAccessible('Widgets', [], 'update');

		if (
			!$lb_contentsEditable &&
			!$lb_formElementsEditable &&
			!$lb_menuEntriesEditable &&
			!$lb_widgetsEditable
		) {
			return;
		}

		$this->set([
			'frontendEditorConfig' => [
				'contents' => ['enabled' => $lb_contentsEditable],
				'formElements' => ['enabled' => $lb_formElementsEditable],
				'menuEntries' => ['enabled' => $lb_menuEntriesEditable],
				'widgets' => ['enabled' => $lb_widgetsEditable],
			],
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	protected function loadFrontendPreview(Page $page): void {
		$lo_session = $this->getRequest()->getSession();
		$this->set([
			'frontendPreviewConfig' => [
				'enabled' => $lo_session->read('previewMode.enabled', false),
				'i18n' => [
					'disable' => __d('system', 'preview_mode_disable'),
					'label' => __d('system', 'preview_mode_label'),
					'markInactiveElements' => __d('system', 'preview_mode_mark_inactive_elements'),
				],
				'markInactiveElements' => $lo_session->read('previewMode.markInactiveElements', false),
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
	 * @return void
	 */
	protected function track404(): void {
		static $lb_tracked;

		if (!isset($lb_tracked)) {
			$lb_tracked = true;
		}
		else {
			return;
		}

		$ls_languageShortcode = $this->getRequest()->getParam('lang');
		$ls_slug = $ls_languageShortcode . '/' . $this->getRequest()->getParam('slug');
		$ls_slug = '/' . trim($ls_slug, '/');

		// Don't track resized and preview images and assets
		if (
			$ls_slug === '/apple-touch-icon-precomposed.png' ||
			$ls_slug === '/apple-touch-icon.png' ||
			$ls_slug === '/favicon.png' ||
			$ls_slug === '/backup' ||
			$ls_slug === '/new' ||
			$ls_slug === '/old' ||
			$ls_slug === '/test' ||
			$ls_slug === '/temp' ||
			str_contains($ls_slug, '/_resized') ||
			str_contains($ls_slug, '_preview/') ||
			str_starts_with($ls_slug, '/assets/') ||
			str_starts_with($ls_slug, '/awyiss/assets/') ||
			str_starts_with($ls_slug, '/config/') ||
			str_starts_with($ls_slug, '//google') ||
			str_starts_with($ls_slug, '/wordpress') ||
			str_starts_with($ls_slug, '/wp-admin')
		) {
			return;
		}

		$la_blocklistedUrls = Configure::read('Awyiss.UrlsNotFound.Frontend.blocklistedUrls', []);
		foreach ($la_blocklistedUrls as $ls_blocklistedUrl) {
			$ls_pattern = preg_quote(trim($ls_blocklistedUrl, '*/'), '/');

			if (str_starts_with($ls_blocklistedUrl, '*')) {
				$ls_pattern = '.*' . $ls_pattern;
			}
			if (str_ends_with($ls_blocklistedUrl, '*')) {
				$ls_pattern .= '.*';
			}

			if (preg_match('/' . $ls_pattern . '/', trim($ls_slug, '/'))) {
				return;
			}
		}

		/**
		 * Check if an entry for the current slug already exists within the last 5 minutes
		 * If it does, don't track it again
		 *
		 * @var \Awyiss\Model\Table\UrlsNotFoundTable $lo_urlsNotFoundTable
		 */
		$lo_urlsNotFoundTable = $this->fetchTable('UrlsNotFound');
		if ($lo_urlsNotFoundTable->exists(['url' => $ls_slug, 'created_on >' => new DateTime('-5 minutes')])) {
			return;
		}

		$lb_isRobot = $this->isRobot();

		$lo_notFound = $lo_urlsNotFoundTable->newDefaultEntity([
			'url' => $ls_slug,
			'referrer' => $this->getRequest()->referer(),
			'isRobot' => $lb_isRobot,
		]);

		$lo_urlsNotFoundTable->save($lo_notFound, ['allowFrontendSave' => true]);
	}


	/**
	 * @return bool
	 */
	protected function isRobot(): bool {
		$ls_userAgent = $this->getRequest()->getHeaderLine('User-Agent');
		$lo_crawlerDetect = new CrawlerDetect();

		return $lo_crawlerDetect->isCrawler($ls_userAgent);
	}


	/**
	 * Check if the parents of the page are published
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return bool
	 */
	protected function parentsArePublished(Page $page): bool {
		if (!$page->parentId) {
			return true;
		}

		$lb_checkAncestorPagesPublicationStatus = Configure::read('Awyiss.System.Frontend.publicationData.checkAncestorPagesPublicationStatus', true);
		if (!$lb_checkAncestorPagesPublicationStatus) {
			return true;
		}

		$la_parts = explode('/', $page->slug);
		// Remove the last part
		array_pop($la_parts);

		/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
		$lo_pagesTable = $this->fetchTable('Pages');

		$lo_query = $lo_pagesTable->find(!$this->previewMode ? 'published' : 'all', skipPageRoleCheck: true);

		$la_slugs = [];
		$ls_lastPart = '';
		// Create an array of slugs. Each item is the previous slug + the current slug
		foreach ($la_parts as $ls_part) {
			$la_slugs[] = $ls_lastPart . $ls_part;
			$ls_lastPart .= $ls_part . '/';
		}

		$lo_query->where([
			'slug IN' => $la_slugs,
			'language_shortcode' => $page->languageShortcode,
		]);

		return $lo_query->count() === count($la_slugs);
	}
}
