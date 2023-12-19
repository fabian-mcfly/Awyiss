<?php

declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * PageRoles Controller
 *
 * @property \Awyiss\Model\Table\PageRolesTable $PageRoles
 * @method \Awyiss\Model\Entity\PageRole[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class PageRolesController extends Controller {
	use \Awyiss\Authorization\Trait\BasicCrudPermissionsTrait;

	
	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|NULL|void Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$lo_pageRoles = $this->paginate($this->PageRoles->find('withAttributes'));

		$this->set([
			'pageRoles' => $lo_pageRoles,
		]);
	}
	

	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|NULL|void Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function add () {
		$lo_pageRole = $this->PageRoles->newEmptyEntity();
		if ($this->request->is('post')) {
			$lo_pageRole = $this->PageRoles->patchEntity($lo_pageRole, $this->request->getData());
			if ($this->PageRoles->save($lo_pageRole)) {
				$this->Flash->success(__('::add_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_pageRole->id]);
			}
			$this->Flash->error(__('::add_failed'));
		}

		$this->set([
			'pageRole' => $lo_pageRole,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|NULL|void Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function edit () {
		$li_id = $this->request->getParam('id');
		$lo_pageRole = $this->PageRoles->get($li_id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$lo_pageRole = $this->PageRoles->patchEntity($lo_pageRole, $this->request->getData());
			if ($this->PageRoles->save($lo_pageRole)) {
				$this->Flash->success(__('::edit_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_pageRole->id]);
			}
			$this->Flash->error(__('::edit_failed'));
		}

		$this->set([
			'pageRole' => $lo_pageRole,
		]);
	}
	

	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response|NULL|void Redirects to overview.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function delete () {
		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_pageRole = $this->PageRoles->get($li_id);
		if ($this->PageRoles->delete($lo_pageRole)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

