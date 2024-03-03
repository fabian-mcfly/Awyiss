<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * Datatables Controller
 *
 * @property \Awyiss\Model\Table\DatatablesTable $Datatables
 * @method Datatable[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = null, array $aa_settings = [])
 */
class DatatablesController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_datatables = $this->paginate($this->Datatables->find());

		$this->set([
			'ao_datatables' => $lo_datatables,
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

		$lo_datatable = $this->Datatables->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_datatable);
		}

		$this->set([
			'ao_datatable' => $lo_datatable,
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $ai_id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		$lo_datatables = $this->Datatables->findAllAndCache();
		/** @var Datatable $lo_datatable */
		$lo_datatable = $lo_datatables->firstMatch(['id' => $ai_id]);

		if (! $lo_datatable) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_datatable, 'edit');
		}

		$this->set([
			'ao_datatable' => $lo_datatable,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Datatable $lo_datatable */
		$lo_datatable = $this->Datatables->findById($ai_id)->first();
		if (! $lo_datatable) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Datatables->delete($lo_datatable)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_datatable->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Datatable $ao_datatable
	 * @param string $as_method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(Datatable $ao_datatable, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Datatables->hasAttributes()) {
			$la_associated[] = $this->Datatables->getAttributesTableName(true);
			$ao_datatable->setAccess('attributes', true);
		}

		$this->Datatables->patchEntity($ao_datatable, $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Datatables->save($ao_datatable, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_datatable->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_datatable->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
