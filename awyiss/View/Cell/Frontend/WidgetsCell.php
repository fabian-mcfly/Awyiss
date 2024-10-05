<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Widget;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
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


	/**
	 * @param string $identifier
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string $identifier, array $options = []): void {
		$la_options = $this->initCellOptions($options);
		$la_options['viewVars']['identifier'] = $identifier;

		$lo_widgets = $this->getThreadedWidgets($identifier);

		$this->addMediaItems($lo_widgets, 'widgets');

		$this->prepareEntities($lo_widgets, (float)$la_options['columnWidth']);

		$this->setViewVars($la_options);

		$ls_widgets = $this->buildContents($lo_widgets->toArray());

		// Set the view variables
		$this->set([
			'fullWidth' => $la_options['fullWidth'],
			'singleColumnBreakpoint' => $la_options['singleColumnBreakpoint'],
			'widgets' => $ls_widgets,
			...$la_options['viewVars'],
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
	 */
	protected function renderElement(Entity $entity, string $children): string {
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#Widget' . $entity->id,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		// Parse the module
		$this->parseModule($entity, $lo_mediaRenderOptions);

		$ls_fullWidthMissingWarning = '';
		if (!$this->View->get('fullWidth')) {
			$ls_fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the widget cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $ls_fullWidthMissingWarning . $this->View->widget($entity->widgetTemplate->fileName, [
			'widget' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}


	/**
	 * @param string $identifier
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getThreadedWidgets(string $identifier): CollectionInterface {
		/** @var \Awyiss\Model\Table\WidgetsTable $lo_widgetsTable */
		$lo_widgetsTable = $this->fetchTable('Widgets');

		if ($this->isPreview()) {
			$lo_query = $lo_widgetsTable->find('all');
		}
		else {
			$lo_query = $lo_widgetsTable->find('active')->find('published');
		}

		$lo_query->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
		$lo_query->where([
			'Widgets.identifier' => $identifier,
		]);

		// Contain WidgetTemplates and MediaAssignments
		$lo_query->contain([
			'WidgetTemplates',
		]);

		$lo_widgets = $lo_query->all();

		/*
		 * Filter out all first level widgets with a parent_id
		 * This is done to prevent the display of nested widgets whose parent isn't
		 * part of the result set.
		 *
		 * Either because it's not active (allowed to happen)
		 * or because it's not part of the same page. (shouldn't happen)
		 */
		return $lo_widgets->filter(function (Widget $widget) {
			return $widget->parentId === null;
		})->compile();
	}
}
