<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * Languages Controller
 *
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 */
class LanguagesController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->Languages->find()->where($this->getOverviewWhere());
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_languages = $this->paginate($lo_query);
		}
		else {
			$lo_languages = $lo_query->all();
			$lo_languagesByRealm = $lo_languages->groupBy('realm');
		}

		$this->set([
			'languages' => $lo_languages,
			'languagesByRealm' => $lo_languagesByRealm ?? null,
			'paginated' => $lb_paginated,
			'realms' => Awyiss::getRealms(),
			'attributes' => $this->Languages->getAttributes(),
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
			'language' => $lo_language,
			'realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var Language $lo_language */
		$lo_language = $this->Languages->findById($id)->find('translations')->find('mediaAssignments')->find('mediaCompositeAssignments')->first();
		if (!$lo_language) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_language, 'edit');
		}

		$this->set([
			'language' => $lo_language,
			'realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Language $lo_language */
		$lo_language = $this->Languages->findById($id)->first();
		if (!$lo_language) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Languages->delete($lo_language)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
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
	 * @param Language $language
	 * @param string $method
	 * @return void
	 */
	protected function save(Language $language, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Languages->hasAttributes()) {
			$la_associated[] = $this->Languages->getAttributesTableName(true);
			$language->setAccess('attributes', true);
		}

		$this->Languages->patchEntity($language, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Languages->save($language, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($language),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $language->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($language->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Languages->getSystemOrderRelatedColumns($language)) {
				$language->systemOrder = null;
			}
			else {
				$language->systemOrder = $language->getOriginal('systemOrder');
			}
		}
	}
}
