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
	protected array $paginate = [
		'enabled' => true,
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Datatables->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();
		$datatables = $this->paginate($query);

		$this->set([
			'datatables' => $datatables,
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

		$datatable = $this->Datatables->newDefaultEntity([
			'mediaElementAssignments' => [],
		]);

		if ($this->request->is('post')) {
			$this->save($datatable);
		}

		$this->set([
			'datatable' => $datatable,
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

		$datatables = $this->Datatables->findAllAndCache();
		/** @var \Awyiss\Model\Entity\Datatable $datatable */
		$datatable = $datatables->firstMatch(['id' => $id]);

		if (!$datatable) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($datatable, 'edit');
		}

		$this->set([
			'datatable' => $datatable,
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

		/** @var \Awyiss\Model\Entity\Datatable $datatable */
		$datatable = $this->Datatables->findById($id)->first();
		if (!$datatable) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Datatables->delete($datatable)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($datatable->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		$associated = [];
		if ($this->Datatables->hasAttributes()) {
			$associated[] = $this->Datatables->getAttributesTableName(true);
			$datatable->setAccess('attributes', true);
		}

		$this->Datatables->patchEntity($datatable, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->Datatables->save($datatable, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($datatable),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $datatable->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($datatable->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}
}
