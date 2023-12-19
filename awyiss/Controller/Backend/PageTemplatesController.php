<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * PageTemplates Controller
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable $PageTemplates
 */
class PageTemplatesController extends Controller {
	public array $categorize = [
		'associationName' => 'PageRoles',
		'enabled' => TRUE,
	];


	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		//$lo_pageTemplates = $this->Categories->filterQuery($this->PageTemplates->find('withAttributes'));
		$lo_pageTemplates = $this->Categories->groupQuery($this->PageTemplates->find('withAttributes'));

		$this->set([
			'ao_pageTemplates' => $lo_pageTemplates,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_pageTemplate = $this->PageTemplates->newDefaultEntity();
		if ($this->request->is('post')) {
			$lo_pageTemplate = $this->PageTemplates->patchEntity($lo_pageTemplate, $this->request->getData());

			$this->Categories->ensurePossibleCategorySelection($lo_pageTemplate);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->PageTemplates->save($lo_pageTemplate)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_pageTemplate->id]);
				}

				$this->Flash->error(__('::add_failed'));
			}
			else {
				$lo_pageTemplate->system_order = NULL;
			}
		}
		else {
			$this->Categories->ensurePossibleCategorySelection($lo_pageTemplate);
		}

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$li_id = $this->request->getParam('id');
		$lo_pageTemplate = $this->PageTemplates->find()->where(['id' => $li_id])->first();

		if ( ! $lo_pageTemplate) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$lo_pageTemplate = $this->PageTemplates->patchEntity($lo_pageTemplate, $this->request->getData());

			$this->Categories->ensurePossibleCategorySelection($lo_pageTemplate);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->PageTemplates->save($lo_pageTemplate)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_pageTemplate->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
			else {
				$lo_pageTemplate->system_order = NULL;
			}
		}
		else {
			$this->Categories->ensurePossibleCategorySelection($lo_pageTemplate);
		}

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_pageTemplate = $this->PageTemplates->get($li_id);

		if ( ! $lo_pageTemplate) {
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

