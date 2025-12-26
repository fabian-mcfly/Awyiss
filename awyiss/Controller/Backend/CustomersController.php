<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Customer;
use Awyiss\Routing\Router;
use Cake\Event\EventInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * Customers Controller
 *
 * @property \Awyiss\Model\Table\CustomersTable $Customers
 */
class CustomersController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'order' => [
			'created_on' => 'desc',
		],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		$this->Authentication->allowUnauthenticated(['login', 'logout']);

		if (in_array($this->getRequest()->getParam('action'), ['login', 'logout'])) {
			$this->Categories->disable();
		}
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Customers->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, false);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		unset($this->paginate['enabled']);
		if ($paginated) {
			$customers = $this->paginate($query);
		}
		else {
			$customers = $query->all();
		}

		$this->set([
			'customers' => $customers,
			'attributes' => $this->Customers->getAttributes(),
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

		$customer = $this->Customers->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($customer);
		}

		if (empty($customer->customerGroups)) {
			$customer->customerGroups = [];
		}

		$this->set([
			'customer' => $customer,
			'customerGroups' => $this->Customers->CustomerGroups->find()->all()->toArray(),
		]);
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
		 * @var \Awyiss\Model\Entity\Customer $customer
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$customer = $this->Customers->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain(['CustomerGroups'])->first();
		if (!$customer) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($customer, 'edit');
		}

		if (empty($customer->customerGroups)) {
			$customer->customerGroups = [];
		}

		$this->set([
			'customer' => $customer,
			'customerGroups' => $this->Customers->CustomerGroups->find()->all()->toArray(),
		]);
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

		/** @var \Awyiss\Model\Entity\Customer $customer */
		$customer = $this->Customers->findById($id)->first();
		if (!$customer) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Customers->delete($customer)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($customer->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\Customer $customer
	 * @param string $method
	 * @return void
	 */
	protected function save(Customer $customer, string $method = 'add'): void {
		$associated = [];
		if ($this->Customers->hasAttributes()) {
			$associated[] = $this->Customers->getAttributesTableName(true);
			$customer->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (empty($requestData['password'])) {
			unset($requestData['password']);
		}

		$associated['CustomerGroups'] = ['onlyIds' => true];

		$this->Customers->patchEntity($customer, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Customers->save($customer, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					$customerGroups = [];

					if ($customer->customerGroups) {
						$customerGroups = Hash::combine($customer->customerGroups, '{n}.id', '{n}.label');

						if ($this->Categories->getConfig('allowAggregation')) {
							$customerGroups += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
						}
					}
					else {
						if ($this->Categories->getConfig('allowUnassigned')) {
							$customerGroups += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
						}
					}

					/*
					 * Make sure the currently selected category is still part of the categories assigned to the customer.
					 * Otherwise the next redirect to the overview would show a site without the modified customer, which could be a bit confusing.
					 */
					$this->Categories->verifySelection(null, $customerGroups);

					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($customer),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $customer->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($customer->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}
}
