<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\DashboardElement;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Enum\DateComparisonOperator;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * Create and edit dashboard elements of the Backend
 *
 * @property \Awyiss\Model\Table\DashboardElementsTable $DashboardElements
 */
class DashboardElementsController extends Controller {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table\DashboardElementsTable::findWithUsages() */
		$query = $this->DashboardElements->find()->where($this->getOverviewWhere());
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

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		if ($paginated) {
			$dashboardElements = $this->paginate($query);
		}
		else {
			$dashboardElements = $query->all();
		}

		$this->set([
			'dashboardElements' => $dashboardElements,
			'attributes' => $this->DashboardElements->getAttributes(),
			'paginated' => $paginated,
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

		$dashboardElement = $this->DashboardElements->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($dashboardElement);
		}

		$this->setViewVars($dashboardElement);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\DashboardElement $dashboardElement
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$dashboardElement = $this->DashboardElements
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$dashboardElement) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($dashboardElement, 'edit');
		}

		$this->setViewVars($dashboardElement);
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

		/** @var \Awyiss\Model\Entity\DashboardElement $dashboardElement */
		$dashboardElement = $this->DashboardElements->findById($id)->first();
		if (!$dashboardElement) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->DashboardElements->delete($dashboardElement)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($dashboardElement->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\DashboardElement $dashboardElement
	 * @param string $method
	 * @return void
	 */
	protected function save(DashboardElement $dashboardElement, string $method = 'add'): void {
		$associated = [];
		if ($this->DashboardElements->hasAttributes()) {
			$associated[] = $this->DashboardElements->getAttributesTableName(true);
			$dashboardElement->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();
		$requestData = $this->filterFilterSettings($requestData);

		$this->DashboardElements->patchEntity($dashboardElement, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->DashboardElements->save($dashboardElement, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $dashboardElement->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($dashboardElement->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\DashboardElement $dashboardElement
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function setViewVars(DashboardElement $dashboardElement): void {
		if ($dashboardElement->scope) {
			/** @var \Awyiss\Model\Table $table */
			$table = $this->fetchTable($dashboardElement->scope);

			$dashboardElement->settings['filter'] ??= [];
			$selectedOperators = $selectedValues = [];
			foreach ($dashboardElement->settings['filter'] as $column => $columnSettings) {
				$selectedOperators[ $column ] = $columnSettings['operator'] ?? null;
				$selectedValues[ $column ] = $columnSettings['value'] ?? null;
			}

			$filterColumns = $table->getFilterColumns([], $selectedOperators, $selectedValues);
		}

		$i18nDomain = $dashboardElement->scope;

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($i18nDomain && $pageRoleEnum::tryFromName($i18nDomain)) {
			$i18nDomain = 'GenericPages';
		}

		$operators = [];
		foreach (ComparisonOperator::cases() as $operator) {
			if ($operator === ComparisonOperator::Regexp) {
				continue;
			}

			$operators[ $operator->value ] = __d('Search', 'operator_' . Inflector::underscore($operator->name));
		}

		$dateOperators = [];
		foreach (DateComparisonOperator::cases() as $operator) {
			$dateOperators[ $operator->value ] = __d('Search', 'date_operator_' . Inflector::underscore($operator->value));
		}

		$tableFields = $dashboardElement->scope ? $this->getTableFields($dashboardElement->scope) : [];
		unset($tableFields['pageRoleId']);

		$this->set([
			'dashboardElement' => $dashboardElement,
			'i18nDomain' => $i18nDomain,
			'controllers' => $this->DashboardElements->getAvailableScopes(),
			'fields' => $tableFields,
			'filterColumns' => $filterColumns ?? [],
			'policies' => $this->getPolicies(),
			'operators' => $operators,
			'dateOperators' => $dateOperators,
		]);
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function getPolicies(): array {
		/** @var \Awyiss\Authorization\AuthorizationService $authorizationService */
		$authorizationService = $this->request->getAttribute('authorization');
		$policies = [];

		/**
		 * @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $policyClass
		 */
		foreach ($authorizationService->getPolicies() as $policyClass) {
			$scope = is_string($policyClass) ? $policyClass::getScope() : $policyClass->getScope();

			$permissionOptionCollection = is_string($policyClass)
				? $policyClass::getPermissionOptions()
				: $policyClass->getPermissionOptions();

			$permissions = [];
			foreach ($permissionOptionCollection as $identifier => $permissionOption) {
				$permissions[] = $identifier;
			}

			$policies[ Inflector::camelize($scope) ] = $permissions;
		}

		ksort($policies);

		return $policies;
	}


	/**
	 * @param array $requestData
	 * @return array
	 */
	protected function filterFilterSettings(array $requestData): array {
		if (empty($requestData['scope'])) {
			unset($requestData['settings']);
		}

		if ($requestData['scope'] ?? null) {
			/** @var \Awyiss\Model\Table $table */
			$table = $this->fetchTable(Inflector::camelize($requestData['scope']));
			$filterColumns = $table->getFilterColumns([], null, null, false);
		}

		$requestData['settings']['filter'] ??= [];
		foreach ($requestData['settings']['filter'] as $column => $columnSettings) {
			if (!isset($filterColumns[ $column ]) || !($columnSettings['active'] ?? false)) {
				unset($requestData['settings']['filter'][ $column ]);
			}
		}


		$requestData['settings']['sort'] ??= [];
		foreach ($requestData['settings']['sort'] as $key => $sortSettings) {
			if (empty($sortSettings['field']) || !in_array($sortSettings['direction'], ['asc', 'desc'], true)) {
				unset($requestData['settings']['sort'][ $key ]);
			}
		}

		return $requestData;
	}
}
