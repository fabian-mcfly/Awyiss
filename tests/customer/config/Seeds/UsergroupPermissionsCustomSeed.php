<?php declare(strict_types=1);


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Utility\Inflector;
use Migrations\AbstractSeed;


/**
 * UsergroupPermissions seed.
 */
class UsergroupPermissionsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	public function run(): void {
		$la_data = [];

		// Get all available policies from the AuthorizationService
		$lo_authorizationService = new AuthorizationService(Awyiss::REALM_BACKEND);
		$la_policies = $lo_authorizationService->getPolicies();
		unset($la_policies['user_configuration']);

		ksort($la_policies);

		/**
		 * @var class-string<\Awyiss\Authorization\Policy\PolicyInterface> $ls_policyClass
		 */
		foreach ($la_policies as $ls_policyScope => $ls_policyClass) {
			$lx_permissions = is_object($ls_policyClass) ? $ls_policyClass->getPermissionOptions() : $ls_policyClass::getPermissionOptions();

			/** @var \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $lo_permission */
			foreach ($lx_permissions as $lo_permission) {
				$ls_identifier = $lo_permission->getConfig('identifier');
				$ls_identifier = Inflector::underscore($ls_identifier);

				$la_data[] = [
					'usergroup_id' => 1,
					'scope' => $ls_policyScope,
					'identifier' => $ls_identifier,
					'access' => 1,
				];

				$la_data[] = [
					'usergroup_id' => 3,
					'scope' => $ls_policyScope,
					'identifier' => $ls_identifier,
					'access' => 0,
				];
			}
		}

		$lo_table = $this->table('usergroup_permissions');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
