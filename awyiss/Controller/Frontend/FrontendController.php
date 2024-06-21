<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Awyiss;
use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Frontend Controller that handles all page requests
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
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 */
	protected function findPage(?string $languageShortcode = null, ?string $slug = null): ?Page {
		/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
		$lo_pagesTable = $this->fetchTable('Pages');

		$lo_query = $lo_pagesTable->find('all', softDelete: ['includeDeleted' => !!$slug], skipPageRoleCheck: true);

		// Include the languages in the query, including deleted languages
		$lo_query->contain([
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
		// If no page was found, return a 404 error
		if (!$page) {
			$this->response = $this->response->withStatus(404);
			$this->render('error404');
			return;
		}

		// If the page or the language of the page is deleted, return a 410 error
		if ($page->deleted || $page->language->deleted) {
			$this->response = $this->response->withStatus(410);
			$this->render('error410');
			return;
		}

		// If the page, the page's parents or the language of the page is not active, return a 404 error
		if (!$page->active || !$page->parentsActive || !$page->language->active) {
			$this->response = $this->response->withStatus(404);
			$this->render('error404');
			return;
		}

		// Redirect to a normalized URL if the current URL does not match the normalized URL
		$this->redirectIfNotNormalized($page);

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$this->set([
			'page' => $page,
			'pageRoleEnum' => $ls_pageRoleEnum,
			'webfont' => $this->getWebfontData(),
		]);

		$this->viewBuilder()
		->setTemplate($page->pageTemplate->fileName)
		->setTemplatePath('Frontend/page');
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
	 * Get the data for the webfont from the design settings
	 *
	 * @return array
	 */
	protected function getWebfontData(): array {
		/** @var \Awyiss\Middleware\DesignMiddleware $lo_designMiddleware */
		$lo_designMiddleware = $this->getRequest()->getAttribute('design');
		$la_variables = $lo_designMiddleware->getDesignVariables();

		$la_webfontData = [];

		if (!$la_variables) {
			return $la_webfontData;
		}

		foreach ($la_variables as $ls_variable => $lx_value) {
			if (!is_array($lx_value) || !isset($lx_value['font']['name'])) {
				continue;
			}

			$la_webfontData[ $ls_variable ] = [
				'name' => $lx_value['font']['name'],
				'variants' => $lx_value['variants'] ?? [],
			];
		}

		return $la_webfontData;
	}
}
