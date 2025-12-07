<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Module\ModulesProvider;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
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
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string $field
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpDocSignatureInspection
	 */
	public function parseModules(Entity $entity, MediaRenderOptions $mediaRenderOptions, string $field = 'text'): void {
		static $modules;

		if (!str_contains($entity->get($field) ?? '', '<module')) {
			return;
		}

		DebugTimer::start('FrontendRenderingTrait::parseModules' . $entity->id, sprintf('FrontendRenderingTrait::parseModules: Parsing modules for entity #%d field "%s"', $entity->id, $field));

		if (!isset($modules)) {
			$modules = ModulesProvider::getModuleFiles();
		}

		$dom = $this->getDom($entity->get($field));

		// Find all <module> tags
		$moduleTags = $dom->querySelectorAll('module');

		// Iterate over the <module> tags
		/** @var \Dom\HTMLElement $moduleTag */
		foreach ($moduleTags as $moduleTag) {
			// Get the value of the data-identifier attribute
			$identifier = Inflector::variable($moduleTag->getAttribute('data-identifier'));

			// Get the text content of the <module> tag
			$settings = json_decode($moduleTag->textContent ?? '', true);

			if (!isset($modules[ $identifier ])) {
				continue;
			}

			/** @var class-string<\Awyiss\Module\ModuleInterface> $moduleClass */
			$moduleClass = $modules[ $identifier ];

			DebugTimer::start('FrontendRenderingTrait::parseModule' . $identifier, sprintf('FrontendRenderingTrait::parseModule: Rendering module "%s" in entity #%d', $identifier, $entity->id));

			/** @noinspection PhpParamsInspection */
			$moduleOutput = $moduleClass::render($settings, $this->getView(), $mediaRenderOptions, $entity, LocaleMiddleware::getLanguage());

			DebugTimer::stop('FrontendRenderingTrait::parseModule' . $identifier);

			// Replace the <module> tag with the rendered output
			if (!empty($moduleOutput)) {
				$moduleTag->innerHTML = $moduleOutput;
				while ($moduleTag->firstChild) {
					$moduleTag->parentNode->insertBefore($moduleTag->firstChild, $moduleTag);
				}
			}

			$moduleTag->remove();
		}

		$entity->set($field, $this->getBody($dom));

		DebugTimer::stop('FrontendRenderingTrait::parseModules' . $entity->id);
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
