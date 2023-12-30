<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Routing\Router;
use Awyiss\Utilities\Menu\BackendMenu;
use Awyiss\Utilities\Menu\Menu;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * MenuEntries Controller
 *
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable $BackendMenuEntries
 */
class BackendMenuEntriesController extends Controller {
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

		$lo_menu = new BackendMenu();

		$this->set([
			'ao_menu' => $lo_menu,
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

		$lo_menuEntry = $this->BackendMenuEntries->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_menuEntry);
		}

		$lo_menu = new BackendMenu();

		$la_parentIdOptions = $this->generateMenuSelectOptions($lo_menu->getDynamicMenu());

		$la_insertAfterOptions = $this->generateMenuSelectOptions($lo_menu->getCustomMenu() ?? $lo_menu->getMenu());

		$lo_threadedMenuEntries = $this->getThreadedMenuEntries($lo_menuEntry);

		$this->set([
			'ao_menu' => $lo_menu,
			'aa_parentIdOptions' => $la_parentIdOptions,
			'aa_insertAfterOptions' => $la_insertAfterOptions,
			'ao_backendMenuEntry' => $lo_menuEntry,
			'ao_threadedMenuEntries' => $lo_threadedMenuEntries,
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

		/** @var BackendMenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->BackendMenuEntries->findById($ai_id)->find('translations')->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menuEntry, 'edit');
		}

		$lo_menu = new BackendMenu();

		$la_parentIdOptions = $this->generateMenuSelectOptions($lo_menu->getDynamicMenu());

		$la_insertAfterOptions = $this->generateMenuSelectOptions($lo_menu->getCustomMenu() ?? $lo_menu->getMenu());

		$lo_threadedMenuEntries = $this->getThreadedMenuEntries($lo_menuEntry);

		$this->set([
			'ao_menu' => $lo_menu,
			'aa_parentIdOptions' => $la_parentIdOptions,
			'aa_insertAfterOptions' => $la_insertAfterOptions,
			'ao_backendMenuEntry' => $lo_menuEntry,
			'ao_threadedMenuEntries' => $lo_threadedMenuEntries,
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

		/** @var BackendMenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->BackendMenuEntries->findById($ai_id)->find('translations')->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->BackendMenuEntries->delete($lo_menuEntry)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Returns a Collection of all available menuentries that exist within the same menu and the same `language_shortcode`
	 * as the entity, provided via `$ao_menuEntry`
	 *
	 * @param BackendMenuEntry $ao_menuEntry
	 * @return CollectionInterface
	 */
	public function getThreadedMenuEntries(BackendMenuEntry $ao_menuEntry): CollectionInterface {
		if (!isset($this->threadedMenuEntries)) {
			$lo_query = $this->BackendMenuEntries->find()->where([
				'parent_id' . ($ao_menuEntry->parentId === null ? ' IS' : null) => $ao_menuEntry->parentId,
				'insert_after_id' . ($ao_menuEntry->insertAfterId === null ? ' IS' : null) => $ao_menuEntry->insertAfterId,
			]);

			$this->threadedMenuEntries = $this->BackendMenuEntries->listNested($lo_query);
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

		$lo_threadedMenuEntries = $lo_threadedMenuEntries->nest('id', 'parentId');


		return $lo_threadedMenuEntries->listNested();
	}


	/**
	 * @param BackendMenuEntry $ao_menuEntry
	 * @param string $as_method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(BackendMenuEntry $ao_menuEntry, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->BackendMenuEntries->hasAttributes()) {
			$la_associated[] = $this->BackendMenuEntries->getAttributesTable(true);
			$ao_menuEntry->setAccess('attributes', true);
		}

		$this->BackendMenuEntries->patchEntity($ao_menuEntry, $this->request->getData(), ['associated' => $la_associated]);

		if (!empty($ao_menuEntry->parentId)) {
			$ao_menuEntry->insertAfterId = null;

			$lo_request = $this->getRequest();
			//When insertAfterId is part of the request data, overwrite it because it's might be outdated
			if ($lo_request->getData('insert_after_id') !== null) {
				$lo_request = $lo_request->withData('insert_after_id', $ao_menuEntry->insertAfterId);
				$this->setRequest($lo_request);
			}
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->BackendMenuEntries->save($ao_menuEntry)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_menuEntry->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->BackendMenuEntries->getSystemOrderRelatedColumns($ao_menuEntry)) {
				$ao_menuEntry->systemOrder = null;
			}
			else {
				$ao_menuEntry->systemOrder = $ao_menuEntry->hasOriginal('systemOrder') ? $ao_menuEntry->getOriginal('systemOrder') : $ao_menuEntry->get('systemOrder');
			}
		}
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [

		];
	}


	/**
	 * @param \Awyiss\Utilities\Menu\Menu $ao_menu
	 * @return array
	 */
	protected function generateMenuSelectOptions(Menu $ao_menu): array {
		$la_options = [];

		foreach ($ao_menu->items() as $ls_identifier => $lo_item) {
			$la_options[ $ls_identifier ] = str_repeat('- ', $lo_item->level - 1) . $lo_item->getTitle();
		}


		return $la_options;
	}
}
