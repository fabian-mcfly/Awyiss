<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\Cell;


/**
 * Contents Cell
 *
 * Outputs the contents of a content area for a specific page.
 */
class ContentsCell extends Cell {
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 */
	public function __construct(ServerRequest $request, Response $response, ?EventManagerInterface $eventManager = null, array $cellOptions = []) {
		parent::__construct($request, $response, $eventManager, $cellOptions);

		$this->view = new FrontendView($request, $response);
	}


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

		$this->view->set([
			'page' => $page,
			...$la_options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Contents');

		$lo_contentsTable = $this->fetchTable('Contents');

		$lo_query = $lo_contentsTable->find('active')->find('threaded');
		$lo_query->where([
			'Contents.page_id' => $page->id,
			'ContentAreas.identifier' => $contentArea,
		]);

		// Contain ContentAreas and ContentTemplates
		$lo_query->contain([
			'ContentAreas',
			'ContentTemplates',
			'MediaAssignments',
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

		$this->prepareContents($lo_contents, (float)$la_options['columnWidth']);

		$ls_contents = $this->buildContent($lo_contents->toArray());

		// Set the view variables
		$this->set([
			'contents' => $ls_contents,
			'identifier' => $contentArea,
			'includeWrapper' => $la_options['includeWrapper'],
		]);
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $contents
	 * @param float $columnWidth
	 */
	protected function prepareContents(CollectionInterface $contents, float $columnWidth = 100.00): void {
		if (!$contents->count()) {
			return;
		}

		$lo_contents = $contents->listNested();
		$lo_lastContent = null;
		$la_parentContents = [];

		/** @var \Awyiss\Model\Entity\Content $lo_content */
		foreach ($lo_contents as $lo_content) {
			$lo_content->setVirtual(['level']);
			//Add the current depth as a level-property to the entity
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_content->level = $lo_contents->getDepth();

			// Remember the parent contents
			if ($lo_lastContent) {
				if ($lo_lastContent->level < $lo_content->level) {
					$la_parentContents[] = $lo_lastContent;
				}
				elseif ($lo_lastContent->level > $lo_content->level) {
					$la_parentContents = array_slice($la_parentContents, 0, $lo_content->level);
				}
			}

			$lo_content->parentContents = $la_parentContents;

			// Set the cssClass property
			$this->setCssClasses($lo_content);

			// Seat the real column width
			$this->setRealColumnWidth($lo_content, $columnWidth);

			// Set the template for the content
			// Will use a custom template named "Content<id>.twig", if it exists.
			$this->setTemplate($lo_content);

			$lo_lastContent = $lo_content;
		}
	}


	/**
	 * Render all provided contents, including children
	 *
	 * @param array $contents
	 * @return string
	 */
	protected function buildContent(array $contents): string {
		if (!$contents) {
			return '';
		}

		$ls_contents = '';
		$lf_currentWidth = 0;
		$ls_rowContents = '';

		/**
		 * @var \Awyiss\Model\Entity\Content $lo_content
		 */
		foreach ($contents as $lo_content) {
			$ls_children = '';

			if ($lo_content->children) {
				$ls_children = $this->buildContent($lo_content->children);
			}

			/*
			 * If the content template should not be rendered in a content row
			 * render the content directly, but not before rendering existing row contents.
			 */
			if (!$lo_content->contentTemplate->inContentRow) {
				if ($ls_rowContents) {
					$ls_contents .= $this->renderContentRow($ls_rowContents);

					// Reset the row contents
					$lf_currentWidth = 0;
					$ls_rowContents = '';
				}

				// Render the content. Adding the width is not necessary, as the content is rendered directly.
				$ls_contents .= $this->renderContent($lo_content, $ls_children);

				continue;
			}

			// Get the real column width of the content
			$lf_columnWidth = 100 * $lo_content->column['width']->getFactor();

			// If the content has a column indent, add the width of the indent to the current column width
			if ($lo_content->column['indent']) {
				$lf_columnWidth += 100 * $lo_content->column['indent']->getFactor();
			}

			/*
			 * Before rendering the content, check if the content row is full,
			 * or if the content - including potential indentation - would exceed the row width.
			 * If that is the case, render the current row and reset the row contents.
			 */
			if ($lf_currentWidth >= 100 || $lf_currentWidth + $lf_columnWidth >= 100) {
				if ($ls_rowContents) {
					$ls_contents .= $this->renderContentRow($ls_rowContents);
				}

				$lf_currentWidth = 0;
				$ls_rowContents = '';
			}

			// Add the content to the row contents
			$ls_rowContents .= $this->renderContent($lo_content, $ls_children);

			// If the content is a finisher, render the row and reset the row contents
			if ($lo_content->columnLast) {
				$ls_contents .= $this->renderContentRow($ls_rowContents);

				$lf_currentWidth = 0;
				$ls_rowContents = '';
			}
			else {
				// Add the width of the content to the current row width
				$lf_currentWidth += $lf_columnWidth;
			}
		}

		// Render the last row
		if ($ls_rowContents) {
			$ls_contents .= $this->renderContentRow($ls_rowContents);
		}


		return $ls_contents;
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param string $children
	 * @return string
	 */
	protected function renderContent(Content $content, string $children): string {
		$ls_content = $this->view->content($content->contentTemplate->fileName, [
			'content' => $content,
			'children' => $children,
		]);

		return $ls_content;
	}


	/**
	 * @param string $contents
	 * @return string
	 */
	protected function renderContentRow(string $contents): string {
		$ls_contentRow = $this->view->element('content_row', [
			'contents' => $contents,
			'class' => $this->view::$rowClass,
		]);

		$this->view::$rowClass = '';

		return $ls_contentRow;
	}


	/**
	 * Set the real column width of a content,
	 * based on the width of its parent content.
	 *
	 * @param \Awyiss\Model\Entity\Content $content
	 * @param float $columnWidth
	 * @return void
	 */
	protected function setRealColumnWidth(Content $content, float $columnWidth): void {
		$content->setVirtual(['realColumnWidth']);

		if (!$content->parentContents) {
			$content->realColumnWidth = $columnWidth * $content->column['width']->getFactor();

			return;
		}

		/**
		 * @var \Awyiss\Model\Entity\Content $lo_parentContent
		 */
		$lo_parentContent = end($content->parentContents);

		$content->realColumnWidth = $lo_parentContent->realColumnWidth * $content->column['width']->getFactor();
	}


	/**
	 * Set the cssClass property
	 * Each content element gets a css class based on its content template, column width and indent,
	 * as well as the cssClass property set in the database.
	 *
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return void
	 */
	protected function setCssClasses(Content $content): void {
		if (empty($content->cssClass)) {
			$content->cssClass = '';
		}

		$ls_cssClass = trim($content->cssClass);

		$content->cssClass = 'ContentElement Template-' . Inflector::ucparts($content->contentTemplate->fileName);
		$content->cssClass .= ' ' . $content->column['width']->getCssClass();

		if ($content->column['indent']) {
			$content->cssClass .= ' ' . $content->column['indent']?->getCssClass();
		}

		if ($content->columnRtl) {
			$content->cssClass .= ' Column-Rtl';
		}

		if ($ls_cssClass) {
			$content->cssClass .= ' ' . $ls_cssClass;
		}
	}


	/**
	 * Check if a custom template exists for a content element,
	 * based on the content id.
	 *
	 * @param \Awyiss\Model\Entity\Content $content
	 * @return void
	 */
	protected function setTemplate(Content $content): void {
		static $ls_templatePath;

		if (!isset($ls_templatePath)) {
			$ls_templatePath = rtrim(Configure::read('App.paths.templates.customer'), DS);
		}

		$ls_fileName = 'Content' . $content->id;

		$ls_filePath = implode(DS, [
			$ls_templatePath,
			'Frontend',
			'content',
			$ls_fileName . '.twig',
		]);

		if (file_exists($ls_filePath)) {
			$content->contentTemplate->fileName = $ls_fileName;
		}
	}
}
