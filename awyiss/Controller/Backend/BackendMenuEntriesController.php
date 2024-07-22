<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\Menu;
use Awyiss\Utility\Menu\MenuItem;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use ReflectionClass;
use ReflectionMethod;


/**
 * MenuEntries Controller
 *
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable $BackendMenuEntries
 */
class BackendMenuEntriesController extends Controller {
	/**
	 * @var string|null Session identifier for the selected insert_after_id
	 */
	protected ?string $selectedInsertAfterIdSessionIdentifier = null;
	/**
	 * @var string|null Session identifier for the selected parent_id
	 */
	protected ?string $selectedParentIdSessionIdentifier = null;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->selectedInsertAfterIdSessionIdentifier = Inflector::underscore($this->getName()) . '.' . ($this->request->getParam('lang') ?? 'global') . '.insert_after_id';
		$this->selectedParentIdSessionIdentifier = Inflector::underscore($this->getName()) . '.' . ($this->request->getParam('lang') ?? 'global') . '.parent_id';
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->BackendMenuEntries->find()->where($this->getOverviewWhere());
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_menu = new BackendMenu();

		$this->set([
			'menu' => $lo_menu,
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
		$lo_menuEntry = $this->BackendMenuEntries->newDefaultEntity([
			'insertAfterId' => $lo_session->read($this->selectedInsertAfterIdSessionIdentifier),
			'parentId' => $lo_session->read($this->selectedParentIdSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_menuEntry);
		}

		$this->setViewVars($lo_menuEntry);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var BackendMenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->BackendMenuEntries->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menuEntry, 'edit');
		}

		$this->setViewVars($lo_menuEntry);
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

		/** @var BackendMenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->BackendMenuEntries->findById($id)->first();
		if (!$lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->BackendMenuEntries->delete($lo_menuEntry)) {
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
	 * Returns a Collection of all possible parent ids for the given menu entry
	 * to prevent circular references
	 *
	 * @param BackendMenuEntry $menuEntry
	 * @param \Awyiss\Utility\Menu\Menu|null $dynamicMenu
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getPossibleParentMenuEntries(BackendMenuEntry $menuEntry, ?Menu $dynamicMenu): CollectionInterface {
		$lo_listNested = collection($dynamicMenu->toArray());

		//We only want to find threaded pages for an existing entity (id equals not null)
		$li_originalId = $menuEntry->get('id');
		if (!$li_originalId) {
			return $lo_listNested;
		}

		$li_foundAtLevel = null;

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$lo_possibleParents = $lo_listNested->filter(function (MenuItem $item, string|int $identifier) use ($li_originalId, &$li_foundAtLevel) {
			if (gettype($identifier) === 'string') {
				return true;
			}

			if ($identifier === $li_originalId) {
				$li_foundAtLevel = $item->getLevel();
			}
			elseif (is_null($li_foundAtLevel) || $item->getLevel() <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});


		return $lo_possibleParents;
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		$lo_identity = $this->getIdentity();

		$la_requestData = $requestData;
		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $table->updateAll(function (QueryExpression $expression) use ($la_requestData, $lo_identity) {
			$lo_insertAfterIdCase = $expression->case();
			$lo_parentIdCase = $expression->case();
			$lo_systemOrderCase = $expression->case();

			foreach ($la_requestData as $la_data) {
				$li_id = (int)$la_data['id'];

				$lo_insertAfterIdCase->when(['id' => $li_id])->then($la_data['insertAfterId'], 'string');
				$lo_parentIdCase->when(['id' => $li_id])->then($la_data['parentId'], 'string');
				$lo_systemOrderCase->when(['id' => $li_id])->then($la_data['systemOrder'], 'integer');
			}


			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return [
				'insert_after_id' => $lo_insertAfterIdCase,
				'parent_id' => $lo_parentIdCase,
				'system_order' => $lo_systemOrderCase,
				'deleted_by' => $lo_identity?->id,
				'deleted_on' => DateTime::now(),
			];
		}, [
			'id IN' => array_keys($requestData),
		]);


		return $li_affectedRows;
	}



	/**
	 * @param BackendMenuEntry $menuEntry
	 * @param string $method
	 * @return void
	 * @throws RedirectException
	 */
	protected function save(BackendMenuEntry $menuEntry, string $method = 'add'): void {
		$la_associated = [];
		if ($this->BackendMenuEntries->hasAttributes()) {
			$la_associated[] = $this->BackendMenuEntries->getAttributesTableName(true);
			$menuEntry->setAccess('attributes', true);
		}

		$this->BackendMenuEntries->patchEntity($menuEntry, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!empty($menuEntry->parentId)) {
			$menuEntry->insertAfterId = null;

			$lo_request = $this->getRequest();
			//When insertAfterId is part of the request data, overwrite it because it's might be outdated
			if ($lo_request->getData('insert_after_id') !== null) {
				$lo_request = $lo_request->withData('insert_after_id', $menuEntry->insertAfterId);
				$this->setRequest($lo_request);
			}
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->BackendMenuEntries->save($menuEntry, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				// Remember the parent id for the next entry
				$lo_session = $this->request->getSession();
				$lo_session->write($this->selectedInsertAfterIdSessionIdentifier, $menuEntry->insertAfterId);
				$lo_session->write($this->selectedParentIdSessionIdentifier, $menuEntry->parentId);

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $menuEntry->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->BackendMenuEntries->getSystemOrderRelatedColumns($menuEntry)) {
				$menuEntry->systemOrder = null;
			}
			else {
				$menuEntry->systemOrder = $menuEntry->hasOriginal('systemOrder') ? $menuEntry->getOriginal('systemOrder') : $menuEntry->get('systemOrder');
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
		$la_options = [];

		/** @var MenuItem $lo_item */
		foreach ($menu->items() as $ls_identifier => $lo_item) {
			$la_options[ $ls_identifier ] = str_repeat('- ', $lo_item->getLevel() - 1) . $lo_item->getLabel();
		}


		return $la_options;
	}


	/**
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $menuEntry
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function setViewVars(BackendMenuEntry $menuEntry): void {
		$lo_menu = new BackendMenu();

		$la_insertAfterOptions = $this->generateMenuSelectOptions($lo_menu->getCustomMenu() ?? $lo_menu->getMenu());

		$lo_possibleParentMenuEntries = $this->getPossibleParentMenuEntries($menuEntry, $lo_menu->getDynamicMenu());

		$la_controllers = $this->getControllers();

		$this->set([
			'menu' => $lo_menu,
			'insertAfterOptions' => $la_insertAfterOptions,
			'backendMenuEntry' => $menuEntry,
			'possibleParentMenuEntries' => $lo_possibleParentMenuEntries,
			'attributes' => $this->BackendMenuEntries->getAttributes(),
			'controllers' => $la_controllers,
			'policies' => $this->getPolicies(),
		]);
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	protected function getControllers(): array {
		static $la_controllers = [];
		static $la_blocklistedMethods = [
			'initialize',
			'beforeFilter',
			'beforeRender',
			'render',
			'setEventManager',
			'dispatchEvent',
		];

		if (!empty($la_controllers)) {
			return $la_controllers;
		}

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Controller\Backend\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Controller', 'Backend', '*Controller.php',]),
			'\Awyiss\Controller\Backend\\' => implode(DS, [ROOT, APP_DIR, 'Controller', 'Backend', '*Controller.php']),
		];

		//Traverse both namespaces
		foreach ($la_paths as $ls_namespace => $ls_path) {
			//Look for files with name "*Table.php"
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_controllerName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -14);

				//If an entry exists or if the table does not allow attributes, skip it
				if (isset($la_controllers[ $ls_controllerName ])) {
					continue;
				}

				$ls_controllerClass = $ls_namespace . $ls_controllerName . 'Controller';

				$lo_reflection = new ReflectionClass($ls_controllerClass);

				$la_methods = array_filter($lo_reflection->getMethods(ReflectionMethod::IS_PUBLIC), function ($method) use ($ls_controllerName, $la_blocklistedMethods) {
					if (in_array($method->getName(), $la_blocklistedMethods)) {
						return false;
					}

					// Check for the NoDirectAccess attribute
					$la_attributes = $method->getAttributes(NoDirectAccess::class);
					if (!empty($la_attributes)) {
						return false;
					}

					return str_ends_with($method->getDeclaringClass()->getName(), $ls_controllerName . 'Controller');
				});

				if (empty($la_methods)) {
					continue;
				}

				array_walk($la_methods, function (ReflectionMethod &$method) use (&$la_methods) {
					/** @noinspection PhpVariableNamingConventionInspection */
					$method = $method->getName();
				});

				$la_controllers[ $ls_controllerName ] = $la_methods;
			}
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_name = Inflector::pluralize($le_pageRole->name);
			if (isset($la_controllers[ $ls_name ])) {
				continue;
			}

			$la_controllers[ $ls_name ] = $la_controllers['Pages'];
		}

		/** @var \Awyiss\Model\Table\DatatablesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Datatables');
		$lo_table->findAllAndCache()->each(function (Datatable $datatable) use (&$la_controllers) {
			$ls_name = Inflector::camelize($datatable->identifier);

			if (!isset($la_controllers[ $ls_name ])) {
				$la_controllers[ $ls_name ] = $la_controllers['GenericDatatables'];
			}
		});

		unset($la_controllers['GenericDatatables']);

		ksort($la_controllers);

		return $la_controllers;
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function getPolicies(): array {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->request->getAttribute('authorization');
		$la_policies = [];

		/**
		 * @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $lx_policyClass
		 */
		foreach ($lo_authorizationService->getPolicies() as $lx_policyClass) {
			$ls_scope = is_string($lx_policyClass) ? $lx_policyClass::getScope() : $lx_policyClass->getScope();

			$la_permissions = [];
			foreach (is_string($lx_policyClass) ? $lx_policyClass::getPermissionOptions() : $lx_policyClass->getPermissionOptions() as $ls_identifier => $lo_permissionOption) {
				$la_permissions[] = $ls_identifier;
			}

			$la_policies[ $ls_scope ] = $la_permissions;
		}

		ksort($la_policies);


		return $la_policies;
	}
}
