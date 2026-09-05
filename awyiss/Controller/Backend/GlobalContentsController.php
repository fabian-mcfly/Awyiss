<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Model\Entity\GlobalContentTemplateElement;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnSystem\ColumnInterface;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * GlobalContents Controller
 *
 * @property \Awyiss\Model\Table\GlobalContentsTable $GlobalContents
 */
class GlobalContentsController extends Controller {
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $globalContentTemplates;
	/**
	 * @var string|null Session identifier for the selected identifier
	 */
	protected ?string $selectedIdentifierSessionIdentifier = null;
	/**
	 * @var string|null Session identifier for the selected parentId
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedGlobalContents;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->selectedIdentifierSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.identifier'
		;

		$this->selectedParentIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.parentId'
		;
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->GlobalContents
			->find('mediaAssignments', useMediaEntity: true)
			->where($this->getOverviewWhere())
			->contain(
				['GlobalContentTemplates']
			)
		;
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		$globalContents = $query
			->formatResults(function (Collection $result): Collection {
				/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
				foreach ($result as $globalContent) {
					$globalContent->class = $globalContent->column['width']->getCssClass();

					if ($globalContent->column['indent']) {
						$globalContent->class .= ' ' . $globalContent->column['indent']->getCssClass();
					}

					if ($globalContent->columnRtl) {
						$globalContent->class .= ' Column-RTL';
					}

					if ($globalContent->columnLast) {
						$globalContent->class .= ' Column-Last';
					}
				}

				return $result;
			})
			->find('threaded')
			->all()
		;

		$globalContents = $globalContents->groupBy('identifier')->toArray();
		ksort($globalContents);

		/** @var class-string<\Awyiss\Utility\Content\ColumnSystem\ColumnSystemInterface> $columnSystemClass */
		$columnSystemClass = $this->GlobalContents->getColumnSystemClass();

		$globalContentTemplates = $this
			->getGlobalContentTemplates()
			->indexBy('id')
			->toArray()
		;
		array_map(function (GlobalContentTemplate $globalContentTemplate) {
			// Build an array of assigned content elements, indexed by their identifier
			$globalContentTemplate->globalContentTemplateElements = collection($globalContentTemplate->globalContentTemplateElements)
				->indexBy('identifier')
				->toArray()
			;
		}, $globalContentTemplates);

		$this->set([
			'globalContents' => $globalContents,
			'globalContentTemplates' => $globalContentTemplates,
			'columnWidths' => $this->GlobalContents->getColumnWidths(),
			'columnIndents' => $this->GlobalContents->getColumnIndents(),
			'columnSystemName' => $columnSystemClass::getName(),
			'attributes' => $this->GlobalContents->getAttributes(),
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
		$globalContent = $this->GlobalContents->newDefaultEntity([
			'identifier' => $session->read($this->selectedIdentifierSessionIdentifier),
			'parentId' => $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($globalContent);
		}

		$this->setViewVars($globalContent);
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
		 * @var \Awyiss\Model\Entity\GlobalContent $globalContent
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$globalContent = $this->GlobalContents
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$globalContent) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($globalContent, 'edit');
		}

		if ($this->request->getParam('mode') === 'frontendEditor') {
			$this
				->viewBuilder()
				->setTemplate('edit_frontend_editor')
				->setLayout('frontend_editor')
			;
		}

		$this->setViewVars($globalContent);

		$this->set('auditDataCount', $this->GlobalContents->countAuditData($globalContent));
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

		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->GlobalContents->findById($id)->first();
		if (!$globalContent) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->GlobalContents->delete($globalContent)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($globalContent->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Save the column width of one content.
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

		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $this->GlobalContents->findById($request->getData('id'))->first();
		if (!$globalContent) {
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


		$globalContent->set('columnWidth', $request->getData('width'));

		$this->GlobalContents->save($globalContent);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', !$globalContent->hasErrors());
			$this->set('message', !$globalContent->hasErrors() ? __('edit_succeeded') : __('edit_failed'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($globalContent->hasErrors()) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if (!$globalContent->hasErrors()) {
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
		$affectedRows = $this->_saveSystemOrder($request->getData('order'), $this->GlobalContents);

		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', true);
			$this->set('message', $affectedRows > 0 ? __d('System', 'system_order_saved') : __d('System', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');
		}
		else {
			if ($affectedRows) {
				$this->Flash->success(__d('System', 'system_order_saved'));
			}
			else {
				$this->Flash->error(__d('System', 'system_order_not_saved'));
			}

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}
	}


	/**
	 * @param GlobalContent $globalContent
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(GlobalContent $globalContent, string $method = 'add'): void {
		$associated = [];
		if ($this->GlobalContents->hasAttributes()) {
			$associated[] = $this->GlobalContents->getAttributesTableName(true);
			$globalContent->setAccess('attributes', true);
		}

		$requestData = $this->formatDataAttributes($this->request->getData());

		$this->GlobalContents->patchEntity($globalContent, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$this->unsetUnassignedElements($globalContent);

			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->GlobalContents->save($globalContent, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedIdentifierSessionIdentifier, $globalContent->identifier);
				$session->write($this->selectedParentIdSessionIdentifier, $globalContent->parentId);

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $globalContent->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($globalContent->getError('_general') as $error) {
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
				'parentId' => $parentCase,
				'systemOrder' => $systemOrderCase,
			];
		}, [
			'id IN' => array_column($orderData, 'id'),
		]);

		return $affectedRows;
	}


	/**
	 * @param GlobalContent $globalContent
	 * @return array
	 */
	protected function getAssignedAttributes(GlobalContent $globalContent): array {
		if (empty($globalContent->globalContentTemplate)) {
			return [];
		}

		return $this->GlobalContents->GlobalContentTemplates->getAssignedGlobalContentAttributes($globalContent->globalContentTemplate);
	}


	/**
	 * Returns a collection of all available GlobalContentTemplates
	 *
	 * @return CollectionInterface
	 */
	protected function getGlobalContentTemplates(): CollectionInterface {
		if (!isset($this->globalContentTemplates)) {
			$query = $this->GlobalContents->GlobalContentTemplates
				->find('active')
				->select([
					'id',
					'title',
					'active',
				])
				->contain([
					'GlobalContentTemplateElements',
				])
			;

			$this->globalContentTemplates = $query->all()->indexBy('id');
		}

		return $this->globalContentTemplates;
	}


	/**
	 * Returns a collection of all possible parent global_contents for the given content
	 * to prevent circular references.
	 *
	 * @param GlobalContent $globalContent
	 * @return CollectionInterface
	 */
	protected function getPossibleParentGlobalContents(GlobalContent $globalContent): CollectionInterface {
		if (!isset($this->threadedGlobalContents)) {
			if (empty($globalContent->identifier)) {
				return new Collection([]);
			}

			$query = $this->GlobalContents
				->find()
				->find('mediaAssignments', useMediaEntity: true)
				->where([
					'identifier' => $globalContent->identifier,
				])
			;

			$this->threadedGlobalContents = $this->GlobalContents->listNested($query);
		}

		return $this->GlobalContents->getPossibleParents($globalContent, $this->threadedGlobalContents);
	}


	/**
	 * @param GlobalContent $globalContent
	 * @param CollectionInterface $threadedGlobalContents
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate|null $selectedGlobalContentTemplate
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function ensurePossibleParentId(
		GlobalContent $globalContent,
		CollectionInterface $threadedGlobalContents,
		?GlobalContentTemplate $selectedGlobalContentTemplate
	): void {
		// Extract all possible parent ids
		$possibleParentIds = $threadedGlobalContents->extract('id')->toList();

		// Build an array of assigned global content elements, indexed by their identifier
		$assignedGlobalContentElements = $selectedGlobalContentTemplate
			? collection($selectedGlobalContentTemplate->globalContentTemplateElements)->indexBy('identifier')->toArray()
			: [];

		$globalContent->setDirty('parentId', false);

		// If the parentId is not in the list of possible parent ids or the parentId is not assigned to the selected content template
		if (
			$globalContent->parentId
			&& (
				!in_array($globalContent->parentId, $possibleParentIds)
				|| !isset($assignedGlobalContentElements['parentId'])
			)
		) {
			// Remember the errors
			$errors = $globalContent->getError('parentId');

			// If the parentId is required and there are possible parent ids, set the parentId to the first possible parent id
			if (($assignedGlobalContentElements['parentId'] ?? null)?->required === true && $possibleParentIds) {
				$globalContent->parentId = reset($possibleParentIds);
			}
			// Otherwise, set the parentId to null
			else {
				$globalContent->parentId = null;
			}

			// If there were errors, set them again
			if ($errors) {
				$globalContent->setError('parentId', $errors);
			}

			$request = $this->getRequest();
			//When parentId is part of the request data, overwrite it since it might be outdated
			if ($request->getData('parentId') !== null) {
				$request = $request->withData('parentId', $globalContent->parentId);
				$this->setRequest($request);
			}
		}
	}


	/**
	 * @param GlobalContent $globalContent
	 * @param CollectionInterface $globalContentTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(GlobalContent $globalContent, CollectionInterface $globalContentTemplates): void {
		if (
			!$globalContent->globalContentTemplateId
			|| !$globalContentTemplates->firstMatch(['id' => $globalContent->globalContentTemplateId])
		) {
			$errors = $globalContent->getError('globalContentTemplateId');

			$globalContent->globalContentTemplate = $globalContentTemplates->first();
			$globalContent->globalContentTemplateId = $globalContent->globalContentTemplate?->id;

			if ($errors) {
				$globalContent->setError('globalContentTemplateId', $errors);
			}
		}
		elseif (!$globalContent->globalContentTemplate) {
			$globalContent->globalContentTemplate = $globalContentTemplates->firstMatch(['id' => $globalContent->globalContentTemplateId]);
		}

		$request = $this->getRequest();
		//When global_content_template_id is part of the request data, overwrite it since it might be outdated
		if ($request->getData('globalContentTemplateId') !== null) {
			$request = $request->withData('globalContentTemplateId', $globalContent->globalContentTemplateId);
			$this->setRequest($request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContent $globalContent
	 * @return void
	 */
	protected function unsetUnassignedElements(GlobalContent $globalContent): void {
		$globalContentTemplates = $this->getGlobalContentTemplates();
		$globalContentTemplate = $globalContentTemplates->firstMatch(['id' => $globalContent->globalContentTemplateId]);

		foreach (
			array_diff(
				array_keys($this->GlobalContents->GlobalContentTemplates->getAvailableGlobalContentElements()),
				array_column($globalContentTemplate->globalContentTemplateElements ?? [], 'identifier')
			) as $element
		) {
			if ($element === 'columnWidth') {
				$columnWidths = $this->GlobalContents->getColumnWidths();

				$globalContent->set($element, key($columnWidths));

				continue;
			}

			$globalContent->set($element);
		}

		$globalContentAttributes = array_keys($this->GlobalContents->GlobalContentTemplates->getAvailableGlobalContentAttributes(true));

		foreach (
			array_diff(
				$globalContentAttributes,
				$this->GlobalContents->GlobalContentTemplates->getAssignedGlobalContentAttributes($globalContentTemplate)
			) as $element
		) {
			$globalContent->set($element);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContent $globalContent
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(GlobalContent $globalContent): void {
		$globalContentTemplates = $this->getGlobalContentTemplates();
		$this->ensurePossibleTemplate($globalContent, $globalContentTemplates);

		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $selectedGlobalContentTemplate */
		$selectedGlobalContentTemplate = $globalContentTemplates->firstMatch(['id' => $globalContent->globalContentTemplateId]);

		$possibleParentGlobalContents = $this->getPossibleParentGlobalContents($globalContent);
		$this->ensurePossibleParentId($globalContent, $possibleParentGlobalContents, $selectedGlobalContentTemplate);

		$assignedAttributes = $this->getAssignedAttributes($globalContent);

		$globalContentElementsByFieldset = [];
		if (!empty($selectedGlobalContentTemplate->globalContentTemplateElements)) {
			$globalContentElementsByFieldset = collection($selectedGlobalContentTemplate->globalContentTemplateElements)
				->groupBy('fieldset')
				->toArray()
			;

			foreach ($globalContentElementsByFieldset as $fieldset => $contentElements) {
				$globalContentElementsByFieldset[ $fieldset ] = collection($contentElements)
					->indexBy(fn(GlobalContentTemplateElement $entity) => Inflector::variable($entity->identifier))
					->toArray()
				;
			}
		}

		$columnWidths = $this->GlobalContents->getColumnWidths();
		$columnWidths = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnWidths);

		$columnIndents = $this->GlobalContents->getColumnIndents();
		$columnIndents = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnIndents);

		$this->set([
			'globalContent' => $globalContent,
			'globalContentTemplates' => $globalContentTemplates,
			'possibleParentGlobalContents' => $possibleParentGlobalContents,
			'assignedAttributes' => $assignedAttributes,
			'globalContentElementsByFieldset' => $globalContentElementsByFieldset,
			'columnWidths' => $columnWidths,
			'columnIndents' => $columnIndents,
			/** @uses \Awyiss\Model\Table::findActive() */
			'forms' => $this->GlobalContents->Forms
				->find('active')
				->orderByAsc('title')
				->all(),
			'linkTargets' => $this->findLinkablePages(),
			/** @uses \Awyiss\Model\Table::findActive() */
			'surveys' => $this->GlobalContents->Surveys
				->find('active')
				->orderByAsc('title')
				->all(),
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


	/**
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function findLinkablePages(): CollectionInterface {
		// Get all page roles that can be included in the link list
		/** @uses \Awyiss\Model\Table::findActive() */
		$pageRoles = $this
			->fetchTable('PageRoles')
			->find('active')
			->where(['includeInLinklist' => true])
			->all()
			->indexBy('id')
			->toArray()
		;

		/**
		 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
		 * @uses \Awyiss\Model\Table::findActive()
		 */
		$pagesTable = $this->fetchTable('Pages');
		$query = $pagesTable
			->find('active')
			->find('forCurrentLanguage', skipPageRoleCheck: true)
			->where([
				'pageRoleId IN' => array_keys($pageRoles),
			])
		;

		return $pagesTable->listNested($query);
	}
}
