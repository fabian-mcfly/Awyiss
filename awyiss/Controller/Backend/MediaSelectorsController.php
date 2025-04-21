<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\MediaSelector;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * MediaSelectors Controller
 *
 * @property \Awyiss\Model\Table\MediaSelectorsTable $MediaSelectors
 * @method \Awyiss\Model\Entity\MediaSelector[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MediaSelectorsController extends Controller {
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
		$lo_query = $this->MediaSelectors->find()->where($this->getOverviewWhere());
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

		$lo_query = $this->MediaSelectors->find()->contain(['CreatedByUser', 'ChangedByUser', 'DeletedByUser']);
		$lo_mediaSelectors = $this->paginate($lo_query);

		$this->set([
			'mediaSelectors' => $lo_mediaSelectors,
			'attributes' => $this->MediaSelectors->getAttributes(),
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

		$lo_mediaSelector = $this->MediaSelectors->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_mediaSelector);
		}

		$this->set([
			'mediaSelector' => $lo_mediaSelector,
			'createdByUser' => $this->MediaSelectors->CreatedByUser->find('list', ['limit' => 200]),

			'changedByUser' => $this->MediaSelectors->ChangedByUser->find('list', ['limit' => 200]),

			'deletedByUser' => $this->MediaSelectors->DeletedByUser->find('list', ['limit' => 200]),

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

		/** @var MediaSelector $lo_mediaSelector */
		$lo_mediaSelector = $this->MediaSelectors->findById($id)->find('translations')->first();
		if (!$lo_mediaSelector) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_mediaSelector, 'edit');
		}

		$this->set([
			'mediaSelector' => $lo_mediaSelector,
			'createdByUser' => $this->MediaSelectors->CreatedByUser->find('list', ['limit' => 200]),

			'changedByUser' => $this->MediaSelectors->ChangedByUser->find('list', ['limit' => 200]),

			'deletedByUser' => $this->MediaSelectors->DeletedByUser->find('list', ['limit' => 200]),

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

		/** @var MediaSelector $lo_mediaSelector */
		$lo_mediaSelector = $this->MediaSelectors->findById($id)->first();
		if (!$lo_mediaSelector) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaSelectors->delete($lo_mediaSelector)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_mediaSelector->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param MediaSelector $mediaSelector
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(MediaSelector $mediaSelector, string $method = 'add'): void {
		$la_associated = [];
		if ($this->MediaSelectors->hasAttributes()) {
			$la_associated[] = $this->MediaSelectors->getAttributesTableName(true);
			$mediaSelector->setAccess('attributes', true);
		}

		$this->MediaSelectors->patchEntity($mediaSelector, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->MediaSelectors->save($mediaSelector, ['asCopy' => $lb_saveAsCopy])) {
				$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $mediaSelector->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($mediaSelector->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
