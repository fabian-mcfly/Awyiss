<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Language;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;


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
	public function overview (): void {
		$this->Authorization->ensure('read');

		$lo_languages = $this->Languages->find('withAttributes')->where($this->getOverviewWhere());
		$lo_languages = $lo_languages->all()->groupBy('type');

		$this->set([
			'ao_languages' => $lo_languages,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Authorization->ensure('create');

		$lo_language = $this->Languages->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->save($lo_language);
		}

		$this->set([
			'ao_language' => $lo_language,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/** @var Language $lo_language */
		$lo_language = $this->Languages->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_language) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_language, 'edit');
		}

		$this->set([
			'ao_language' => $lo_language,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Language $lo_language */
		$lo_language = $this->Languages->findById((int) $this->request->getParam('id'))->first();
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


	/**
	 * @param Language $ao_language
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (Language $ao_language, string $as_method = 'add'): void {
		$this->Languages->patchEntity($ao_language, $this->request->getData());

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Languages->save($ao_language)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_language->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method . '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_language->getError('_general')));
		}
		else {
			if ($this->Languages->getSystemOrderRelatedColumns($ao_language)) {
				$ao_language->system_order = NULL;
			}
			else {
				$ao_language->system_order = $ao_language->getOriginal('system_order');
			}
		}
	}
}

