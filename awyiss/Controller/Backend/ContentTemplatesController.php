<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * ContentTemplates Controller
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable $ContentTemplates
 * @method \Awyiss\Model\Entity\ContentTemplate[]|\Cake\Datasource\ResultSetInterface paginate($object = NULL, array $settings = [])
 */
class ContentTemplatesController extends Controller {
	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function overview () {
		$contentTemplates = $this->paginate($this->ContentTemplates->find('withAttributes'));

		$this->set(compact('contentTemplates'));
	}


	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
	 */
	public function add () {
		$contentTemplate = $this->ContentTemplates->newEmptyEntity();
		if ($this->request->is('post')) {
			$contentTemplate = $this->ContentTemplates->patchEntity($contentTemplate, $this->request->getData());
			if ($this->ContentTemplates->save($contentTemplate)) {
				$this->Flash->success(__('The content template has been saved.'));

				return $this->redirect(['action' => 'overview']);
			}
			$this->Flash->error(__('The content template could not be saved. Please, try again.'));
		}
		$this->set(compact('contentTemplate'));
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function edit () {
		$id = $this->request->getParam('id');

		$contentTemplate = $this->ContentTemplates->get($id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$contentTemplate = $this->ContentTemplates->patchEntity($contentTemplate, $this->request->getData());
			if ($this->ContentTemplates->save($contentTemplate)) {
				$this->Flash->success(__('The content template has been saved.'));

				return $this->redirect(['action' => 'overview']);
			}
			$this->Flash->error(__('The content template could not be saved. Please, try again.'));
		}
		$this->set(compact('contentTemplate'));
	}


	/**
	 * Delete method
	 *
	 * @param string|null $id Content Template id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects to overview.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete ($id = NULL) {
		$this->request->allowMethod(['post', 'delete']);
		$contentTemplate = $this->ContentTemplates->get($id);
		if ($this->ContentTemplates->delete($contentTemplate)) {
			$this->Flash->success(__('The content template has been deleted.'));
		}
		else {
			$this->Flash->error(__('The content template could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}
