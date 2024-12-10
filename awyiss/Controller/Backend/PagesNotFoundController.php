<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * PagesNotFound Controller
 *
 * @property \Awyiss\Model\Table\PagesNotFoundTable $PagesNotFound
 */
class PagesNotFoundController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'order' => [
			'created_on' => 'desc',
			'id' => 'desc',
		],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->PagesNotFound->find()->where($this->getOverviewWhere());
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_pagesTable = $this->fetchTable('Pages');
		$lo_pagesQuery = $lo_pagesTable->find('active', skipPageRoleCheck: true)
		->disableAutoFields()
		->find('published')
		->select(function ($query) {
			return ['slug' => $query->func()->concat(['/', 'language_shortcode' => 'identifier', '/', 'slug' => 'identifier', '/'])];
		});

		$lo_urlHistoryTable = $this->fetchTable('UrlHistory');
		$lo_urlHistoryQuery = $lo_urlHistoryTable->find()
		->disableAutoFields()
		->select(function ($query) {
			return ['slug' => $query->func()->concat(['/', 'slug' => 'identifier', '/'])];
		});

		$lo_query = $this->PagesNotFound->find()
		->where(function ($exp) use ($lo_pagesQuery, $lo_urlHistoryQuery) {
			return $exp->notIn('slug', $lo_pagesQuery)->notIn('slug', $lo_urlHistoryQuery);
		});

		$lb_grouped = $this->request->getParam('grouped', false) === 'true';
		if ($lb_grouped) {
			$lo_query->select([
				'occurrences' => $lo_query->func()->count('*'),
				'first_occurrence' => $lo_query->func()->min('created_on', ['datetime']),
				'last_occurrence' => $lo_query->func()->max('created_on', ['datetime']),
			])
			->enableAutoFields()
			->groupBy('slug');

			array_unshift($this->paginate['order'], 'occurrences');

			/** @var \Awyiss\Model\Entity\PagesNotFound $ls_entityClass */
			$ls_entityClass = $this->PagesNotFound->getEntityClass();
			$ls_entityClass::addFieldMapping('first_occurrence', 'firstOccurrence');
			$ls_entityClass::addFieldMapping('last_occurrence', 'lastOccurrence');
			$lo_pagesNotFound = $this->paginate($lo_query, [
				'order' => [
					'occurrences' => 'desc',
				],
				'defaultSortableFields' => [
					'occurrences',
					'first_occurrence',
					'last_occurrence',
				],
			]);
		}
		else {
			$lo_pagesNotFound = $this->paginate($lo_query);
		}

		$this->set([
			'pagesNotFound' => $lo_pagesNotFound,
			'attributes' => $this->PagesNotFound->getAttributes(),
			'grouped' => $lb_grouped,
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

		/** @var \Awyiss\Model\Entity\PagesNotFound $lo_pagesNotFound */
		$lo_pagesNotFound = $this->PagesNotFound->findById($id)->first();
		if (!$lo_pagesNotFound) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PagesNotFound->delete($lo_pagesNotFound)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_pagesNotFound->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}
}
