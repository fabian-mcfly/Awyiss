<?php declare(strict_types=1);


namespace Awyiss\Module;


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
 * Class BreadcrumbsModule
 * Show a list of breadcrumbs, optionally including the homepage and/or the current page
 */
class BreadcrumbsModule extends AbstractModule {
	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		// Translate using __d() if needed
		return 'Breadcrumbs';
	}


	/**
	 * @inheritDoc
	 */
	public static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
		return [
			// Checkbox if the homepage should be included in the breadcrumbs (default: true)
			'settings.includeHomepage' => [
				'checked' => $settings['includeHomepage'] ?? true,
				'columnSpan' => 4,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/module', 'include_homepage'),
				'type' => 'checkbox',
			],

			// Checkbox if the current page should be included in the breadcrumbs (default: true)
			'settings.includeCurrentPage' => [
				'checked' => $settings['includeCurrentPage'] ?? true,
				'columnSpan' => 4,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/module', 'include_current_page'),
				'type' => 'checkbox',
			],

			// Checkbox if the breadcrumb should be shown on the homepage (default: false)
			'settings.showOnHomepage' => [
				'checked' => $settings['showOnHomepage'] ?? false,
				'columnSpan' => 4,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/module', 'show_on_homepage'),
				'type' => 'checkbox',
			],

			// A dropdown to select the homepage (for the current language)
			'settings.homepageId' => [
				'columnSpan' => 12,
				'empty' => true,
				'label' => __df('Frontend/breadcrumbs', 'Frontend/module', 'homepage_id'),
				'options' => static::getHomepageOptions(),
				'type' => 'select',
				'value' => $settings['homepageId'] ?? null,
			],
		];
	}


	/**
	 * @inheritDoc
	 */
	public static function render(array $settings, FrontendView $view, ?MediaRenderOptions $mediaRenderOptions, ?Entity $entity = null, ?Language $frontendLanguage = null): string {
		$includeHomepage = $settings['includeHomepage'] ?? true;
		$includeCurrentPage = $settings['includeCurrentPage'] ?? true;
		$showOnHomepage = $settings['showOnHomepage'] ?? false;
		$homepageId = $settings['homepageId'] ?? null;

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

		return $view->element('module/breadcrumbs', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'includeHomepage' => $includeHomepage,
			'includeCurrentPage' => $includeCurrentPage,
			'showOnHomepage' => $showOnHomepage,
			'homepageId' => $homepageId,
			'homepage' => $homepage,
			'pages' => $pages,
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
		$query = $pagesTable->find(!static::isPreview() ? 'published' : 'all', skipPageRoleCheck: true);

		// Include the languages in the query, including deleted languages
		$query->contain([
			'DuplicateOfPage',
			'Languages' => [
				'finder' => $languageShortcode ? 'withDeleted' : 'all',
			],
			'PageRoles',
			'PageTemplates',
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
