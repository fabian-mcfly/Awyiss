<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Widget;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Query\SelectQuery;


/**
 * ContentElementTrait to be used in Cells
 * for Frontend content elements (content and widgets)
 */
trait ContentElementTrait {
	/**
	 * @var \Awyiss\Utility\Media\ResizedImageManager $resizedImageManager
	 */
	protected static ResizedImageManager $resizedImageManager;


	/**
	 * @var \Awyiss\View\FrontendView $view
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 */
	public function __construct(ServerRequest $request, Response $response, ?EventManagerInterface $eventManager = null, array $cellOptions = []) {
		parent::__construct($request, $response, $eventManager, $cellOptions);

		if (!isset(static::$resizedImageManager)) {
			static::$resizedImageManager = new ResizedImageManager();
		}

		$this->view = new FrontendView($request, $response);
	}


	/**
	 * Fetch all used media items and add them to the ResizedImageManager
	 *
	 * @param \Cake\Collection\CollectionInterface $entities
	 * @param string $scope
	 * @return void
	 */
	protected function addMediaItems(CollectionInterface $entities, string $scope): void {
		$lo_entities = $entities->listNested()->compile(false);
		$la_entityIds = $lo_entities->extract('id')->toArray();

		if (!$la_entityIds) {
			return;
		}

		$lo_mediaTable = $this->fetchTable('Media');
		$lo_query = $lo_mediaTable->find()->matching('MediaAssignments', function (SelectQuery $query) use ($la_entityIds, $scope) {
			return $query->where([
				'MediaAssignments.foreign_key IN' => $la_entityIds,
				'MediaAssignments.scope' => $scope,
			]);
		})
		->contain(['MediaResizedImages'])
		->distinct('Media.id');

		static::$resizedImageManager->setMediaItems($lo_query->all()->toArray());
	}


	/**
	 * Attempt to find the width of the page by
	 * - checking the view vars for a page width
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findFullWidth(array $options): ?float {
		if (isset($options['viewVars']['fullWidth'])) {
			return (float)$options['viewVars']['fullWidth'];
		}

		if (isset($options['viewVars']['designSettings']['pageWidth'])) {
			return (float)$options['viewVars']['designSettings']['pageWidth'];
		}

		return null;
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $entities
	 * @param float $columnWidth
	 * @param float|null $fullWidth
	 */
	protected function prepareEntities(CollectionInterface $entities, float $columnWidth = 100.00, ?float $fullWidth = null): void {
		if (!$entities->count()) {
			return;
		}

		$lo_entities = $entities->listNested();
		$lo_lastEntity = null;
		$la_parentEntities = [];

		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $lo_entity
		 */
		foreach ($lo_entities as $lo_entity) {
			$lo_entity->setVirtual(['level']);
			//Add the current depth as a level-property to the entity
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_entity->level = $lo_entities->getDepth();

			// Remember the parent entities
			if ($lo_lastEntity) {
				if ($lo_lastEntity->level < $lo_entity->level) {
					$la_parentEntities[] = $lo_lastEntity;
				}
				elseif ($lo_lastEntity->level > $lo_entity->level) {
					$la_parentEntities = array_slice($la_parentEntities, 0, $lo_entity->level);
				}
			}

			if ($lo_entity instanceof Content) {
				$lo_entity->parentContents = $la_parentEntities;
			}
			else {
				$lo_entity->parentWidgets = $la_parentEntities;
			}

			// Set the cssClass property
			$this->setCssClasses($lo_entity);

			// Seat the real column width
			$this->setRealColumnWidth($lo_entity, $columnWidth);

			// Set the template for the entity
			// Will use a custom template named "Content<id>.twig", if it exists.
			$this->setTemplate($lo_entity);

			$lo_lastEntity = $lo_entity;
		}
	}


	/**
	 * Render all provided elements, including children,
	 * in the correct order and with the correct column widths, using the provided template.
	 *
	 * @param array $entities
	 * @param bool $noContentRow
	 * @return string
	 */
	protected function buildContents(array $entities, bool $noContentRow = false): string {
		if (!$entities) {
			return '';
		}

		$ls_contentElements = '';
		$lf_currentWidth = 0;
		$ls_rowContent = '';

		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $lo_entity
		 */
		foreach ($entities as $lo_entity) {
			$ls_children = '';

			if ($lo_entity->children) {
				$ls_children = $this->buildContents($lo_entity->children, $noContentRow);
			}

			$lb_noContentRow = $noContentRow;
			if (!$lb_noContentRow) {
				$ls_template = $lo_entity instanceof Widget ? 'widgetTemplate' : 'contentTemplate';
				$lb_noContentRow = !$lo_entity->$ls_template->inContentRow;
			}

			/*
			 * If the template should not be rendered in a content row
			 * render the element directly, but not before rendering existing row contents.
			 */
			if ($lb_noContentRow) {
				if ($ls_rowContent) {
					$ls_contentElements .= $this->renderContentRow($ls_rowContent);

					// Reset the row contents
					$lf_currentWidth = 0;
					$ls_rowContent = '';
				}

				// Render the content. Adding the width is not necessary, as the content is rendered directly.
				$ls_contentElements .= $this->renderContentElement($lo_entity, $ls_children);

				continue;
			}

			// Get the real column width of the content
			$lf_columnWidth = 100 * $lo_entity->column['width']->getFactor();

			// If the content has a column indent, add the width of the indent to the current column width
			if ($lo_entity->column['indent']) {
				$lf_columnWidth += 100 * $lo_entity->column['indent']->getFactor();
			}

			/*
			 * Before rendering the content, check if the content row is full,
			 * or if the content - including potential indentation - would exceed the row width.
			 * If that is the case, render the current row and reset the row contents.
			 */
			if ($lf_currentWidth > 100 || $lf_currentWidth + $lf_columnWidth > 100) {
				if ($ls_rowContent) {
					$ls_contentElements .= $this->renderContentRow($ls_rowContent);
				}

				$lf_currentWidth = 0;
				$ls_rowContent = '';
			}

			// Add the content to the row contents
			$ls_rowContent .= $this->renderContentElement($lo_entity, $ls_children);

			// If the content is a finisher, render the row and reset the row contents
			if ($lo_entity->columnLast) {
				$ls_contentElements .= $this->renderContentRow($ls_rowContent);

				$lf_currentWidth = 0;
				$ls_rowContent = '';
			}
			else {
				// Add the width of the content to the current row width
				$lf_currentWidth += $lf_columnWidth;
			}
		}

		// Render the last row
		if ($ls_rowContent) {
			$ls_contentElements .= $this->renderContentRow($ls_rowContent);
		}


		return $ls_contentElements;
	}


	/**
	 * Render an entity as content element using the provided template.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $children
	 * @return string
	 */
	abstract protected function renderContentElement(Entity $entity, string $children): string;


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
	 * Set the cssClass property
	 * Each element gets a css class based on its template, column width and indent,
	 * as well as the cssClass property set in the database.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $entity
	 * @return void
	 */
	protected function setCssClasses(Content|Widget $entity): void {
		if (empty($entity->cssClass)) {
			$entity->cssClass = '';
		}

		$ls_cssClass = trim($entity->cssClass);

		$ls_template = $entity instanceof Widget ? 'widgetTemplate' : 'contentTemplate';

		$entity->cssClass = $entity instanceof Widget ? 'Widget' : 'Content';
		$entity->cssClass .= 'Element Template-' . Inflector::ucparts($entity->$ls_template->fileName);
		$entity->cssClass .= ' ' . $entity->column['width']->getCssClass();

		if ($entity->column['indent']) {
			$entity->cssClass .= ' ' . $entity->column['indent']?->getCssClass();
		}

		if ($entity->columnRtl) {
			$entity->cssClass .= ' Column-Rtl';
		}

		if ($ls_cssClass) {
			$entity->cssClass .= ' ' . $ls_cssClass;
		}
	}


	/**
	 * Set the real column width of an entity,
	 * based on the width of its parent entities.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $entity
	 * @param float $columnWidth
	 * @return void
	 */
	protected function setRealColumnWidth(Content|Widget $entity, float $columnWidth): void {
		$entity->setVirtual(['realColumnWidth']);

		if (!$entity->parentWidgets) {
			$entity->realColumnWidth = $columnWidth * $entity->column['width']->getFactor();

			return;
		}

		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $lo_parent
		 */
		$lo_parent = $entity instanceof Widget ? end($entity->parentWidgets) : end($entity->parentContents);

		$entity->realColumnWidth = $lo_parent->realColumnWidth * $entity->column['width']->getFactor();
	}


	/**
	 * Check if a custom template exists for a content element,
	 * based on the id.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $entity
	 * @return void
	 */
	protected function setTemplate(Content|Widget $entity): void {
		static $ls_templatePath;

		if (!isset($ls_templatePath)) {
			$ls_templatePath = rtrim(Configure::read('App.paths.templates.customer'), DS);
		}

		$ls_fileName = ($entity instanceof Widget ? 'Widget' : 'Content') . $entity->id;

		$ls_filePath = implode(DS, [
			$ls_templatePath,
			'Frontend',
			$entity instanceof Widget ? 'widget' : 'content',
			$ls_fileName . '.twig',
		]);

		if (file_exists($ls_filePath)) {
			$ls_template = $entity instanceof Widget ? 'widgetTemplate' : 'contentTemplate';
			$entity->$ls_template->fileName = $ls_fileName;
		}
	}
}
