<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Widget;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Cake\Core\Configure;
use Cake\View\Cell;
use RuntimeException;


/**
 * Widgets Cell
 *
 * Outputs the widgets for a given identifier
 */
class WidgetsCell extends Cell {
	use ContentElementTrait;


	/**
	 * @param string $identifier
	 * @param array $options
	 * @return void
	 */
	public function display(string $identifier, array $options = []): void {
		$la_options = $options + [
			'columnWidth' => 100.00,
			'viewVars' => [],
		];

		if (!isset($la_options['fullWidth'])) {
			$la_options['fullWidth'] = $this->findFullWidth($la_options);

			if ($la_options['fullWidth'] === null) {
				throw new RuntimeException('Cannot determine page width. Please provide a page width when rendering widgets');
			}
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

		$this->View->set([
			'fullWidth' => $la_options['fullWidth'],
			'identifier' => $identifier,
			'singleColumnBreakpoint' => $la_options['singleColumnBreakpoint'],
			...$la_options['viewVars'],
		]);

		$lo_widgetsTable = $this->fetchTable('Widgets');

		$lo_query = $lo_widgetsTable->find('active')->find('published')->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
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
		$lo_widgets = $lo_widgets->filter(function (Widget $widget) {
			return $widget->parentId === null;
		})->compile();

		$this->addMediaItems($lo_widgets, 'widgets');

		$this->prepareEntities($lo_widgets, (float)$la_options['columnWidth']);

		$ls_widgets = $this->buildContents($lo_widgets->toArray());

		// Set the view variables
		$this->set([
			'fullWidth' => $la_options['fullWidth'],
			'identifier' => $identifier,
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
	 */
	protected function renderContentElement(Entity $entity, string $children): string {
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth'),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			columnWidth: $entity->realColumnWidth,
			selector: '#Widget' . $entity->id,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->View->widget($entity->widgetTemplate->fileName, [
			'widget' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}
}
