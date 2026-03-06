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
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
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
		$query = $this->MediaSelectors->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->MediaSelectors->find()->contain(['CreatedByUser', 'ChangedByUser', 'DeletedByUser']);
		$mediaSelectors = $this->paginate($query);

		$this->set([
			'mediaSelectors' => $mediaSelectors,
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

		$mediaSelector = $this->MediaSelectors->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($mediaSelector);
		}

		$this->set([
			'mediaSelector' => $mediaSelector,
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

		/**
		 * @var \Awyiss\Model\Entity\MediaSelector $mediaSelector
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$mediaSelector = $this->MediaSelectors->findById($id)->find('translations')->first();
		if (!$mediaSelector) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($mediaSelector, 'edit');
		}

		$this->set([
			'mediaSelector' => $mediaSelector,
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

		/** @var \Awyiss\Model\Entity\MediaSelector $mediaSelector */
		$mediaSelector = $this->MediaSelectors->findById($id)->first();
		if (!$mediaSelector) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaSelectors->delete($mediaSelector)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($mediaSelector->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaSelector $mediaSelector
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(MediaSelector $mediaSelector, string $method = 'add'): void {
		$associated = [];
		if ($this->MediaSelectors->hasAttributes()) {
			$associated[] = $this->MediaSelectors->getAttributesTableName(true);
			$mediaSelector->setAccess('attributes', true);
		}

		$this->MediaSelectors->patchEntity($mediaSelector, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->MediaSelectors->save($mediaSelector, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($mediaSelector),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $mediaSelector->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($mediaSelector->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}
}
