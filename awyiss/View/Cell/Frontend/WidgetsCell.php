<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity\Widget;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\Cell;


/**
 * Widgets Cell
 *
 * Outputs the widgets for a given identifier
 */
class WidgetsCell extends Cell {
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 */
	public function __construct(ServerRequest $request, Response $response, ?EventManagerInterface $eventManager = null, array $cellOptions = []) {
		parent::__construct($request, $response, $eventManager, $cellOptions);

		$this->view = new FrontendView($request, $response);
	}


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

		$this->view->set([
			'identifier' => $identifier,
			...$la_options['viewVars'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Widgets');

		$lo_widgetsTable = $this->fetchTable('Widgets');

		$lo_query = $lo_widgetsTable->find('active')->find('threaded');
		$lo_query->where([
			'Widgets.identifier' => $identifier,
		]);

		// Contain WidgetTemplates and MediaAssignments
		$lo_query->contain([
			'WidgetTemplates',
			'MediaAssignments',
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

		$this->prepareWidgets($lo_widgets, (float)$la_options['columnWidth']);

		$ls_widgets = $this->buildWidget($lo_widgets->toArray());

		// Set the view variables
		$this->set([
			'identifier' => $identifier,
			'widgets' => $ls_widgets,
			...$la_options['viewVars'],
		]);
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $widgets
	 * @param float $columnWidth
	 */
	protected function prepareWidgets(CollectionInterface $widgets, float $columnWidth = 100.00): void {
		if (!$widgets->count()) {
			return;
		}

		$lo_widgets = $widgets->listNested();
		$lo_lastWidget = null;
		$la_parentWidgets = [];

		/** @var \Awyiss\Model\Entity\Widget $lo_widget */
		foreach ($lo_widgets as $lo_widget) {
			$lo_widget->setVirtual(['level']);
			//Add the current depth as a level-property to the entity
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_widget->level = $lo_widgets->getDepth();

			// Remember the parent widgets
			if ($lo_lastWidget) {
				if ($lo_lastWidget->level < $lo_widget->level) {
					$la_parentWidgets[] = $lo_lastWidget;
				}
				elseif ($lo_lastWidget->level > $lo_widget->level) {
					$la_parentWidgets = array_slice($la_parentWidgets, 0, $lo_widget->level);
				}
			}

			$lo_widget->parentWidgets = $la_parentWidgets;

			// Set the cssClass property
			$this->setCssClasses($lo_widget);

			// Seat the real column width
			$this->setRealColumnWidth($lo_widget, $columnWidth);

			// Set the template for the widget
			// Will use a custom template named "Widget<id>.twig", if it exists.
			$this->setTemplate($lo_widget);

			$lo_lastWidget = $lo_widget;
		}
	}


	/**
	 * Render all provided widgets, including children
	 *
	 * @param array $widgets
	 * @return string
	 */
	protected function buildWidget(array $widgets): string {
		if (!$widgets) {
			return '';
		}

		$ls_widgets = '';
		$lf_currentWidth = 0;
		$ls_rowContents = '';

		/**
		 * @var \Awyiss\Model\Entity\Widget $lo_widget
		 */
		foreach ($widgets as $lo_widget) {
			$ls_children = '';

			if ($lo_widget->children) {
				$ls_children = $this->buildWidget($lo_widget->children);
			}

			/*
			 * If the widget template should not be rendered in a content row
			 * render the widget directly, but not before rendering existing row widgets.
			 */
			if (!$lo_widget->widgetTemplate->inContentRow) {
				if ($ls_rowContents) {
					$ls_widgets .= $this->renderContentRow($ls_rowContents);

					// Reset the row widgets
					$lf_currentWidth = 0;
					$ls_rowContents = '';
				}

				// Render the widget. Adding the width is not necessary, as the widget is rendered directly.
				$ls_widgets .= $this->renderWidget($lo_widget, $ls_children);

				continue;
			}

			// Get the real column width of the widget
			$lf_columnWidth = 100 * $lo_widget->column['width']->getFactor();

			// If the widget has a column indent, add the width of the indent to the current column width
			if ($lo_widget->column['indent']) {
				$lf_columnWidth += 100 * $lo_widget->column['indent']->getFactor();
			}

			/*
			 * Before rendering the widget, check if the content row is full,
			 * or if the widget - including potential indentation - would exceed the row width.
			 * If that is the case, render the current row and reset the row widgets.
			 */
			if ($lf_currentWidth >= 100 || $lf_currentWidth + $lf_columnWidth >= 100) {
				if ($ls_rowContents) {
					$ls_widgets .= $this->renderContentRow($ls_rowContents);
				}

				$lf_currentWidth = 0;
				$ls_rowContents = '';
			}

			// Add the widget to the row widgets
			$ls_rowContents .= $this->renderWidget($lo_widget, $ls_children);

			// If the widget is a finisher, render the row and reset the row widgets
			if ($lo_widget->columnLast) {
				$ls_widgets .= $this->renderContentRow($ls_rowContents);

				$lf_currentWidth = 0;
				$ls_rowContents = '';
			}
			else {
				// Add the width of the widget to the current row width
				$lf_currentWidth += $lf_columnWidth;
			}
		}

		// Render the last row
		if ($ls_rowContents) {
			$ls_widgets .= $this->renderContentRow($ls_rowContents);
		}


		return $ls_widgets;
	}


	/**
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @param string $children
	 * @return string
	 */
	protected function renderWidget(Widget $widget, string $children): string {
		$ls_widget = $this->view->widget($widget->widgetTemplate->fileName, [
			'widget' => $widget,
			'children' => $children,
		]);

		return $ls_widget;
	}


	/**
	 * @param string $widgets
	 * @return string
	 */
	protected function renderContentRow(string $widgets): string {
		$ls_contentRow = $this->view->element('content_row', [
			'contents' => $widgets,
			'class' => $this->view::$rowClass,
		]);

		$this->view::$rowClass = '';

		return $ls_contentRow;
	}


	/**
	 * Set the real column width of a widget,
	 * based on the width of its parent widget.
	 *
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @param float $columnWidth
	 * @return void
	 */
	protected function setRealColumnWidth(Widget $widget, float $columnWidth): void {
		$widget->setVirtual(['realColumnWidth']);

		if (!$widget->parentWidgets) {
			$widget->realColumnWidth = $columnWidth * $widget->column['width']->getFactor();

			return;
		}

		/**
		 * @var \Awyiss\Model\Entity\Widget $lo_parentWidget
		 */
		$lo_parentWidget = end($widget->parentWidgets);

		$widget->realColumnWidth = $lo_parentWidget->realColumnWidth * $widget->column['width']->getFactor();
	}


	/**
	 * Set the cssClass property
	 * Each widget element gets a css class based on its widget template, column width and indent,
	 * as well as the cssClass property set in the database.
	 *
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @return void
	 */
	protected function setCssClasses(Widget $widget): void {
		if (empty($widget->cssClass)) {
			$widget->cssClass = '';
		}

		$ls_cssClass = trim($widget->cssClass);

		$widget->cssClass = 'WidgetElement Template-' . Inflector::ucparts($widget->widgetTemplate->fileName);
		$widget->cssClass .= ' ' . $widget->column['width']->getCssClass();

		if ($widget->column['indent']) {
			$widget->cssClass .= ' ' . $widget->column['indent']?->getCssClass();
		}

		if ($widget->columnRtl) {
			$widget->cssClass .= ' Column-Rtl';
		}

		if ($ls_cssClass) {
			$widget->cssClass .= ' ' . $ls_cssClass;
		}
	}


	/**
	 * Check if a custom template exists for a widget element,
	 * based on the widget id.
	 *
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @return void
	 */
	protected function setTemplate(Widget $widget): void {
		static $ls_templatePath;

		if (!isset($ls_templatePath)) {
			$ls_templatePath = rtrim(Configure::read('App.paths.templates.customer'), DS);
		}

		$ls_fileName = 'Widget' . $widget->id;

		$ls_filePath = implode(DS, [
			$ls_templatePath,
			'Frontend',
			'widget',
			$ls_fileName . '.twig',
		]);

		if (file_exists($ls_filePath)) {
			$widget->widgetTemplate->fileName = $ls_fileName;
		}
	}
}
