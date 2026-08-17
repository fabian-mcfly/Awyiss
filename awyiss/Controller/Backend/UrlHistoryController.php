<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Entity\UrlHistory;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Text;


/**
 * UrlHistory Controller
 *
 * @property \Awyiss\Model\Table\UrlHistoryTable $UrlHistory
 */
class UrlHistoryController extends Controller {
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
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected CollectionInterface $threadedPages;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		$scopes = $this->UrlHistory->getAvailableScopes();
		$this->paginate['fieldTranslations']['scope'] = array_combine($scopes, array_map(function ($scope) {
			return Text::slug(__('scope_' . $scope));
		}, $scopes));

		parent::initialize();
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->UrlHistory
			->find()
			->contain([
				'Media',
				'Pages',
			])
		;

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

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		if ($paginated) {
			$urlHistory = $this->paginate($query);
		}
		else {
			$urlHistory = $query->all();
		}

		$this->set([
			'urlHistory' => $urlHistory,
			'paginated' => $paginated,
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

		$urlHistory = $this->UrlHistory->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($urlHistory);
		}

		$this->setViewVars($urlHistory);
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
		 * @var \Awyiss\Model\Entity\UrlHistory $urlHistory
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$urlHistory = $this->UrlHistory
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$urlHistory) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($urlHistory, 'edit');
		}

		$this->setViewVars($urlHistory);
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

		/** @var \Awyiss\Model\Entity\UrlHistory $urlHistory */
		$urlHistory = $this->UrlHistory->findById($id)->first();
		if (!$urlHistory) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->UrlHistory->delete($urlHistory)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($urlHistory->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\UrlHistory $urlHistory
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(UrlHistory $urlHistory, string $method = 'add'): void {
		$associated = [];
		if ($this->UrlHistory->hasAttributes()) {
			$associated[] = $this->UrlHistory->getAttributesTableName(true);
			$urlHistory->setAccess('attributes', true);
		}

		$this->UrlHistory->patchEntity($urlHistory, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->UrlHistory->save($urlHistory, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($urlHistory),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $urlHistory->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($urlHistory->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * Return a collection of pages for the currently set languageShortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	protected function getThreadedPages(): CollectionInterface {
		if (!isset($this->threadedPages)) {
			/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
			$pagesTable = $this->fetchTable('Pages');

			/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
			$query = $pagesTable->find('forCurrentLanguage', skipPageRoleCheck: true);

			$this->threadedPages = $pagesTable->listNested($query);
		}


		return $this->threadedPages;
	}


	/**
	 * Get a collection of pages that have been deleted but are not in the url history
	 *
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getDeletedPages(): CollectionInterface {
		$historyPageIdQuery = $this->UrlHistory
			->find()
			->select('foreignKey')
			->where(['scope' => 'Pages'])
		;
		$pagesSlugQuery = $this->UrlHistory
			->find('all')
			->disableAutoFields()
			->select('url')
		;

		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $this->fetchTable('Pages');

		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted() */
		$query = $pagesTable->find('deleted', skipPageRoleCheck: true);

		$pages = $query
			->where(fn(QueryExpression $exp) => $exp
				->notIn('id', $historyPageIdQuery)
				->notIn(
					$query->func()->concat(['languageShortcode' => 'identifier', '/', 'slug' => 'identifier']),
					$pagesSlugQuery,
					'string'
				))
			->orderBy('title')
			->all()
		;

		return $pages->each(function (Page $page) {
			$page->set('title', $page->label . ' (' . $page->languageShortcode . '/' . $page->slug . ')');
		});
	}


	/**
	 * @param \Awyiss\Model\Entity\UrlHistory $urlHistory
	 * @return void
	 */
	protected function setViewVars(UrlHistory $urlHistory): void {
		$deletedPages = $this->getDeletedPages();

		$threadedPages = $this->getThreadedPages();

		$scopes = [];
		foreach ($this->UrlHistory->getAvailableScopes() as $scope) {
			$scopes[ $scope ] = __('scope_' . Inflector::underscore($scope));
		}

		if ($urlHistory->scope === 'Media' && !$this->request->getData('foreignKey')) {
			$this->UrlHistory->loadInto($urlHistory, ['Media']);
		}

		$this->set([
			'urlHistory' => $urlHistory,
			'deletedPages' => $deletedPages,
			'scopes' => $scopes,
			'threadedPages' => $threadedPages->toList(),
		]);
	}
}
