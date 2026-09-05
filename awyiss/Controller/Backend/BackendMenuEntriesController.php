<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Menu\Menu;
use Awyiss\Utility\Menu\MenuItem;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use ReflectionClass;
use ReflectionMethod;


/**
 * MenuEntries Controller
 *
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable $BackendMenuEntries
 */
class BackendMenuEntriesController extends Controller {
	/**
	 * A list of methods that should not be listed as possible link target actions
	 *
	 * @var array
	 */
	protected static array $blocklistedMethods = [
		'initialize',
		'beforeFilter',
		'beforeRender',
		'render',
		'setEventManager',
		'dispatchEvent',
	];
	/**
	 * An array of all controllers found
	 *
	 * @var array
	 */
	protected static array $controllers;


	/**
	 * @var string|null Session identifier for the selected insertAfterId
	 */
	protected ?string $selectedInsertAfterIdSessionIdentifier = null;
	/**
	 * @var string|null Session identifier for the selected parentId
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->selectedInsertAfterIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.insertAfterId'
		;

		$this->selectedParentIdSessionIdentifier = Inflector::variable($this->getName()) . '.'
			. ($this->request->getParam('lang') ?? 'global') . '.parentId'
		;
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->BackendMenuEntries->find()->where($this->getOverviewWhere());
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

		if ($this->BackendMenuEntries->searchIsActive()) {
			$menuEntries = $this
				->getOverviewQuery()
				->find('threaded')
				->all()
			;
		}
		else {
			/** @var class-string<\Awyiss\Utility\Menu\BackendMenuProvider> $backendMenuProviderClass */
			$backendMenuProviderClass = App::className('BackendMenuProvider', 'Utility/Menu');
			$menu = new $backendMenuProviderClass(null, $this->getOverviewQuery());
		}

		$this->set([
			'menu' => $menu ?? null,
			'menuEntries' => $menuEntries ?? null,
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
		$menuEntry = $this->BackendMenuEntries->newDefaultEntity([
			'insertAfterId' => $session->read($this->selectedInsertAfterIdSessionIdentifier),
			'parentId' => $session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($menuEntry);
		}

		$this->setViewVars($menuEntry);
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
		 * @var \Awyiss\Model\Entity\BackendMenuEntry $menuEntry
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$menuEntry = $this->BackendMenuEntries
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

		$this->setViewVars($menuEntry);
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

		/** @var BackendMenuEntry $menuEntry */
		$menuEntry = $this->BackendMenuEntries->findById($id)->first();
		if (!$menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->BackendMenuEntries->delete($menuEntry)) {
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
	 * Returns a Collection of all possible parent ids for the given menu entry
	 * to prevent circular references
	 *
	 * @param BackendMenuEntry $menuEntry
	 * @param \Awyiss\Utility\Menu\Menu|null $dynamicMenu
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getPossibleParentMenuEntries(BackendMenuEntry $menuEntry, ?Menu $dynamicMenu): CollectionInterface {
		$listNested = collection($dynamicMenu->toArray());

		//We only want to find threaded pages for an existing entity (id equals not null)
		$originalId = $menuEntry->get('id');
		if (!$originalId) {
			return $listNested;
		}

		$foundAtLevel = null;

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$possibleParents = $listNested->filter(function (MenuItem $item, string|int $identifier) use ($originalId, &$foundAtLevel) {
			if (gettype($identifier) === 'string') {
				return true;
			}

			if ($identifier === $originalId) {
				$foundAtLevel = $item->getLevel();
			}
			elseif (is_null($foundAtLevel) || $item->getLevel() <= $foundAtLevel) {
				$foundAtLevel = null;


				return true;
			}


			return false;
		});


		return $possibleParents;
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		$identity = $this->getIdentity();

		return $table->updateAll(function (QueryExpression $expression) use ($requestData, $identity) {
			$insertAfterIdCase = $expression->case();
			$parentIdCase = $expression->case();
			$systemOrderCase = $expression->case();

			foreach ($requestData as $data) {
				$id = (int)$data['id'];

				$insertAfterIdCase->when(['id' => $id])->then($data['insertAfterId'], 'string');
				$parentIdCase->when(['id' => $id])->then($data['parentId'], 'string');
				$systemOrderCase->when(['id' => $id])->then($data['systemOrder'], 'integer');
			}


			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return [
				'insertAfterId' => $insertAfterIdCase,
				'parentId' => $parentIdCase,
				'systemOrder' => $systemOrderCase,
				'deletedBy' => $identity?->id,
				'deletedOn' => DateTime::now(),
			];
		}, [
			'id IN' => array_keys($requestData),
		]);
	}


	/**
	 * @param BackendMenuEntry $menuEntry
	 * @param string $method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(BackendMenuEntry $menuEntry, string $method = 'add'): void {
		$associated = [];
		if ($this->BackendMenuEntries->hasAttributes()) {
			$associated[] = $this->BackendMenuEntries->getAttributesTableName(true);
			$menuEntry->setAccess('attributes', true);
		}

		$this->BackendMenuEntries->patchEntity($menuEntry, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!empty($menuEntry->parentId)) {
			$menuEntry->insertAfterId = null;

			$request = $this->getRequest();
			//When insertAfterId is part of the request data, overwrite it because it's might be outdated
			if ($request->getData('insertAfterId') !== null) {
				$request = $request->withData('insertAfterId', $menuEntry->insertAfterId);
				$this->setRequest($request);
			}
		}

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->BackendMenuEntries->save($menuEntry, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$session = $this->request->getSession();
				$session->write($this->selectedInsertAfterIdSessionIdentifier, $menuEntry->insertAfterId);
				$session->write($this->selectedParentIdSessionIdentifier, $menuEntry->parentId);

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $menuEntry->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($menuEntry->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
	 * @param \Awyiss\Utility\Menu\Menu $menu
	 * @return array
	 */
	protected function generateMenuSelectOptions(Menu $menu): array {
		$options = [];

		/**
		 * @var MenuItem $item
		 * @noinspection PhpLoopCanBeConvertedToArrayMapInspection
		 */
		foreach ($menu->items() as $identifier => $item) {
			$options[ $identifier ] = str_repeat('- ', $item->getLevel() - 1) . $item->getLabel();
		}


		return $options;
	}


	/**
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $menuEntry
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function setViewVars(BackendMenuEntry $menuEntry): void {
		/** @var class-string<\Awyiss\Utility\Menu\BackendMenuProvider> $backendMenuProviderClass */
		$backendMenuProviderClass = App::className('BackendMenuProvider', 'Utility/Menu');
		$menu = new $backendMenuProviderClass();

		$insertAfterOptions = $this->generateMenuSelectOptions($menu->getCustomMenu() ?? $menu->getMenu());

		$possibleParentMenuEntries = $this->getPossibleParentMenuEntries($menuEntry, $menu->getDynamicMenu());

		$this->set([
			'menu' => $menu,
			'insertAfterOptions' => $insertAfterOptions,
			'backendMenuEntry' => $menuEntry,
			'possibleParentMenuEntries' => $possibleParentMenuEntries,
			'attributes' => $this->BackendMenuEntries->getAttributes(),
			'controllers' => $this->getControllers(),
			'policies' => $this->getPolicies(),
		]);
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	protected function getControllers(): array {
		if (!empty(static::$controllers)) {
			return static::$controllers;
		}

		$classes = App::classes('*', 'Controller/Backend', 'Controller');

		foreach ($classes as $controllerName => $className) {
			$reflection = new ReflectionClass($className);

			$methods = array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), function ($method) use ($controllerName) {
				if (in_array($method->getName(), static::$blocklistedMethods)) {
					return false;
				}

				// Check for the NoDirectAccess attribute
				$attributes = $method->getAttributes(NoDirectAccess::class);
				if (!empty($attributes)) {
					return false;
				}

				return str_ends_with($method->getDeclaringClass()->getName(), $controllerName);
			});

			if (empty($methods)) {
				continue;
			}

			array_walk($methods, function (ReflectionMethod &$method) {
				$method = $method->getName();
			});

			$controllerName = substr($controllerName, 0, -10);

			static::$controllers[ $controllerName ] = $methods;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$name = Inflector::pluralize($pageRole->name);

			static::$controllers[ $name ] ??= static::$controllers['Pages'];
		}

		/** @var \Awyiss\Model\Table\DatatablesTable $table */
		$table = $this->fetchTable('Datatables');
		$table
			->findAllAndCache()
			->each(function (Datatable $datatable) {
				static::$controllers[ $datatable->identifier ] ??= static::$controllers['GenericDatatables'];
			})
		;
		unset(static::$controllers['GenericDatatables']);

		ksort(static::$controllers);

		return static::$controllers;
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function getPolicies(): array {
		/** @var \Awyiss\Authorization\AuthorizationService $authorizationService */
		$authorizationService = $this->request->getAttribute('authorization');
		$policies = [];

		/**
		 * @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $policyClass
		 */
		foreach ($authorizationService->getPolicies() as $policyClass) {
			$scope = is_string($policyClass) ? $policyClass::getScope() : $policyClass->getScope();

			$permissionOptionCollection = is_string($policyClass)
				? $policyClass::getPermissionOptions()
				: $policyClass->getPermissionOptions();

			$permissions = [];
			foreach ($permissionOptionCollection as $identifier => $permissionOption) {
				$permissions[] = $identifier;
			}

			$policies[ Inflector::camelize($scope) ] = $permissions;
		}

		ksort($policies);

		return $policies;
	}
}
