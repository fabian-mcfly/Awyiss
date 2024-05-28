<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Routing\Router;
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
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedMenuEntries;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->MenuEntries->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

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
			$lo_menuEntries = $this->paginate($lo_query);
		}
		else {
			$lo_menuEntries = $lo_query->find('threaded')->all();
		}

		$this->set([
			'menuEntries' => $lo_menuEntries,
			'paginated' => $lb_paginated,
			'attributes' => $this->MenuEntries->getAttributes(),
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

		$lo_possibleParentMenuEntries = $this->getPossibleParentMenuEntries($lo_menuEntry);
		$this->ensurePossibleParentId($lo_menuEntry, $lo_possibleParentMenuEntries);

		$this->set([
			'menuEntry' => $lo_menuEntry,
			'possibleParentMenuEntries' => $lo_possibleParentMenuEntries,
			'languageRealm' => Awyiss::REALM_FRONTEND,
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

		/** @var MenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->MenuEntries->findById($id)->find('translations')->find('mediaAssignments')->find('mediaCompositeAssignments')->first();
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
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Returns a collection of possible parent menu entries for the given menu entry
	 * to prevent circular references
	 *
	 * @param MenuEntry $menuEntry
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
			if ($this->MenuEntries->save($menuEntry, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $menuEntry->languageShortcode,
						'menuId' => $menuEntry->menuId,
						'page' => $this->Paginate->calculateEntityPagePosition($menuEntry),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $menuEntry->languageShortcode, 'id' => $menuEntry->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->MenuEntries->getSystemOrderRelatedColumns($menuEntry)) {
				$menuEntry->systemOrder = null;
			}
			else {
				$menuEntry->systemOrder = $menuEntry->hasOriginal('systemOrder') ? $menuEntry->getOriginal('systemOrder') : $menuEntry->get('systemOrder');
			}
		}

		$this->Categories->ensurePossibleCategory($menuEntry);
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
}
