<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss\Trait;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Usergroup;
use Awyiss\Utility\Inflector;
use Cake\ORM\TableRegistry;
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
	 */
	protected function createAdminUsergroup(): Usergroup {
		/** @var \Awyiss\Model\Table\UsergroupsTable $usersTable */
		$usergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

		/** @var \Awyiss\Model\Entity\Usergroup $usergroup */
		$usergroup = $usergroupsTable->findOrCreate([
			'title' => 'Admin',
		]);

		$associated = ['UsergroupPermissions'];
		$usergroup->setAccess('usergroupPermissions', true);
		$data = [
			'usergroupPermissions' => $this->getPermissions(),
		];

		$usergroupsTable->patchEntity($usergroup, $data, ['associated' => $associated]);

		if ($usergroupsTable->save($usergroup, ['audit' => ['skip' => true]])) {
			$this->io->success('Admin usergroup created successfully.');
		}
		else {
			$this->io->abort('Failed to create admin usergroup.');
		}


		return $usergroup;
	}


	/**
	 * Create the admin user with the provided username and password
	 * If no admin username is provided, this method will skip admin user creation.
	 *
	 * @return void
	 */
	protected function createAdminUser(): void {
		if (!$this->adminUsername) {
			$this->io->out('No admin username provided. Skipping admin user creation.');

			return;
		}

		if ($this->dryRun) {
			$this->io->success('Admin usergroup created successfully.');
			$this->io->success('Admin user created successfully.', $this->adminPassword ? 1 : 0);
			if (!$this->adminPassword) {
				$this->io->info(' The password for the admin user is: ' . Security::randomString(16));
			}

			return;
		}

		$usergroup = $this->createAdminUsergroup();

		/** @var \Awyiss\Model\Table\UsergroupsTable $usersTable */
		$usersTable = TableRegistry::getTableLocator()->get('Users');

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $usersTable->newDefaultEntity();

		$password = $this->adminPassword ?: Security::randomString(16);
		$data = [
			'username' => $this->adminUsername,
			'password' => $password,
			'active' => true,
			'usergroups' => [
				'_ids' => [
					$usergroup->id,
				],
			],
		];

		$associated = ['Usergroups' => ['onlyIds' => true]];

		$usersTable->patchEntity($user, $data, ['associated' => $associated, 'validate' => false]);

		if ($usersTable->save($user, ['audit' => ['skip' => true]])) {
			$this->io->success('Admin user created successfully.', $this->adminPassword ? 1 : 0);
			if (!$this->adminPassword) {
				$this->io->info(' The password for the admin user is: ' . $password);
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
	 */
	protected function getPermissions(): array {
		$permissions = [];

		// Get all available policies from the AuthorizationService
		$authorizationService = new AuthorizationService(Awyiss::REALM_BACKEND);
		$policies = $authorizationService->getPolicies();
		unset($policies['UserConfiguration']);

		ksort($policies);

		/**
		 * @var class-string<\Awyiss\Authorization\Policy\PolicyInterface> $policyClass
		 */
		foreach ($policies as $policyScope => $policyClass) {
			/** @var \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $permission */
			foreach ($policyClass::getPermissionOptions() as $permission) {
				$identifier = $permission->getConfig('identifier');
				$identifier = Inflector::variable($identifier);

				$permissions[] = [
					'scope' => $policyScope,
					'identifier' => $identifier,
					'access' => 1,
				];
			}
		}


		return $permissions;
	}
}
