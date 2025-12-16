<?php declare(strict_types=1);


namespace Awyiss\Widget;


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
 * Class NewsListingWidget
 *
 * Show a list of news, either paginated or limited to a certain number of items
 */
class NewsListingWidget extends AbstractWidget {
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
	public static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
		$formFields = [
			'settings.titleTag' => [
				'columnSpan' => 6,
				'empty' => false,
				'label' => __df('Frontend/news', 'Frontend/widgets', 'title_tag'),
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
			],

			'settings.paginate' => [
				'label' => __df('Frontend/news', 'Frontend/widgets', 'paginate'),
				'type' => 'checkbox',
				'data-form-updater' => true,
			],
		];

		if (!empty($settings['paginate'])) {
			$formFields += [
				'settings.itemsPerPage' => [
					'columnSpan' => 6,
					'max' => 100,
					'min' => 1,
					'label' => __df('Frontend/news', 'Frontend/widgets', 'items_per_page'),
					'placeholder' => 9,
					'required' => true,
					'type' => 'number',
					'value' => $settings['itemsPerPage'] ?? null,
				],
			];
		}
		else {
			$formFields += [
				'settings.items' => [
					'columnSpan' => 6,
					'label' => __df('Frontend/news', 'Frontend/widgets', 'number_of_items'),
					'max' => 20,
					'min' => 1,
					'placeholder' => 3,
					'required' => true,
					'type' => 'number',
					'value' => $settings['items'] ?? null,
				],

				'settings.offset' => [
					'columnSpan' => 6,
					'label' => __df('Frontend/news', 'Frontend/widgets', 'offset'),
					'max' => 20,
					'min' => 1,
					'placeholder' => 0,
					'type' => 'number',
					'value' => $settings['offset'] ?? null,
				],
			];
		}

		$categoriesField = static::getCategoriesField(
			$frontendLanguage,
			$userLanguage,
			$settings
		);

		return $categoriesField + $formFields;
	}


	/**
	 * @inheritDoc
	 */
	public static function isAvailable(): bool {
		/** @var \Awyiss\Model\Enum\PageRoleEnumInterface $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');

		return !!$pageRoleEnum::tryFromName('news');
	}


	/**
	 * @inheritDoc
	 */
	public static function render(array $settings, FrontendView $view, ?MediaRenderOptions $mediaRenderOptions = null, ?Entity $entity = null, ?Language $frontendLanguage = null): string {
		$paginate = isset($settings['paginate']) && $settings['paginate'] === true;
		$itemsLimit = $settings['items'] ?? 3;
		$itemsPerPage = $settings['itemsPerPage'] ?? 9;
		$offset = $settings['offset'] ?? 0;

		$query = static::getQuery($paginate, $settings, $entity);

		if (!$query) {
			return '';
		}

		if ($paginate) {
			$news = static::paginate($query, $itemsPerPage);
		}
		else {
			$query->limit((int)$itemsLimit)->offset((int)$offset);
			$news = $query->all();
		}

		foreach ($news as $newsItem) {
			ResizedImageManager::addMediaItemsFromEntity($newsItem);
		}

		return $view->element('widget/news_listing', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'news' => $news,
			'paginate' => $paginate,
			'items' => $itemsLimit,
			'itemsPerPage' => $itemsPerPage,
			'offset' => $offset,
			'settings' => $settings,
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param int $itemsPerPage
	 * @return \Cake\Datasource\Paging\PaginatedInterface
	 */
	protected static function paginate(SelectQuery $query, int $itemsPerPage): PaginatedInterface {
		$paginator = new NumericPaginator();
		$params = ['limit' => $itemsPerPage] + Router::getRequest()->getQueryParams();

		$settings = [
			'sortableFields' => [],
		];

		return $paginator->paginate($query, $params, $settings);
	}


	/**
	 * @param \Awyiss\Model\Entity\Language|null $frontendLanguage
	 * @param \Awyiss\Model\Entity\Language|null $userLanguage
	 * @param array $settings
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected static function getCategoriesField(?Language $frontendLanguage, ?Language $userLanguage, array $settings): array {
		/** @var \Awyiss\Model\Table\PagesTable $newsTable */
		$newsTable = FactoryLocator::get('Table')->get('News');

		if (
			!$newsTable->hasBehavior('Categories') ||
			!$newsTable->getBehavior('Categories')->getConfig('enabled')
		) {
			return [];
		}

		$categories = $newsTable->getCategories();

		if (!$categories) {
			return [];
		}

		$categoriesField = [
			'label' => __df('Frontend/news', 'Frontend/widgets', 'categories'),
			'multiple' => true,
			'options' => $categories,
			'type' => 'select',
			'value' => $settings['categories'] ?? null,
		];

		return [
			'settings.categories' => $categoriesField,
		];
	}


	/**
	 * @param bool $paginate
	 * @param array $settings
	 * @param \Awyiss\Model\Entity|null $entity
	 * @return \Cake\ORM\Query\SelectQuery|null
	 */
	protected static function getQuery(bool $paginate, array $settings, ?Entity $entity): ?SelectQuery {
		$tableLocator = FactoryLocator::get('Table');

		try {
			/** @var \Awyiss\Model\Table $newsTable */
			$newsTable = $tableLocator->get('News');
		}
		catch (MissingTableClassException) {
			return null;
		}

		if (static::isPreview()) {
			$query = $newsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $newsTable->find('active')->find('published');
		}

		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query->find('forCurrentLanguage')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		$query->orderBy(['date' => 'DESC']);

		if (!$paginate && isset($newsTable->getAttributes()['inTeaser'])) {
			$query->where(['in_teaser' => true]);
		}

		if (isset($settings['categories'])) {
			if (is_array($settings['categories'])) {
				$query->where(['parent_id IN' => $settings['categories']]);
			}
		}
		elseif ($entity instanceof Page) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
			$pageRoleEnum = App::className('PageRole', 'Model/Enum');

			if ($entity->pageRoleId === $pageRoleEnum::Newscategory) {
				$query->where(['parent_id' => $entity->id]);
			}
		}

		return $query;
	}
}
