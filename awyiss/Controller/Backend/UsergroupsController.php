<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Authorization\Policy\GenericPagePolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Entity\Usergroup;
use Awyiss\Model\Table\UsergroupsTable;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Exception;
use ReflectionException;


/**
 * Usergroups Controller
 *
 * @property UsergroupsTable $Usergroups
 */
class UsergroupsController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_usergroups = $this->Usergroups->find()->where($this->getOverviewWhere());
		$lo_usergroups = $this->paginate($lo_usergroups);

		$this->set([
			'ao_usergroups' => $lo_usergroups,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$lb_usersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Users', NULL, ['create', 'update']);

		$lo_usergroup = $this->Usergroups->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_usergroup, $lb_usersScopeIsAccessible);
		}

		$this->formatPermissions($lo_usergroup);

		$lo_users = NULL;
		if ($lb_usersScopeIsAccessible) {
			$lo_users = $this->Usergroups->Users->find()->all()->toArray();
		}

		$this->set([
			'ao_usergroup' => $lo_usergroup,
			'ao_users' => $lo_users,
			'aa_authorizationPolicies' => $this->getAuthorizationPolicies(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws Exception
	 */
	public function edit() {
		$this->Authorization->ensure('update');

		$lb_usersScopeIsAccessible = $this->Authorization->scopeIsAccessible('Users', NULL, ['create', 'update']);

		$la_contain = ['UsergroupPermissions'];
		if ($lb_usersScopeIsAccessible) {
			$la_contain[] = 'Users';
		}
		$lo_usergroup = $this->Usergroups->findById((int) $this->request->getParam('id'))->find('translations')->contain($la_contain)->first();
		if (!$lo_usergroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_usergroup, $lb_usersScopeIsAccessible, 'edit');
		}

		$this->formatPermissions($lo_usergroup);

		$lo_users = NULL;
		if ($lb_usersScopeIsAccessible) {
			$lo_users = $this->Usergroups->Users->find()->all()->toArray();
		}

		$this->set([
			'ao_usergroup' => $lo_usergroup,
			'ao_users' => $lo_users,
			'aa_authorizationPolicies' => $this->getAuthorizationPolicies(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return Response
	 *
	 * @throws Exception
	 */
	public function delete(): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Usergroup $lo_usergroup */
		$lo_usergroup = $this->Usergroups->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if (!$lo_usergroup) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Usergroups->delete($lo_usergroup)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Usergroup $ao_usergroup
	 * @param bool $ab_usersScopeIsAccessible
	 * @param string $as_method
	 *
	 * @return void
	 *
	 * @throws RedirectException
	 * @throws Exception
	 */
	protected function save(Usergroup $ao_usergroup, bool $ab_usersScopeIsAccessible, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Usergroups->hasAttributes()) {
			$la_associated[] = $this->Usergroups->getAttributesTable(TRUE);
			$ao_usergroup->setAccess('attributes', TRUE);
		}

		$la_data = $this->request->getData();
		$la_data['usergroup_permissions'] = $this->reformatPermissionsData($la_data);

		$la_associated[] = 'UsergroupPermissions';
		$ao_usergroup->setAccess('usergroupPermissions', TRUE);
		if ($ab_usersScopeIsAccessible) {
			$la_associated['Users'] = ['onlyIds' => TRUE];
			$ao_usergroup->setAccess('users', TRUE);
		}

		$this->Usergroups->patchEntity($ao_usergroup, $la_data, ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Usergroups->save($ao_usergroup)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_usergroup->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_usergroup->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * Retreive all available AuthorizationPolicies, found in both the Awyiss and the custom namespace,
	 * combined with instances of GenericPagePolicy for page roles without a specified policy
	 *
	 * @return array<string, class-string<PolicyInterface>|GenericPagePolicy>
	 * @throws ReflectionException
	 */
	protected function getAuthorizationPolicies(): array {
		static $la_policies;

		if (isset($la_policies)) {
			return $la_policies;
		}

		/** @var AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$la_policies = $lo_authorizationService->getPolicies();

		//Get all page roles from the database because we want them to have policies too
		$lo_pageRoles = $this->fetchTable('PageRoles')->find('active', authorize: ['skip' => TRUE])->all();

		/** @var PageRole $lo_pageRole */
		foreach ($lo_pageRoles as $lo_pageRole) {
			$ls_scope = Inflector::camelize(Inflector::tableize($lo_pageRole->identifier));

			/*
			 * If there's no policy for the identifier yet, we add an instance of GenericPagePolicy for the page role.
			 * This way, a custom policy for every page role can be set, but it'll fall back
			 * to a generic CRUD policy
			 */
			if (!isset($la_policies[ $ls_scope ])) {
				$la_policies[ $ls_scope ] = new GenericPagePolicy($ls_scope);
			}
		}

		ksort($la_policies);


		return $la_policies;
	}


	/**
	 * Traverses all AuthorizationPolicies and sets an array-element if policy-related settings are present in $aa_data
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
	 *]
	 * ```
	 *
	 * @param array $aa_data
	 *
	 * @return array<int, array{scope: string, identifier: string, access: mixed, settings: mixed}>
	 * @throws Exception
	 */
	protected function reformatPermissionsData(array $aa_data = []): array {
		$la_permissions = [];

		$la_authorizationPolicies = $this->getAuthorizationPolicies();

		/** @var class-string<PolicyInterface>|GenericPagePolicy $lo_authorizationPolicy */
		foreach ($la_authorizationPolicies as $lo_authorizationPolicy) {
			/** @var PermissionOptionInterface $lo_permission */
			foreach ((!is_object($lo_authorizationPolicy) ? $lo_authorizationPolicy::getPermissionOptions() : $lo_authorizationPolicy->getPermissionOptions()) as $lo_permission) {
				$ls_scope = !is_object($lo_authorizationPolicy) ? $lo_authorizationPolicy::getScope() : $lo_authorizationPolicy->getScope();
				$ls_scope = Inflector::underscore($ls_scope);

				$ls_identifier = $lo_permission->getConfig('identifier');
				$ls_identifier = Inflector::underscore($ls_identifier);

				$lx_access = Hash::get($aa_data, ['permissions', $ls_scope, $ls_identifier]);
				$lx_access = $lo_permission->harmonizeOptionValue($lx_access);

				if (is_null($lx_access)) {
					continue;
				}

				$lx_settings = Hash::get($aa_data, ['permission_settings', $ls_scope, $ls_identifier]);

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
	 * @param Usergroup $ao_usergroup
	 *
	 * @return void
	 */
	protected function formatPermissions(Usergroup $ao_usergroup): void {
		$la_currentPermissions = [];

		foreach ($ao_usergroup->usergroup_permissions ?? [] as $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = (object) [
				'access' => $lo_usergroupPermission->access,
				'settings' => $lo_usergroupPermission->settings,
			];
		}

		$ao_usergroup->usergroup_permissions = $la_currentPermissions;
	}
}
