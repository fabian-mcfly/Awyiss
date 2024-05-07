<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\SlugHistory;
use Awyiss\Routing\Router;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * SlugHistory Controller
 *
 * @property \Awyiss\Model\Table\SlugHistoryTable $SlugHistory
 */
class SlugHistoryController extends Controller {
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
	public function getOverviewQuery(): ?SelectQuery {
		return $this->SlugHistory->find()->contain('Pages');
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
			$lo_slugHistory = $this->paginate($lo_query);
		}
		else {
			$lo_slugHistory = $lo_query->all();
		}

		$this->set([
			'slugHistory' => $lo_slugHistory,
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

		$lo_slugHistory = $this->SlugHistory->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_slugHistory);
		}

		$lo_threadedPages = $this->getThreadedPages();

		$this->set([
			'slugHistory' => $lo_slugHistory,
			'threadedPages' => $lo_threadedPages->toList(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $ai_id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var SlugHistory $lo_slugHistory */
		$lo_slugHistory = $this->SlugHistory->findById($ai_id)->find('translations')->first();
		if (! $lo_slugHistory) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_slugHistory, 'edit');
		}

		$lo_threadedPages = $this->getThreadedPages();

		$this->set([
			'slugHistory' => $lo_slugHistory,
			'threadedPages' => $lo_threadedPages->toList(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var SlugHistory $lo_slugHistory */
		$lo_slugHistory = $this->SlugHistory->findById($ai_id)->first();
		if (! $lo_slugHistory) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->SlugHistory->delete($lo_slugHistory)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_slugHistory->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param SlugHistory $ao_slugHistory
	 * @param string $as_method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(SlugHistory $ao_slugHistory, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->SlugHistory->hasAttributes()) {
			$la_associated[] = $this->SlugHistory->getAttributesTableName(true);
			$ao_slugHistory->setAccess('attributes', true);
		}

		$this->SlugHistory->patchEntity($ao_slugHistory, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->SlugHistory->save($ao_slugHistory, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($as_method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($ao_slugHistory),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_slugHistory->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_slugHistory->getError('_general') as $ls_error) {
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
			$lo_query = $this->SlugHistory->Pages->find('forCurrentLanguage', skipPageRoleCheck: true);

			$this->threadedPages = $this->SlugHistory->Pages->listNested($lo_query);
		}


		return $this->threadedPages;
	}
}
