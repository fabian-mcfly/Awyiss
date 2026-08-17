<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Exception;


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
			'createdOn' => 'desc',
			'id' => 'desc',
		],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->UrlsNotFound->find()->where($this->getOverviewWhere());
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

		$pagesTable = $this->fetchTable('Pages');
		/**
		 * @uses \Awyiss\Model\Table::findActive()
		 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
		 */
		$pagesQuery = $pagesTable
			->find('active', skipPageRoleCheck: true, customerGroupAccessSettings: ['skip' => true])
			->disableAutoFields()
			->find('published')
			->select(fn($query) => [
				'slug' => $query->func()->concat(['/', 'languageShortcode' => 'identifier', '/', 'slug' => 'identifier']),
			], true)
		;

		$urlHistoryTable = $this->fetchTable('UrlHistory');
		$urlHistoryQuery = $urlHistoryTable
			->find()
			->disableAutoFields()
			->select(function ($query) {
				return ['url' => $query->func()->concat(['/', 'url' => 'identifier'])];
			})
		;

		$query = $this
			->getOverviewQuery()
			->where(function ($exp) use ($pagesQuery, $urlHistoryQuery) {
				return $exp->notIn('url', $pagesQuery)->notIn('url', $urlHistoryQuery);
			})
		;

		$grouped = $this->request->getParam('grouped', false) === 'true';
		if ($grouped) {
			$query
				->select([
					'occurrences' => $query->func()->count('*'),
					'firstOccurrence' => $query->func()->min('createdOn', ['datetime']),
					'lastOccurrence' => $query->func()->max('createdOn', ['datetime']),
				])
				->enableAutoFields()
				->groupBy('url')
			;

			array_unshift($this->paginate['order'], 'occurrences');

			$urlsNotFound = $this->paginate($query, [
				'order' => [
					'occurrences' => 'desc',
				],
				'defaultSortableFields' => [
					'occurrences',
					'firstOccurrence',
					'lastOccurrence',
				],
			]);
		}
		else {
			$urlsNotFound = $this->paginate($query);
		}

		$this->set([
			'urlsNotFound' => $urlsNotFound,
			'attributes' => $this->UrlsNotFound->getAttributes(),
			'grouped' => $grouped,
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

		/** @var \Awyiss\Model\Entity\UrlsNotFound $urlsNotFound */
		$urlsNotFound = $this->UrlsNotFound->findById($id)->first();
		if (!$urlsNotFound) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->UrlsNotFound->delete($urlsNotFound)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));

				foreach ($urlsNotFound->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function deleteOld(): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		$duration = $this->request->getParam('olderThan');
		$duration = match ($duration) {
			'oneWeek' => '-1 week',
			'oneMonth' => '-1 month',
			'oneYear' => '-1 year',
			'all' => null,
		};

		$where = [];
		if ($duration) {
			$where['createdOn <'] = new DateTime($duration);
		}

		try {
			$this->UrlsNotFound->deleteAll($where);
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_old_succeeded'));
			}
		}
		catch (Exception) {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_old_failed'));
			}
		}

		return $this->redirect(['action' => 'overview']);
	}
}
