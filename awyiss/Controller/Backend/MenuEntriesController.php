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
	 * @var string|null Session identifier for the selected parentId
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

		$this->selectedParentIdSessionIdentifier = 'menuEntries.' . ($this->request->getParam('lang') ?? 'global') . '.parentId';
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->MenuEntries->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		if ($this->request->getParam('menuIdentifier')) {
			/** @noinspection PhpUndefinedMethodInspection */
			$menu = $this
				->fetchTable('Menus')
				->findByIdentifier(Inflector::variable($this->request->getParam('menuIdentifier')))
				->first()
			;
			if ($menu) {
				throw new RedirectException(Router::url([
					'action' => 'overview',
					'menuId' => $menu->id,
				], true), 302);
			}
		}

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		if ($paginated) {
			$menuEntries = $this->paginate($query);
		}
		else {
			$menuEntries = $query->find('threaded')->all();
		}

		$menu = $this
			->fetchTable('Menus')
			->findById($this->Categories->getSelectedCategory())
			->first()
		;

		$this->set([
			'menuEntries' => $menuEntries,
			'menu' => $menu,
			'paginated' => $paginated,
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

		$session = $this->request->getSession();
		$menuEntry = $this->MenuEntries->newDefaultEntity([
			'languageShortcode' => $this->getOverviewWhere('languageShortcode'),
			'menuId' => $this->request->getParam('menuId') ?? $this->Categories->getSelectedCategory(),
			'parentId' => $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($menuEntry);
		}

		$possibleParentMenuEntries = $this->getPossibleParentMenuEntries($menuEntry);
		$this->ensurePossibleParentId($menuEntry, $possibleParentMenuEntries);

		$this->set([
			'menuEntry' => $menuEntry,
			'possibleParentMenuEntries' => $possibleParentMenuEntries,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'pages' => $this->findLinkablePages(true),
		]);
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
		 * @var \Awyiss\Model\Entity\MenuEntry $menuEntry
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$menuEntry = $this->MenuEntries
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($menuEntry, 'edit');
		}

		$possibleParentMenuEntries = $this->getPossibleParentMenuEntries($menuEntry);
		$this->ensurePossibleParentId($menuEntry, $possibleParentMenuEntries);

		$this->set([
			'menuEntry' => $menuEntry,
			'possibleParentMenuEntries' => $possibleParentMenuEntries,
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

		/** @var \Awyiss\Model\Entity\MenuEntry $menuEntry */
		$menuEntry = $this->MenuEntries->findById($id)->first();
		if (!$menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MenuEntries->delete($menuEntry)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($menuEntry->getError('_general') as $error) {
					$this->Flash->error($error);
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
			$query = $this->MenuEntries
				->find()
				->where([
					'languageShortcode' => $menuEntry->languageShortcode,
					'menuId' => $menuEntry->menuId,
				])
			;

			$this->threadedMenuEntries = $this->MenuEntries->listNested($query);
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
		$associated = [];
		if ($this->MenuEntries->hasAttributes()) {
			$associated[] = $this->MenuEntries->getAttributesTableName(true);
			$menuEntry->setAccess('attributes', true);
		}

		$this->MenuEntries->patchEntity($menuEntry, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->MenuEntries->save($menuEntry, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedParentIdSessionIdentifier, $menuEntry->parentId);

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $menuEntry->languageShortcode,
						'menuId' => $menuEntry->menuId,
						'page' => $this->Paginate->calculateEntityPagePosition($menuEntry),
					], true), 302);
				}

				throw new RedirectException(
					Router::url(['action' => 'edit', 'lang' => $menuEntry->languageShortcode, 'id' => $menuEntry->id], true),
					302
				);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($menuEntry->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		$this->Categories->ensurePossibleCategory($menuEntry);
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		$newIds = [];

		foreach (($requestData['newEntries'] ?? []) as $key => $data) {
			$data = array_intersect_key($data, array_flip(['menuId', 'title', 'link', 'active', 'languageShortcode']));
			$entity = $table->newDefaultEntity($data);

			if ($table->save($entity)) {
				$newIds[ $key ] = $entity->id;
			}
		}

		$orderData = [];
		// Replace all new entries with their new ids in the order array
		foreach ($requestData['order'] as $parentId => $entryIds) {
			if (isset($newIds[ $parentId ])) {
				$parentId = $newIds[ $parentId ];
			}

			foreach ($entryIds as $key => $entryId) {
				$orderData[ $parentId ][ $key ] = $newIds[ $entryId ] ?? $entryId;
			}
		}

		return parent::_saveSystemOrder($orderData, $table);
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [
			'languageShortcode' => LocaleMiddleware::getLanguage()->shortcode,
		];

		parent::initializeOverviewWhere();
	}


	/**
	 * @param \Awyiss\Model\Entity\MenuEntry $menuEntry
	 * @param \Cake\Collection\CollectionInterface $threadedMenuEntries
	 * @return void
	 */
	protected function ensurePossibleParentId(MenuEntry $menuEntry, CollectionInterface $threadedMenuEntries): void {
		$possibleParentIds = $threadedMenuEntries->extract('id')->toList();

		if (!empty($menuEntry->parentId) && !in_array($menuEntry->parentId, $possibleParentIds)) {
			$errors = $menuEntry->getError('parentId');

			$menuEntry->set('parentId', null, ['setter' => false]);

			if ($errors) {
				$menuEntry->setError('parentId', $errors, true);
			}
		}
	}


	/**
	 * @param bool $listNested
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function findLinkablePages(bool $listNested = false): CollectionInterface {
		$table = $this->fetchTable('Pages');
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query = $table->find('forCurrentLanguage');

		if ($listNested) {
			$pages = $table->listNested($query);
		}
		else {
			$pages = $query->find('threaded')->all();
		}

		return $pages;
	}
}
