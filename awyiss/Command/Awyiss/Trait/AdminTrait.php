<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss\Trait;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Usergroup;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Security;


/**
 * Trait AdminTrait
 *
 * This trait provides methods for creating an admin user and usergroup.
 * It includes methods for creating an admin usergroup, creating an admin user, and getting all available permissions.
 */
trait AdminTrait {
	/**
	 * @var string The admin user's username
	 */
	protected string $adminUsername;
	/**
	 * @var string The admin user's password
	 */
	protected string $adminPassword;


	/**
	 * Create the admin usergroup if it does not exist
	 *
	 * @return \Awyiss\Model\Entity\Usergroup The created or found admin usergroup
	 * @throws \ReflectionException
	 */
	protected function createAdminUsergroup(): Usergroup {
		/** @var \Awyiss\Model\Table\UsergroupsTable $lo_usersTable */
		$lo_usergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

		/** @var \Awyiss\Model\Entity\Usergroup $lo_usergroup */
		$lo_usergroup = $lo_usergroupsTable->findOrCreate([
			'title' => 'Admin',
		]);

		$la_associated = ['UsergroupPermissions'];
		$lo_usergroup->setAccess('usergroupPermissions', true);
		$la_data = [
			'usergroup_permissions' => $this->getPermissions(),
		];

		$lo_usergroupsTable->patchEntity($lo_usergroup, $la_data, ['associated' => $la_associated]);

		if ($lo_usergroupsTable->save($lo_usergroup)) {
			$this->io->success('Admin usergroup created successfully.');
		}
		else {
			$this->io->abort('Failed to create admin usergroup.');
		}


		return $lo_usergroup;
	}


	/**
	 * Create the admin user with the provided username and password
	 * If no admin username is provided, this method will skip admin user creation.
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createAdminUser(): void {
		if (!$this->adminUsername) {
			$this->io->out('No admin username provided. Skipping admin user creation.');


			return;
		}

		$lo_usergroup = $this->createAdminUsergroup();

		/** @var \Awyiss\Model\Table\UsergroupsTable $lo_usersTable */
		$lo_usersTable = TableRegistry::getTableLocator()->get('Users');

		/** @var \Awyiss\Model\Entity\User $lo_user */
		$lo_user = $lo_usersTable->newDefaultEntity();

		$ls_password = $this->adminPassword ?: Security::randomString(16);
		$la_data = [
			'username' => $this->adminUsername,
			'password' => $ls_password,
			'active' => true,
			'usergroups' => [
				'_ids' => [
					$lo_usergroup->id,
				],
			],
		];

		$la_associated = ['Usergroups' => ['onlyIds' => true]];

		$lo_usersTable->patchEntity($lo_user, $la_data, ['associated' => $la_associated, 'validate' => false]);

		if ($lo_usersTable->save($lo_user)) {
			$this->io->success('Admin user created successfully.', $this->adminPassword ? 1 : 0);
			if (!$this->adminPassword) {
				$this->io->warning(' The password for the admin user is: ' . $ls_password);
			}
		}
		else {
			$this->io->abort('Failed to create admin user.');
		}
	}


	/**
	 * Get all available permissions and return them in an array
	 * in the form of
	 * [
	 *  'attributes' => ['read' => true, 'create' => true, ...],
	 *  'other' => ['read' => true, 'create' => true, ...],
	 * ]
	 *
	 * @return array The array of all available permissions
	 * @throws \ReflectionException
	 */
	protected function getPermissions(): array {
		//permissions[attributes][read] = true;
		$la_permissions = [];

		// Get all available policies from the AuthorizationService
		$lo_authorizationService = new AuthorizationService(Awyiss::REALM_BACKEND);
		$la_policies = $lo_authorizationService->getPolicies();
		unset($la_policies['user_configuration']);

		ksort($la_policies);

		/**
		 * @var class-string<\Awyiss\Authorization\Policy\PolicyInterface> $ls_policyClass
		 */
		foreach ($la_policies as $ls_policyScope => $ls_policyClass) {
			/** @var \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $lo_permission */
			foreach ($ls_policyClass::getPermissionOptions() as $lo_permission) {
				$ls_identifier = $lo_permission->getConfig('identifier');
				$ls_identifier = Inflector::underscore($ls_identifier);

				$la_permissions[] = [
					'scope' => $ls_policyScope,
					'identifier' => $ls_identifier,
					'access' => 1,
				];
			}
		}


		return $la_permissions;
	}
}
