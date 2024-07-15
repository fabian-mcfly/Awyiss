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
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Frontend Controller that handles all page requests
 *
 * For every found, active and published page, the controller will call
 * a method with the name of the page role in snake_case.
 *
 * @see \Awyiss\Controller\Frontend\FrontendController::news()
 */
class FrontendController extends AppController {
	use LocatorAwareTrait;


	/**
	 * @throws \Exception
	 */
	public function initialize(): void {
		AppController::initialize();

		//Load event listeners for the current controller in the "backend"-folder
		EventListenersProvider::loadListener($this->getName(), Awyiss::REALM_FRONTEND);

		$this->viewBuilder()->setClassName('Frontend');
	}


	/**
	 * This method is called when a page with the page role "page" is requested
	 * and after the page has been found and checked for its status.
	 *
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return void
	 */
	public function news(Page $page): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_newsTable */
		$lo_newsTable = $this->fetchTable('News');

		$lo_query = $lo_newsTable->find('active')->find('published', skipPageRoleCheck: true);
		$lo_query->where(['id' => $page->parentId]);
		$lo_newsCategory = $lo_query->first();

		$la_where = [
			'parent_id' => $page->parentId,
			'system_order <' => $page->systemOrder,
		];
		$lo_newer = $lo_newsTable->find('active')->find('published')->where($la_where)->orderBy(['system_order' => 'DESC'])->limit(1)->first();

		$la_where = [
			'parent_id' => $page->parentId,
			'system_order >' => $page->systemOrder,
		];
		$lo_older = $lo_newsTable->find('active')->find('published')->where($la_where)->orderBy(['system_order' => 'DESC'])->limit(1)->first();


		$la_designVariables = $this->getRequest()->getAttribute('design')->getDesignVariables();

		$lo_mediaRenderOptions = new MediaRenderOptions(
			baseWidth: intval($la_designVariables['pageWidth'] ?? 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			singleColumnBreakpoint: intval($la_designVariables['singleColumnBreakpoint'] ?? 768),
		);

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
		$ls_slug = $this->request->getParam('slug');
		$ls_slug = $ls_slug ? rtrim($ls_slug, '/') : null;

		// Find the first page for the current language
		$lo_page = $this->findPage(
			$this->request->getParam('lang'),
			$ls_slug
		);

		$this->handlePage($lo_page);
	}


	/**
	 * Action for incomplete URLs.
	 * This action is called when the URL does not contain a language or a slug.
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function incompleteUrl(): void {
		$ls_language = $this->request->getParam('lang');

		$ls_slug = $this->request->getParam('slug');
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

		$lo_query = $lo_pagesTable->find('published', softDelete: ['includeDeleted' => !!$slug], skipPageRoleCheck: true);

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

		// Order all by deleted, active, parents_active, system_order
		$lo_query->orderBy([
			'Pages.deleted' => 'ASC',
			'Pages.parents_active' => 'DESC',
			'Pages.active' => 'DESC',
		]);

		if ($slug) {
			$lo_query->where(['slug' => $slug]);

			if ($languageShortcode) {
				$lo_query->andWhere(['language_shortcode' => $languageShortcode]);
			}
			else {
				$lo_query->orderBy([
					'Languages.deleted' => 'ASC',
					'Languages.active' => 'DESC',
				]);
			}
		}
		else {
			$ls_languageShortcode = $languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode;

			$lo_query->where(['language_shortcode' => $ls_languageShortcode]);

			// Order by parent_id first
			$lo_query->orderBy(['Pages.parent_id' => 'ASC']);
		}

		$lo_query->limit(1);

		return $lo_query->first();
	}


	/**
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return void
	 * @throws \Exception
	 */
	protected function handlePage(?Page $page): void {
		$lb_isErrorPage = false;
		$lo_page = $page;

		if (!$lo_page) {
			// Try to find an entry in the slug history
			$this->historyRedirect(trim($this->request->getPath(), '/'));
		}

		/*
		 * If the page or the language of the page is deleted,
		 * It must be active, not deleted and in the same language as the current.
		 */
		if ($lo_page && ($lo_page->deleted || $lo_page->language->deleted)) {
			// Try to find an entry in the slug history
			$this->historyRedirect($lo_page->languageShortcode . '/' . $lo_page->slug);

			// Find the 410 page for the current language
			$lo_page = $this->findPage($lo_page->languageShortcode, '410', ['active' => true, 'deleted' => false]);
			$lb_isErrorPage = true;

			if (!$lo_page) {
				throw new NotFoundException();
			}
		}
		/*
		 * If no page was found, check if a 404 page exists.
		 * It must be active, not deleted and in the same language as the current.
		 */
		if (!$lo_page || !$lo_page->active || !$lo_page->parentsActive || !$lo_page->language->active) {
			// Find the 404 page for the current language
			$lo_page = $this->findPage(LocaleMiddleware::getLanguage()->shortcode, '404', ['active' => true, 'deleted' => false]);
			$lb_isErrorPage = true;

			if (!$lo_page) {
				throw new NotFoundException();
			}
		}

		if (!$lb_isErrorPage) {
			// Redirect to a normalized URL if the current URL does not match the normalized URL
			$this->redirectIfNotNormalized($lo_page);
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		// Make sure the page is of the correct entity class (page role specific)
		$lo_page = $this->ensureCorrectEntityType($lo_page, $ls_pageRoleEnum);

		// If there is no page, it's most likely not published
		if (!$lo_page) {
			// Find the 404 page for the current language
			$lo_page = $this->findPage(LocaleMiddleware::getLanguage()->shortcode, '404', ['active' => true, 'deleted' => false]);

			if (!$lo_page) {
				throw new NotFoundException();
			}
		}

		ResizedImageManager::addMediaItemsFromEntity($lo_page);

		$la_designVariables = $this->getRequest()->getAttribute('design')->getDesignVariables();

		$lo_mediaRenderOptions = new MediaRenderOptions(
			baseWidth: intval($la_designVariables['pageWidth'] ?? 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			singleColumnBreakpoint: intval($la_designVariables['singleColumnBreakpoint'] ?? 768),
		);

		$this->set([
			'page' => $lo_page,
			'pageRoleEnum' => $ls_pageRoleEnum,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);

		if ($this->request->getSession()->read('Auth')) {
			$this->loadFrontendEditor($lo_page);
		}

		$this->viewBuilder()
		->setTemplate($lo_page->pageTemplate->fileName)
		->setTemplatePath('Frontend/page');

		// Call the page role specific method
		$ls_methodName = Inflector::underscore($lo_page->pageRoleId->name);
		if (method_exists($this, $ls_methodName)) {
			$this->{$ls_methodName}($lo_page);
		}
	}


	/**
	 * Redirects to a normalized URL if the current URL does not match the normalized URL
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
		$ls_languageShortcode = $this->request->getParam('lang');
		$ls_slug = $this->request->getParam('slug');
		$la_params = $this->request->getQueryParams();
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
					$ls_url = Router::url(['_name' => 'FrontendRoot', ...$this->request->getQueryParams(), '?' => $this->request->getParam('?'),], true);

					throw new RedirectException($ls_url, 301);
				}

				if (!str_ends_with($this->request->getPath(), '/')) {
					$ls_url = Router::url([
						'_name' => 'FrontendLanguageRoot',
						'lang' => $ls_languageShortcode,
						'?' => $this->request->getParam('?'),
						...$this->request->getQueryParams(),
					], true);

					throw new RedirectException($ls_url, 301);
				}
				else {
					return;
				}
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
						'?' => $this->request->getParam('?'),
						...$this->request->getQueryParams(),
					];

					// For the default language, the language is not included in the URL
					if ($ls_languageShortcode === LocaleMiddleware::getDefaultLanguage()->shortcode) {
						$la_url = [
							'?' => $this->request->getParam('?'),
							...$this->request->getQueryParams(),
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
			!str_ends_with($this->request->getPath(), '/') ||
			(
				$ls_testUrl !== $ls_currentUrl &&
				$ls_currentUrl !== '/' &&
				$ls_currentUrl !== '//'
			)
		) {
			if (!trim($ls_currentUrl, '/')) {
				$ls_realUrl = Router::url([
					'?' => $this->request->getParam('?'),
					...$this->request->getQueryParams(),
					'_name' => 'FrontendRoot',
				]);
			}
			else {
				$ls_realUrl = Router::url([
					'lang' => $page->languageShortcode,
					'slug' => $page->slug,
					'?' => $this->request->getParam('?'),
					...$this->request->getQueryParams(),
				]);
			}

			throw new RedirectException($ls_realUrl, 301);
		}
	}


	/**
	 * Redirects to current page if the URL is found in the slug history
	 *
	 * @param string $url
	 * @return void
	 */
	protected function historyRedirect(string $url): void {
		$lo_historyTable = $this->fetchTable('SlugHistory');

		$ls_url = preg_replace('/[^a-zA-Z0-9\/:\-]/', '', $url);

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
			->where(['slug IN' => $la_urls])
			->contain(['Pages'])
			->limit(1);

		/** @noinspection PhpUndefinedMethodInspection */
		$lo_query->orderByAsc($lo_query->newExpr($lo_query->func()->FIND_IN_SET([
			'SlugHistory.slug' => 'identifier',
			implode(',', $la_urls),
		])), true);

		$lo_record = $lo_query->first();

		if ($lo_record?->page) {
			$ls_realUrl = Router::url([
				'lang' => $lo_record->page->languageShortcode,
				'slug' => $lo_record->page->slug,
			]);

			throw new RedirectException($ls_realUrl, 301);
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

		$lo_query = $lo_table->find('published')->find('mediaAssignments', useMediaEntity: true)->where(['id' => $page->id])->limit(1);

		// Include the languages in the query, including deleted languages
		$lo_query->contain([
			'DuplicateOf' . $page->pageRoleId->name,
			'Languages',
			'PageTemplates',
		]);

		return $lo_query->first();
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

		$lo_identity = $this->request->getSession()->read('Auth');

		if (!$lo_identity instanceof IdentityPermissionsInterface) {
			return;
		}

		$lb_contentsEditable = $lo_identity->permissionCollection->scopeIsAccessible($page->pageRoleId->name, [], 'contents');
		$lb_widgetsEditable = $lo_identity->permissionCollection->scopeIsAccessible('Widgets', [], 'update');
		$lb_menuEntriesEditable = $lo_identity->permissionCollection->scopeIsAccessible('MenuEntries', [], 'update');

		if (!$lb_contentsEditable && !$lb_widgetsEditable && !$lb_menuEntriesEditable) {
			return;
		}

		$this->set([
			'frontendEditorConfig' => [
				'contents' => ['enabled' => $lb_contentsEditable],
				'widgets' => ['enabled' => $lb_widgetsEditable],
				'menuEntries' => ['enabled' => $lb_menuEntriesEditable],
			],
		]);
	}
}
