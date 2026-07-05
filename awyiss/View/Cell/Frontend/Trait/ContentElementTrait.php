<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\I18n\DateTime;


/**
 * ContentElementTrait to be used in Cells
 * for Frontend content elements (content, form elements and global_contents)
 */
trait ContentElementTrait {
	use FrontendRenderingTrait;


	/**
	 * @param \Cake\Collection\CollectionInterface $entities
	 * @param float $columnWidth
	 */
	protected function prepareEntities(CollectionInterface $entities, float $columnWidth = 100.00): void {
		$count = $entities->count();
		DebugTimer::start('ContentElementTrait::prepareEntities', sprintf('ContentElementTrait::prepareEntities: Preparing %d entities', $count));

		if (!$count) {
			DebugTimer::stop('ContentElementTrait::prepareEntities');
			return;
		}

		$entities = $entities->listNested();
		$lastEntity = null;
		$parentEntities = [];

		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\GlobalContent $entity
		 */
		foreach ($entities as $entity) {
			$this->applyDuplicateData($entity);

			$entity->setVirtual(['level'], true);
			//Add the current depth as a level-property to the entity
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity->level = $entities->getDepth();

			// Remember the parent entities
			if ($lastEntity) {
				if ($lastEntity->level < $entity->level) {
					$parentEntities[] = $lastEntity;
				}
				elseif ($lastEntity->level > $entity->level) {
					$parentEntities = array_slice($parentEntities, 0, $entity->level);
				}
			}

			if ($entity instanceof Content) {
				$entity->parentContents = $parentEntities;
			}
			elseif ($entity instanceof FormElement) {
				$entity->parentFormElements = $parentEntities;
			}
			else {
				$entity->parentGlobalContents = $parentEntities;
			}

			// Set the cssClass property
			$this->setCssClasses($entity);

			// Seat the real column width
			$this->setRealColumnWidth($entity, $columnWidth);

			// Set the template for the entity
			// Will use a custom template named "Content/GlobalContent<id>.twig", if it exists.
			$this->setTemplate($entity);

			$lastEntity = $entity;
		}

		DebugTimer::stop('ContentElementTrait::prepareEntities');
	}


	/**
	 * Render all provided elements, including children,
	 * in the correct order and with the correct column widths, using the provided template.
	 *
	 * @param array $entities
	 * @param bool $noContentRow Whether to render all elements without a content row
	 * @param bool $autoSection Whether to automatically wrap first level content rows in a section
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function buildContents(array $entities, bool $noContentRow = false, bool $autoSection = false, int $level = 0): string {
		$count = count($entities);
		DebugTimer::start('ContentElementTrait::buildContents' . $level, sprintf('ContentElementTrait::buildContents: Building %d entities for level %d', $count, $level));

		if (!$count) {
			DebugTimer::stop('ContentElementTrait::buildContents' . $level);

			return '';
		}

		$realSystemOrder = 0;
		$contentElements = '';
		$currentWidth = 0;
		$rowContent = '';
		$contentRowClasses = [];
		$type = 'content';
		$entities = array_values($entities);
		if ($entities[0] instanceof FormElement) {
			$type = 'form_element';
		}
		elseif ($entities[0] instanceof GlobalContent) {
			$type = 'global_content';
		}

		$initialNoContentRow = $noContentRow;
		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\GlobalContent $entity
		 */
		foreach ($entities as $entity) {
			$entity->realSystemOrder = ++$realSystemOrder;

			$children = '';

			if ($entity->children) {
				$children = $this->buildContents($entity->children, $initialNoContentRow, false, $level + 1);
			}

			$entityType ??= match (true) {
				$entity instanceof Content => 'Content',
				$entity instanceof FormElement => 'FormElement',
				$entity instanceof GlobalContent => 'GlobalContent',
			};

			$timerName = sprintf('ContentElementTrait::buildContent%s#%d', $entityType, $entity->id);
			DebugTimer::start($timerName, sprintf('ContentElementTrait::buildContents: Building %s #%d', $entityType, $entity->id));

			// Render the content before determining if it should be rendered in a content row
			// This allows the template to modify the content row setting
			$renderedContent = $this->renderElement($entity, $children);

			$noContentRow = $initialNoContentRow;
			if (!$noContentRow && !$entity instanceof FormElement) {
				$template = $entity instanceof GlobalContent ? 'globalContentTemplate' : 'contentTemplate';

				$noContentRow = !$entity->$template->inContentRow;
				if ($entity->has('inContentRow')) {
					$noContentRow = !$entity->inContentRow;
				}
			}
			elseif ($entity instanceof FormElement && in_array($entity->type, ['fieldset', 'hidden'])) {
				$noContentRow = true;
			}

			/*
			 * If the template should not be rendered in a content row
			 * render the element directly, but not before rendering existing row contents.
			 */
			if ($noContentRow) {
				if ($rowContent) {
					$contentElements .= $this->renderContentRow($rowContent, $type, $contentRowClasses, $autoSection);

					// Reset the row contents
					$currentWidth = 0;
					$rowContent = '';
				}

				// Render the content. Adding the width is not necessary, as the content is rendered directly.
				$contentElements .= $renderedContent;
				// Unset the row class. Follow-up contents will start a new row and with a blank row class.
				$contentRowClasses = [];
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$this->getView()->setRowClass('');

				DebugTimer::stop($timerName);
				continue;
			}

			// Get the real column width of the content
			$columnWidth = 100 * $entity->column['width']->getFactor();

			// If the content has a column indent, add the width of the indent to the current column width
			if ($entity->column['indent']) {
				$columnWidth += 100 * $entity->column['indent']->getFactor();
			}

			/*
			 * Before rendering the content, check if the content row is full,
			 * or if the content - including potential indentation - would exceed the row width.
			 * If that is the case, render the current row and reset the row contents.
			 */
			if ($currentWidth > 100 || $currentWidth + $columnWidth > 100) {
				if ($rowContent) {
					$contentElements .= $this->renderContentRow($rowContent, $type, $contentRowClasses, $autoSection);
					// Unset the row class. Follow-up contents will start a new row and with a blank row class.
					$contentRowClasses = [];
				}

				$currentWidth = 0;
				$rowContent = '';
			}

			// If the row class is set, add it to the content row class
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			if ($this->getView()->getRowClass()) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$contentRowClasses[] = $this->getView()->getRowClass();
			}

			// Add the content to the row contents
			$rowContent .= $renderedContent;

			// If the content is a finisher, render the row and reset the row contents
			if ($entity->columnLast) {
				$contentElements .= $this->renderContentRow($rowContent, $type, $contentRowClasses, $autoSection);
				// Unset the row class. Follow-up contents will start a new row and with a blank row class.
				$contentRowClasses = [];

				$currentWidth = 0;
				$rowContent = '';
			}
			else {
				// Add the width of the content to the current row width
				$currentWidth += $columnWidth;
			}

			// Clear the row class for the next content element
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$this->getView()->setRowClass('');

			DebugTimer::stop($timerName);
		}

		// Render the last row
		if ($rowContent) {
			$contentElements .= $this->renderContentRow($rowContent, $type, $contentRowClasses, $autoSection);
		}

		// Unset the row class.
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->getView()->setRowClass('');

		DebugTimer::stop('ContentElementTrait::buildContents' . $level);

		return $contentElements;
	}


	/**
	 * Render an entity as element using the provided template.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $children
	 * @return string
	 */
	abstract protected function renderElement(Entity $entity, string $children): string;


	/**
	 * @param string $contents
	 * @param string $type
	 * @param array $rowClasses
	 * @param bool $autoSection
	 * @return string
	 */
	protected function renderContentRow(string $contents, string $type = 'content', array $rowClasses = [], bool $autoSection = false): string {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->getView()->element($type === 'form_element' ? 'form_row' : 'content_row', [
			'contents' => $contents,
			'class' => implode(' ', array_unique($rowClasses)),
			'autoSection' => $autoSection,
			'type' => $type,
		]);
	}


	/**
	 * Set the cssClass property
	 * Each element gets a css class based on its template, column width and indent,
	 * as well as the cssClass property set in the database.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @return void
	 */
	protected function setCssClasses(Entity $entity): void {
		static $now = new DateTime();

		if (empty($entity->cssClass)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity->cssClass = '';
		}

		$cssClass = trim($entity->cssClass);

		$template = match (true) {
			$entity instanceof Content => 'contentTemplate',
			$entity instanceof FormElement => null,
			$entity instanceof GlobalContent => 'globalContentTemplate',
		};

		$entity->cssClass = match (true) {
			$entity instanceof Content => 'Content',
			$entity instanceof FormElement => 'Form',
			$entity instanceof GlobalContent => 'GlobalContent',
		};
		$entity->cssClass .= 'Element';
		$entity->cssClass .= ($template ? ' Template-' . Inflector::ucparts(Inflector::underscore($entity->$template->fileName), false) : '');
		$entity->cssClass .= ' ' . $entity->column['width']->getCssClass();

		if ($entity instanceof FormElement) {
			$entity->cssClass .= ' FormElementType-' . Inflector::ucparts(Inflector::underscore($entity->type), false);
			$entity->cssClass .= ' FormElement-' . Inflector::ucparts(Inflector::underscore($entity->identifier ?? (string)$entity->id), false);
		}
		elseif ($entity instanceof GlobalContent) {
			$entity->cssClass .= ' GlobalContent-' . Inflector::ucparts(Inflector::underscore($entity->identifier), false);
		}

		if ($entity->column['indent']) {
			$entity->cssClass .= ' ' . $entity->column['indent']?->getCssClass();
		}

		if ($entity->columnRtl) {
			$entity->cssClass .= ' Column-Rtl';
		}

		if ($cssClass) {
			$entity->cssClass .= ' ' . $cssClass;
		}

		if (
			!$entity->active ||
			(
				$entity->publicationStart &&
				$entity->publicationStart > $now
			) ||
			(
				$entity->publicationEnd &&
				$entity->publicationEnd < $now
			)
		) {
			$entity->cssClass .= ' ' . FrontendView::getPreviewModeElementClass();
		}
	}


	/**
	 * Set the real column width of an entity,
	 * based on the width of its parent entities.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\GlobalContent $entity
	 * @param float $columnWidth
	 * @return void
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function setRealColumnWidth(Entity $entity, float $columnWidth): void {
		$entity->setVirtual(['realColumnWidth'], true);

		$property = match (true) {
			$entity instanceof Content => 'parentContents',
			$entity instanceof FormElement => 'parentFormElements',
			$entity instanceof GlobalContent => 'parentGlobalContents',
		};

		if (!$entity->$property) {
			$entity->realColumnWidth = $columnWidth * $entity->column['width']->getFactor();

			return;
		}

		/** @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\GlobalContent $parent */
		$parent = end($entity->$property);

		$entity->realColumnWidth = round($parent->realColumnWidth * $entity->column['width']->getFactor(), 4);
	}


	/**
	 * Check if a custom template exists for a content element,
	 * based on the id.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\GlobalContent $entity
	 * @return void
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function setTemplate(Entity $entity): void {
		static $templatePath;

		// Skip form elements as they have no template
		if ($entity instanceof FormElement) {
			return;
		}

		if (!isset($templatePath)) {
			$templatePath = rtrim(Configure::read('App.paths.templates.customer'), DS);
		}

		$fileName = ($entity instanceof GlobalContent ? 'GlobalContent' : 'Content') . $entity->id;

		$filePath = implode(DS, [
			$templatePath,
			'Frontend',
			$entity instanceof GlobalContent ? 'global_content' : 'content',
			$fileName . '.twig',
		]);

		if (file_exists($filePath)) {
			$template = $entity instanceof GlobalContent ? 'globalContentTemplate' : 'contentTemplate';
			$entity->$template->set('fileName', $fileName, ['setter' => false]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @return void
	 */
	protected function applyDuplicateData(Entity $entity): void {
		// Do nothing per default
	}
}
