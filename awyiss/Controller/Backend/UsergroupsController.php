<?php

declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * Usergroups Controller
 *
 * @property \Awyiss\Model\Table\UsergroupsTable $Usergroups
 * @method \Awyiss\Model\Entity\Usergroup[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class UsergroupsController extends Controller {
	use \Awyiss\Authorization\Trait\BasicCrudPermissionsTrait;

	
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
		$lo_usergroup = $this->Usergroups->newEmptyEntity();
		if ($this->request->is('post')) {
			$lo_usergroup = $this->Usergroups->patchEntity($lo_usergroup, $this->request->getData());
			if ($this->Usergroups->save($lo_usergroup)) {
				$this->Flash->success(__('::add_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					return $this->redirect(['action' => 'overview']);
				}

				return $this->redirect(['action' => 'edit', 'id' => $lo_usergroup->id]);
			}
			$this->Flash->error(__('::add_failed'));
		}

		$this->set([
			'usergroup' => $lo_usergroup,
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
		$la_controllerPermissions = $this->_getControllerPermissions();

		$li_id = $this->request->getParam('id');
		$lo_usergroup = $this->Usergroups->get($li_id, [
			'contain' => ['UsergroupsPermissions'],
		]);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_data = $this->request->getData();
			$la_data['usergroups_permissions'] = $this->_reformatPermissionsData($la_controllerPermissions, $la_data);

			$lo_usergroup = $this->Usergroups->patchEntity($lo_usergroup, $la_data, ['associated' => ['UsergroupsPermissions']]);

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
		foreach ($lo_usergroup->usergroups_permissions AS $lo_usergroupPermission) {
			if (!isset($la_currentPermissions[ $lo_usergroupPermission->scope ])) {
				$la_currentPermissions[ $lo_usergroupPermission->scope ] = [];
			}

			$la_currentPermissions[ $lo_usergroupPermission->scope ][ $lo_usergroupPermission->identifier ] = [
				'access' => $lo_usergroupPermission->access,
				'settings' => json_decode($lo_usergroupPermission->settings ?? "", TRUE),
			];
		}

		$this->set([
			'usergroup' => $lo_usergroup,
			'currentPermissions' => $la_currentPermissions,
			'controllerPermissions' => $la_controllerPermissions,
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


	private function _getControllerPermissions (): array {
		$la_paths = [
			CUSTOM_NAMESPACE . '\Controller\Backend\\' => ROOT . DS . CUSTOM_DIR . DS . 'Controller' . DS . 'Backend/*Controller.php',
			'Awyiss\Controller\Backend\\' => ROOT . DS . APP_DIR . DS . 'Controller' . DS . 'Backend/*Controller.php',
		];

		$la_controllers = [];

		foreach ($la_paths AS $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_controllerName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				$ls_title = \Cake\Utility\Inflector::underscore(substr($ls_controllerName, 0, -10));
				$ls_controller = $ls_namespace . $ls_controllerName;

				if (isset($la_controllers[ $ls_title ])) continue;

				/** @var \Awyiss\Controller\BackendController $ls_controller */
				$la_controllers[ $ls_title ] = [
					'title' => $ls_title,
					'namespace' => $ls_namespace,
					'permissions' => $ls_controller::getPermissions(),
				];
			}
		}

		ksort($la_controllers);

		return $la_controllers;
	}


	private function _reformatPermissionsData (array $aa_controllerPermissions, array $aa_data = []): array {
		$la_permissions = [];

		foreach (array_column($aa_controllerPermissions, 'permissions') AS $la_controllerPermissions) {
			/** @var \Awyiss\Authorization\Permission\PermissionInterface $lo_permission */
			foreach ($la_controllerPermissions AS $lo_permission) {
				$la_permissionData = [
					'scope' => $lo_permission->getScope(),
					'identifier' => $lo_permission->getIdentifier(),
					'access' => $lo_permission->getAccessFromData($aa_data),
					'settings' => $lo_permission->getSettingsFromData($aa_data),
				];

				if (!empty($la_permissionData['access'])) {
					$la_permissions[] = $la_permissionData;
				}
			}
		}

		return $la_permissions;
	}
}

