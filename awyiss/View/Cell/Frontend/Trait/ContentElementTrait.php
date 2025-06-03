<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Widget;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\I18n\DateTime;


/**
 * ContentElementTrait to be used in Cells
 * for Frontend content elements (content, form elements and widgets)
 */
trait ContentElementTrait {
	use FrontendRenderingTrait;


	/**
	 * @param \Cake\Collection\CollectionInterface $entities
	 * @param float $columnWidth
	 */
	protected function prepareEntities(CollectionInterface $entities, float $columnWidth = 100.00): void {
		if (!$entities->count()) {
			return;
		}

		$lo_entities = $entities->listNested();
		$lo_lastEntity = null;
		$la_parentEntities = [];

		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $lo_entity
		 */
		foreach ($lo_entities as $lo_entity) {
			$this->applyDuplicateData($lo_entity);

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
			elseif ($lo_entity instanceof FormElement) {
				$lo_entity->parentFormElements = $la_parentEntities;
			}
			else {
				$lo_entity->parentWidgets = $la_parentEntities;
			}

			// Set the cssClass property
			$this->setCssClasses($lo_entity);

			// Seat the real column width
			$this->setRealColumnWidth($lo_entity, $columnWidth);

			// Set the template for the entity
			// Will use a custom template named "Content/Widget<id>.twig", if it exists.
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
	 * @throws \ReflectionException
	 */
	protected function buildContents(array $entities, bool $noContentRow = false): string {
		if (!$entities) {
			return '';
		}

		$ls_contentElements = '';
		$lf_currentWidth = 0;
		$ls_rowContent = '';

		/**
		 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $lo_entity
		 */
		foreach ($entities as $lo_entity) {
			$ls_children = '';

			if ($lo_entity->children) {
				$ls_children = $this->buildContents($lo_entity->children, $noContentRow);
			}

			// Render the content before determining if it should be rendered in a content row
			// This allows the template to modify the content row setting
			$ls_renderedContent = $this->renderElement($lo_entity, $ls_children);

			$lb_noContentRow = $noContentRow;
			if (!$lb_noContentRow && !$lo_entity instanceof FormElement) {
				$ls_template = $lo_entity instanceof Widget ? 'widgetTemplate' : 'contentTemplate';

				$lb_noContentRow = !$lo_entity->$ls_template->inContentRow;
				if ($lo_entity->has('inContentRow')) {
					$lb_noContentRow = !$lo_entity->inContentRow;
				}
			}
			elseif ($lo_entity instanceof FormElement && in_array($lo_entity->type, ['fieldset', 'hidden'])) {
				$lb_noContentRow = true;
			}

			/*
			 * If the template should not be rendered in a content row
			 * render the element directly, but not before rendering existing row contents.
			 */
			if ($lb_noContentRow) {
				if ($ls_rowContent) {
					$ls_contentElements .= $this->renderContentRow($ls_rowContent, $lo_entity instanceof FormElement);

					// Reset the row contents
					$lf_currentWidth = 0;
					$ls_rowContent = '';
				}

				// Render the content. Adding the width is not necessary, as the content is rendered directly.
				$ls_contentElements .= $ls_renderedContent;

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
					$ls_contentElements .= $this->renderContentRow($ls_rowContent, $lo_entity instanceof FormElement);
				}

				$lf_currentWidth = 0;
				$ls_rowContent = '';
			}

			// Add the content to the row contents
			$ls_rowContent .= $ls_renderedContent;

			// If the content is a finisher, render the row and reset the row contents
			if ($lo_entity->columnLast) {
				$ls_contentElements .= $this->renderContentRow($ls_rowContent, $lo_entity instanceof FormElement);

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
			$ls_contentElements .= $this->renderContentRow($ls_rowContent, $lo_entity instanceof FormElement);
		}


		return $ls_contentElements;
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
	 * @param bool $isFormRow
	 * @return string
	 */
	protected function renderContentRow(string $contents, bool $isFormRow = false): string {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ls_contentRow = $this->getView()->element($isFormRow ? 'form_row' : 'content_row', [
			'contents' => $contents,
			'class' => $this->getView()::$rowClass,
		]);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->getView()::$rowClass = '';

		return $ls_contentRow;
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
		static $ld_now = new DateTime();

		if (empty($entity->cssClass)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity->cssClass = '';
		}

		$ls_cssClass = trim($entity->cssClass);

		$ls_template = match (true) {
			$entity instanceof Content => 'contentTemplate',
			$entity instanceof FormElement => null,
			$entity instanceof Widget => 'widgetTemplate',
		};

		$entity->cssClass = match (true) {
			$entity instanceof Content => 'Content',
			$entity instanceof FormElement => 'Form',
			$entity instanceof Widget => 'Widget',
		};
		$entity->cssClass .= 'Element';
		$entity->cssClass .= ($ls_template ? ' Template-' . Inflector::ucparts($entity->$ls_template->fileName) : '');
		$entity->cssClass .= ' ' . $entity->column['width']->getCssClass();

		if ($entity instanceof FormElement) {
			$entity->cssClass .= ' FormElementType-' . Inflector::ucparts($entity->type, false);
			$entity->cssClass .= ' FormElement-' . Inflector::ucparts($entity->identifier ?? (string)$entity->id, false);
		}
		elseif ($entity instanceof Widget) {
			$entity->cssClass .= ' Widget-' . Inflector::ucparts($entity->identifier, false);
		}

		if ($entity->column['indent']) {
			$entity->cssClass .= ' ' . $entity->column['indent']?->getCssClass();
		}

		if ($entity->columnRtl) {
			$entity->cssClass .= ' Column-Rtl';
		}

		if ($ls_cssClass) {
			$entity->cssClass .= ' ' . $ls_cssClass;
		}

		if (
			!$entity->active ||
			($entity->publicationStart && $entity->publicationStart > $ld_now) ||
			($entity->publicationEnd && $entity->publicationEnd < $ld_now)
		) {
			$entity->cssClass .= ' ' . Awyiss::PREVIEW_MODE_ELEMENT_CLASSNAME;
		}
	}


	/**
	 * Set the real column width of an entity,
	 * based on the width of its parent entities.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $entity
	 * @param float $columnWidth
	 * @return void
	 */
	protected function setRealColumnWidth(Entity $entity, float $columnWidth): void {
		$entity->setVirtual(['realColumnWidth']);

		$ls_property = match (true) {
			$entity instanceof Content => 'parentContents',
			$entity instanceof FormElement => 'parentFormElements',
			$entity instanceof Widget => 'parentWidgets',
		};

		if (!$entity->$ls_property) {
			$entity->realColumnWidth = $columnWidth * $entity->column['width']->getFactor();

			return;
		}

		/** @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $lo_parent */
		$lo_parent = end($entity->$ls_property);

		$entity->realColumnWidth = round($lo_parent->realColumnWidth * $entity->column['width']->getFactor(), 4);
	}


	/**
	 * Check if a custom template exists for a content element,
	 * based on the id.
	 *
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $entity
	 * @return void
	 */
	protected function setTemplate(Entity $entity): void {
		static $ls_templatePath;

		// Skip form elements as they have no template
		if ($entity instanceof FormElement) {
			return;
		}

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
			$entity->$ls_template->set('fileName', $ls_fileName, ['setter' => false]);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $blocklistedKeys
	 * @return void
	 */
	protected function applyDuplicateData(Entity $entity): void {
		// Do nothing per default
	}
}
