<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * Languages Controller
 *
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 */
class LanguagesController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_languages = $this->Languages->find()->where($this->getOverviewWhere());
		$lo_languages = $lo_languages->all()->groupBy('realm');

		$this->set([
			'ao_languages' => $lo_languages,
			'aa_realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$lo_language = $this->Languages->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_language);
		}

		$this->set([
			'ao_language' => $lo_language,
			'aa_realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var Language $lo_language */
		$lo_language = $this->Languages->findById($ai_id)->find('translations')->first();
		if (!$lo_language) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_language, 'edit');
		}

		$this->set([
			'ao_language' => $lo_language,
			'aa_realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Language $lo_language */
		$lo_language = $this->Languages->findById($ai_id)->find('translations')->first();
		if (!$lo_language) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Languages->delete($lo_language)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_language->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Language $ao_language
	 * @param string $as_method
	 * @return void
	 */
	protected function save(Language $ao_language, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Languages->hasAttributes()) {
			$la_associated[] = $this->Languages->getAttributesTableName(true);
			$ao_language->setAccess('attributes', true);
		}

		$this->Languages->patchEntity($ao_language, $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Languages->save($ao_language, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_language->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_language->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Languages->getSystemOrderRelatedColumns($ao_language)) {
				$ao_language->systemOrder = null;
			}
			else {
				$ao_language->systemOrder = $ao_language->getOriginal('systemOrder');
			}
		}
	}
}
