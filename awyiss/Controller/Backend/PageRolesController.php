<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * PageRoles Controller
 *
 * @property \Awyiss\Model\Table\PageRolesTable $PageRoles
 */
class PageRolesController extends Controller {
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
		$lo_query = $this->PageRoles->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($lo_query);

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
			$lo_pageRoles = $this->paginate($lo_query);
		}
		else {
			$lo_pageRoles = $lo_query->all();
		}

		$this->set([
			'pageRoles' => $lo_pageRoles,
			'attributes' => $this->PageRoles->getAttributes(),
			'paginated' => $lb_paginated,
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

		$lo_pageRole = $this->PageRoles->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_pageRole);
		}

		$this->set([
			'pageRole' => $lo_pageRole,
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

		/**
		 * @var \Awyiss\Model\Entity\PageRole $lo_pageRole
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$lo_pageRole = $this->PageRoles->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_pageRole) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_pageRole, 'edit');
		}

		$this->set([
			'pageRole' => $lo_pageRole,
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

		/** @var PageRole $lo_pageRole */
		$lo_pageRole = $this->PageRoles->findById($id)->first();
		if (!$lo_pageRole) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageRoles->delete($lo_pageRole)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_pageRole->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param PageRole $pageRole
	 * @param string $method
	 * @return void
	 */
	protected function save(PageRole $pageRole, string $method = 'add'): void {
		$la_associated = [];
		if ($this->PageRoles->hasAttributes()) {
			$la_associated[] = $this->PageRoles->getAttributesTableName(true);
			$pageRole->setAccess('attributes', true);
		}

		$this->PageRoles->patchEntity($pageRole, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->PageRoles->save($pageRole, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($pageRole),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $pageRole->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($pageRole->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		else {
			if ($this->PageRoles->hasDirtyRelatedSystemOrderColumns($pageRole)) {
				$pageRole->systemOrder = null;
			}
			else {
				$pageRole->systemOrder = $pageRole->hasOriginal('systemOrder') ? $pageRole->getOriginal('systemOrder') : $pageRole->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $pageRole->systemOrder);
			$this->setRequest($lo_request);
		}
	}
}
