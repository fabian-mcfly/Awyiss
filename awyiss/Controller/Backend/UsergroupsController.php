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
	 * @return \Cake\Http\Response|NULL|void Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$lo_usergroups = $this->paginate($this->Usergroups->find('withAttributes')->contain(['UsergroupsPermissions']));

		$this->set([
			'usergroups' => $lo_usergroups,
		]);
	}
	

	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|NULL|void Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function add () {
		$la_authorizationPolicies = $this->_getAuthorizationPolicies();

		$lo_usergroup = $this->Usergroups->newEmptyEntity();
		if ($this->request->is('post')) {
			$la_data = $this->request->getData();
			$la_data['usergroups_permissions'] = $this->_reformatPermissionsData($la_authorizationPolicies, $la_data);

			$lo_usergroup = $this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => ['UsergroupsPermissions', 'Users']]);
			if ($this->Usergroups->save($lo_usergroup)) {
				$this->Flash->success(__('::add_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_usergroup->id]);
			}
			$this->Flash->error(__('::add_failed'));
		}

		$la_currentPermissions = [];
		foreach ($lo_usergroup->usergroups_permissions ?? [] AS $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = [
				'access' => $lo_usergroupPermission->access,
				'settings' => json_decode($lo_usergroupPermission->settings ?? "", TRUE),
			];
		}
		$lo_usergroup->usergroups_permissions = $la_currentPermissions;

		$this->set([
			'usergroup' => $lo_usergroup,
			'users' => $this->Usergroups->Users->find()->all(),
			'authorizationPolicies' => $la_authorizationPolicies,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|NULL|void Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function edit () {
		$la_authorizationPolicies = $this->_getAuthorizationPolicies();

		$li_id = $this->request->getParam('id');
		$lo_usergroup = $this->Usergroups->get($li_id, [
			'contain' => ['UsergroupsPermissions', 'Users'],
		]);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_data = $this->request->getData();
			$la_data['usergroups_permissions'] = $this->_reformatPermissionsData($la_authorizationPolicies, $la_data);

			$lo_usergroup = $this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => ['UsergroupsPermissions', 'Users']]);

			if ($this->Usergroups->save($lo_usergroup)) {
				$this->Flash->success(__('::edit_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_usergroup->id]);
			}
			$this->Flash->error(__('::edit_failed'));
		}

		$la_currentPermissions = [];
		foreach ($lo_usergroup->usergroups_permissions ?? [] AS $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = (object)[
				'access' => $lo_usergroupPermission->access,
				'settings' => json_decode($lo_usergroupPermission->settings ?? "", TRUE),
			];
		}
		$lo_usergroup->usergroups_permissions = $la_currentPermissions;

		$this->set([
			'usergroup' => $lo_usergroup,
			'users' => $this->Usergroups->Users->find()->all(),
			'authorizationPolicies' => $la_authorizationPolicies,
		]);
	}
	

	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response|NULL|void Redirects to overview.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function delete () {
		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_usergroup = $this->Usergroups->get($li_id);
		if ($this->Usergroups->delete($lo_usergroup)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	private function _getAuthorizationPolicies (): array {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$la_policies = $lo_authorizationService->getPolicies();

		ksort($la_policies);

		return $la_policies;
	}


	private function _reformatPermissionsData (array $aa_authorizationPolicies, array $aa_data = []): array {
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

