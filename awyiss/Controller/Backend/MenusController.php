<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Menu;
use Awyiss\Model\Table\MenusTable;
use Awyiss\Routing\Router;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * Menus Controller
 *
 * @property MenusTable $Menus
 * @method Menu[]|ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class MenusController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 *
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_menus = $this->paginate($this->Menus->find());

		$this->set([
			'ao_menus' => $lo_menus,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$lo_menu = $this->Menus->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_menu);
		}

		$this->set([
			'ao_menu' => $lo_menu,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws \Exception
	 *
	 */
	public function edit() {
		$this->Authorization->ensure('update');

		/** @var Menu $lo_menu */
		$lo_menu = $this->Menus->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if (!$lo_menu) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menu, 'edit');
		}

		$this->set([
			'ao_menu' => $lo_menu,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 *
	 */
	public function delete(): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Menu $lo_menu */
		$lo_menu = $this->Menus->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if (!$lo_menu) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Menus->delete($lo_menu)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Menu $ao_menu
	 * @param string $as_method
	 *
	 * @return void
	 *
	 * @throws RedirectException
	 */
	protected function save(Menu $ao_menu, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Menus->hasAttributes()) {
			$la_associated[] = $this->Menus->getAttributesTable(TRUE);
			$ao_menu->setAccess('attributes', TRUE);
		}

		$this->Menus->patchEntity($ao_menu, $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Menus->save($ao_menu)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_menu->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_menu->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
