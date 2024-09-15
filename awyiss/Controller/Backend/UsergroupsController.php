<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Entity\Usergroup;
use Awyiss\Routing\Router;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


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
		return $this->Usergroups->find()->where($this->getOverviewWhere());
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
		unset($this->paginate['enabled']);
		if ($lb_paginated) {
			$lo_usergroups = $this->paginate($lo_query);
		}
		else {
			$lo_usergroups = $lo_query->all();
		}

		$this->set([
			'usergroups' => $lo_usergroups,
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

		$lb_usersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Users', [], ['create', 'update']);

		$lo_usergroup = $this->Usergroups->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_usergroup, $lb_usersScopeIsAccessible);
		}

		$this->formatPermissions($lo_usergroup);

		$this->setViewVars($lo_usergroup, $lb_usersScopeIsAccessible);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		$lb_usersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Users', [], ['create', 'update']);

		$la_contain = ['UsergroupPermissions'];
		if ($lb_usersScopeIsAccessible) {
			$la_contain[] = 'Users';
		}
		$lo_usergroup = $this->Usergroups->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain($la_contain)->first();
		if (!$lo_usergroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_usergroup, $lb_usersScopeIsAccessible, 'edit');
		}

		$this->formatPermissions($lo_usergroup);

		$this->setViewVars($lo_usergroup, $lb_usersScopeIsAccessible);
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

		/** @var Usergroup $lo_usergroup */
		$lo_usergroup = $this->Usergroups->findById($id)->first();
		if (!$lo_usergroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Usergroups->delete($lo_usergroup)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_usergroup->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Usergroup $usergroup
	 * @param bool $usersScopeIsAccessible
	 * @param string $method
	 * @return void
	 * @throws RedirectException
	 * @throws \Exception
	 */
	protected function save(Usergroup $usergroup, bool $usersScopeIsAccessible, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Usergroups->hasAttributes()) {
			$la_associated[] = $this->Usergroups->getAttributesTableName(true);
			$usergroup->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();
		$la_data['usergroup_permissions'] = $this->reformatPermissionsData($la_data);

		$la_associated[] = 'UsergroupPermissions';
		$usergroup->setAccess('usergroupPermissions', true);
		if ($usersScopeIsAccessible) {
			$la_associated['Users'] = ['onlyIds' => true];
			$usergroup->setAccess('users', true);
		}

		$this->Usergroups->patchEntity($usergroup, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Usergroups->save($usergroup, ['asCopy' => $lb_saveAsCopy])) {
				/** @var \Awyiss\Model\Entity\User $lo_currentUser */
				$lo_session = $this->request->getSession();
				$lo_currentUser = $lo_session->read('Auth');
				$li_userId = $lo_currentUser?->id;

				if ($usergroup->users && in_array($li_userId, array_column($usergroup->users, 'id'))) {
					$lo_currentUser->usergroups = null;
					$lo_session->delete('Backend.menu');
				}

				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($usergroup),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $usergroup->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($usergroup->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * Retreive all available AuthorizationPolicies, found in both the Awyiss and the custom namespace,
	 * combined with instances of AbstractGenericPolicy for page roles without a specified policy
	 *
	 * @return array<string, class-string<\Awyiss\Authorization\Policy\PolicyInterface>|\Awyiss\Authorization\Policy\AbstractGenericPolicy>
	 * @throws \ReflectionException
	 */
	protected function getAuthorizationPolicies(): array {
		static $la_policies;

		if (isset($la_policies)) {
			return $la_policies;
		}

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$la_policies = $lo_authorizationService->getPolicies();
		unset($la_policies['user_configuration']);

		ksort($la_policies);


		return $la_policies;
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
		$la_permissions = [];

		$la_authorizationPolicies = $this->getAuthorizationPolicies();

		/** @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $lx_authorizationPolicy */
		foreach ($la_authorizationPolicies as $lx_authorizationPolicy) {
			/** @var \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $lo_permission */
			foreach ((!is_object($lx_authorizationPolicy) ? $lx_authorizationPolicy::getPermissionOptions() : $lx_authorizationPolicy->getPermissionOptions()) as $lo_permission) {
				$ls_scope = !is_object($lx_authorizationPolicy) ? $lx_authorizationPolicy::getScope() : $lx_authorizationPolicy->getScope();
				$ls_scope = Inflector::underscore($ls_scope);

				$ls_identifier = $lo_permission->getConfig('identifier');
				$ls_identifier = Inflector::underscore($ls_identifier);

				$lx_access = Hash::get($data, ['permissions', $ls_scope, $ls_identifier]);
				$lx_access = $lo_permission->harmonizeOptionValue($lx_access);

				if (is_null($lx_access)) {
					continue;
				}

				$lx_settings = Hash::get($data, ['permission_settings', $ls_scope, $ls_identifier]);

				$la_permissions[] = [
					'scope' => $ls_scope,
					'identifier' => $lo_permission->getConfig('identifier'),
					'access' => $lx_access->value,
					'settings' => $lx_settings,
				];
			}
		}


		return $la_permissions;
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
		$la_currentPermissions = [];

		foreach ($usergroup->usergroup_permissions ?? [] as $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = (object)[
				'access' => $lo_usergroupPermission->access,
				'settings' => $lo_usergroupPermission->settings,
			];
		}

		$usergroup->usergroup_permissions = $la_currentPermissions;
	}


	/**
	 * @param \Awyiss\Model\Entity\Usergroup $usergroup
	 * @param bool $usersScopeIsAccessible
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function setViewVars(Usergroup $usergroup, bool $usersScopeIsAccessible): void {
		$lo_users = null;
		if ($usersScopeIsAccessible) {
			$lo_query = $this->Usergroups->Users->find();
			$this->paginate = [
				'order' => [
					'username' => 'asc',
				],
			];
			$lo_users = $this->paginate($lo_query);
		}

		/** @var \Awyiss\Model\Table\DatatablesTable $lo_datatablesTable */
		$lo_datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		$la_datatables = $lo_datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
		$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$la_pageRoles = $lo_pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$la_authorizationPolicies = [];
		/**
		 * @var \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface> $lx_policyClass
		 */
		foreach ($this->getAuthorizationPolicies() as $lx_policyClass) {
			$ls_scope = is_string($lx_policyClass) ? $lx_policyClass::getScope() : $lx_policyClass->getScope();

			if (isset($la_pageRoles[ $ls_scope ])) {
				$ls_title = $la_pageRoles[ $ls_scope ]->label;
			}
			elseif (isset($la_datatables[ $ls_scope ])) {
				$ls_title = $la_datatables[ $ls_scope ]->label;
			}
			else {
				$ls_title = __d($ls_scope, 'headline_overview');
			}

			$la_authorizationPolicies[] = [
				'permissions' => is_string($lx_policyClass) ? $lx_policyClass::getPermissionOptions() : $lx_policyClass->getPermissionOptions(),
				'scope' => $ls_scope,
				'title' => $ls_title,
			];
		}

		// Sort the policies by title
		usort($la_authorizationPolicies, function ($a, $b) {
			return strcasecmp($a['title'], $b['title']);
		});

		$this->set([
			'usergroup' => $usergroup,
			'users' => $lo_users,
			'authorizationPolicies' => $la_authorizationPolicies,
			'datatables' => $la_datatables,
			'pageRoles' => $la_pageRoles,
		]);
	}
}
