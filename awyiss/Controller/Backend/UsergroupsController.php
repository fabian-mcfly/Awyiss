<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Entity\Usergroup;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * Usergroups Controller
 *
 * @property \Awyiss\Model\Table\UsergroupsTable $Usergroups
 */
class UsergroupsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Usergroups->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		unset($this->paginate['enabled']);
		if ($paginated) {
			$usergroups = $this->paginate($query);
		}
		else {
			$usergroups = $query->all();
		}

		$this->set([
			'usergroups' => $usergroups,
			'attributes' => $this->Usergroups->getAttributes(),
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

		$usersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Users', [], ['create', 'update']);

		$usergroup = $this->Usergroups->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($usergroup, $usersScopeIsAccessible);
		}

		$this->formatPermissions($usergroup);

		$this->setViewVars($usergroup, $usersScopeIsAccessible);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		$usersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Users', [], ['create', 'update']);

		$contain = ['UsergroupPermissions'];
		if ($usersScopeIsAccessible) {
			$contain[] = 'Users';
		}
		/**
		 * @var \Awyiss\Model\Entity\Usergroup $usergroup
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$usergroup = $this->Usergroups->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain($contain)->first();
		if (!$usergroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($usergroup, $usersScopeIsAccessible, 'edit');
		}

		$this->formatPermissions($usergroup);

		$this->setViewVars($usergroup, $usersScopeIsAccessible);
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

		/** @var \Awyiss\Model\Entity\Usergroup $usergroup */
		$usergroup = $this->Usergroups->findById($id)->first();
		if (!$usergroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Usergroups->delete($usergroup)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($usergroup->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\Usergroup $usergroup
	 * @param bool $usersScopeIsAccessible
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 * @throws \Exception
	 */
	protected function save(Usergroup $usergroup, bool $usersScopeIsAccessible, string $method = 'add'): void {
		$associated = [];
		if ($this->Usergroups->hasAttributes()) {
			$associated[] = $this->Usergroups->getAttributesTableName(true);
			$usergroup->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();
		$requestData['usergroup_permissions'] = $this->reformatPermissionsData($requestData);

		$associated[] = 'UsergroupPermissions';
		$usergroup->setAccess('usergroupPermissions', true);
		if ($usersScopeIsAccessible) {
			$associated['Users'] = ['onlyIds' => true];
			$usergroup->setAccess('users', true);
		}

		$this->Usergroups->patchEntity($usergroup, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Usergroups->save($usergroup, ['asCopy' => $saveAsCopy])) {
				/** @var \Awyiss\Model\Entity\User $currentUser */
				$session = $this->request->getSession();
				$currentUser = $session->read('Auth');
				$userId = $currentUser?->id;

				if ($usergroup->users && in_array($userId, array_column($usergroup->users, 'id'))) {
					$currentUser->usergroups = null;
					$session->delete('Backend.menu');
				}

				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($usergroup),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $usergroup->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($usergroup->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * Retrieve all available AuthorizationPolicies, found in both the Awyiss and the custom namespace,
	 * combined with instances of AbstractGenericPolicy for page roles without a specified policy
	 *
	 * @return array<string, class-string<\Awyiss\Authorization\Policy\PolicyInterface>|\Awyiss\Authorization\Policy\AbstractGenericPolicy>
	 */
	protected function getAuthorizationPolicies(): array {
		static $policies;

		if (isset($policies)) {
			return $policies;
		}

		/** @var \Awyiss\Authorization\AuthorizationService $authorizationService */
		$authorizationService = $this->getRequest()->getAttribute('authorization');
		$policies = $authorizationService->getPolicies();
		unset($policies['user_configuration']);

		ksort($policies);


		return $policies;
	}


	/**
	 * Traverses all AuthorizationPolicies and sets an array-element if policy-related settings are present in $data
	 *
	 * The result is an array of arrays, each compatible with \Awyiss\Model\Entity\UsergroupPermission::class
	 *
	 * ```
	 * [
	 *    [
	 *        "scope" => scope1,
	 *        "identifier" => identifier1,
	 *        "access" => 1,
	 *        "settings" => ...,
	 *    ],
	 *    [
	 *        "scope" => scope1,
	 *        "identifier" => identifier2,
	 *        "access" => 0,
	 *        "settings" => ...,
	 *    ],
	 *    [
	 *        "scope" => scope2,
	 *        "identifier" => identifier1,
	 *        "access" => -1,
	 *        "settings" => ...,
	 *    ],
	 * ]
	 * ```
	 *
	 * @param array $data
	 * @return array<int, array{scope: string, identifier: string, access: mixed, settings: mixed}>
	 * @throws \Exception
	 */
	protected function reformatPermissionsData(array $data = []): array {
		$permissions = [];

		$authorizationPolicies = $this->getAuthorizationPolicies();

		/** @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $authorizationPolicy */
		foreach ($authorizationPolicies as $authorizationPolicy) {
			/** @var \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $permission */
			foreach ((!is_object($authorizationPolicy) ? $authorizationPolicy::getPermissionOptions() : $authorizationPolicy->getPermissionOptions()) as $permission) {
				$scope = !is_object($authorizationPolicy) ? $authorizationPolicy::getScope() : $authorizationPolicy->getScope();
				$scope = Inflector::underscore($scope);

				$identifier = $permission->getConfig('identifier');
				$identifier = Inflector::underscore($identifier);

				$access = Hash::get($data, ['permissions', $scope, $identifier]);
				$access = $permission->harmonizeOptionValue($access);

				if (is_null($access)) {
					continue;
				}

				$settings = Hash::get($data, ['permission_settings', $scope, $identifier]);

				$permissions[] = [
					'scope' => $scope,
					'identifier' => $permission->getConfig('identifier'),
					'access' => $access->value,
					'settings' => $settings,
				];
			}
		}


		return $permissions;
	}


	/**
	 * Formats the usergroup_permissions entities of the Usergroup entity for the add and edit views
	 *
	 * The result is a multidimensional array:
	 *
	 * ```
	 * [
	 *    scope1    =>    [
	 *                    identifier1 => [access, settings],
	 *                    identifier2 => [access, settings],
	 *                    identifier3 => [access, settings],
	 *                    identifier4 => [access, settings]
	 *                ],
	 *    scope2    =>    [
	 *                    identifier1 => [access, settings],
	 *                    identifier2 => [access, settings]
	 *                ]
	 * ]
	 * ```
	 *
	 * @param Usergroup $usergroup
	 * @return void
	 */
	protected function formatPermissions(Usergroup $usergroup): void {
		$currentPermissions = [];

		foreach ($usergroup->usergroupPermissions ?? [] as $usergroupPermission) {
			if (!isset($currentPermissions[ $usergroupPermission->scope ])) {
				$currentPermissions[ $usergroupPermission->scope ] = [];
			}

			$currentPermissions[ $usergroupPermission->scope ][ $usergroupPermission->identifier ] = (object)[
				'access' => $usergroupPermission->access,
				'settings' => $usergroupPermission->settings,
			];
		}

		$usergroup->usergroupPermissions = $currentPermissions;
	}


	/**
	 * @param \Awyiss\Model\Entity\Usergroup $usergroup
	 * @param bool $usersScopeIsAccessible
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Usergroup $usergroup, bool $usersScopeIsAccessible): void {
		$users = [];
		if ($usersScopeIsAccessible) {
			$users = $this->Usergroups->Users->find()->all()->toArray();
		}

		/** @var \Awyiss\Model\Table\DatatablesTable $datatablesTable */
		$datatablesTable = $this->fetchTable('Datatables');
		$datatables = $datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = $this->fetchTable('PageRoles');
		$pageRoles = $pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$authorizationPolicies = [];
		/**
		 * @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $policyClass
		 */
		foreach ($this->getAuthorizationPolicies() as $policyClass) {
			$scope = is_string($policyClass) ? $policyClass::getScope() : $policyClass->getScope();

			if (isset($pageRoles[ $scope ])) {
				$title = $pageRoles[ $scope ]->label;
			}
			elseif (isset($datatables[ $scope ])) {
				$title = $datatables[ $scope ]->label;
			}
			else {
				$title = __d($scope, 'headline_overview');
			}

			$authorizationPolicies[] = [
				'permissions' => is_string($policyClass) ? $policyClass::getPermissionOptions() : $policyClass->getPermissionOptions(),
				'scope' => $scope,
				'title' => $title,
			];
		}

		// Sort the policies by title
		usort($authorizationPolicies, function ($a, $b) {
			return strcasecmp($a['title'], $b['title']);
		});

		$this->set([
			'usergroup' => $usergroup,
			'users' => $users,
			'authorizationPolicies' => $authorizationPolicies,
			'datatables' => $datatables,
			'pageRoles' => $pageRoles,
		]);
	}
}
