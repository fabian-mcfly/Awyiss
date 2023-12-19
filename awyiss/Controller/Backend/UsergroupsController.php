<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Controller\BackendController as Controller;
use Cake\Utility\Hash;


/**
 * Usergroups Controller
 *
 * @property \Awyiss\Model\Table\UsergroupsTable $Usergroups
 * @method \Awyiss\Model\Entity\Usergroup[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class UsergroupsController extends Controller {
	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_usergroups = $this->Usergroups->find('withAttributes')->where($this->getOverviewWhere());
		$lo_usergroups = $this->paginate($lo_usergroups);

		$this->set([
			'ao_usergroups' => $lo_usergroups,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lb_usersScopeIsAccessible = $this->Access->scopeIsAccessible('Users', NULL, ['create', 'update']);

		$la_authorizationPolicies = $this->getAuthorizationPolicies();

		$lo_usergroup = $this->Usergroups->newDefaultEntity();
		if ($this->request->is('post')) {
			$la_data = $this->request->getData();
			$la_data['usergroup_permissions'] = $this->reformatPermissionsData($la_authorizationPolicies, $la_data);

			$la_associated = ['UsergroupPermissions'];
			if ($lb_usersScopeIsAccessible) {
				$la_associated[] = 'Users';
			}

			$this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => $la_associated]);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Usergroups->save($lo_usergroup)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_usergroup->id]);
				}
				$this->Flash->error(__('::add_failed'));
			}
		}

		$la_currentPermissions = [];
		foreach ($lo_usergroup->usergroup_permissions ?? [] AS $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = [
				'access' => $lo_usergroupPermission->access,
				'settings' => json_decode($lo_usergroupPermission->settings ?? "", TRUE),
			];
		}
		$lo_usergroup->usergroup_permissions = $la_currentPermissions;

		$lo_users = NULL;
		if ($lb_usersScopeIsAccessible) {
			$lo_users = $this->Usergroups->Users->find();
		}

		$this->set([
			'ao_usergroup' => $lo_usergroup,
			'ao_users' => $lo_users,
			'aa_authorizationPolicies' => $la_authorizationPolicies,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$lb_usersScopeIsAccessible = $this->Access->scopeIsAccessible('Users', NULL, ['create', 'update']);

		$la_authorizationPolicies = $this->getAuthorizationPolicies();

		$li_id = $this->request->getParam('id');

		$la_contain = ['UsergroupPermissions'];
		if ($lb_usersScopeIsAccessible) {
			$la_contain[] = 'Users';
		}
		/** @var \Awyiss\Model\Entity\Usergroup $lo_usergroup */
		$lo_usergroup = $this->Usergroups->find()->contain($la_contain)->where(['id' => $li_id])->first();

		if ( ! $lo_usergroup) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		//dd($lo_usergroup->usergroup_permissions);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_data = $this->request->getData();
			$la_data['usergroup_permissions'] = $this->reformatPermissionsData($la_authorizationPolicies, $la_data);

			$la_associated = ['UsergroupPermissions'];
			if ($lb_usersScopeIsAccessible) {
				$la_associated[] = 'Users';
			}
			$this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => $la_associated]);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Usergroups->save($lo_usergroup)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_usergroup->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
		}

		$la_currentPermissions = [];
		foreach ($lo_usergroup->usergroup_permissions ?? [] AS $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = (object)[
				'access' => $lo_usergroupPermission->access,
				//'settings' => json_decode($lo_usergroupPermission->settings ?? "", TRUE),
			];
		}
		$lo_usergroup->usergroup_permissions = $la_currentPermissions;

		$lo_users = NULL;
		if ($this->Access->scopeIsAccessible('Users', NULL, ['create', 'update'])) {
			$lo_users = $this->Usergroups->Users->find();
		}

		$this->set([
			'ao_usergroup' => $lo_usergroup,
			'ao_users' => $lo_users,
			'aa_authorizationPolicies' => $la_authorizationPolicies,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_usergroup = $this->Usergroups->get($li_id);

		if ( ! $lo_usergroup) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Usergroups->delete($lo_usergroup)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	protected function getAuthorizationPolicies (): array {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$la_policies = $lo_authorizationService->getPolicies();


		$lo_pageRolesTable = $this->getTableLocator()->get('PageRoles');
		$lo_pageRoles = $lo_pageRolesTable->find('active', [
			'access' => ['skip' => TRUE]
		])->all();
		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRoles AS $lo_pageRole) {
			$ls_identifier = \Cake\Utility\Inflector::pluralize($lo_pageRole->identifier);
			if (!isset($la_policies[ $ls_identifier ])) {
				$la_policies[ $ls_identifier ] = new AnonymousPolicy($ls_identifier);
			}
		}


		ksort($la_policies);

		return $la_policies;
	}


	/**
	 * @param PolicyInterface[] $aa_authorizationPolicies
	 * @param array $aa_data
	 *
	 * @return array
	 */
	protected function reformatPermissionsData (array $aa_authorizationPolicies, array $aa_data = []): array {
		$la_permissions = [];

		foreach ($aa_authorizationPolicies AS $lo_authorizationPolicy) {
			foreach ((!is_object($lo_authorizationPolicy) ? $lo_authorizationPolicy::getPermissions() : $lo_authorizationPolicy->getPermissions()) AS $lo_permission) {
				$ls_scope = !is_object($lo_authorizationPolicy) ? $lo_authorizationPolicy::getScope() : $lo_authorizationPolicy->getScope();
				$ls_identifier = $lo_permission->getConfig('identifier');

				$lx_access = Hash::get($aa_data, 'permissions.' . $ls_scope . '.' . $ls_identifier);
				$lx_access = $lo_permission->harmonizeOptionValue($lx_access);

				if (is_null($lx_access)) continue;

				$lx_settings = Hash::get($aa_data, 'permission_settings.' . $ls_scope . '.' . $ls_identifier);

				$la_permissions[] = [
					'scope' => $ls_scope,
					'identifier' => $lo_permission->getConfig('identifier'),
					'access' => $lx_access,
					'settings' => $lx_settings,
				];
			}
		}

		return $la_permissions;
	}
}

