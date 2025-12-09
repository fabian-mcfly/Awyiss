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
		'fieldset' => ['column_width', 'column_indent', 'column_last', 'column_rtl', 'options', 'placeholder', 'required', 'text'],
		'email' => ['options', 'text'],
		'number' => ['options', 'text'],
		'range' => ['options', 'placeholder', 'text'],
		'tel' => ['options', 'text'],
		'date' => ['options', 'text'],
		'time' => ['options', 'text'],
		'datetime' => ['options', 'text'],
		'url' => ['options', 'text'],
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
		$query = $this->FormElements->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		/**
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$query = $this->getOverviewQuery()->find('mediaAssignments');

		$formElements = $query->formatResults(function (CollectionInterface $result): CollectionInterface {
			/** @var \Awyiss\Model\Entity\FormElement $formElement */
			foreach ($result as $formElement) {
				/** @noinspection PhpUndefinedFieldInspection */
				$formElement->class = $formElement->column['width']->getCssClass();

				if ($formElement->column['indent']) {
					$formElement->class .= ' ' . $formElement->column['indent']->getCssClass();
				}

				if ($formElement->columnRtl) {
					$formElement->class .= ' Column-RTL';
				}

				if ($formElement->columnLast) {
					$formElement->class .= ' Column-Last';
				}
			}

			return $result;
		})->find('threaded')->all();

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $columnSystemClass */
		$columnSystemClass = $this->FormElements->getColumnSystemClass();

		$form = $this->fetchTable('Forms')->findById($this->Categories->getSelectedCategory())->first();

		$this->set([
			'formElements' => $formElements,
			'form' => $form,
			'columnWidths' => $this->FormElements->getColumnWidths(),
			'columnIndents' => $this->FormElements->getColumnIndents(),
			'columnSystemName' => $columnSystemClass::getName(),
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

		$session = $this->request->getSession();
		$formElement = $this->FormElements->newDefaultEntity([
			'formId' => $this->request->getParam('formId') ?? $this->Categories->getSelectedCategory(),
			'parentId' => $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($formElement);
		}

		$this->setViewVars($formElement);
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
		 * @var \Awyiss\Model\Entity\FormElement $formElement
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$formElement = $this->FormElements->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$formElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($formElement, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this->viewBuilder()
			->setTemplate('edit_frontend_editor')
			->setLayout('frontend_editor');
		}

		$this->setViewVars($formElement);
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

		/** @var \Awyiss\Model\Entity\FormElement $formElement */
		$formElement = $this->FormElements->findById($id)->first();
		if (!$formElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->FormElements->delete($formElement)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($formElement->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function saveColumnWidth(): void {
		$request = Router::getRequest();

		/** @var \Awyiss\Model\Entity\FormElement $formElement */
		$formElement = $this->FormElements->findById($request->getData('id'))->first();
		if (!$formElement) {
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

		$formElement->set('columnWidth', $request->getData('width'));

		$this->FormElements->save($formElement);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$formElement->hasErrors());
			$this->set('message', !$formElement->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($formElement->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$formElement->hasErrors()) {
				$this->Flash->success(__('edit_succeeded'));
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
			$query = $this->FormElements->find('mediaAssignments')->where([
				'form_id' => $formElement->formId,
			]);

			$this->threadedFormElements = $this->FormElements->listNested($query);
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
		$associated = [];
		if ($this->FormElements->hasAttributes()) {
			$associated[] = $this->FormElements->getAttributesTableName(true);
			$formElement->setAccess('attributes', true);
		}

		$requestData = $this->formatOptions($this->request->getData());

		$this->FormElements->patchEntity($formElement, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->FormElements->save($formElement, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedParentIdSessionIdentifier, $formElement->parentId);

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'formId' => $formElement->formId,
						'page' => $this->Paginate->calculateEntityPagePosition($formElement),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $formElement->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($formElement->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		$possibleParentIds = $threadedFormElements->extract('id')->toList();

		if (!empty($formElement->parentId) && !in_array($formElement->parentId, $possibleParentIds)) {
			$errors = $formElement->getError('parentId');

			$formElement->set('parentId', null, ['setter' => false]);

			if ($errors) {
				$formElement->setError('parentId', $errors, true);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\FormElement $formElement
	 * @return void
	 */
	protected function setViewVars(FormElement $formElement): void {
		$columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $this->FormElements->getColumnWidths());

		$columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $this->FormElements->getColumnIndents());

		$possibleParentFormElements = $this->getPossibleParentFormElements($formElement);
		$this->ensurePossibleParentId($formElement, $possibleParentFormElements);

		$this->set([
			'formElement' => $formElement,
			'availableTypes' => $this->FormElements->getAvailableTypes(true),
			'blocklistedElements' => $this->blocklistedElements[ $formElement->type ] ?? [],
			'columnWidths' => $columnWidths,
			'columnIndents' => $columnIndents,
			'possibleParentFormElements' => $possibleParentFormElements,
			'expertMode' => $this->request->getParam('expertMode'),
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected function formatOptions(array $data): array {
		if (empty($data['options'])) {
			$data['options'] = null;

			return $data;
		}

		$options = [];

		foreach (array_values((array)$data['options']) as $key => $value) {
			$emptyKey = empty($value['key']);
			$emptyValue = empty($value['value']);

			if (isset($value['_translations'])) {
				$emptyKey = !array_filter($value['_translations'], function (array $translation): bool {
					return !empty($translation['key']);
				});
				$emptyValue = !array_filter($value['_translations'], function (array $translation): bool {
					return !empty($translation['value']);
				});
			}

			if ($emptyKey && $emptyValue && $key > 0) {
				continue;
			}

			$options[] = [
				'key' => $value['key'] ?? null,
				'value' => $value['value'] ?? null,
				'_translations' => $value['_translations'] ?? [],
			];
		}

		if (count($options) === 1) {
			// If key, value and all translations are empty, no options are set
			$emptyKey = empty($options[0]['key']) && !array_filter($options[0]['_translations'], function (array $translation): bool {
				return !empty($translation['key']);
			});

			$emptyValue = empty($options[0]['value']) && !array_filter($options[0]['_translations'], function (array $translation): bool {
				return !empty($translation['value']);
			});

			if ($emptyKey && $emptyValue) {
				$options = [];
			}
		}

		$data['options'] = $options;

		// Update the request data
		$request = $this->request->withData('options', $options);
		$this->setRequest($request);

		return $data;
	}
}
