<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Cake\Http\Response;


/**
 * PageRoles Controller
 *
 * @property \Awyiss\Model\Table\PageRolesTable $PageRoles
 * @method \Awyiss\Model\Entity\PageRole[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class PageRolesController extends Controller {
	/**
	 * Overview method
	 *
	 * @return void|?Response Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->assureOne('create', 'update', 'delete');

		$lo_pageRoles = $this->PageRoles->find('withAttributes');

		$this->set([
			'ao_pageRoles' => $lo_pageRoles,
		]);
	}
	

	/**
	 * Add method
	 *
	 * @return void|?Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->assure('create');

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
			'ao_pageRole' => $lo_pageRole,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return void|?Response Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpDocSignatureInspection
	 */
	public function edit () {
		$this->Access->assure('update');

		$li_id = $this->request->getParam('id');
		$lo_pageRole = $this->PageRoles->find()->where(['id' => $li_id])->first();

		if (!$lo_pageRole) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$lo_pageRole = $this->PageRoles->patchEntity($lo_pageRole, $this->request->getData());
			if ($this->PageRoles->save($lo_pageRole)) {
				$this->Flash->success(__('::editSucceeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_pageRole->id]);
			}
			$this->Flash->error(__('::editFailed'));
		}

		$this->set([
			'ao_pageRole' => $lo_pageRole,
		]);
	}
	

	/**
	 * Delete method
	 *
	 * @return void|?Response Redirects to overview.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->assure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');

		$lo_pageRole = $this->PageRoles->find()->where(['id' => $li_id])->first();

		if (!$lo_pageRole) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageRoles->delete($lo_pageRole)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

