<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * Datatables Controller
 *
 * @property \Awyiss\Model\Table\DatatablesTable $Datatables
 */
class DatatablesController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->Datatables->find()->where($this->getOverviewWhere());
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();
		$lo_datatables = $this->paginate($lo_query);

		$this->set([
			'datatables' => $lo_datatables,
			'attributes' => $this->Datatables->getAttributes(),
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
			'datatable' => $lo_datatable,
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		$lo_datatables = $this->Datatables->findAllAndCache();
		/** @var Datatable $lo_datatable */
		$lo_datatable = $lo_datatables->firstMatch(['id' => $id]);

		if (! $lo_datatable) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_datatable, 'edit');
		}

		$this->set([
			'datatable' => $lo_datatable,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Datatable $lo_datatable */
		$lo_datatable = $this->Datatables->findById($id)->first();
		if (! $lo_datatable) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Datatables->delete($lo_datatable)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
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
	 * @param Datatable $datatable
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(Datatable $datatable, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Datatables->hasAttributes()) {
			$la_associated[] = $this->Datatables->getAttributesTableName(true);
			$datatable->setAccess('attributes', true);
		}

		$this->Datatables->patchEntity($datatable, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Datatables->save($datatable, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($datatable),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $datatable->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($datatable->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
