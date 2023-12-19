<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table\PageRolesTable;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Awyiss\Routing\Router;


/**
 * PageRoles Controller
 *
 * @property PageRolesTable $PageRoles
 */
class PageRolesController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Authorization->ensure('read');

		$lo_pageRoles = $this->PageRoles->find()->where($this->getOverviewWhere());

		$this->set([
			'ao_pageRoles' => $lo_pageRoles,
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

		$lo_pageRole = $this->PageRoles->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_pageRole);
		}

		$this->set([
			'ao_pageRole' => $lo_pageRole,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/** @var PageRole $lo_pageRole */
		$lo_pageRole = $this->PageRoles->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if ( ! $lo_pageRole) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_pageRole, 'edit');
		}

		$this->set([
			'ao_pageRole' => $lo_pageRole,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var PageRole $lo_pageRole */
		$lo_pageRole = $this->PageRoles->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if ( ! $lo_pageRole) {
			$this->Flash->error(__('record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageRoles->delete($lo_pageRole)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param PageRole $ao_pageRole
	 * @param string   $as_method
	 *
	 * @return void
	 */
	protected function save (PageRole $ao_pageRole, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->PageRoles->hasAttributes()) {
			$la_associated[] = $this->PageRoles->getAttributesTable(TRUE);
			$ao_pageRole->setAccess('attributes', TRUE);
		}

		$this->PageRoles->patchEntity($ao_pageRole, $this->request->getData(), ['associated' => $la_associated]);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->PageRoles->save($ao_pageRole)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_pageRole->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_pageRole->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}

