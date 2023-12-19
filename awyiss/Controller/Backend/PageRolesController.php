<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * PageRoles Controller
 *
 * @property \Awyiss\Model\Table\PageRolesTable $PageRoles
 */
class PageRolesController extends Controller {
	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_pageRoles = $this->PageRoles->find('withAttributes')->where($this->getOverviewWhere());

		$this->set([
			'ao_pageRoles' => $lo_pageRoles,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_pageRole = $this->PageRoles->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->PageRoles->patchEntity($lo_pageRole, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->PageRoles->save($lo_pageRole)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_pageRole->id]);
				}
				$this->Flash->error(__('::add_failed'));
			}
		}

		$this->set([
			'ao_pageRole' => $lo_pageRole,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$li_id = $this->request->getParam('id');
		$lo_pageRole = $this->PageRoles->find()->where(['id' => $li_id])->first();

		if ( ! $lo_pageRole) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->PageRoles->patchEntity($lo_pageRole, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->PageRoles->save($lo_pageRole)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_pageRole->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
		}

		$this->set([
			'ao_pageRole' => $lo_pageRole,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_pageRole = $this->PageRoles->find()->where(['id' => $li_id])->first();

		if ( ! $lo_pageRole) {
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

