<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Entity\UrlHistory;
use Awyiss\Routing\Router;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


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
			'created_on' => 'desc',
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
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->UrlHistory->find()->contain([
			'Media',
			'Pages',
		]);

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
			$lo_urlHistory = $this->paginate($lo_query);
		}
		else {
			$lo_urlHistory = $lo_query->all();
		}

		$this->set([
			'urlHistory' => $lo_urlHistory,
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

		$lo_urlHistory = $this->UrlHistory->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_urlHistory);
		}

		$this->setViewVars($lo_urlHistory);
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

		/** @var \Awyiss\Model\Entity\UrlHistory $lo_urlHistory */
		$lo_urlHistory = $this->UrlHistory->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (! $lo_urlHistory) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_urlHistory, 'edit');
		}

		$this->setViewVars($lo_urlHistory);
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

		/** @var \Awyiss\Model\Entity\UrlHistory $lo_urlHistory */
		$lo_urlHistory = $this->UrlHistory->findById($id)->first();
		if (! $lo_urlHistory) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->UrlHistory->delete($lo_urlHistory)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_urlHistory->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
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
		$la_associated = [];
		if ($this->UrlHistory->hasAttributes()) {
			$la_associated[] = $this->UrlHistory->getAttributesTableName(true);
			$urlHistory->setAccess('attributes', true);
		}

		$this->UrlHistory->patchEntity($urlHistory, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->UrlHistory->save($urlHistory, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($urlHistory),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $urlHistory->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($urlHistory->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
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
			/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
			$lo_pagesTable = $this->fetchTable('Pages');

			$lo_query = $lo_pagesTable->find('forCurrentLanguage', skipPageRoleCheck: true);

			$this->threadedPages = $lo_pagesTable->listNested($lo_query);
		}


		return $this->threadedPages;
	}


	/**
	 * Get a collection of pages that have been deleted but are not in the url history
	 *
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getDeletedPages(): CollectionInterface {
		$lo_historyPageIdQuery = $this->UrlHistory->find()->select('foreign_key')->where(['scope' => 'pages']);
		$lo_pagesSlugQuery = $this->UrlHistory->find('all')->disableAutoFields()->select('url');

		/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
		$lo_pagesTable = $this->fetchTable('Pages');

		$lo_query = $lo_pagesTable->find('deleted', skipPageRoleCheck: true);

		$lo_pages = $lo_query->where(function (QueryExpression $exp) use ($lo_historyPageIdQuery, $lo_pagesSlugQuery, $lo_query) {
			return $exp->notIn('id', $lo_historyPageIdQuery)
			->notIn(
				$lo_query->func()->concat(['language_shortcode' => 'identifier', '/', 'slug' => 'identifier']),
				$lo_pagesSlugQuery,
				'string'
			);
		})->orderBy('title')->all();

		return $lo_pages->each(function (Page $page) {
			$page->set('title', $page->label . ' (' . $page->languageShortcode . '/' . $page->slug . ')');
		});
	}


	/**
	 * @param \Awyiss\Model\Entity\UrlHistory $urlHistory
	 * @return void
	 */
	protected function setViewVars(UrlHistory $urlHistory): void {
		$lo_deletedPages = $this->getDeletedPages();

		$lo_threadedPages = $this->getThreadedPages();

		$la_scopes = [];
		foreach ($this->UrlHistory->getAvailableScopes() as $ls_scope) {
			$la_scopes[ $ls_scope ] = __('scope_' . $ls_scope);
		}

		if ($urlHistory->scope === 'media' && !$this->request->getData('foreign_key')) {
			$this->UrlHistory->loadInto($urlHistory, ['Media']);
		}

		$this->set([
			'urlHistory' => $urlHistory,
			'deletedPages' => $lo_deletedPages,
			'scopes' => $la_scopes,
			'threadedPages' => $lo_threadedPages->toList(),
		]);
	}
}
