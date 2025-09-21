<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
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
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string $contentArea, Page $page, array $options = []): void {
		$la_options = $this->initCellOptions($options);

		if ($options['pageId'] ?? null) {
			/**
			 * @var \Awyiss\Model\Entity\Page $page
			 * @noinspection PhpVariableNamingConventionInspection
			 */
			$page = $this->fetchTable('Pages')->find($this->isPreview() ? 'all' : 'active')->where(['id' => $options['pageId']])->firstOrFail();
		}

		$lo_contents = $this->getThreadedContents($page, $contentArea, $this->isPreview());

		$this->cacheAssignedMediaItems($lo_contents, 'contents');

		$this->addDuplicates($lo_contents, $this->isPreview());

		$this->prepareEntities($lo_contents, (float)$la_options['columnWidth']);

		$this->setViewVars($la_options);

		$ls_contents = $this->buildContents($lo_contents->toArray());

		// Set the view variables
		$this->set([
			'contents' => $ls_contents,
			'fullWidth' => $la_options['fullWidth'],
			'identifier' => $contentArea,
			'includeWrapper' => $la_options['includeWrapper'],
			'page' => $page,
			'singleColumnBreakpoint' => $la_options['singleColumnBreakpoint'],
			...$la_options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Contents');
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
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->getView()->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->getView()->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#Content' . $entity->id,
			singleColumnBreakpoint: $this->getView()->get('singleColumnBreakpoint'),
		);

		// Parse the module
		$this->parseAwyissImageTags($entity, $lo_mediaRenderOptions);

		// Parse the module
		$this->parseModule($entity, $lo_mediaRenderOptions);

		$ls_fullWidthMissingWarning = '';
		if (!$this->getView()->get('fullWidth')) {
			$ls_fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the content cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $ls_fullWidthMissingWarning . $this->getView()->content($entity->contentTemplate->fileName, [
			'content' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}


	/**
	 * Add duplicated contents and their children to the entities.
	 *
	 * @param \Cake\Collection\CollectionInterface $contents
	 * @param bool $isPreview
	 * @return void
	 */
	protected function addDuplicates(CollectionInterface $contents, bool $isPreview = false): void {
		$lo_contents = $contents->listNested();

		$la_duplicatingEntities = [];
		/** @var \Awyiss\Model\Entity\Content $lo_entity */
		foreach ($lo_contents as $lo_entity) {
			if ($lo_entity->duplicateOf ?? null) {
				$la_duplicatingEntities[] = $lo_entity;
			}
		}

		if (!$la_duplicatingEntities) {
			return;
		}

		$la_duplicatedIds = array_column($la_duplicatingEntities, 'duplicateOf');

		$lo_query = $this->getContentsQuery();
		$lo_query->where([
			'Contents.id IN' => $la_duplicatedIds,
		]);
		$la_duplicatedEntities = $lo_query->all()->indexBy('id')->toArray();

		/** @var \Awyiss\Model\Entity\Content $lo_entity */
		foreach ($la_duplicatingEntities as $lo_entity) {
			if (empty($la_duplicatedEntities[ $lo_entity->duplicateOf ])) {
				continue;
			}

			/** @var \Awyiss\Model\Entity\Content $lo_duplicatedEntity */
			$lo_duplicatedEntity = $la_duplicatedEntities[ $lo_entity->duplicateOf ];
			$lo_entity->set('duplicateOfContent', $lo_duplicatedEntity);

			// If the duplicating content has children, we don't need to fetch children for the duplicated content
			if (!empty($lo_entity->children)) {
				continue;
			}

			$la_finders = [];

			if (!$isPreview) {
				$la_finders = ['active', 'published'];
			}

			$la_finders = array_merge($la_finders, [
				'mediaAssignments' => [
					'includeElementSelector' => true,
					'useMediaEntity' => true,
				],
			]);

			$lo_children = $lo_duplicatedEntity->getNestedChildren([
				'contain' => [
					'ContentAreas',
					'ContentTemplates',
				],
				'finders' => $la_finders,
			]);

			if ($lo_children->count()) {
				$lo_children = $lo_children->nest('id', 'parent_id');
				foreach ($lo_children as $lo_child) {
					$lo_child->parentId = $lo_entity->id;
				}
			}

			$lo_entity->set('children', $lo_children->toList());
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return void
	 */
	protected function applyDuplicateData(Entity $entity): void {
		static $la_blocklistedKeys;

		if (!isset($la_blocklistedKeys)) {
			/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
			$lo_table = $this->fetchTable('Contents');

			$la_blocklistedKeys = array_merge($lo_table->getAllowedKeyForDuplicating(), [
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

		$la_data = $entity->duplicateOfContent->extract(null, false, false);
		$la_data = array_diff_key($la_data, array_flip($la_blocklistedKeys));

		$entity->patch($la_data);
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param string $contentArea
	 * @param bool $isPreview
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getThreadedContents(Page $page, string $contentArea, bool $isPreview = false): CollectionInterface {
		$lo_query = $this->getContentsQuery($isPreview);

		$lo_query->where([
			'Contents.page_id' => $page->duplicateOf ?? $page->id,
			'ContentAreas.identifier' => $contentArea,
		]);

		if (!$isPreview) {
			$lo_query->where([
				'ContentAreas.active' => true,
			]);
		}

		$lo_contents = $lo_query->all();

		/*
		 * Filter out all first level contents with a parent_id
		 * This is done to prevent the display of nested contents whose parent isn't
		 * part of the result set.
		 *
		 * Either because it's not active (allowed to happen)
		 * or because it's not part of the same page. (shouldn't happen)
		 */
		return $lo_contents->filter(function (Content $content) {
			return $content->parentId === null;
		})->compile();
	}


	/**
	 * @param bool $isPreview
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function getContentsQuery(bool $isPreview = false): SelectQuery {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_contentsTable */
		$lo_contentsTable = $this->fetchTable('Contents');

		if ($isPreview) {
			$lo_query = $lo_contentsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$lo_query = $lo_contentsTable->find('active')->find('published');
		}

		$lo_query->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		// Contain ContentAreas and ContentTemplates
		$lo_query->contain([
			'ContentAreas',
			'ContentTemplates',
		]);

		return $lo_query;
	}
}
