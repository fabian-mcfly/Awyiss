<?php

declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * SystemConfiguration Controller
 *
 * @property \Awyiss\Model\Table\SystemConfigurationTable $SystemConfiguration
 * @method \Awyiss\Model\Entity\SystemConfiguration[]|\Cake\Datasource\ResultSetInterface paginate($object = NULL, array $settings = [])
 */
class SystemConfigurationController extends Controller {
	use \Awyiss\Authorization\Trait\BasicCrudPermissionsTrait;

	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function overview () {
		$systemConfiguration = $this->paginate($this->SystemConfiguration);

		$this->set(compact('systemConfiguration'));
	}


	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
	 */
	public function add () {
		$systemConfiguration = $this->SystemConfiguration->newEmptyEntity();
		if ($this->request->is('post')) {
			$systemConfiguration = $this->SystemConfiguration->patchEntity($systemConfiguration, $this->request->getData());
			if ($this->SystemConfiguration->save($systemConfiguration)) {
				$this->Flash->success(__('The system configuration has been saved.'));

				return $this->redirect(['action' => 'overview']);
			}
			$this->Flash->error(__('The system configuration could not be saved. Please, try again.'));
		}
		$this->set(compact('systemConfiguration'));
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function edit () {
		$id = $this->request->getParam('id');
		$systemConfiguration = $this->SystemConfiguration->get($id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$systemConfiguration = $this->SystemConfiguration->patchEntity($systemConfiguration, $this->request->getData());
			if ($this->SystemConfiguration->save($systemConfiguration)) {
				$this->Flash->success(__('The system configuration has been saved.'));

				return $this->redirect(['action' => 'overview']);
			}
			$this->Flash->error(__('The system configuration could not be saved. Please, try again.'));
		}
		$this->set(compact('systemConfiguration'));
	}


	/**
	 * Delete method
	 *
	 * @param string|null $id System Configuration id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects to overview.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete ($id = NULL) {
		$this->request->allowMethod(['post', 'delete']);
		$systemConfiguration = $this->SystemConfiguration->get($id);
		if ($this->SystemConfiguration->delete($systemConfiguration)) {
			$this->Flash->success(__('The system configuration has been deleted.'));
		}
		else {
			$this->Flash->error(__('The system configuration could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}
