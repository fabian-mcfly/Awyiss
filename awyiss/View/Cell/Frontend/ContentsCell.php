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

		$this->View->set([
			'fullWidth' => $la_options['fullWidth'],
			'page' => $page,
			...$la_options['viewVars'],
		]);

		$lo_contentsTable = $this->fetchTable('Contents');

		$lo_query = $lo_contentsTable->find('active')->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
		$lo_query->where([
			'Contents.page_id' => $page->id,
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
			'fullWidth' => $la_options['fullWidth'],
			'contents' => $ls_contents,
			'page' => $page,
			'identifier' => $contentArea,
			'includeWrapper' => $la_options['includeWrapper'],
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
		return $this->view->content($entity->contentTemplate->fileName, [
			'content' => $entity,
			'children' => $children,
		]);
	}
}
