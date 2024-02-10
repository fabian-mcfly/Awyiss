<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Routing\Router;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * MenuEntries Controller
 *
 * @property \Awyiss\Model\Table\MenuEntriesTable $MenuEntries
 */
class MenuEntriesController extends Controller {
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedMenuEntries;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_menuEntries = $this->MenuEntries->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_menuEntries);
		$lo_menuEntries = $this->MenuEntries->listNested($lo_menuEntries);

		$this->set([
			'ao_menuEntries' => $lo_menuEntries,
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

		$lo_menuEntry = $this->MenuEntries->newDefaultEntity([
			'language_shortcode' => $this->getOverviewWhere('language_shortcode'),
			'menu_id' => $this->request->getParam('menuId') ?? $this->Categories->getSelectedCategory(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_menuEntry);
		}

		$lo_threadedMenuEntries = $this->getThreadedMenuEntries($lo_menuEntry);
		$this->ensurePossibleParentId($lo_menuEntry, $lo_threadedMenuEntries);

		$this->set([
			'ao_menuEntry' => $lo_menuEntry,
			'ao_threadedMenuEntries' => $lo_threadedMenuEntries,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var MenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->MenuEntries->findById($ai_id)->find('translations')->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menuEntry, 'edit');
		}

		$lo_threadedMenuEntries = $this->getThreadedMenuEntries($lo_menuEntry);
		$this->ensurePossibleParentId($lo_menuEntry, $lo_threadedMenuEntries);

		$this->set([
			'ao_menuEntry' => $lo_menuEntry,
			'ao_threadedMenuEntries' => $lo_threadedMenuEntries,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var MenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->MenuEntries->findById($ai_id)->find('translations')->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MenuEntries->delete($lo_menuEntry)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Returns a Collection of all available menuentries that exist within the same menu and the same `language_shortcode`
	 * as the entity, provided via `$ao_menuEntry`
	 *
	 * @param MenuEntry $ao_menuEntry
	 * @return CollectionInterface
	 */
	public function getThreadedMenuEntries(MenuEntry $ao_menuEntry): CollectionInterface {
		if (!isset($this->threadedMenuEntries)) {
			$lo_query = $this->MenuEntries->find()->where([
				'language_shortcode' => $ao_menuEntry->languageShortcode,
				'menu_id' => $ao_menuEntry->menuId,
			]);

			$this->threadedMenuEntries = $this->MenuEntries->listNested($lo_query);
		}

		//We only want to find threaded menu entries at the same level for an existing entity (id equals not null)
		$li_originalId = $ao_menuEntry->get('id');
		if (!$li_originalId) {
			return $this->threadedMenuEntries;
		}

		$li_foundAtLevel = null;
		$lo_threadedMenuEntries = new Collection($this->threadedMenuEntries->toList());

		$lo_threadedMenuEntries = $lo_threadedMenuEntries->filter(function ($ao_menuEntry) use ($li_originalId, &$li_foundAtLevel) {
			if ($ao_menuEntry->get('id') === $li_originalId) {
				$li_foundAtLevel = $ao_menuEntry->level;
			}
			elseif (is_null($li_foundAtLevel) || $ao_menuEntry->level <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});

		$lo_threadedMenuEntries = $lo_threadedMenuEntries->nest('id', 'parent_id');


		return $lo_threadedMenuEntries->listNested();
	}


	/**
	 * @param MenuEntry $ao_menuEntry
	 * @param string $as_method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(MenuEntry $ao_menuEntry, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->MenuEntries->hasAttributes()) {
			$la_associated[] = $this->MenuEntries->getAttributesTableName(true);
			$ao_menuEntry->setAccess('attributes', true);
		}

		$this->MenuEntries->patchEntity($ao_menuEntry, $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->MenuEntries->save($ao_menuEntry)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $ao_menuEntry->languageShortcode, 'menuId' => $ao_menuEntry->menuId], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $ao_menuEntry->languageShortcode, 'id' => $ao_menuEntry->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->MenuEntries->getSystemOrderRelatedColumns($ao_menuEntry)) {
				$ao_menuEntry->systemOrder = null;
			}
			else {
				$ao_menuEntry->systemOrder = $ao_menuEntry->hasOriginal('systemOrder') ? $ao_menuEntry->getOriginal('systemOrder') : $ao_menuEntry->get('systemOrder');
			}
		}

		$this->Categories->ensurePossibleCategory($ao_menuEntry);
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [
			'language_shortcode' => LocaleMiddleware::getLanguage()->shortcode,
		];

		parent::initializeOverviewWhere();
	}


	/**
	 * @param \Awyiss\Model\Entity\MenuEntry $ao_menuEntry
	 * @param \Cake\Collection\CollectionInterface $ao_threadedMenuEntries
	 * @return void
	 */
	protected function ensurePossibleParentId(MenuEntry $ao_menuEntry, CollectionInterface $ao_threadedMenuEntries): void {
		$la_possibleParentIds = $ao_threadedMenuEntries->extract('id')->toList();

		if (!empty($ao_menuEntry->parentId) && !in_array($ao_menuEntry->parentId, $la_possibleParentIds)) {
			$la_errors = $ao_menuEntry->getError('parentId');

			$ao_menuEntry->set('parentId', null, ['setter' => false]);

			if ($la_errors) {
				$ao_menuEntry->setError('parentId', $la_errors, true);
			}
		}
	}
}
