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
		$query = $this->Widgets->find('mediaAssignments', useMediaEntity: true)->where($this->getOverviewWhere())->contain(['WidgetTemplates']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		$widgets = $query->formatResults(function (Collection $result): Collection {
			/** @var \Awyiss\Model\Entity\Widget $widget */
			foreach ($result as $widget) {
				/** @noinspection PhpUndefinedFieldInspection */
				$widget->class = $widget->column['width']->getCssClass();

				if ($widget->column['indent']) {
					$widget->class .= ' ' . $widget->column['indent']->getCssClass();
				}

				if ($widget->columnRtl) {
					$widget->class .= ' Column-RTL';
				}

				if ($widget->columnLast) {
					$widget->class .= ' Column-Last';
				}
			}

			return $result;
		})->find('threaded')->all();

		$widgets = $widgets->groupBy('identifier')->toArray();
		ksort($widgets);

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $columnSystemClass */
		$columnSystemClass = $this->Widgets->getColumnSystemClass();

		$widgetTemplates = $this->getWidgetTemplates()->indexBy('id')->toArray();
		array_map(function (WidgetTemplate $widgetTemplate) {
			// Build an array of assigned widget elements, indexed by their identifier
			$widgetTemplate->widgetTemplateElements = collection($widgetTemplate->widgetTemplateElements)->indexBy('identifier')->toArray();
		}, $widgetTemplates);

		$this->set([
			'widgets' => $widgets,
			'widgetTemplates' => $widgetTemplates,
			'columnWidths' => $this->Widgets->getColumnWidths(),
			'columnIndents' => $this->Widgets->getColumnIndents(),
			'columnSystemName' => $columnSystemClass::getName(),
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

		$session = $this->request->getSession();
		$widget = $this->Widgets->newDefaultEntity([
			'identifier' => $session->read($this->selectedIdentifierSessionIdentifier),
			'parentId' => $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($widget);
		}

		$this->setViewVars($widget);
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
		 * @var \Awyiss\Model\Entity\Widget $widget
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$widget = $this->Widgets->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$widget) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($widget, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this->viewBuilder()
			->setTemplate('edit_frontend_editor')
			->setLayout('frontend_editor');
		}

		$this->setViewVars($widget);

		/** @noinspection PhpUndefinedMethodInspection */
		$this->set('auditDataCount', $this->Widgets->countAuditData($widget));
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

		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->Widgets->findById($id)->first();
		if (!$widget) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Widgets->delete($widget)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($widget->getError('_general') as $error) {
					$this->Flash->error($error);
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
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function saveColumnWidth(): void {
		$this->Authorization->ensure('read');

		$request = Router::getRequest();

		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $this->Widgets->findById($request->getData('id'))->first();
		if (!$widget) {
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


		$widget->set('columnWidth', $request->getData('width'));

		$this->Widgets->save($widget);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$widget->hasErrors());
			$this->set('message', !$widget->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($widget->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$widget->hasErrors()) {
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
		$request = Router::getRequest();
		$affectedRows = $this->_saveSystemOrder($request->getData('order'), $this->Widgets);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', true);
			$this->set('message', $affectedRows > 0 ? __d('system', 'system_order_saved') : __d('system', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}
		else {
			if ($affectedRows) {
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
		$associated = [];
		if ($this->Widgets->hasAttributes()) {
			$associated[] = $this->Widgets->getAttributesTableName(true);
			$widget->setAccess('attributes', true);
		}

		$requestData = $this->formatDataAttributes($this->request->getData());

		$this->Widgets->patchEntity($widget, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$this->unsetUnassignedElements($widget);

			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Widgets->save($widget, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedIdentifierSessionIdentifier, $widget->identifier);
				$session->write($this->selectedParentIdSessionIdentifier, $widget->parentId);

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $widget->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($widget->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
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
		$orderData = [];
		foreach ($requestData as $itemsByIdentifier) {
			foreach ($itemsByIdentifier as $parentId => $items) {
				array_map(function (array $item) use (&$orderData, $parentId) {
					$orderData[] = $item + ['parentId' => $parentId ?: null];
				}, $items);
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$affectedRows = $table->updateAll(function (QueryExpression $expression) use ($orderData) {
			$identifierCase = $expression->case();
			$parentCase = $expression->case();
			$systemOrderCase = $expression->case();

			foreach ($orderData as $data) {
				$identifierCase->when(['id' => $data['id']])->then($data['identifier'], 'string');
				$parentCase->when(['id' => $data['id']])->then($data['parentId'], 'integer');
				$systemOrderCase->when(['id' => $data['id']])->then($data['systemOrder'], 'integer');
			}

			return [
				'identifier' => $identifierCase,
				'parent_id' => $parentCase,
				'system_order' => $systemOrderCase,
			];
		}, [
			'id IN' => array_column($orderData, 'id'),
		]);

		return $affectedRows;
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
			$query = $this->Widgets->WidgetTemplates->find(
				'active',
			)->select([
				'id',
				'title',
				'active',
			])->contain([
				'WidgetTemplateElements',
			]);

			$this->widgetTemplates = $query->all()->indexBy('id');
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

			$query = $this->Widgets->find()->find('mediaAssignments', useMediaEntity: true)->where([
				'identifier' => $widget->identifier,
			]);

			$this->threadedWidgets = $this->Widgets->listNested($query);
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
		$possibleParentIds = $threadedWidgets->extract('id')->toList();

		// Build an array of assigned widget elements, indexed by their identifier
		$assignedWidgetElements = $selectedWidgetTemplate ? collection($selectedWidgetTemplate->widgetTemplateElements)->indexBy('identifier')->toArray() : [];

		$widget->setDirty('parentId', false);

		// If the parent_id is not in the list of possible parent ids or the parent_id is not assigned to the selected widget template
		if (
			$widget->parentId && (!in_array($widget->parentId, $possibleParentIds) || !isset($assignedWidgetElements['parent_id']))
		) {
			// Remember the errors
			$errors = $widget->getError('parentId');

			// If the parent_id is required and there are possible parent ids, set the parent_id to the first possible parent id
			if (($assignedWidgetElements['parent_id'] ?? null)?->required === true && $possibleParentIds) {
				$widget->parentId = reset($possibleParentIds);
			}
			// Otherwise, set the parent_id to null
			else {
				$widget->parentId = null;
			}

			// If there were errors, set them again
			if ($errors) {
				$widget->setError('parentId', $errors);
			}

			$request = $this->getRequest();
			//When parent_id is part of the request data, overwrite it since it might be outdated
			if ($request->getData('parent_id') !== null) {
				$request = $request->withData('parent_id', $widget->parentId);
				$this->setRequest($request);
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
			$errors = $widget->getError('widgetTemplateId');

			$widget->widgetTemplate = $widgetTemplates->first();
			$widget->widgetTemplateId = $widget->widgetTemplate?->id;

			if ($errors) {
				$widget->setError('widgetTemplateId', $errors);
			}
		}
		elseif (!$widget->widgetTemplate) {
			$widget->widgetTemplate = $widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId]);
		}

		$request = $this->getRequest();
		//When widget_template_id is part of the request data, overwrite it since it might be outdated
		if ($request->getData('widget_template_id') !== null) {
			$request = $request->withData('widget_template_id', $widget->widgetTemplateId);
			$this->setRequest($request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @return void
	 */
	protected function unsetUnassignedElements(Widget $widget): void {
		$widgetTemplates = $this->getWidgetTemplates();
		$widgetTemplate = $widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId]);

		foreach (
			array_diff(
				array_keys($this->Widgets->WidgetTemplates->getAvailableWidgetElements()),
				array_column($widgetTemplate->widgetTemplateElements ?? [], 'identifier')
			) as $element
		) {
			if ($element === 'column_width') {
				$columnWidths = $this->Widgets->getColumnWidths();

				$widget->set($element, key($columnWidths));

				continue;
			}

			$widget->set($element);
		}

		$widgetAttributes = array_keys($this->Widgets->WidgetTemplates->getAvailableWidgetAttributes(true));

		foreach (
			array_diff(
				$widgetAttributes,
				$this->Widgets->WidgetTemplates->getAssignedWidgetAttributes($widgetTemplate)
			) as $element
		) {
			$widget->set($element);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Widget $widget
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Widget $widget): void {
		$widgetTemplates = $this->getWidgetTemplates();
		$this->ensurePossibleTemplate($widget, $widgetTemplates);

		/** @var \Awyiss\Model\Entity\WidgetTemplate $selectedWidgetTemplate */
		$selectedWidgetTemplate = $widgetTemplates->firstMatch(['id' => $widget->widgetTemplateId]);

		$possibleParentWidgets = $this->getPossibleParentWidgets($widget);
		$this->ensurePossibleParentId($widget, $possibleParentWidgets, $selectedWidgetTemplate);

		$assignedAttributes = $this->getAssignedAttributes($widget);

		$widgetElementsByFieldset = [];
		if (!empty($selectedWidgetTemplate->widgetTemplateElements)) {
			$widgetElementsByFieldset = collection($selectedWidgetTemplate->widgetTemplateElements)->groupBy('fieldset')->toArray();

			foreach ($widgetElementsByFieldset as $fieldset => $widgetElements) {
				$widgetElementsByFieldset[ $fieldset ] = collection($widgetElements)->indexBy(function (WidgetTemplateElement $entity) {
					return Inflector::variable($entity->identifier);
				})->toArray();
			}
		}

		$columnWidths = $this->Widgets->getColumnWidths();
		$columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnWidths);

		$columnIndents = $this->Widgets->getColumnIndents();
		$columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnIndents);

		$this->set([
			'widget' => $widget,
			'widgetTemplates' => $widgetTemplates,
			'possibleParentWidgets' => $possibleParentWidgets,
			'assignedAttributes' => $assignedAttributes,
			'widgetElementsByFieldset' => $widgetElementsByFieldset,
			'columnWidths' => $columnWidths,
			'columnIndents' => $columnIndents,
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
		if (empty($data['data'])) {
			unset($data['data']);

			return $data;
		}


		$dataSet = [];

		foreach ((array)$data['data'] as $key => $value) {
			if (!is_numeric($key)) {
				if (empty($value)) {
					continue;
				}

				$dataSet[ $key ] = $value;
				continue;
			}

			if (isset($value['key']) && $value['key'] !== '' && isset($value['value']) && $value['value'] !== '') {
				$dataSet[ $value['key'] ] = $value['value'];
			}
		}

		$data['data'] = $dataSet;

		$request = $this->request->withData('data', $dataSet);
		$this->setRequest($request);

		return $data;
	}
}
