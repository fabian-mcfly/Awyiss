<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Cake\Core\Configure;
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

		$lo_contentsTable = $this->fetchTable('Contents');

		$lo_query = $lo_contentsTable->find('active')->find('published')->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
		$lo_query->where([
			'Contents.page_id' => $page->duplicateOf ?? $page->id,
			'ContentAreas.identifier' => $contentArea,
		]);

		// Contain ContentAreas and ContentTemplates
		$lo_query->contain([
			'ContentAreas',
			'ContentTemplates',
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

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->View->content($entity->contentTemplate->fileName, [
			'content' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}
}
