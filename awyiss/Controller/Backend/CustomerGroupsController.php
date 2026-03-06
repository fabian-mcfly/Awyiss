<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * CustomerGroups Controller
 *
 * @property \Awyiss\Model\Table\CustomerGroupsTable $CustomerGroups
 */
class CustomerGroupsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->CustomerGroups->find()->where($this->getOverviewWhere());
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
		unset($this->paginate['enabled']);
		if ($paginated) {
			$customerGroups = $this->paginate($query);
		}
		else {
			$customerGroups = $query->all();
		}

		$this->set([
			'customerGroups' => $customerGroups,
			'attributes' => $this->CustomerGroups->getAttributes(),
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

		$customersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Customers', [], ['create', 'update']);

		$customerGroup = $this->CustomerGroups->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($customerGroup, $customersScopeIsAccessible);
		}

		$this->setViewVars($customerGroup, $customersScopeIsAccessible);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		$customersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Customers', [], ['create', 'update']);

		$contain = [];
		if ($customersScopeIsAccessible) {
			$contain[] = 'Customers';
		}
		/**
		 * @var \Awyiss\Model\Entity\CustomerGroup $customerGroup
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$customerGroup = $this->CustomerGroups->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain($contain)->first();
		if (!$customerGroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($customerGroup, $customersScopeIsAccessible, 'edit');
		}

		$this->setViewVars($customerGroup, $customersScopeIsAccessible);
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

		/** @var \Awyiss\Model\Entity\CustomerGroup $customerGroup */
		$customerGroup = $this->CustomerGroups->findById($id)->first();
		if (!$customerGroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->CustomerGroups->delete($customerGroup)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($customerGroup->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\CustomerGroup $customerGroup
	 * @param bool $customersScopeIsAccessible
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 * @throws \Exception
	 */
	protected function save(CustomerGroup $customerGroup, bool $customersScopeIsAccessible, string $method = 'add'): void {
		$associated = [];
		if ($this->CustomerGroups->hasAttributes()) {
			$associated[] = $this->CustomerGroups->getAttributesTableName(true);
			$customerGroup->setAccess('attributes', true);
		}

		if ($customersScopeIsAccessible) {
			$associated['Customers'] = ['onlyIds' => true];
			$customerGroup->setAccess('customers', true);
		}

		$this->CustomerGroups->patchEntity($customerGroup, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->CustomerGroups->save($customerGroup, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($customerGroup),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $customerGroup->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($customerGroup->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\CustomerGroup $customerGroup
	 * @param bool $customersScopeIsAccessible
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(CustomerGroup $customerGroup, bool $customersScopeIsAccessible): void {
		$customers = [];
		if ($customersScopeIsAccessible) {
			$customers = $this->CustomerGroups->Customers->find()->all()->toArray();
		}

		$this->set([
			'customerGroup' => $customerGroup,
			'customers' => $customers,
		]);
	}
}
