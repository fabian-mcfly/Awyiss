<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\Widget\WidgetsProvider;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\View\View;
use Dom\HTMLDocument;


/**
 * Trait to be used in Cells
 * that work with media and texts that need parsing and rendering
 */
trait FrontendRenderingTrait {
	/**
	 * @param array $options
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	public function initCellOptions(array $options): array {
		$options += [
			'columnWidth' => 100.00,
			'includeWrapper' => false,
			'viewVars' => [],
		];

		/** @noinspection DuplicatedCode */
		if (!isset($options['fullWidth'])) {
			$options['fullWidth'] = $this->findFullWidth($options);
		}
		else {
			$options['fullWidth'] = (float)$options['fullWidth'];
		}

		$options['columnWidth'] = (float)$options['columnWidth'];

		if (!array_key_exists('singleColumnBreakpoint', $options)) {
			$options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($options);
		}
		elseif ($options['singleColumnBreakpoint'] !== null) {
			$options['singleColumnBreakpoint'] = (float)$options['singleColumnBreakpoint'];
		}

		return $options;
	}


	/**
	 * Fetch all used media items and add them to the ResizedImageManager
	 *
	 * @param \Cake\Collection\CollectionInterface $entities
	 * @param string $scope
	 * @return array<\Awyiss\Model\Entity\Media>
	 */
	protected function cacheAssignedMediaItems(CollectionInterface $entities, string $scope): array {
		DebugTimer::start('FrontendRenderingTrait::cacheAssignedMediaItems', sprintf('FrontendRenderingTrait::cacheAssignedMediaItems: Caching %d %s media items', $entities->count(), $scope));

		$entities = $entities->listNested()->compile(false);
		$entityIds = $entities->extract('id')->toArray();

		if (!$entityIds) {
			DebugTimer::stop('FrontendRenderingTrait::cacheAssignedMediaItems');
			return [];
		}

		$mediaTable = $this->fetchTable('Media');
		$query = $mediaTable->find()
			->matching('MediaAssignments', function (SelectQuery $query) use ($entityIds, $scope) {
				return $query->where([
					'MediaAssignments.foreign_key IN' => $entityIds,
					'MediaAssignments.scope' => $scope,
				]);
			})
			->contain(['MediaResizedImages'])
			->distinct('Media.id');

		$media = $query->all()->toList();

		ResizedImageManager::setMediaItems($media);

		DebugTimer::stop('FrontendRenderingTrait::cacheAssignedMediaItems');
		return $media;
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
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $fields
	 * @return void
	 * @throws \Exception
	 */
	public function parseAwyissImageTags(Entity $entity, MediaRenderOptions $mediaRenderOptions, array $fields = []): void {
		DebugTimer::start('FrontendRenderingTrait::parseAwyissImageTags' . $entity->id, sprintf('FrontendRenderingTrait::parseAwyissImageTags: Parsing image tags for entity #%d', $entity->id));

		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $imageHandlerClass */
		static $imageHandlerClass;

		if (!$imageHandlerClass) {
			$imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		$imageHandlerClass::replaceCustomImageTags($entity, $this->View, $mediaRenderOptions, $fields);

		DebugTimer::stop('FrontendRenderingTrait::parseAwyissImageTags' . $entity->id);
	}


	/**
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\GlobalContent $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string $field
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpDocSignatureInspection
	 */
	public function parseWidgets(Entity $entity, MediaRenderOptions $mediaRenderOptions, string $field = 'text'): void {
		static $widgets;

		if (!str_contains($entity->get($field) ?? '', '<widget')) {
			return;
		}

		DebugTimer::start('FrontendRenderingTrait::parseWidgets' . $entity->id, sprintf('FrontendRenderingTrait::parseWidgets: Parsing widgets for entity #%d field "%s"', $entity->id, $field));

		if (!isset($widgets)) {
			$widgets = WidgetsProvider::getWidgetFiles();
		}

		$dom = $this->getDom($entity->get($field));

		// Find all <widget> tags
		$widgetTags = $dom->querySelectorAll('widget');

		// Iterate over the <widget> tags
		/** @var \Dom\HTMLElement $widgetTag */
		foreach ($widgetTags as $widgetTag) {
			// Get the value of the data-identifier attribute
			$identifier = Inflector::variable($widgetTag->getAttribute('data-identifier'));

			// Get the text content of the <widget> tag
			$settings = json_decode($widgetTag->textContent ?? '', true);

			if (!isset($widgets[ $identifier ])) {
				continue;
			}

			/** @var class-string<\Awyiss\Widget\WidgetInterface> $widgetClass */
			$widgetClass = $widgets[ $identifier ];

			DebugTimer::start('FrontendRenderingTrait::parseWidget' . $identifier, sprintf('FrontendRenderingTrait::parseWidget: Rendering widget "%s" in entity #%d', $identifier, $entity->id));

			/** @noinspection PhpParamsInspection */
			$widgetOutput = $widgetClass::render($settings, $this->getView(), $mediaRenderOptions, $entity, LocaleMiddleware::getLanguage());

			DebugTimer::stop('FrontendRenderingTrait::parseWidget' . $identifier);

			// Replace the <widget> tag with the rendered output
			if (!empty($widgetOutput)) {
				$widgetTag->innerHTML = $widgetOutput;
				while ($widgetTag->firstChild) {
					$widgetTag->parentNode->insertBefore($widgetTag->firstChild, $widgetTag);
				}
			}

			$widgetTag->remove();
		}

		$entity->set($field, $this->getBody($dom));

		DebugTimer::stop('FrontendRenderingTrait::parseWidgets' . $entity->id);
	}


	/**
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
	 * @return \Cake\View\View
	 */
	protected function getView(): View {
		if (!isset($this->View)) {
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			$this->View = $this->createView('Frontend');
		}

		return $this->View;
	}


	/**
	 * @param string|null $text
	 * @return \Dom\HTMLDocument
	 */
	protected function getDom(?string $text): HTMLDocument {
		return HTMLDocument::createFromString($text, LIBXML_NOERROR, 'UTF-8');
	}


	/**
	 * Returns the contents of `<body>`-tag of the given \Dom\HTMLDocument as a string
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return string|false
	 */
	protected function getBody(HTMLDocument $dom): string|false {
		$html = '';

		// Remove the opening and closing `<body>`-tags
		$body = $dom->querySelector('body');

		while ($body->firstChild) {
			$html .= $dom->saveHTML($body->firstChild);
			$body->removeChild($body->firstChild);
		}

		// Return the cleaned HTML
		return $html;
	}
}
