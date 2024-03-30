<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Routing\Router;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\Menu;
use Awyiss\Utility\Menu\MenuItem;
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

		$la_insertAfterOptions = $this->generateMenuSelectOptions($lo_menu->getCustomMenu() ?? $lo_menu->getMenu());

		$lo_possibleParentMenuEntries = $this->getPossibleParentMenuEntries($lo_menuEntry, $lo_menu->getDynamicMenu());

		$this->set([
			'ao_menu' => $lo_menu,
			'aa_insertAfterOptions' => $la_insertAfterOptions,
			'ao_backendMenuEntry' => $lo_menuEntry,
			'ao_possibleParentMenuEntries' => $lo_possibleParentMenuEntries,
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

		$la_insertAfterOptions = $this->generateMenuSelectOptions($lo_menu->getCustomMenu() ?? $lo_menu->getMenu());

		$lo_possibleParentMenuEntries = $this->getPossibleParentMenuEntries($lo_menuEntry, $lo_menu->getDynamicMenu());

		$this->set([
			'ao_menu' => $lo_menu,
			'aa_insertAfterOptions' => $la_insertAfterOptions,
			'ao_backendMenuEntry' => $lo_menuEntry,
			'ao_possibleParentMenuEntries' => $lo_possibleParentMenuEntries,
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

			foreach ($lo_menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Returns a Collection of all possible parent ids for the given menu entry
	 * to prevent circular references
	 *
	 * @param BackendMenuEntry $ao_menuEntry
	 * @param \Awyiss\Utility\Menu\Menu|null $ao_dynamicMenu
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function getPossibleParentMenuEntries(BackendMenuEntry $ao_menuEntry, ?Menu $ao_dynamicMenu): CollectionInterface {
		$lo_listNested = collection($ao_dynamicMenu->toArray());

		//We only want to find threaded pages for an existing entity (id equals not null)
		$li_originalId = $ao_menuEntry->get('id');
		if (!$li_originalId) {
			return $lo_listNested;
		}


		$li_foundAtLevel = null;

		$lo_possibleParents = $lo_listNested->filter(function (MenuItem $ao_item, string|int $ax_identifier) use ($li_originalId, &$li_foundAtLevel) {
			if (gettype($ax_identifier) === 'string') {
				return true;
			}

			if ($ax_identifier === $li_originalId) {
				$li_foundAtLevel = $ao_item->getLevel();
			}
			elseif (is_null($li_foundAtLevel) || $ao_item->getLevel() <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});


		return $lo_possibleParents->map(function (MenuItem $ao_item) {
			return $ao_item->getTitle();
		});
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
			$la_associated[] = $this->BackendMenuEntries->getAttributesTableName(true);
			$ao_menuEntry->setAccess('attributes', true);
		}

		$this->BackendMenuEntries->patchEntity($ao_menuEntry, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

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
			if ($this->BackendMenuEntries->save($ao_menuEntry, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
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
	 * @param \Awyiss\Utility\Menu\Menu $ao_menu
	 * @return array
	 */
	protected function generateMenuSelectOptions(Menu $ao_menu): array {
		$la_options = [];

		/** @var MenuItem $lo_item */
		foreach ($ao_menu->items() as $ls_identifier => $lo_item) {
			$la_options[ $ls_identifier ] = str_repeat('- ', $lo_item->getLevel() - 1) . $lo_item->getTitle();
		}


		return $la_options;
	}
}
