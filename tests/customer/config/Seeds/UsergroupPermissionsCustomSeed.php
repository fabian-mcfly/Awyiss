<?php declare(strict_types=1);


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Utility\Inflector;
use Migrations\BaseSeed;


/**
 * UsergroupPermissions seed.
 */
class UsergroupPermissionsCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	public function run(): void {
		$data = [];

		// Get all available policies from the AuthorizationService
		$authorizationService = new AuthorizationService(Awyiss::REALM_BACKEND);
		$policies = $authorizationService->getPolicies();
		unset($policies['UserConfiguration']);

		ksort($policies);

		/**
		 * @var class-string<\Awyiss\Authorization\Policy\PolicyInterface> $policyClass
		 */
		foreach ($policies as $policyScope => $policyClass) {
			$permissions = is_object($policyClass) ? $policyClass->getPermissionOptions() : $policyClass::getPermissionOptions();

			/** @var \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $permission */
			foreach ($permissions as $permission) {
				$identifier = $permission->getConfig('identifier');
				$identifier = Inflector::underscore($identifier);

				$data[] = [
					'usergroupId' => 1,
					'scope' => $policyScope,
					'identifier' => $identifier,
					'access' => 1,
				];

				$data[] = [
					'usergroupId' => 3,
					'scope' => $policyScope,
					'identifier' => $identifier,
					'access' => 0,
				];
			}
		}

		$table = $this->table('usergroup_permissions');
		$table->truncate();
		$table->insert($data)->save();
	}
}
