<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Cake\Datasource\FactoryLocator;


/**
 * Class BreadcrumbsModule
 * Show a list of breadcrumbs, optionally including the homepage and/or the current page
 */
class BreadcrumbsModule extends AbstractModule {
	/**
	 * The identifier of the module
	 *
	 * @var string
	 */
	protected static string $identifier = 'breadcrumbs';


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
		$lb_includeHomepage = $settings['includeHomepage'] ?? true;
		$lb_includeCurrentPage = $settings['includeCurrentPage'] ?? true;
		$lb_showOnHomepage = $settings['showOnHomepage'] ?? false;
		$li_homepageId = $settings['homepageId'] ?? null;

		/** @var \Awyiss\Model\Table\PagesTable $lo_pageTable */
		$lo_pagesTable = FactoryLocator::get('Table')->get('Pages');

		if ($li_homepageId) {
			$lo_homepage = $lo_pagesTable->get($li_homepageId);
		}
		else {
			// Get the homepage entity (first page for the current language)
			/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
			$lo_query = $lo_pagesTable->find('forCurrentLanguage', skipPageRoleCheck: true)->orderBy([
				'Pages.parent_id' => 'ASC',
			]);

			if (!static::isPreview()) {
				/**
				 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
				 */
				$lo_query->find('published')->where([
					'Pages.active' => true,
					'Pages.parents_active' => true,
				]);
			}

			$lo_homepage = $lo_query->first();
		}

		$li_homepageId = $lo_homepage->id;

		// Get the current path
		$ls_path = trim(Router::getRequest()?->getPath() ?? '', '/');
		$la_pathParts = explode('/', $ls_path);
		array_shift($la_pathParts);

		// Get all pages in the current path
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$lo_query = $lo_pagesTable->find('forCurrentLanguage', skipPageRoleCheck: true);

		$ls_currentPath = '';
		$la_paths = [];
		foreach ($la_pathParts as $ls_pathPart) {
			$ls_currentPath .= ($ls_currentPath ? '/' : '') . $ls_pathPart;
			$la_paths[] = $ls_currentPath;
		}

		if (!$lb_includeCurrentPage) {
			array_pop($la_paths);
		}

		if ($la_paths) {
			$lo_query->where(['Pages.slug IN' => $la_paths])
				/**
				 * Order by the length of the slug since
				 * all slugs are nested, and we want to
				 * show the shortest slug first
				 * (e.g. /about/team should come before /about/team/john)
				 */
				->orderBy(['LENGTH(Pages.slug)' => 'ASC']);
			$la_pages = $lo_query->all()->indexBy('id')->toArray();
		}
		else {
			$la_pages = [];
		}

		if ($lb_includeHomepage) {
			$la_pages = [$li_homepageId => $lo_homepage] + $la_pages;
		}

		return $view->element('module/breadcrumbs', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'includeHomepage' => $lb_includeHomepage,
			'includeCurrentPage' => $lb_includeCurrentPage,
			'showOnHomepage' => $lb_showOnHomepage,
			'homepageId' => $li_homepageId,
			'homepage' => $lo_homepage,
			'pages' => $la_pages,
			'settings' => $settings,
		]);
	}


	/**
	 * @return array
	 */
	protected static function getHomepageOptions(): array {
		$la_options = [];

		/** @var \Awyiss\Model\Table\PagesTable $lo_pageTable */
		$lo_pageTable = FactoryLocator::get('Table')->get('Pages');

		/**
		 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
		 * @uses \Awyiss\Model\Table::findActive()
		 */
		$lo_query = $lo_pageTable->find('active')->find('forCurrentLanguage');
		$lo_pages = $lo_pageTable->listNested($lo_query);

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages ?? [] as $lo_page) {
			$la_options[ $lo_page->id ] = str_repeat('- ', $lo_page->level) . ' ' . $lo_page->title;
		}

		return $la_options;
	}
}
