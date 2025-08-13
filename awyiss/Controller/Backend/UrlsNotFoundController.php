<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * UrlsNotFound Controller
 *
 * @property \Awyiss\Model\Table\UrlsNotFoundTable $UrlsNotFound
 */
class UrlsNotFoundController extends Controller {
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
		$lo_query = $this->UrlsNotFound->find()->where($this->getOverviewWhere());
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

		$lo_pagesTable = $this->fetchTable('Pages');
		/**
		 * @uses \Awyiss\Model\Table::findActive()
		 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
		 */
		$lo_pagesQuery = $lo_pagesTable->find('active', skipPageRoleCheck: true)
		->find('published')
		->disableAutoFields()
		->select(function ($query) {
			return ['slug' => $query->func()->concat(['/', 'language_shortcode' => 'identifier', '/', 'slug' => 'identifier'])];
		});

		$lo_urlHistoryTable = $this->fetchTable('UrlHistory');
		$lo_urlHistoryQuery = $lo_urlHistoryTable->find()
		->disableAutoFields()
		->select(function ($query) {
			return ['url' => $query->func()->concat(['/', 'url' => 'identifier'])];
		});

		$lo_query = $this->getOverviewQuery()
		->where(function ($exp) use ($lo_pagesQuery, $lo_urlHistoryQuery) {
			return $exp->notIn('url', $lo_pagesQuery)->notIn('url', $lo_urlHistoryQuery);
		});

		$lb_grouped = $this->request->getParam('grouped', false) === 'true';
		if ($lb_grouped) {
			$lo_query->select([
				'occurrences' => $lo_query->func()->count('*'),
				'first_occurrence' => $lo_query->func()->min('created_on', ['datetime']),
				'last_occurrence' => $lo_query->func()->max('created_on', ['datetime']),
			])
			->enableAutoFields()
			->groupBy('url');

			array_unshift($this->paginate['order'], 'occurrences');

			/** @var \Awyiss\Model\Entity\UrlsNotFound $ls_entityClass */
			$ls_entityClass = $this->UrlsNotFound->getEntityClass();
			$ls_entityClass::addFieldMapping('first_occurrence', 'firstOccurrence');
			$ls_entityClass::addFieldMapping('last_occurrence', 'lastOccurrence');
			$lo_urlsNotFound = $this->paginate($lo_query, [
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
			$lo_urlsNotFound = $this->paginate($lo_query);
		}

		$this->set([
			'urlsNotFound' => $lo_urlsNotFound,
			'attributes' => $this->UrlsNotFound->getAttributes(),
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

		/** @var \Awyiss\Model\Entity\UrlsNotFound $lo_urlsNotFound */
		$lo_urlsNotFound = $this->UrlsNotFound->findById($id)->first();
		if (!$lo_urlsNotFound) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->UrlsNotFound->delete($lo_urlsNotFound)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));

				foreach ($lo_urlsNotFound->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}
}
