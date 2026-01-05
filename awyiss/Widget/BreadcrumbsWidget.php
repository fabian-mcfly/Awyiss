<?php declare(strict_types=1);


namespace Awyiss\Widget;


use Awyiss\Controller\Frontend\FrontendController;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Routing\Router;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Cake\Datasource\FactoryLocator;
use Exception;


/**
 * Class BreadcrumbsWidget
 * Show a list of breadcrumbs, optionally including the homepage and/or the current page
 */
class BreadcrumbsWidget extends AbstractWidget {
	/**
	 * Additional breadcrumb items for routes that don't have page entries
	 * Format: [['title' => 'Title', 'url' => '/url'], ...]
	 *
	 * @var array<int, array{title: string, url: string}>
	 */
	protected static array $additionalCrumbs = [];


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		// Translate using __d() if needed
		return 'Breadcrumbs';
	}


	/**
	 * Register an additional breadcrumb item for controller routes without page entries
	 *
	 * @param string $title The title to display in the breadcrumb
	 * @param string $url The URL to link to
	 * @return void
	 */
	public static function registerCrumb(string $title, string $url): void {
		static::$additionalCrumbs[] = [
			'title' => $title,
			'url' => $url,
		];
	}


	/**
	 * Clear all registered additional crumbs (useful for testing)
	 *
	 * @return void
	 */
	public static function clearCrumbs(): void {
		static::$additionalCrumbs = [];
	}


	/**
	 * Get all registered additional crumbs
	 *
	 * @return array<int, array{title: string, url: string}>
	 */
	public static function getAdditionalCrumbs(): array {
		return static::$additionalCrumbs;
	}


	/**
	 * @inheritDoc
	 */
	public static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
		return [
			// Checkbox if the homepage should be included in the breadcrumbs (default: true)
			'settings.includeHomepage' => [
				'checked' => $settings['includeHomepage'] ?? true,
				'columnSpan' => 6,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/widgets', 'include_homepage'),
				'type' => 'checkbox',
			],

			// Checkbox if the current page should be included in the breadcrumbs (default: true)
			'settings.includeCurrentPage' => [
				'checked' => $settings['includeCurrentPage'] ?? true,
				'columnSpan' => 6,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/widgets', 'include_current_page'),
				'type' => 'checkbox',
			],

			// Checkbox if the breadcrumb should be shown on the homepage (default: false)
			'settings.showOnHomepage' => [
				'checked' => $settings['showOnHomepage'] ?? false,
				'columnSpan' => 6,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/widgets', 'show_on_homepage'),
				'type' => 'checkbox',
			],

			// Checkbox if inaccessible pages should be included in the breadcrumbs (default: false)
			'settings.includeInaccessiblePages' => [
				'checked' => $settings['includeInaccessiblePages'] ?? false,
				'columnSpan' => 6,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/widgets', 'include_inaccessible_pages'),
				'type' => 'checkbox',
			],

			// A dropdown to select the homepage (for the current language)
			'settings.homepageId' => [
				'columnSpan' => 12,
				'empty' => true,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/widgets', 'homepage_id'),
				'options' => static::getHomepageOptions(),
				'type' => 'select',
				'value' => $settings['homepageId'] ?? null,
			],
		];
	}


	/**
	 * @inheritDoc
	 */
	public static function render(array $settings, FrontendView $view, ?MediaRenderOptions $mediaRenderOptions = null, ?Entity $entity = null, ?Language $frontendLanguage = null): string {
		$includeHomepage = $settings['includeHomepage'] ?? true;
		$includeCurrentPage = $settings['includeCurrentPage'] ?? true;
		$showOnHomepage = $settings['showOnHomepage'] ?? false;
		$homepageId = $settings['homepageId'] ?? null;
		$includeInaccessiblePages = $settings['includeInaccessiblePages'] ?? false;

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = FactoryLocator::get('Table')->get('Pages');

		try {
			if ($homepageId) {
				$homepage = $pagesTable->get($homepageId);
			}
			else {
				$homepage = static::findHomepage($pagesTable, $frontendLanguage?->shortcode);
			}
			$homepageId = $homepage?->id;
		}
		catch (Exception) {
			$homepage = null;
			$includeHomepage = false;
		}

		// Get the current path
		$path = trim($settings['path'] ?? Router::getRequest()?->getPath() ?? '', '/');
		$pathParts = explode('/', $path);
		array_shift($pathParts);

		// Get all pages in the current path
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query = $pagesTable->find('forCurrentLanguage', skipPageRoleCheck: true);

		if (!$includeInaccessiblePages) {
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

		$currentPath = '';
		$paths = [];
		foreach ($pathParts as $pathPart) {
			$currentPath .= ($currentPath ? '/' : '') . $pathPart;
			$paths[] = $currentPath;
		}

		if (!$includeCurrentPage) {
			array_pop($paths);
		}

		if ($paths) {
			$query->where(['Pages.slug IN' => $paths])
				/**
				 * Order by the length of the slug since
				 * all slugs are nested, and we want to
				 * show the shortest slug first
				 * (e.g. /about/team should come before /about/team/john)
				 */
				->orderBy(['LENGTH(Pages.slug)' => 'ASC']);
			$pages = $query->all()->indexBy('id')->toArray();
		}
		else {
			$pages = [];
		}

		if ($includeHomepage) {
			$pages = [$homepageId => $homepage] + $pages;
		}

		return $view->element('widget/breadcrumbs', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'includeHomepage' => $includeHomepage,
			'includeCurrentPage' => $includeCurrentPage,
			'showOnHomepage' => $showOnHomepage,
			'homepageId' => $homepageId,
			'homepage' => $homepage,
			'pages' => $pages,
			'additionalCrumbs' => static::$additionalCrumbs,
			'settings' => $settings,
		]);
	}


	/**
	 * @return array
	 */
	protected static function getHomepageOptions(): array {
		$options = [];

		/** @var \Awyiss\Model\Table\PagesTable $pageTable */
		$pageTable = FactoryLocator::get('Table')->get('Pages');

		/**
		 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
		 * @uses \Awyiss\Model\Table::findActive()
		 */
		$query = $pageTable->find('active')->find('forCurrentLanguage');
		$pages = $pageTable->listNested($query);

		/** @var \Awyiss\Model\Entity\Page $page */
		foreach ($pages ?? [] as $page) {
			/** @noinspection PhpUndefinedFieldInspection */
			$options[ $page->id ] = str_repeat('- ', $page->level) . ' ' . $page->title;
		}

		return $options;
	}


	/**
	 * @param \Awyiss\Model\Table\PagesTable $pagesTable
	 * @param string|null $languageShortcode
	 * @return \Awyiss\Model\Entity\Page|null
	 * @throws \Exception
	 */
	protected static function findHomepage(PagesTable $pagesTable, ?string $languageShortcode = null): ?Page {
		$firstPage = FrontendController::getFirstPage();

		if ($firstPage) {
			return $firstPage;
		}

		$query = $pagesTable->find(!static::isPreview() ? 'published' : 'all', skipPageRoleCheck: true);

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

		if (static::$isPreview) {
			// Order all by deleted, system_order
			$query->orderBy([
				'Pages.deleted' => 'ASC',
			]);
		}
		else {
			// Order all by deleted, parents_active, active, system_order
			$query->orderBy([
				'Pages.deleted' => 'ASC',
				'Pages.parents_active' => 'DESC',
				'Pages.active' => 'DESC',
			]);
		}

		$query->orderBy([
			'PageRoles.active' => 'DESC',
			'PageRoles.system_order' => 'ASC',
		]);

		$query->where(['language_shortcode' => $languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode]);

		// Order by parent_id first
		$query->orderBy(['Pages.parent_id' => 'ASC']);

		$query->limit(1);

		return $query->first();
	}
}
