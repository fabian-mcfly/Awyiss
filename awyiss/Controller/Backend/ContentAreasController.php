<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\ContentArea;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * ContentAreas Controller
 *
 * @property \Awyiss\Model\Table\ContentAreasTable $ContentAreas
 */
class ContentAreasController extends Controller {
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
		$query = $this->ContentAreas->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->setScope('PageTemplates')->ensure('read');

		$query = $this->getOverviewQuery();
		$contentAreas = $this->paginate($query);

		$this->set([
			'contentAreas' => $contentAreas,
			'attributes' => $this->ContentAreas->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->setScope('PageTemplates')->ensure('create');

		$contentArea = $this->ContentAreas->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($contentArea);
		}

		$this->setViewVars($contentArea);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->setScope('PageTemplates')->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\ContentArea $contentArea
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$contentArea = $this->ContentAreas->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();

		if (!$contentArea) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($contentArea, 'edit');
		}

		$this->setViewVars($contentArea);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->setScope('PageTemplates')->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\ContentArea $contentArea */
		$contentArea = $this->ContentAreas->findById($id)->first();
		if (!$contentArea) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->ContentAreas->delete($contentArea)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($contentArea->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param ContentArea $contentArea
	 * @param string $method
	 * @return void
	 */
	protected function save(ContentArea $contentArea, string $method = 'add'): void {
		$associated = [];
		if ($this->ContentAreas->hasAttributes()) {
			$associated[] = $this->ContentAreas->getAttributesTableName(true);
			$contentArea->setAccess('attributes', true);
		}

		$requestData = $this->request->getData() + ['content_template_elements' => []];

		$this->ContentAreas->patchEntity($contentArea, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->ContentAreas->save($contentArea, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($contentArea),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $contentArea->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($contentArea->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentArea $contentArea
	 * @return void
	 */
	protected function setViewVars(ContentArea $contentArea): void {
		$this->set([
			'contentArea' => $contentArea,
		]);
	}


	/**
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function requestLock(string $method = 'update'): void {
		$this->Authorization->setScope('PageTemplates');

		parent::requestLock($method);
	}


	/**
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function releaseLock(string $method = 'update'): void {
		$this->Authorization->setScope('PageTemplates');

		parent::releaseLock($method);
	}
}
