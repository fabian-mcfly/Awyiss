<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Widget;
use Awyiss\Model\Entity\WidgetTemplate;
use Awyiss\Model\Entity\WidgetTemplateElement;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * Widgets Controller
 *
 * @property \Awyiss\Model\Table\WidgetsTable $Widgets
 */
class WidgetsController extends Controller {
	/**
	 * @var string|null Session identifier for the selected identifier
	 */
	protected ?string $selectedIdentifierSessionIdentifier = null;
	/**
	 * @var string|null Session identifier for the selected parent_id
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $widgetTemplates;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedWidgets;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->selectedIdentifierSessionIdentifier = Inflector::underscore($this->getName()) . '.' . ($this->request->getParam('lang') ?? 'global') . '.identifier';
		$this->selectedParentIdSessionIdentifier = Inflector::underscore($this->getName()) . '.' . ($this->request->getParam('lang') ?? 'global') . '.parent_id';
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Widgets->find('mediaAssignments', useMediaEntity: true)->where($this->getOverviewWhere())->contain(['WidgetTemplates']);
		$this->Search->filterQuery($lo_query);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		$lo_widgets = $lo_query->formatResults(function (Collection $result): Collection {
			/** @var \Awyiss\Model\Entity\Widget $lo_widget */
			foreach ($result as $lo_widget) {
				$lo_widget->class = $lo_widget->column['width']->getCssClass();

				if ($lo_widget->column['indent']) {
					$lo_widget->class .= ' ' . $lo_widget->column['indent']->getCssClass();
				}

				if ($lo_widget->columnRtl) {
					$lo_widget->class .= ' Column-RTL';
				}

				if ($lo_widget->columnLast) {
					$lo_widget->class .= ' Column-Last';
				}
			}

			return $result;
		})->find('threaded')->all();

		$la_widgets = $lo_widgets->groupBy('identifier')->toArray();
		ksort($la_widgets);

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $ls_columnSystemClass */
		$ls_columnSystemClass = $this->Widgets->getColumnSystemClass();

		$la_widgetTemplates = $this->getWidgetTemplates()->indexBy('id')->toArray();
		array_map(function (WidgetTemplate $widgetTemplate) {
			// Build an array of assigned widget elements, indexed by their identifier
			$widgetTemplate->widgetTemplateElements = collection($widgetTemplate->widgetTemplateElements)->indexBy('identifier')->toArray();
		}, $la_widgetTemplates);

		$this->set([
			'widgets' => $la_widgets,
			'widgetTemplates' => $la_widgetTemplates,
			'columnWidths' => $this->Widgets->getColumnWidths(),
			'columnIndents' => $this->Widgets->getColumnIndents(),
			'columnSystemName' => $ls_columnSystemClass::getName(),
			'attributes' => $this->Widgets->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$lo_session = $this->request->getSession();
		$lo_widget = $this->Widgets->newDefaultEntity([
			'identifier' => $lo_session->read($this->selectedIdentifierSessionIdentifier),
			'parentId' => $lo_session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_widget);
		}

		$this->setViewVars($lo_widget);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var Widget $lo_widget
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$lo_widget = $this->Widgets->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_widget) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_widget, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this->viewBuilder()
			->setTemplate('edit_frontend_editor')
			->setLayout('frontend_editor');
		}

		$this->setViewVars($lo_widget);

		/** @noinspection PhpUndefinedMethodInspection */
		$this->set('auditDataCount', $this->Widgets->countAuditData($lo_widget));
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Widget $lo_widget */
		$lo_widget = $this->Widgets->findById($id)->first();
		if (!$lo_widget) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Widgets->delete($lo_widget)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_widget->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Save the column width of one widget.
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function saveColumnWidth(): void {
		$this->Authorization->ensure('read');

		$lo_request = Router::getRequest();

		/** @var Widget $lo_widget */
		$lo_widget = $this->Widgets->findById($lo_request->getData('id'))->first();
		if (!$lo_widget) {
			if ($this->request->accepts('application/json')) {
				$this->viewBuilder()->setOption('serialize', ['success', 'message']);

				$this->set('success', false);
				$this->set('message', __('record_not_found'));

				// Set the view class to JSON
				$this->viewBuilder()->setClassName('Json');

				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(404, 'Record not found');
			}
			else {
				$this->Flash->error(__('record_not_found'));

				throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
			}
		}


		$lo_widget->set('columnWidth', $lo_request->getData('width'));

		$this->Widgets->save($lo_widget);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$lo_widget->hasErrors());
			$this->set('message', !$lo_widget->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($lo_widget->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$lo_widget->hasErrors()) {
				$this->Flash->success(__('edit_succeeded'));
			}
			else {
				$this->Flash->error(__('edit_failed'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function saveSystemOrder(): void {
		$lo_request = Router::getRequest();
		$lo_table = $this->Widgets;

		$li_affectedRows = $this->_saveSystemOrder($lo_request->getData('order'), $lo_table);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', true);
			$this->set('message', $li_affectedRows > 0 ? __d('system', 'system_order_saved') : __d('system', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}
		else {
			if ($li_affectedRows) {
				$this->Flash->success(__d('system', 'system_order_saved'));
			}
			else {
				$this->Flash->error(__d('system', 'system_order_not_saved'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * @param Widget $widget
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Widget $widget, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Widgets->hasAttributes()) {
			$la_associated[] = $this->Widgets->getAttributesTableName(true);
			$widget->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		$la_data = $this->formatDataAttributes($la_data);

		$this->Widgets->patchEntity($widget, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$this->unsetUnassignedElements($widget);

			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Widgets->save($widget, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$lo_session = $this->request->getSession();
				$lo_session->write($this->selectedIdentifierSessionIdentifier, $widget->identifier);
				$lo_session->write($this->selectedParentIdSessionIdentifier, $widget->parentId);

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $widget->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($widget->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		else {
			if ($this->Widgets->hasDirtyRelatedSystemOrderColumns($widget)) {
				$widget->systemOrder = null;
			}
			else {
				$widget->systemOrder = $widget->hasOriginal('systemOrder') ? $widget->getOriginal('systemOrder') : $widget->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $widget->systemOrder);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 * @throws \Exception
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		$this->Authorization->ensure('read');

		// Create a flat array of all order data
		$la_orderData = [];
		foreach ($requestData as $la_itemsByIdentifier) {
			foreach ($la_itemsByIdentifier as $li_parentId => $la_items) {
				array_map(function (array $item) use (&$la_orderData, $li_parentId) {
					$la_orderData[] = $item + ['parentId' => $li_parentId ?: null];
				}, $la_items);
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $table->updateAll(function (QueryExpression $expression) use ($la_orderData) {
			$lo_identifierCase = $expression->case();
			$lo_parentCase = $expression->case();
			$lo_systemOrderCase = $expression->case();

			foreach ($la_orderData as $la_data) {
				$lo_identifierCase->when(['id' => $la_data['id']])->then($la_data['identifier'], 'string');
				$lo_parentCase->when(['id' => $la_data['id']])->then($la_data['parentId'], 'integer');
				$lo_systemOrderCase->when(['id' => $la_data['id']])->then($la_data['systemOrder'], 'integer');
			}

			return [
				'identifier' => $lo_identifierCase,
				'parent_id' => $lo_parentCase,
				'system_order' => $lo_systemOrderCase,
			];
		}, [
			'id IN' => array_column($la_orderData, 'id'),
		]);

		return $li_affectedRows;
	}


	/**
	 * @param Widget $widget
	 * @return array
	 */
	protected function getAssignedAttributes(Widget $widget): array {
		if (empty($widget->widgetTemplate)) {
			return [];
		}

		return $this->Widgets->WidgetTemplates->getAssignedWidgetAttributes($widget->widgetTemplate);
	}


	/**
	 * Returns a collection of all available WidgetTemplates
	 *
	 * @return CollectionInterface
	 */
	protected function getWidgetTemplates(): CollectionInterface {
		if (!isset($this->widgetTemplates)) {
			$lo_query = $this->Widgets->WidgetTemplates->find(
				'active',
			)->select([
				'id',
				'title',
				'active',
			])->contain([
				'WidgetTemplateElements',
			]);

			$this->widgetTemplates = $lo_query->all()->indexBy('id');
		}

		return $this->widgetTemplates;
	}


	/**
	 * Returns a collection of all possible parent widgets for the given widget
	 * to prevent circular references.
	 *
	 * @param Widget $widget
	 * @return CollectionInterface
	 */
	protected function getPossibleParentWidgets(Widget $widget): CollectionInterface {
		if (!isset($this->threadedWidgets)) {
			if (empty($widget->identifier)) {
				return new Collection([]);
			}

			$lo_query = $this->Widgets->find()->find('mediaAssignments', useMediaEntity: true)->where([
				'identifier' => $widget->identifier,
			]);

			$this->threadedWidgets = $this->Widgets->listNested($lo_query);
		}

		return $this->Widgets->getPossibleParents($widget, $this->threadedWidgets);
	}


	/**
	 * @param Widget $widget
	 * @param CollectionInterface $threadedWidgets
	 * @param \Awyiss\Model\Entity\WidgetTemplate|null $selectedWidgetTemplate
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function ensurePossibleParentId(Widget $widget, CollectionInterface $threadedWidgets, ?WidgetTemplate $selectedWidgetTemplate): void {
		// Extract all possible parent ids
		$la_possibleParentIds = $threadedWidgets->extract('id')->toList();

		// Build an array of assigned widget elements, indexed by their identifier
		$la_assignedWidgetElements = $selectedWidgetTemplate ? collection($selectedWidgetTemplate->widgetTemplateElements)->indexBy('identifier')->toArray() : [];

		// If the parent_id is not in the list of possible parent ids or the parent_id is not assigned to the selected widget template
		if (
			$widget->parentId && (!in_array($widget->parentId, $la_possibleParentIds) || !isset($la_assignedWidgetElements['parent_id']))
		) {
			// Remember the errors
			$la_errors = $widget->getError('parentId');

			// If the parent_id is required and there are possible parent ids, set the parent_id to the first possible parent id
			if (($la_assignedWidgetElements['parent_id'] ?? null)?->required === true && $la_possibleParentIds) {
				$widget->parentId = reset($la_possibleParentIds);
			}
			// Otherwise, set the parent_id to null
			else {
				$widget->parentId = null;
			}

			// If there were errors, set them again
			if ($la_errors) {
				$widget->setError('parentId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When parent_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('parent_id') !== null) {
				$lo_request = $lo_request->withData('parent_id', $widget->parentId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Widget $widget
	 * @param CollectionInterface $widgetTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Widget $widget, CollectionInterface $widgetTemplates): void {
		if (!$widget->widgetTemplateId || !$widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId])) {
			$la_errors = $widget->getError('widgetTemplateId');

			$widget->widgetTemplate = $widgetTemplates->first();
			$widget->widgetTemplateId = $widget->widgetTemplate?->id;

			if ($la_errors) {
				$widget->setError('widgetTemplateId', $la_errors);
			}
		}
		elseif (!$widget->widgetTemplate) {
			$widget->widgetTemplate = $widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId]);
		}

		$lo_request = $this->getRequest();
		//When widget_template_id is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('widget_template_id') !== null) {
			$lo_request = $lo_request->withData('widget_template_id', $widget->widgetTemplateId);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @return void
	 */
	protected function unsetUnassignedElements(Widget $widget): void {
		$lo_widgetTemplates = $this->getWidgetTemplates();
		$lo_widgetTemplate = $lo_widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId]);

		foreach (
			array_diff(
				array_keys($this->Widgets->WidgetTemplates->getAvailableWidgetElements()),
				array_column($lo_widgetTemplate->widgetTemplateElements ?? [], 'identifier')
			) as $ls_element
		) {
			if ($ls_element === 'column_width') {
				$la_columnWidths = $this->Widgets->getColumnWidths();

				$widget->set($ls_element, key($la_columnWidths));

				continue;
			}

			$widget->set($ls_element);
		}

		$la_widgetAttributes = $this->Widgets->WidgetTemplates->getAvailableWidgetAttributes(true);
		$la_widgetAttributes = array_column($la_widgetAttributes, 'identifier');

		foreach (
			array_diff(
				$la_widgetAttributes,
				$this->Widgets->WidgetTemplates->getAssignedWidgetAttributes($lo_widgetTemplate)
			) as $ls_element
		) {
			$widget->set($ls_element);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Widget $widget): void {
		$lo_widgetTemplates = $this->getWidgetTemplates();
		$this->ensurePossibleTemplate($widget, $lo_widgetTemplates);

		/** @var \Awyiss\Model\Entity\WidgetTemplate $lo_selectedWidgetTemplate */
		$lo_selectedWidgetTemplate = $lo_widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId]);

		$lo_possibleParentWidgets = $this->getPossibleParentWidgets($widget);
		$this->ensurePossibleParentId($widget, $lo_possibleParentWidgets, $lo_selectedWidgetTemplate);

		$la_assignedAttributes = $this->getAssignedAttributes($widget);

		$la_widgetElementsByFieldset = [];
		if (!empty($lo_selectedWidgetTemplate->widgetTemplateElements)) {
			$la_widgetElementsByFieldset = collection($lo_selectedWidgetTemplate->widgetTemplateElements)->groupBy('fieldset')->toArray();

			foreach ($la_widgetElementsByFieldset as $ls_fieldset => $la_widgetElements) {
				$la_widgetElementsByFieldset[ $ls_fieldset ] = collection($la_widgetElements)->indexBy(function (WidgetTemplateElement $entity) {
					return Inflector::variable($entity->identifier);
				})->toArray();
			}
		}

		$la_columnWidths = $this->Widgets->getColumnWidths();
		$la_columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnWidths);

		$la_columnIndents = $this->Widgets->getColumnIndents();
		$la_columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnIndents);

		$this->set([
			'widget' => $widget,
			'widgetTemplates' => $lo_widgetTemplates,
			'possibleParentWidgets' => $lo_possibleParentWidgets,
			'assignedAttributes' => $la_assignedAttributes,
			'widgetElementsByFieldset' => $la_widgetElementsByFieldset,
			'columnWidths' => $la_columnWidths,
			'columnIndents' => $la_columnIndents,
			/** @uses \Awyiss\Model\Table::findActive() */
			'forms' => $this->Widgets->Forms->find('active')->orderByAsc('title')->all(),
			/** @uses \Awyiss\Model\Table::findActive() */
			'surveys' => $this->Widgets->Surveys->find('active')->orderByAsc('title')->all(),
			'expertMode' => $this->request->getParam('expertMode'),
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected function formatDataAttributes(array $data): array {
		$la_data = $data;

		if (empty($la_data['data'])) {
			unset($la_data['data']);

			return $la_data;
		}


		$la_dataSet = [];

		foreach ((array)$la_data['data'] as $lx_key => $lx_value) {
			if (!is_numeric($lx_key)) {
				if (empty($lx_value)) {
					continue;
				}

				$la_dataSet[ $lx_key ] = $lx_value;
				continue;
			}

			if (isset($lx_value['key']) && $lx_value['key'] !== '' && isset($lx_value['value']) && $lx_value['value'] !== '') {
				$la_dataSet[ $lx_value['key'] ] = $lx_value['value'];
			}
		}

		$la_data['data'] = $la_dataSet;

		$lo_request = $this->request->withData('data', $la_dataSet);
		$this->setRequest($lo_request);

		return $la_data;
	}
}
