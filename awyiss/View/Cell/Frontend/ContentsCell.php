<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\View\Cell;
use RuntimeException;


/**
 * Contents Cell
 *
 * Outputs the contents of a content area for a specific page.
 */
class ContentsCell extends Cell {
	use ContentElementTrait;


	/**
	 * @param string $contentArea
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param array $options
	 * @return void
	 */
	public function display(string $contentArea, Page $page, array $options = []): void {
		$la_options = $options + [
			'columnWidth' => 100.00,
			'includeWrapper' => true,
			'viewVars' => [],
		];

		if (!isset($la_options['fullWidth'])) {
			$la_options['fullWidth'] = $this->findFullWidth($la_options);

			if ($la_options['fullWidth'] === null) {
				throw new RuntimeException('Cannot determine page width. Please provide a page width when rendering contents');
			}
		}
		else {
			$la_options['fullWidth'] = (float)$la_options['fullWidth'];
		}

		if (!array_key_exists('singleColumnBreakpoint', $la_options)) {
			$la_options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($la_options);
		}
		elseif ($la_options['singleColumnBreakpoint'] !== null) {
			$la_options['singleColumnBreakpoint'] = (float)$la_options['singleColumnBreakpoint'];
		}

		$this->View->set([
			'fullWidth' => $la_options['fullWidth'],
			'page' => $page,
			'singleColumnBreakpoint' => $la_options['singleColumnBreakpoint'],
			...$la_options['viewVars'],
		]);

		$lo_query = $this->getContentsQuery();

		$lo_query->where([
			'Contents.page_id' => $page->duplicateOf ?? $page->id,
			'ContentAreas.identifier' => $contentArea,
		]);

		$lo_contents = $lo_query->all();

		/*
		 * Filter out all first level contents with a parent_id
		 * This is done to prevent the display of nested contents whose parent isn't
		 * part of the result set.
		 *
		 * Either because it's not active (allowed to happen)
		 * or because it's not part of the same page. (shouldn't happen)
		 */
		$lo_contents = $lo_contents->filter(function (Content $content) {
			return $content->parentId === null;
		})->compile();

		$this->addMediaItems($lo_contents, 'contents');

		$this->addDuplicates($lo_contents);

		$this->prepareEntities($lo_contents, (float)$la_options['columnWidth']);

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
	 */
	protected function renderContentElement(Entity $entity, string $children): string {
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth'),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			columnWidth: $entity->realColumnWidth,
			selector: '#Content' . $entity->id,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		// Parse the module
		$this->parseModule($entity, $lo_mediaRenderOptions);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->View->content($entity->contentTemplate->fileName, [
			'content' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}


	/**
	 * Add duplicated contents and their children to the entities.
	 *
	 * @param \Cake\Collection\CollectionInterface $contents
	 * @return void
	 */
	protected function addDuplicates(CollectionInterface $contents): void {
		$lo_contents = $contents->listNested();

		$la_duplicatingEntities = [];
		/** @var \Awyiss\Model\Entity\Content $lo_entity */
		foreach ($lo_contents as $lo_entity) {
			if ($lo_entity->duplicateOf) {
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

			$lo_children = $lo_duplicatedEntity->getNestedChildren([
				'contain' => [
					'ContentAreas',
					'ContentTemplates',
				],
				'finders' => [
					'active',
					'published',
					'mediaAssignments' => [
						'includeElementSelector' => true,
						'useMediaEntity' => true,
					],
				],
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
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function getContentsQuery(): SelectQuery {
		$lo_contentsTable = $this->fetchTable('Contents');
		$lo_query = $lo_contentsTable->find('active')->find('published')->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		// Contain ContentAreas and ContentTemplates
		$lo_query->contain([
			'ContentAreas',
			'ContentTemplates',
		]);

		return $lo_query;
	}
}
