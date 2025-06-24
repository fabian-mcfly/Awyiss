<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Module\ModulesProvider;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Cake\Collection\CollectionInterface;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Query\SelectQuery;
use Cake\View\View;
use DOMDocument;
use DOMXPath;


/**
 * Trait to be used in Cells
 * that work with media and texts that need parsing and rendering
 */
trait FrontendRenderingTrait {
	/**
	 * @inheritDoc
	 * @noinspection PhpMultipleClassDeclarationsInspection
	 */
	public function __construct(ServerRequest $request, Response $response, ?EventManagerInterface $eventManager = null, array $cellOptions = []) {
		parent::__construct($request, $response, $eventManager, $cellOptions);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->View = $this->createView('Frontend');
	}


	/**
	 * @param array $options
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

		$la_options['columnWidth'] = (float)$la_options['columnWidth'];

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
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $fields
	 * @return void
	 * @throws \Exception
	 */
	public function parseAwyissImageTags(Entity $entity, MediaRenderOptions $mediaRenderOptions, array $fields = []): void {
		/** @var class-string<\Awyiss\Utility\Content\ImageHandler> $ls_imageHandlerClass */
		static $ls_imageHandlerClass;

		if (!$ls_imageHandlerClass) {
			$ls_imageHandlerClass = App::className('ImageHandler', 'Utility/Content');
		}

		$ls_imageHandlerClass::replaceCustomImageTags($entity, $this->View, $mediaRenderOptions, $fields);
	}


	/**
	 * @param \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\FormElement|\Awyiss\Model\Entity\Widget $entity
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string $field
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpDocSignatureInspection
	 */
	public function parseModule(Entity $entity, MediaRenderOptions $mediaRenderOptions, string $field = 'text'): void {
		static $la_modules;

		if (!str_contains($entity->get($field) ?? '', '<module')) {
			return;
		}

		if (!isset($la_modules)) {
			$la_modules = ModulesProvider::getModuleFiles();
		}

		$lo_dom = $this->getDom($entity->get($field));

		// Create an XPath instance
		$lo_xpath = new DOMXPath($lo_dom);

		// Find all <module> tags
		$lo_moduleTags = $lo_xpath->query('//module');

		// Iterate over the <module> tags
		/** @var \DOMElement $lo_moduleTag */
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

			// Re-encode the html entities. Otherwise, calling `saveHTML()`
			// would include unencoded html entities like `ü` instead of `&uuml;`.
			$lo_moduleTag->nodeValue = htmlentities($lo_moduleTag->textContent ?? '', ENT_NOQUOTES, 'UTF-8', false);

			if ($ls_moduleOutput) {
				$entity->set($field, str_replace($lo_moduleTag->ownerDocument->saveHTML($lo_moduleTag), $ls_moduleOutput, $entity->get($field)));
			}
			else {
				$entity->set($field, str_replace($lo_moduleTag->ownerDocument->saveHTML($lo_moduleTag), '', $entity->get($field)));
			}
		}
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
		$lo_dom->loadHTML('<!DOCTYPE html>' . mb_encode_numericentity($text, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

		// Clear any errors collected during loadHTML
		libxml_clear_errors();

		return $lo_dom;
	}
}
