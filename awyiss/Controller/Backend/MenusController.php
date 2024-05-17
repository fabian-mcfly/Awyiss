<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Menu;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * Menus Controller
 *
 * @property \Awyiss\Model\Table\MenusTable $Menus
 */
class MenusController extends Controller {
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
		return $this->Menus->find();
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
			$lo_menus = $this->paginate($lo_query);
		}
		else {
			$lo_menus = $lo_query->all();
		}

		$this->set([
			'menus' => $lo_menus,
			'paginated' => $lb_paginated,
			'attributes' => $this->Menus->getAttributes(),
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

		$lo_menu = $this->Menus->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_menu);
		}

		$this->set([
			'menu' => $lo_menu,
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

		/** @var Menu $lo_menu */
		$lo_menu = $this->Menus->findById($id)->find('translations')->first();
		if (!$lo_menu) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menu, 'edit');
		}

		$this->set([
			'menu' => $lo_menu,
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

		/** @var Menu $lo_menu */
		$lo_menu = $this->Menus->findById($id)->find('translations')->first();
		if (!$lo_menu) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Menus->delete($lo_menu)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_menu->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Menu $menu
	 * @param string $method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(Menu $menu, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Menus->hasAttributes()) {
			$la_associated[] = $this->Menus->getAttributesTableName(true);
			$menu->setAccess('attributes', true);
		}

		$this->Menus->patchEntity($menu, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Menus->save($menu, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($menu),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $menu->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($menu->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
