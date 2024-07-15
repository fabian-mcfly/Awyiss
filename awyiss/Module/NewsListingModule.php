<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Core\App;
use Awyiss\Datasource\Paging\NumericPaginator;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Query\SelectQuery;


/**
 * Class NewsListingModule
 *
 * Show a list of news, either paginated or limited to a certain number of items
 */
class NewsListingModule implements ModuleInterface {
	/**
	 * The identifier of the module
	 *
	 * @var string
	 */
	protected static string $identifier = 'newsListing';


	/**
	 * @inheritDoc
	 */
	public static function getIdentifier(): string {
		return static::$identifier;
	}


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		// Translate using __d() if needed
		return 'News-Listing';
	}


	/**
	 * @inheritDoc
	 */
	public static function renderForm(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): string {
		$ls_return = '';

		/**
		 * Get the form helper
		 * @var \Awyiss\View\Helper\FormHelper $lo_formHelper
		 */
		$lo_formHelper = $view->helpers()->get('Form');

		// Todo: show a multiselect / custom multiselect for the categories, if PageRoleEnumInterface::Newscategory exists

		$ls_return .= $lo_formHelper->control('settings.titleTag', [
			'columnSpan' => 6,
			'empty' => false,
			'label' => __d('module', 'title_tag'),
			'options' => [
				'h1' => 'H1',
				'h2' => 'H2',
				'h3' => 'H3',
				'h4' => 'H4',
				'h5' => 'H5',
				'h6' => 'H6',
			],
			'type' => 'select',
			'value' => $settings['titleTag'] ?? 'h3',
		]);

		$ls_return .= $lo_formHelper->control('settings.paginate', [
			'label' => __d('module', 'paginate'),
			'type' => 'checkbox',
			'data-form-updater' => true,
		]);

		if (!empty($settings['paginate'])) {
			$ls_return .= $lo_formHelper->control('settings.itemsPerPage', [
				'columnSpan' => 6,
				'max' => 100,
				'min' => 1,
				'label' => __d('module', 'items_per_page'),
				'placeholder' => 9,
				'required' => true,
				'type' => 'number',
				'value' => $settings['itemsPerPage'] ?? null,
			]);
		}
		else {
			$ls_return .= $lo_formHelper->control('settings.items', [
				'columnSpan' => 6,
				'label' => __d('module', 'number_of_items'),
				'max' => 20,
				'min' => 1,
				'placeholder' => 3,
				'required' => true,
				'type' => 'number',
				'value' => $settings['items'] ?? null,
			]);

			$ls_return .= $lo_formHelper->control('settings.offset', [
				'columnSpan' => 6,
				'label' => __d('module', 'offset'),
				'max' => 20,
				'min' => 1,
				'placeholder' => 0,
				'type' => 'number',
				'value' => $settings['offset'] ?? null,
			]);
		}

		return $ls_return;
	}


	/**
	 * @inheritDoc
	 */
	public static function render(array $settings, FrontendView $view, ?MediaRenderOptions $mediaRenderOptions, ?Entity $entity = null, ?Language $frontendLanguage = null): string {
		$lb_paginate = isset($settings['paginate']) && $settings['paginate'] === true;
		$li_items = $settings['items'] ?? 3;
		$li_itemsPerPage = $settings['itemsPerPage'] ?? 9;
		$li_offset = $settings['offset'] ?? 0;

		$lo_tableLocator = FactoryLocator::get('Table');

		try {
			/** @var \Awyiss\Model\Table $lo_newsTable */
			$lo_newsTable = $lo_tableLocator->get('News');
		}
		catch (MissingTableClassException) {
			return '';
		}

		$lo_query = $lo_newsTable->find('active')
		->find('published')
		->find('forCurrentLanguage')
		->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		$lo_query->orderBy(['date' => 'DESC']);

		if (isset($lo_newsTable->getAttributes()['inTeaser'])) {
			$lo_query->where(['in_teaser' => true]);
		}

		if (isset($settings['categories'])) {
			if (is_array($settings['categories'])) {
				$lo_query->where(['parent_id IN' => $settings['categories']]);
			}
		}
		elseif ($entity instanceof Page) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

			if ($entity->pageRoleId === $ls_pageRoleEnum::Newscategory) {
				$lo_query->where(['parent_id' => $entity->id]);
			}
		}

		if ($lb_paginate) {
			$lo_news = static::paginate($lo_query, $li_itemsPerPage);
		}
		else {
			$lo_query->limit((int)$li_items)->offset((int)$li_offset);
			$lo_news = $lo_query->all();
		}

		foreach ($lo_news as $lo_newsItem) {
			ResizedImageManager::addMediaItemsFromEntity($lo_newsItem);
		}

		return $view->element('module/news_listing', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'news' => $lo_news,
			'paginate' => $lb_paginate,
			'items' => $li_items,
			'itemsPerPage' => $li_itemsPerPage,
			'offset' => $li_offset,
			'settings' => $settings,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param int $itemsPerPage
	 * @return \Cake\Datasource\Paging\PaginatedInterface
	 */
	protected static function paginate(SelectQuery $query, int $itemsPerPage): PaginatedInterface {
		$lo_paginator = new NumericPaginator();
		$la_params = ['limit' => $itemsPerPage] + Router::getRequest()->getQueryParams();

		$la_settings = [
			'sortableFields' => [],
		];

		$lo_news = $lo_paginator->paginate($query, $la_params, $la_settings);

		return $lo_news;
	}
}
