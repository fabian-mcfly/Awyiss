<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Widget;
use Awyiss\Routing\Router;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\View\Cell;


/**
 * Widgets Cell
 *
 * Outputs the widgets for a given identifier
 */
class WidgetsCell extends Cell {
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
		$this->View = $view;

		$options = $this->initCellOptions($options);
		$options['viewVars']['identifier'] = $identifier;

		$widgets = $this->getThreadedWidgets($identifier, $this->isPreview());

		$this->cacheAssignedMediaItems($widgets, 'widgets');

		$this->prepareEntities($widgets, (float)$options['columnWidth']);

		$this->setViewVars($options);

		$renderedWidgets = $this->buildContents($widgets->toArray());

		$currentRoute = Router::url($this->request->getRequestTarget());
		if ($renderedWidgets && $currentRoute !== '/') {
			// Replace all `href="#anchor"` with `href="<currentRoute>#anchor"`
			$renderedWidgets = preg_replace('/href=[\'"](#[^\'"]+)[\'"]/', 'href="' . ltrim($currentRoute, '/') . '$1"', $renderedWidgets);
		}

		// Set the view variables
		$this->set([
			'fullWidth' => $options['fullWidth'],
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
			'widgets' => $renderedWidgets,
			...$options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Widgets');
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param string $children
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function renderElement(Entity $entity, string $children): string {
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$mediaRenderOptions = $this->getView()->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->getView()->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#Widget' . $entity->id,
			singleColumnBreakpoint: $this->getView()->get('singleColumnBreakpoint'),
		);

		// Parse the module
		$this->parseAwyissImageTags($entity, $mediaRenderOptions);

		// Parse the module
		$this->parseModule($entity, $mediaRenderOptions);

		$fullWidthMissingWarning = '';
		if (!$this->getView()->get('fullWidth')) {
			$fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the widget cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $fullWidthMissingWarning . $this->getView()->widget($entity->widgetTemplate->fileName, [
			'widget' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);
	}


	/**
	 * @param string $identifier
	 * @param bool $isPreview
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getThreadedWidgets(string $identifier, bool $isPreview = false): CollectionInterface {
		/** @var \Awyiss\Model\Table\WidgetsTable $widgetsTable */
		$widgetsTable = $this->fetchTable('Widgets');

		if ($isPreview) {
			$query = $widgetsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $widgetsTable->find('active')->find('published');
		}

		$query->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
		$query->where([
			'Widgets.identifier' => $identifier,
		]);

		// Contain WidgetTemplates and MediaAssignments
		$query->contain([
			'WidgetTemplates',
		]);

		$widgets = $query->all();

		/*
		 * Filter out all first level widgets with a parent_id
		 * This is done to prevent the display of nested widgets whose parent isn't
		 * part of the result set.
		 *
		 * Either because it's not active (allowed to happen)
		 * or because it's not part of the same page. (shouldn't happen)
		 */
		return $widgets->filter(function (Widget $widget) {
			return $widget->parentId === null;
		})->compile();
	}
}
