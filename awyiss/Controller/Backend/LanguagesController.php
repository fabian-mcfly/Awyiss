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
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_languages = $this->Languages->find('withAttributes')->where($this->overviewWhere)->all();
		$lo_languages = $lo_languages->groupBy('type');

		$la_types = [];
		foreach (array_keys($lo_languages->toArray()) as $ls_type) {
			$la_types[ $ls_type ] = __('::' . $ls_type);
		}
		krsort($la_types);
		/*uasort($la_types, function($a, $b) {
			return strnatcmp($a, $b);
		});*/

		$this->set([
			'ao_languages' => $lo_languages,
			'aa_types' => $la_types,
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

		$lo_language = $this->Languages->newDefaultEntity();
		if ($this->request->is('post')) {
			$lo_language = $this->Languages->patchEntity($lo_language, $this->request->getData());

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
			$lo_language = $this->Languages->patchEntity($lo_language, $this->request->getData());

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
				$lo_language->system_order = NULL;
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

