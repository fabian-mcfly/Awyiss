<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Utility\DebugTimer;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\View\Cell;


/**
 * Contents Cell
 *
 * Outputs the contents of a content area for a specific page.
 */
class ContentsCell extends Cell {
	use ContentElementTrait;
	use PreviewTrait;
	use RedirectAwareTrait;
	use RenderTrimmedTrait;


	/**
	 * @param string $contentArea
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param \Awyiss\View\FrontendView $view
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string $contentArea, Page $page, FrontendView $view, array $options = []): void {
		DebugTimer::start('ContentsCell::display', sprintf('ContentsCell::display: Rendering content area "%s" on page %d', $contentArea, $page->id));

		$this->View = $view;

		$options = $this->initCellOptions($options);

		if ($options['pageId'] ?? null) {
			/**
			 * @var \Awyiss\Model\Entity\Page $page
			 */
			$page = $this->fetchTable('Pages')->find($this->isPreview() ? 'all' : 'active')->where(['id' => $options['pageId']])->firstOrFail();
		}

		$contents = $this->getThreadedContents($page, $contentArea, $this->isPreview());

		$this->cacheAssignedMediaItems($contents, 'contents');

		$this->addDuplicates($contents, $this->isPreview());

		$this->prepareEntities($contents, (float)$options['columnWidth']);

		$this->addDynamicCss($contents);

		$this->setViewVars($options);

		$renderedContents = $this->buildContents($contents->toArray(), false, $options['autoSection'] ?? true);

		$currentRoute = Router::url($this->request->getRequestTarget());
		if ($renderedContents && $currentRoute !== '/') {
			// Replace all `href="#anchor"` with `href="<currentRoute>#anchor"`
			$renderedContents = preg_replace('/href=[\'"](#[^\'"]+)[\'"]/', 'href="' . ltrim($currentRoute, '/') . '$1"', $renderedContents);
		}

		// Set the view variables
		$this->set([
			'contents' => $renderedContents,
			'fullWidth' => $options['fullWidth'],
			'identifier' => $contentArea,
			'includeWrapper' => $options['includeWrapper'],
			'page' => $page,
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
			...$options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Contents');

		DebugTimer::stop('ContentsCell::display');
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param string $children
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function renderElement(Entity $entity, string $children): string {
		DebugTimer::start('ContentsCell::renderElement' . $entity->id, sprintf('ContentsCell::renderElement: Rendering content #%d with template "%s"', $entity->id, $entity->contentTemplate->fileName));

		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$mediaRenderOptions = $this->getView()->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->getView()->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#Content' . $entity->id,
			singleColumnBreakpoint: $this->getView()->get('singleColumnBreakpoint'),
		);

		// Parse the Awyiss image tags
		$this->parseAwyissImageTags($entity, $mediaRenderOptions);

		// Parse the widgets
		$this->parseWidgets($entity, $mediaRenderOptions);

		$fullWidthMissingWarning = '';
		if (!$this->getView()->get('fullWidth')) {
			$fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the content cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$result = $fullWidthMissingWarning . $this->getView()->content($entity->contentTemplate->fileName, [
			'content' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);

		DebugTimer::stop('ContentsCell::renderElement' . $entity->id);
		return $result;
	}


	/**
	 * Add duplicated contents and their children to the entities.
	 *
	 * @param \Cake\Collection\CollectionInterface $contents
	 * @param bool $isPreview
	 * @return void
	 */
	protected function addDuplicates(CollectionInterface $contents, bool $isPreview = false): void {
		DebugTimer::start('ContentsCell::addDuplicates');

		$contents = $contents->listNested();

		$duplicatingEntities = [];
		/** @var \Awyiss\Model\Entity\Content $entity */
		foreach ($contents as $entity) {
			if ($entity->duplicateOf ?? null) {
				$duplicatingEntities[] = $entity;
			}
		}

		if (!$duplicatingEntities) {
			DebugTimer::stop('ContentsCell::addDuplicates');
			return;
		}

		$duplicatedIds = array_column($duplicatingEntities, 'duplicateOf');

		$query = $this->getContentsQuery();
		$query->where([
			'Contents.id IN' => $duplicatedIds,
		]);
		$duplicatedEntities = $query->all()->indexBy('id')->toArray();

		/** @var \Awyiss\Model\Entity\Content $entity */
		foreach ($duplicatingEntities as $entity) {
			if (empty($duplicatedEntities[ $entity->duplicateOf ])) {
				continue;
			}

			/** @var \Awyiss\Model\Entity\Content $duplicatedEntity */
			$duplicatedEntity = $duplicatedEntities[ $entity->duplicateOf ];
			$entity->set('duplicateOfContent', $duplicatedEntity);

			// If the duplicating content has children, we don't need to fetch children for the duplicated content
			if (!empty($entity->children)) {
				continue;
			}

			$finders = [];

			if (!$isPreview) {
				$finders = ['active', 'published'];
			}

			$finders = array_merge($finders, [
				'mediaAssignments' => [
					'includeElementSelector' => true,
					'useMediaEntity' => true,
				],
			]);

			$children = $duplicatedEntity->getNestedChildren([
				'contain' => [
					'ContentAreas',
					'ContentTemplates',
				],
				'finders' => $finders,
			]);

			if ($children->count()) {
				$children = $children->nest('id', 'parent_id');
				foreach ($children as $child) {
					$child->parentId = $entity->id;
				}
			}

			$entity->set('children', $children->toList());
		}

		DebugTimer::stop('ContentsCell::addDuplicates');
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return void
	 */
	protected function applyDuplicateData(Entity $entity): void {
		static $blocklistedKeys;

		if (!isset($blocklistedKeys)) {
			/** @var \Awyiss\Model\Table\ContentsTable $table */
			$table = $this->fetchTable('Contents');

			$blocklistedKeys = array_merge($table->getAllowedKeyForDuplicating(), [
				'id',
				'contentTemplateId',
				'createdBy',
				'createdOn',
				'changedBy',
				'changedOn',
				'deletedBy',
				'deletedOn',
				'contentTemplate',
				'contentArea',
				'children',
				'level',
			]);
		}

		// If the content has a duplicated one, use some data from the duplicated content
		if (!$entity->duplicateOfContent) {
			return;
		}

		$data = $entity->duplicateOfContent->extract(null, false, false);
		$data = array_diff_key($data, array_flip($blocklistedKeys));

		$entity->patch($data);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param string $contentArea
	 * @param bool $isPreview
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getThreadedContents(Page $page, string $contentArea, bool $isPreview = false): CollectionInterface {
		DebugTimer::start('ContentsCell::getThreadedContents', sprintf('ContentsCell::getThreadedContents: Fetching threaded contents for content area "%s" on page %d', $contentArea, $page->id));
		$query = $this->getContentsQuery($isPreview);

		$query->where([
			'Contents.page_id' => $page->duplicateOf ?? $page->id,
			'ContentAreas.identifier' => $contentArea,
		]);

		if (!$isPreview) {
			$query->where([
				'ContentAreas.active' => true,
			]);
		}

		$contents = $query->all();

		/*
		 * Filter out all first level contents with a parent_id
		 * This is done to prevent the display of nested contents whose parent isn't
		 * part of the result set.
		 *
		 * Either because it's not active (allowed to happen)
		 * or because it's not part of the same page. (shouldn't happen)
		 */
		$contents = $contents->filter(function (Content $content) {
			return $content->parentId === null;
		})->compile();

		DebugTimer::stop('ContentsCell::getThreadedContents');

		return $contents;
	}


	/**
	 * @param bool $isPreview
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function getContentsQuery(bool $isPreview = false): SelectQuery {
		/** @var \Awyiss\Model\Table\ContentsTable $contentsTable */
		$contentsTable = $this->fetchTable('Contents');

		if ($isPreview) {
			$query = $contentsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $contentsTable->find('active')->find('published');
		}

		$query->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		// Contain ContentAreas and ContentTemplates
		$query->contain([
			'ContentAreas',
			'ContentTemplates',
		]);

		return $query;
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $contents
	 * @return void
	 */
	protected function addDynamicCss(CollectionInterface $contents): void {
		DebugTimer::start('ContentsCell::addDynamicCss', 'ContentsCell::addDynamicCss: Adding dynamic CSS for contents');
		/** @var \Awyiss\View\Helper\AssetHelper $assetHelper */
		$assetHelper = $this->View->helpers()->get('Asset');

		/** @var \Awyiss\Model\Entity\Content $content */
		foreach ($contents->listNested() as $content) {
			if (empty($content->css)) {
				continue;
			}

			$assetHelper->addContentStyleBlock('#Content' . $content->id, $content->css);
		}

		DebugTimer::stop('ContentsCell::addDynamicCss');
	}
}
