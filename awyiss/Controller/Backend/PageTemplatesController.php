<?php

declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * PageTemplates Controller
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable $PageTemplates
 * @method \Awyiss\Model\Entity\PageTemplate[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class PageTemplatesController extends Controller {
	use \Awyiss\Authorization\Trait\BasicCrudPermissionsTrait;

	
	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|NULL|void Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->paginate = [
			'contain' => ['PageRoles'],
		];
		$lo_pageTemplates = $this->paginate($this->PageTemplates->find('withAttributes'));

		$this->set([
			'pageTemplates' => $lo_pageTemplates,
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
			'pageTemplate' => $lo_pageTemplate,
			'pageRoles' => $this->PageTemplates->PageRoles->find('list', ['limit' => 200]),
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
		$lo_pageTemplate = $this->PageTemplates->get($li_id, [
			'contain' => [],
		]);
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
			'pageTemplate' => $lo_pageTemplate,
			'$pageRoles' => $this->PageTemplates->PageRoles->find('list', ['limit' => 200]),
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
		$lo_pageTemplate = $this->PageTemplates->get($li_id);
		if ($this->PageTemplates->delete($lo_pageTemplate)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

