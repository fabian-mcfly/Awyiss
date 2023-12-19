<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * Languages Controller
 *
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 * @method \Awyiss\Model\Entity\Language[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class LanguagesController extends Controller {
	private array $la_overviewWhere = ['type' => 'frontend'];


	public function initialize (): void {
		parent::initialize();

		if ($ls_type = $this->request->getParam('type')) {
			$this->la_overviewWhere['type'] = $ls_type;
		}
	}


	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|NULL|void Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$lo_languages = $this->paginate($this->Languages->find('withAttributes')->where($this->la_overviewWhere));

		$this->set([
			'languages' => $lo_languages,
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
		$lo_language = $this->Languages->newEmptyEntity();
		if ($this->request->is('post')) {
			$lo_language = $this->Languages->patchEntity($lo_language, $this->request->getData());
			if ($this->Languages->save($lo_language)) {
				$this->Flash->success(__('::add_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_language->id]);
			}
			$this->Flash->error(__('::add_failed'));
		}

		$this->set([
			'language' => $lo_language,
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
		$lo_language = $this->Languages->get($li_id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$lo_language = $this->Languages->patchEntity($lo_language, $this->request->getData());
			if ($this->Languages->save($lo_language)) {
				$this->Flash->success(__('::edit_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_language->id]);
			}
			$this->Flash->error(__('::edit_failed'));
		}

		$this->set([
			'language' => $lo_language,
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
		$lo_language = $this->Languages->get($li_id);
		if ($this->Languages->delete($lo_language)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

