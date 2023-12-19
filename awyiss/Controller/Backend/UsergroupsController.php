<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


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
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_usergroups = $this->paginate($this->Usergroups->find('withAttributes'));

		$this->set([
			'ao_usergroups' => $lo_usergroups,
		]);
	}
	

	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$la_authorizationPolicies = $this->getAuthorizationPolicies();

		$lo_usergroup = $this->Usergroups->newDefaultEntity();
		if ($this->request->is('post')) {
			$la_data = $this->request->getData();
			$la_data['usergroup_permissions'] = $this->reformatPermissionsData($la_authorizationPolicies, $la_data);
			$lo_usergroup = $this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => ['UsergroupPermissions', 'Users']]);

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

		$this->set([
			'ao_usergroup' => $lo_usergroup,
			'ao_users' => $this->Usergroups->Users->find()->all(),
			'aa_authorizationPolicies' => $la_authorizationPolicies,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$la_authorizationPolicies = $this->getAuthorizationPolicies();

		$li_id = $this->request->getParam('id');
		$lo_usergroup = $this->Usergroups->find()->contain(['UsergroupPermissions', 'Users'])->where(['id' => $li_id])->first();

		if ( ! $lo_usergroup) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_data = $this->request->getData();
			$la_data['usergroup_permissions'] = $this->reformatPermissionsData($la_authorizationPolicies, $la_data);

			$lo_usergroup = $this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => ['UsergroupPermissions', 'Users']]);

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
				'settings' => json_decode($lo_usergroupPermission->settings ?? "", TRUE),
			];
		}
		$lo_usergroup->usergroup_permissions = $la_currentPermissions;

		$this->set([
			'ao_usergroup' => $lo_usergroup,
			'ao_users' => $this->Usergroups->Users->find()->all(),
			'aa_authorizationPolicies' => $la_authorizationPolicies,
		]);
	}
	

	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
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

		ksort($la_policies);

		return $la_policies;
	}


	protected function reformatPermissionsData (array $aa_authorizationPolicies, array $aa_data = []): array {
		$la_permissions = [];

		/** @var \Awyiss\Authorization\Policy\PolicyInterface $lo_authorizationPolicy */
		foreach ($aa_authorizationPolicies AS $lo_authorizationPolicy) {
			/** @var \Awyiss\Authorization\Permission\PermissionInterface $lo_permission */
			foreach ($lo_authorizationPolicy::getPermissions() AS $lo_permission) {
				$ls_scope = $lo_authorizationPolicy::getScope();
				$ls_identifier = $lo_permission->getConfig('identifier');

				$lx_access = Hash::get($aa_data, 'permissions.' . $ls_scope . '.' . $ls_identifier);
				$lx_access = $lo_permission->harmonizeOptionValue($lx_access);

				if (is_null($lx_access)) continue;

				$lx_settings = Hash::get($aa_data, 'permission_settings.' . $ls_scope . '.' . $ls_identifier);

				$la_permissions[] = [
					'scope' => $lo_authorizationPolicy::getScope(),
					'identifier' => $lo_permission->getConfig('identifier'),
					'access' => $lx_access,
					'settings' => $lx_settings,
				];
			}
		}

		return $la_permissions;
	}
}

