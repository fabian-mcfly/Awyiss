<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Widget;
use Awyiss\Module\ModulesProvider;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\View\View;
use DOMDocument;
use DOMXPath;


/**
 * ContentElementTrait to be used in Cells
 * for Frontend content elements (content, form elements and widgets)
 */
trait ContentElementTrait {
	/**
	 * @inheritDoc
	 */
	public function __construct(ServerRequest $request, Response $response, ?EventManagerInterface $eventManager = null, array $cellOptions = []) {
		parent::__construct($request, $response, $eventManager, $cellOptions);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->View = $this->createView('Frontend');
	}


	/**
	 * @param array $options
	 * @param string $languageShortcode
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	public function initCellOptions(array $options): array {
		$la_options = $options + [
			'columnWidth' => 100.00,
			'includeWrapper' => true,
			'viewVars' => [],
		];

		/** @noinspection DuplicatedCode */
		if (!isset($la_options['fullWidth'])) {
			$la_options['fullWidth'] = $this->findFullWidth($la_options);
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

		return $la_options;
	}


	/**
	 * Fetch all used media items and add them to the ResizedImageManager
	 *
	 * @param \Cake\Collection\CollectionInterface $entities
	 * @param string $scope
	 * @return array<\Awyiss\Model\Entity\Media>
	 */
	protected function cacheAssignedMediaItems(CollectionInterface $entities, string $scope): array {
		$lo_entities = $entities->listNested()->compile(false);
		$la_entityIds = $lo_entities->extract('id')->toArray();

		if (!$la_entityIds) {
			return [];
		}

		$lo_mediaTable = $this->fetchTable('Media');
		$ls_scope = $scope;
		$lo_query = $lo_mediaTable->find()->matching('MediaAssignments', function (SelectQuery $query) use ($la_entityIds, $ls_scope) {
			return $query->where([
				'MediaAssignments.foreign_key IN' => $la_entityIds,
				'MediaAssignments.scope' => $ls_scope,
			]);
		})
		->contain(['MediaResizedImages'])
		->distinct('Media.id');

		$la_media = $lo_query->all()->toList();

		ResizedImageManager::setMediaItems($la_media);

		return $la_media;
	}


	/**
	 * Attempt to find the width of the page by
	 * - checking the view vars for a page width
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findFullWidth(array $options): ?float {
		if (!empty($options['viewVars']['fullWidth'])) {
			return (float)$options['viewVars']['fullWidth'];
		}

		if (!empty($options['viewVars']['designSettings']['pageWidth'])) {
			return (float)$options['viewVars']['designSettings']['pageWidth'];
		}

		return null;
	}


	/**
	 * Attempt to find the single column breakpoint by
	 * - checking the view vars for a single column breakpoint setting
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findSingleColumnBreakpoint(array $options): ?float {
		if (isset($options['viewVars']['singleColumnBreakpoint'])) {
			return (float)$options['viewVars']['singleColumnBreakpoint'];
		}

		if (isset($options['viewVars']['designSettings']['singleColumnBreakpoint'])) {
			return (float)$options['viewVars']['designSettings']['singleColumnBreakpoint'];
		}

		return null;
	}


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
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return void
	 * @throws \Exception
	 */
	public function parseResponsiveImageTags(Entity $entity, MediaRenderOptions $mediaRenderOptions): void {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		$ls_imageHandlerClass::replaceCustomImageTags($entity, $this->View, $mediaRenderOptions);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function parseModule(Entity $entity, MediaRenderOptions $mediaRenderOptions): void {
		static $la_modules;

		if (!str_contains($entity->text ?? '', '<module')) {
			return;
		}

		if (!isset($la_modules)) {
			$la_modules = ModulesProvider::getModuleFiles();
		}

		$lo_dom = $this->getDom($entity->text);

		// Create an XPath instance
		$lo_xpath = new DOMXPath($lo_dom);

		// Find all <module> tags
		$lo_moduleTags = $lo_xpath->query('//module');

		// Iterate over the <module> tags
		foreach ($lo_moduleTags as $lo_moduleTag) {
			// Get the value of the data-identifier attribute
			$ls_identifier = Inflector::variable($lo_moduleTag->getAttribute('data-identifier'));

			// Get the text content of the <module> tag
			$la_settings = json_decode($lo_moduleTag->textContent ?? '', true);

			if (!isset($la_modules[ $ls_identifier ])) {
				continue;
			}

			/** @var class-string<\Awyiss\Module\ModuleInterface> $ls_moduleClass */
			$ls_moduleClass = $la_modules[ $ls_identifier ];

			/** @noinspection PhpParamsInspection */
			$ls_moduleOutput = $ls_moduleClass::render($la_settings, $this->getView(), $mediaRenderOptions, $entity, LocaleMiddleware::getLanguage());

			if ($ls_moduleOutput) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$entity->text = str_replace($lo_moduleTag->ownerDocument->saveHTML($lo_moduleTag), $ls_moduleOutput, $entity->text);
			}
			else {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$entity->text = str_replace($lo_moduleTag->ownerDocument->saveHTML($lo_moduleTag), '', $entity->text);
			}
		}
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
	 * @param string $identifier
	 * @param array $options
	 * @return void
	 */
	protected function setViewVars(array $options): void {
		$this->getView()->set([
			...$options['viewVars'],
			'fullWidth' => $options['fullWidth'],
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $blocklistedKeys
	 * @return void
	 */
	protected function applyDuplicateData(Entity $entity): void {
		// Do nothing per default
	}


	/**
	 * @param string $tableName
	 * @return \Cake\ORM\Table
	 */
	protected function getView(): View {
		return $this->View;
	}


	/**
	 * @param string|null $text
	 * @return \DOMDocument
	 */
	protected function getDom(?string $text): DOMDocument {
		// Create a new DOMDocument instance
		$lo_dom = new DOMDocument('1.0', 'UTF-8');

		// Suppress errors due to malformed HTML
		libxml_use_internal_errors(true);

		// Load the HTML string into the DOMDocument
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_dom->loadHTML('<!DOCTYPE html>' . $text);

		// Clear any errors collected during loadHTML
		libxml_clear_errors();

		return $lo_dom;
	}
}
