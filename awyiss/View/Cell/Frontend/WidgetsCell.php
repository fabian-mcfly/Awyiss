<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Widget;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
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
		elseif ($la_options['fullWidth'] !== null) {
			$la_options['fullWidth'] = (float)$la_options['fullWidth'];
		}

		$this->view->set([
			'identifier' => $identifier,
			...$la_options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Widgets');

		$lo_widgetsTable = $this->fetchTable('Widgets');

		$lo_query = $lo_widgetsTable->find('active')->find('threaded')->find('mediaAssignments', includeElementSelector: true);
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

		$this->prepareEntities($lo_widgets, (float)$la_options['columnWidth'], $la_options['fullWidth']);

		$ls_widgets = $this->buildContents($lo_widgets->toArray());

		// Set the view variables
		$this->set([
			'fullWidth' => $la_options['fullWidth'],
			'identifier' => $identifier,
			'widgets' => $ls_widgets,
			...$la_options['viewVars'],
		]);
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param string $children
	 * @return string
	 */
	protected function renderContentElement(Entity $entity, string $children): string {
		return $this->view->widget($entity->widgetTemplate->fileName, [
			'widget' => $entity,
			'children' => $children,
		]);
	}
}
