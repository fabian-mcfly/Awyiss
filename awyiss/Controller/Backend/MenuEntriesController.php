<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * MenuEntries Controller
 *
 * @property \Awyiss\Model\Table\MenuEntriesTable $MenuEntries
 */
class MenuEntriesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'uriParam' => 'menu-id',
	];
	/**
	 * @var string|null Session identifier for the selected parent_id
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedMenuEntries;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();


		$this->Authorization->setScope('menus');

		$this->selectedParentIdSessionIdentifier = 'menu_entries.' . ($this->request->getParam('lang') ?? 'global') . '.parent_id';
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->MenuEntries->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);
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

		if ($this->request->getParam('menuIdentifier')) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_menu = $this->fetchTable('Menus')->findByIdentifier(Inflector::underscore($this->request->getParam('menuIdentifier')))->first();
			if ($lo_menu) {
				throw new RedirectException(Router::url([
					'action' => 'overview',
					'menuId' => $lo_menu->id,
				], true), 302);
			}
		}

		$lo_query = $this->getOverviewQuery();

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_menuEntries = $this->paginate($lo_query);
		}
		else {
			$lo_menuEntries = $lo_query->find('threaded')->all();
		}

		$lo_menu = $this->fetchTable('Menus')->findById($this->Categories->getSelectedCategory())->first();

		$this->set([
			'menuEntries' => $lo_menuEntries,
			'menu' => $lo_menu,
			'paginated' => $lb_paginated,
			'attributes' => $this->MenuEntries->getAttributes(),
			'pages' => $this->findLinkablePages(),
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

		$lo_session = $this->request->getSession();
		$lo_menuEntry = $this->MenuEntries->newDefaultEntity([
			'languageShortcode' => $this->getOverviewWhere('language_shortcode'),
			'menuId' => $this->request->getParam('menuId') ?? $this->Categories->getSelectedCategory(),
			'parentId' => $lo_session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_menuEntry);
		}

		$lo_possibleParentMenuEntries = $this->getPossibleParentMenuEntries($lo_menuEntry);
		$this->ensurePossibleParentId($lo_menuEntry, $lo_possibleParentMenuEntries);

		$this->set([
			'menuEntry' => $lo_menuEntry,
			'possibleParentMenuEntries' => $lo_possibleParentMenuEntries,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'pages' => $this->findLinkablePages(true),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\MenuEntry $lo_menuEntry
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$lo_menuEntry = $this->MenuEntries->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menuEntry, 'edit');
		}

		$lo_possibleParentMenuEntries = $this->getPossibleParentMenuEntries($lo_menuEntry);
		$this->ensurePossibleParentId($lo_menuEntry, $lo_possibleParentMenuEntries);

		$this->set([
			'menuEntry' => $lo_menuEntry,
			'possibleParentMenuEntries' => $lo_possibleParentMenuEntries,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'pages' => $this->findLinkablePages(true),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var MenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->MenuEntries->findById($id)->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MenuEntries->delete($lo_menuEntry)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_menuEntry->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Returns a collection of possible parent menu entries for the given menu entry
	 * to prevent circular references
	 *
	 * @param \Awyiss\Model\Entity\MenuEntry $menuEntry
	 * @return CollectionInterface
	 */
	protected function getPossibleParentMenuEntries(MenuEntry $menuEntry): CollectionInterface {
		if (!isset($this->threadedMenuEntries)) {
			$lo_query = $this->MenuEntries->find()->where([
				'language_shortcode' => $menuEntry->languageShortcode,
				'menu_id' => $menuEntry->menuId,
			]);

			$this->threadedMenuEntries = $this->MenuEntries->listNested($lo_query);
		}


		return $this->MenuEntries->getPossibleParents($menuEntry, $this->threadedMenuEntries);
	}


	/**
	 * @param MenuEntry $menuEntry
	 * @param string $method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(MenuEntry $menuEntry, string $method = 'add'): void {
		$la_associated = [];
		if ($this->MenuEntries->hasAttributes()) {
			$la_associated[] = $this->MenuEntries->getAttributesTableName(true);
			$menuEntry->setAccess('attributes', true);
		}

		$this->MenuEntries->patchEntity($menuEntry, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->MenuEntries->save($menuEntry, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$lo_session = $this->request->getSession();
				$lo_session->write($this->selectedParentIdSessionIdentifier, $menuEntry->parentId);

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $menuEntry->languageShortcode,
						'menuId' => $menuEntry->menuId,
						'page' => $this->Paginate->calculateEntityPagePosition($menuEntry),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $menuEntry->languageShortcode, 'id' => $menuEntry->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($menuEntry->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		else {
			if ($this->MenuEntries->hasDirtySystemOrderRelatedColumns($menuEntry)) {
				$menuEntry->systemOrder = null;
			}
			else {
				$menuEntry->systemOrder = $menuEntry->hasOriginal('systemOrder') ? $menuEntry->getOriginal('systemOrder') : $menuEntry->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $menuEntry->systemOrder);
			$this->setRequest($lo_request);
		}

		$this->Categories->ensurePossibleCategory($menuEntry);
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		$la_newIds = [];

		if (!empty($requestData['new_entries'])) {
			foreach ($requestData['new_entries'] as $li_key => $la_data) {
				$la_data = array_intersect_key($la_data, array_flip(['menu_id', 'title', 'link', 'active', 'language_shortcode']));
				$lo_entity = $table->newDefaultEntity($la_data);

				if ($table->save($lo_entity)) {
					$la_newIds[ $li_key ] = $lo_entity->id;
				}
			}
		}

		$la_requestData = [];

		// Replace all new entries with their new ids in the order array
		foreach ($requestData['order'] as $li_parentId => $la_entryIds) {
			if (isset($la_newIds[ $li_parentId ])) {
				$li_parentId = $la_newIds[ $li_parentId ];
			}

			foreach ($la_entryIds as $li_key => $li_entryId) {
				$la_requestData[ $li_parentId ][ $li_key ] = $la_newIds[ $li_entryId ] ?? $li_entryId;
			}
		}

		return parent::_saveSystemOrder($la_requestData, $table);
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
	 * @param \Awyiss\Model\Entity\MenuEntry $menuEntry
	 * @param \Cake\Collection\CollectionInterface $threadedMenuEntries
	 * @return void
	 */
	protected function ensurePossibleParentId(MenuEntry $menuEntry, CollectionInterface $threadedMenuEntries): void {
		$la_possibleParentIds = $threadedMenuEntries->extract('id')->toList();

		if (!empty($menuEntry->parentId) && !in_array($menuEntry->parentId, $la_possibleParentIds)) {
			$la_errors = $menuEntry->getError('parentId');

			$menuEntry->set('parentId', null, ['setter' => false]);

			if ($la_errors) {
				$menuEntry->setError('parentId', $la_errors, true);
			}
		}
	}


	/**
	 * @param bool $listNested
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function findLinkablePages(bool $listNested = false): CollectionInterface {
		$lo_table = $this->fetchTable('Pages');
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$lo_query = $lo_table->find('forCurrentLanguage');

		if ($listNested) {
			$lo_pages = $lo_table->listNested($lo_query);
		}
		else {
			$lo_pages = $lo_query->all();
		}

		return $lo_pages;
	}
}
