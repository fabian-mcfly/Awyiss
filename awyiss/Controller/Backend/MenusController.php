<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


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
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Menus->find();

		return $lo_query;
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
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var Menu $lo_menu */
		$lo_menu = $this->Menus->findById($ai_id)->find('translations')->first();
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
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Menu $lo_menu */
		$lo_menu = $this->Menus->findById($ai_id)->find('translations')->first();
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
	 * @param Menu $ao_menu
	 * @param string $as_method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(Menu $ao_menu, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Menus->hasAttributes()) {
			$la_associated[] = $this->Menus->getAttributesTableName(true);
			$ao_menu->setAccess('attributes', true);
		}

		$this->Menus->patchEntity($ao_menu, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Menus->save($ao_menu, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($as_method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($ao_menu),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_menu->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_menu->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
