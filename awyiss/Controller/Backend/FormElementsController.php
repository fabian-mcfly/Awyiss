<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * FormElements Controller
 *
 * @property \Awyiss\Model\Table\FormElementsTable $FormElements
 */
class FormElementsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'uriParam' => 'form-id',
	];
	protected array $blocklistedElements = [
		'text' => ['options', 'text'],
		'free_text' => ['identifier', 'options', 'placeholder', 'required', 'title', 'title_email'],
		'fieldset' => ['options', 'placeholder', 'required', 'text'],
		'email' => ['options', 'text'],
		'date' => ['options', 'text'],
		'time' => ['options', 'text'],
		'datetime' => ['options', 'text'],
		'textarea' => ['options', 'text'],
		'checkbox' => ['placeholder', 'text'],
		'radio' => ['placeholder', 'text'],
		'select' => ['placeholder', 'text'],
		'select_multiple' => ['placeholder', 'text'],
		'file' => ['options', 'placeholder', 'text'],
		'hidden' => ['column_width', 'column_indent', 'column_last', 'column_rtl', 'options', 'placeholder', 'required', 'text'],
		'submit' => ['identifier', 'options', 'placeholder', 'required', 'text', 'title_email'],
	];

	/**
	 * @var string|null Session identifier for the selected parent_id
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;
	/**
	 * @var \Cake\Collection\CollectionInterface
	 */
	protected CollectionInterface $threadedFormElements;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->Authorization->setScope('forms');

		$this->selectedParentIdSessionIdentifier = 'form_elements.parent_id';
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->FormElements->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		$lo_formElements = $lo_query->formatResults(function (CollectionInterface $result): CollectionInterface {
			/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
			foreach ($result as $lo_formElement) {
				$lo_formElement->class = $lo_formElement->column['width']->getCssClass();

				if ($lo_formElement->column['indent']) {
					$lo_formElement->class .= ' ' . $lo_formElement->column['indent']->getCssClass();
				}

				if ($lo_formElement->columnRtl) {
					$lo_formElement->class .= ' Column-RTL';
				}

				if ($lo_formElement->columnLast) {
					$lo_formElement->class .= ' Column-Last';
				}
			}

			return $result;
		})->find('threaded')->all();

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $ls_columnSystemClass */
		$ls_columnSystemClass = $this->FormElements->getColumnSystemClass();

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_form = $this->fetchTable('Forms')->findById($this->Categories->getSelectedCategory())->first();

		$this->set([
			'formElements' => $lo_formElements,
			'form' => $lo_form,
			'columnWidths' => $this->FormElements->getColumnWidths(),
			'columnIndents' => $this->FormElements->getColumnIndents(),
			'columnSystemName' => $ls_columnSystemClass::getName(),
			'attributes' => $this->FormElements->getAttributes(),
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
		$lo_formElement = $this->FormElements->newDefaultEntity([
			'formId' => $this->request->getParam('formId') ?? $this->Categories->getSelectedCategory(),
			'parentId' => $lo_session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_formElement);
		}

		$this->setViewVars($lo_formElement);
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

		/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
		$lo_formElement = $this->FormElements->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_formElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_formElement, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this->viewBuilder()
			->setTemplate('edit_frontend_editor')
			->setLayout('frontend_editor');
		}

		$this->setViewVars($lo_formElement);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var FormElement $lo_formElement */
		$lo_formElement = $this->FormElements->findById($id)->first();
		if (!$lo_formElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->FormElements->delete($lo_formElement)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_formElement->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Save the column width of one element.
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function saveColumnWidth(): void {
		$lo_request = Router::getRequest();

		/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
		$lo_formElement = $this->FormElements->findById($lo_request->getData('id'))->first();
		if (!$lo_formElement) {
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

		$this->Authorization->ensure('read');

		$lo_formElement->set('columnWidth', $lo_request->getData('width'));

		$this->FormElements->save($lo_formElement);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$lo_formElement->hasErrors());
			$this->set('message', !$lo_formElement->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($lo_formElement->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$lo_formElement->hasErrors()) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__('edit_succeeded'));
				}
			}
			else {
				$this->Flash->error(__('edit_failed'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * Returns a collection of possible parent form elements for the given form elements
	 * to prevent circular references
	 *
	 * @param \Awyiss\Model\Entity\FormElement $formElement
	 * @return CollectionInterface
	 */
	protected function getPossibleParentFormElements(FormElement $formElement): CollectionInterface {
		if (!isset($this->threadedFormElements)) {
			$lo_query = $this->FormElements->find()->where([
				'form_id' => $formElement->formId,
			]);

			$this->threadedFormElements = $this->FormElements->listNested($lo_query);
		}


		return $this->FormElements->getPossibleParents($formElement, $this->threadedFormElements);
	}


	/**
	 * @param FormElement $formElement
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(FormElement $formElement, string $method = 'add'): void {
		$la_associated = [];
		if ($this->FormElements->hasAttributes()) {
			$la_associated[] = $this->FormElements->getAttributesTableName(true);
			$formElement->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();
		$la_data = $this->formatOptions($la_data);

		$this->FormElements->patchEntity($formElement, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->FormElements->save($formElement, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$lo_session = $this->request->getSession();
				$lo_session->write($this->selectedParentIdSessionIdentifier, $formElement->parentId);

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'formId' => $formElement->formId,
						'page' => $this->Paginate->calculateEntityPagePosition($formElement),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $formElement->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($formElement->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->FormElements->getSystemOrderRelatedColumns($formElement)) {
				$formElement->systemOrder = null;
			}
			else {
				$formElement->systemOrder = $formElement->hasOriginal('systemOrder') ? $formElement->getOriginal('systemOrder') : $formElement->get('systemOrder');
			}
		}

		$this->Categories->ensurePossibleCategory($formElement);
	}


	/**
	 * @param \Awyiss\Model\Entity\FormElement $formElement
	 * @param \Cake\Collection\CollectionInterface $threadedFormElements
	 * @return void
	 */
	protected function ensurePossibleParentId(FormElement $formElement, CollectionInterface $threadedFormElements): void {
		$la_possibleParentIds = $threadedFormElements->extract('id')->toList();

		if (!empty($formElement->parentId) && !in_array($formElement->parentId, $la_possibleParentIds)) {
			$la_errors = $formElement->getError('parentId');

			$formElement->set('parentId', null, ['setter' => false]);

			if ($la_errors) {
				$formElement->setError('parentId', $la_errors, true);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\FormElement $formElement
	 * @return void
	 */
	protected function setViewVars(FormElement $formElement): void {
		$la_columnWidths = $this->FormElements->getColumnWidths();
		$la_columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnWidths);

		$la_columnIndents = $this->FormElements->getColumnIndents();
		$la_columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnIndents);

		$lo_possibleParentFormElements = $this->getPossibleParentFormElements($formElement);
		$this->ensurePossibleParentId($formElement, $lo_possibleParentFormElements);

		$this->set([
			'formElement' => $formElement,
			'availableTypes' => $this->FormElements->getAvailableTypes(true),
			'blocklistedElements' => $this->blocklistedElements[ $formElement->type ] ?? [],
			'columnWidths' => $la_columnWidths,
			'columnIndents' => $la_columnIndents,
			'possibleParentFormElements' => $lo_possibleParentFormElements,
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected function formatOptions(array $data): array {
		$la_data = $data;

		if (empty($la_data['options'])) {
			$la_data['options'] = null;

			return $la_data;
		}

		$la_options = [];

		foreach (array_values((array)$la_data['options']) as $lx_key => $lx_value) {
			$lb_emptyKey = empty($lx_value['key']);
			$lb_emptyValue = empty($lx_value['value']);

			if (isset($lx_value['_translations'])) {
				$lb_emptyKey = !array_filter($lx_value['_translations'], function (array $translation): bool {
					return !empty($translation['key']);
				});
				$lb_emptyValue = !array_filter($lx_value['_translations'], function (array $translation): bool {
					return !empty($translation['value']);
				});
			}

			if ($lb_emptyKey && $lb_emptyValue && $lx_key > 0) {
				continue;
			}

			$la_options[] = [
				'key' => $lx_value['key'] ?? null,
				'value' => $lx_value['value'] ?? null,
				'_translations' => $lx_value['_translations'] ?? [],
			];
		}

		if (count($la_options) === 1) {
			// If key, value and all translations are empty, no options are set
			$lb_emptyKey = empty($la_options[0]['key']) && !array_filter($la_options[0]['_translations'], function (array $translation): bool {
				return !empty($translation['key']);
			});

			$lb_emptyValue = empty($la_options[0]['value']) && !array_filter($la_options[0]['_translations'], function (array $translation): bool {
				return !empty($translation['value']);
			});

			if ($lb_emptyKey && $lb_emptyValue) {
				$la_options = [];
			}
		}

		$la_data['options'] = $la_options;

		// Update the request data
		$lo_request = $this->request->withData('options', $la_options);
		$this->setRequest($lo_request);

		return $la_data;
	}
}
