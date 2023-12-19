<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * Languages Controller
 *
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 */
class LanguagesController extends Controller {
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

		$lo_languages = $this->Languages->find('withAttributes')->where($this->getOverviewWhere());
		$lo_languages = $lo_languages->all()->groupBy('type');

		$this->set([
			'ao_languages' => $lo_languages,
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

		$lo_language = $this->Languages->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->Languages->patchEntity($lo_language, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Languages->save($lo_language)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_language->id]);
				}

				$this->Flash->error(__('::add_failed'));
			}
			else {
				$lo_language->system_order = NULL;
			}
		}

		$this->set([
			'ao_language' => $lo_language,
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
		$lo_language = $this->Languages->find()->where(['id' => $li_id])->first();

		if ( ! $lo_language) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->Languages->patchEntity($lo_language, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Languages->save($lo_language)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_language->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
			else {
				if ($this->Languages->getSystemOrderRelatedColumns($lo_language)) {
					$lo_language->system_order = NULL;
				}
				else {
					$lo_language->system_order = $lo_language->getOriginal('system_order');
				}
			}
		}

		$this->set([
			'ao_language' => $lo_language,
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
		$lo_language = $this->Languages->find()->where(['id' => $li_id])->first();

		if ( ! $lo_language) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Languages->delete($lo_language)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

