<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Routing\Router;
use Awyiss\Utility\DebugTimer;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\View\Cell;


/**
 * GlobalContents Cell
 *
 * Outputs the global_contents for a given identifier
 */
class GlobalContentsCell extends Cell {
	use ContentElementTrait;
	use PreviewTrait;
	use RedirectAwareTrait;
	use RenderTrimmedTrait;


	/**
	 * @param string $identifier
	 * @param \Awyiss\View\FrontendView $view
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string $identifier, FrontendView $view, array $options = []): void {
		DebugTimer::start('GlobalContentsCell::display', sprintf('GlobalContentsCell::display: Rendering global contents area "%s"', $identifier));

		$this->View = $view;

		$options = $this->initCellOptions($options);
		$options['viewVars']['identifier'] = $identifier;

		$globalContents = $this->getThreadedGlobalContents($identifier, $this->isPreview());

		$this->cacheAssignedMediaItems($globalContents, 'global_contents');

		$this->prepareEntities($globalContents, (float)$options['columnWidth']);

		$this->setViewVars($options);

		$renderedGlobalContents = $this->buildContents($globalContents->toArray());

		$currentRoute = Router::url($this->request->getRequestTarget());
		if ($renderedGlobalContents && $currentRoute !== '/') {
			// Replace all `href="#anchor"` with `href="<currentRoute>#anchor"`
			$renderedGlobalContents = preg_replace('/href=[\'"](#[^\'"]+)[\'"]/', 'href="' . ltrim($currentRoute, '/') . '$1"', $renderedGlobalContents);
		}

		// Set the view variables
		$this->set([
			'fullWidth' => $options['fullWidth'],
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
			'global_contents' => $renderedGlobalContents,
			...$options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/GlobalContents');

		DebugTimer::stop('GlobalContentsCell::display');
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\GlobalContent $entity
	 * @param string $children
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function renderElement(Entity $entity, string $children): string {
		DebugTimer::start('GlobalContentsCell::renderElement' . $entity->id, sprintf('GlobalContentsCell::renderElement: Rendering global content #%d with template "%s"', $entity->id, $entity->globalContentTemplate->fileName));

		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$mediaRenderOptions = $this->getView()->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->getView()->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#GlobalContent' . $entity->id,
			singleColumnBreakpoint: $this->getView()->get('singleColumnBreakpoint'),
		);

		// Parse the Awyiss image tags
		$this->parseAwyissImageTags($entity, $mediaRenderOptions);

		// Parse the widgets
		$this->parseWidgets($entity, $mediaRenderOptions);

		$fullWidthMissingWarning = '';
		if (!$this->getView()->get('fullWidth')) {
			$fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the global content cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$result = $fullWidthMissingWarning . $this->getView()->globalContent($entity->globalContentTemplate->fileName, [
			'globalContent' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);

		DebugTimer::stop('GlobalContentsCell::renderElement' . $entity->id);

		return $result;
	}


	/**
	 * @param string $identifier
	 * @param bool $isPreview
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getThreadedGlobalContents(string $identifier, bool $isPreview = false): CollectionInterface {
		DebugTimer::start('GlobalContentsCell::getThreadedGlobalContents', sprintf('GlobalContentsCell::getThreadedGlobalContents: Loading global_contents for identifier "%s"', $identifier));

		/** @var \Awyiss\Model\Table\GlobalContentsTable $globalContentsTable */
		$globalContentsTable = $this->fetchTable('GlobalContents');

		if ($isPreview) {
			$query = $globalContentsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $globalContentsTable
				->find('accessible')
				->find('active')
				->find('published');
		}

		$query->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
		$query->where([
			'GlobalContents.identifier' => $identifier,
		]);

		// Contain GlobalContentTemplates and MediaAssignments
		$query->contain([
			'GlobalContentTemplates',
		]);

		$globalContents = $query->all();

		/*
		 * Filter out all first level global_contents with a parent_id
		 * This is done to prevent the display of nested global_contents whose parent isn't
		 * part of the result set.
		 *
		 * Either because it's not active (allowed to happen)
		 * or because it's not part of the same page. (shouldn't happen)
		 */
		$globalContents = $globalContents->filter(function (GlobalContent $globalContent) {
			return $globalContent->parentId === null;
		})->compile();

		DebugTimer::stop('GlobalContentsCell::getThreadedGlobalContents');

		return $globalContents;
	}
}
