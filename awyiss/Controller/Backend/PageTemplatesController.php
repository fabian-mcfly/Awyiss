<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;

use Cake\Http\Response;

/**
 * PageTemplates Controller
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable $PageTemplates
 * @method \Awyiss\Model\Entity\PageTemplate[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class PageTemplatesController extends Controller {
	
	/**
	 * Overview method
	 *
	 * @return void|?Response Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->assureOne('create', 'update', 'delete');

		$this->paginate = [
			'contain' => ['PageRoles'],
		];
		$lo_pageTemplates = $this->paginate($this->PageTemplates->find('withAttributes'));

		$this->set([
			'ao_pageTemplates' => $lo_pageTemplates,
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

		$lo_pageTemplate = $this->PageTemplates->newEmptyEntity();
		if ($this->request->is('post')) {
			$lo_pageTemplate = $this->PageTemplates->patchEntity($lo_pageTemplate, $this->request->getData());
			if ($this->PageTemplates->save($lo_pageTemplate)) {
				$this->Flash->success(__('::add_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_pageTemplate->id]);
			}
			$this->Flash->error(__('::add_failed'));
		}

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
			'ao_pageRoles' => $this->PageTemplates->PageRoles->find('list', ['limit' => 200]),
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return void|?Response Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->assure('update');

		$li_id = $this->request->getParam('id');
		$lo_pageTemplate = $this->PageTemplates->find()->where(['id' => $li_id])->first();

		if (!$lo_pageTemplate) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$lo_pageTemplate = $this->PageTemplates->patchEntity($lo_pageTemplate, $this->request->getData());
			if ($this->PageTemplates->save($lo_pageTemplate)) {
				$this->Flash->success(__('::edit_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_pageTemplate->id]);
			}
			$this->Flash->error(__('::edit_failed'));
		}

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
			'ao_pageRoles' => $this->PageTemplates->PageRoles->find('list', ['limit' => 200]),
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
		$this->Access->assureOne('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_pageTemplate = $this->PageTemplates->get($li_id);

		if (!$lo_pageTemplate) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageTemplates->delete($lo_pageTemplate)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

