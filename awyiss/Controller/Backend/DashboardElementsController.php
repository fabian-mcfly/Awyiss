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
		$lo_query = $this->DashboardElements->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($lo_query);

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

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_dashboardElements = $this->paginate($lo_query);
		}
		else {
			$lo_dashboardElements = $lo_query->all();
		}

		$this->set([
			'dashboardElements' => $lo_dashboardElements,
			'attributes' => $this->DashboardElements->getAttributes(),
			'paginated' => $lb_paginated,
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

		$lo_dashboardElement = $this->DashboardElements->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_dashboardElement);
		}

		$this->setViewVars($lo_dashboardElement);
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
		 * @var \Awyiss\Model\Entity\DashboardElement $lo_dashboardElement
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$lo_dashboardElement = $this->DashboardElements->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_dashboardElement) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_dashboardElement, 'edit');
		}

		$this->setViewVars($lo_dashboardElement);
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

		/** @var \Awyiss\Model\Entity\DashboardElement $lo_dashboardElement */
		$lo_dashboardElement = $this->DashboardElements->findById($id)->first();
		if (!$lo_dashboardElement) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->DashboardElements->delete($lo_dashboardElement)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_dashboardElement->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
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
		$la_associated = [];
		if ($this->DashboardElements->hasAttributes()) {
			$la_associated[] = $this->DashboardElements->getAttributesTableName(true);
			$dashboardElement->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData();
		$la_requestData = $this->filterFilterSettings($la_requestData);

		$this->DashboardElements->patchEntity($dashboardElement, $la_requestData, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->DashboardElements->save($dashboardElement, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $dashboardElement->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($dashboardElement->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
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
			$lo_table = $this->fetchTable(Inflector::camelize($dashboardElement->scope));

			$dashboardElement->settings['filter'] ??= [];
			$la_selectedOperators = $la_selectedValues = [];
			foreach ($dashboardElement->settings['filter'] as $ls_column => $la_columnSettings) {
				$la_selectedOperators[ $ls_column ] = $la_columnSettings['operator'] ?? null;
				$la_selectedValues[ $ls_column ] = $la_columnSettings['value'] ?? null;
			}

			$la_filterColumns = $lo_table->getFilterColumns([], $la_selectedOperators, $la_selectedValues);
		}

		$ls_i18nDomain = $dashboardElement->scope;

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		if ($ls_i18nDomain && $ls_pageRoleEnum::tryFromName($ls_i18nDomain)) {
			$ls_i18nDomain = 'generic_pages';
		}

		$la_operators = [];
		foreach (ComparisonOperator::cases() as $le_operator) {
			if ($le_operator === ComparisonOperator::Regexp) {
				continue;
			}

			$la_operators[ $le_operator->value ] = __d('search', 'operator_' . Inflector::underscore($le_operator->name));
		}

		$la_dateOperators = [];
		foreach (DateComparisonOperator::cases() as $le_operator) {
			$la_dateOperators[ $le_operator->value ] = __d('search', 'date_operator_' . Inflector::underscore($le_operator->value));
		}

		$la_tableFields = $dashboardElement->scope ? $this->getTableFields($dashboardElement->scope) : [];
		unset($la_tableFields['page_role_id']);

		$this->set([
			'dashboardElement' => $dashboardElement,
			'i18nDomain' => $ls_i18nDomain,
			'controllers' => $this->DashboardElements->getAvailableScopes(),
			'fields' => $la_tableFields,
			'filterColumns' => $la_filterColumns ?? [],
			'policies' => $this->getPolicies(),
			'operators' => $la_operators,
			'dateOperators' => $la_dateOperators,
		]);
	}

	/**
	 * @return array
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function getPolicies(): array {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->request->getAttribute('authorization');
		$la_policies = [];

		/**
		 * @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $lx_policyClass
		 */
		foreach ($lo_authorizationService->getPolicies() as $lx_policyClass) {
			$ls_scope = is_string($lx_policyClass) ? $lx_policyClass::getScope() : $lx_policyClass->getScope();

			$la_permissions = [];
			foreach (is_string($lx_policyClass) ? $lx_policyClass::getPermissionOptions() : $lx_policyClass->getPermissionOptions() as $ls_identifier => $lo_permissionOption) {
				$la_permissions[] = $ls_identifier;
			}

			$la_policies[ Inflector::camelize($ls_scope) ] = $la_permissions;
		}

		ksort($la_policies);

		return $la_policies;
	}


	/**
	 * @param array $requestData
	 * @return array
	 */
	protected function filterFilterSettings(array $requestData): array {
		$la_requestData = $requestData;

		if (empty($la_requestData['scope'])) {
			unset($la_requestData['settings']);
		}

		if ($requestData['scope'] ?? null) {
			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = $this->fetchTable(Inflector::camelize($requestData['scope']));
			$la_filterColumns = $lo_table->getFilterColumns([], null, null, false);
		}

		$la_requestData['settings']['filter'] ??= [];
		foreach ($la_requestData['settings']['filter'] as $ls_column => $la_columnSettings) {
			if (!isset($la_filterColumns[ $ls_column ]) || !($la_columnSettings['active'] ?? false)) {
				unset($la_requestData['settings']['filter'][ $ls_column ]);
			}
		}


		$la_requestData['settings']['sort'] ??= [];
		foreach ($la_requestData['settings']['sort'] as $li_key => $la_sortSettings) {
			if (empty($la_sortSettings['field']) || !in_array($la_sortSettings['direction'], ['asc', 'desc'], true)) {
				unset($la_requestData['settings']['sort'][ $li_key ]);
			}
		}

		return $la_requestData;
	}
}
