<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Configuration\ConfigOptionsProvider;


/**
 * Classes that extend this one need to define
 * - `protected static PermissionOptionCollection $permissionOptionCollection;`
 * - `protected static string $scope;`
 */
abstract class AbstractGenericPolicy {
	protected PermissionOptionCollection $permissionOptionCollection;
	protected string $scope;


	/**
	 * Sets the scope for this policy only when creating an instance
	 *
	 * @param string $scope
	 */
	public function __construct(string $scope) {
		$this->scope = AuthorizationService::sanitizeScope($scope);
	}


	/**
	 * Returns the scope for the policy
	 *
	 * @return string
	 */
	public function getScope(): string {
		return $this->scope;
	}


	/**
	 * Returns the complete `PermissionOptionCollection`
	 *
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 */
	public function getPermissionOptions(): PermissionOptionCollection {
		if (!isset($this->permissionOptionCollection)) {
			$this->permissionOptionCollection = static::loadPermissionOptions();
		}


		return $this->permissionOptionCollection;
	}


	/**
	 * Returns one `PermissionInterface` for the provided `$identifier`, otherwise null
	 *
	 * @param string $identifier
	 * @return PermissionOptionInterface|null
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function getPermissionOption(string $identifier): ?PermissionOptionInterface {
		if (!isset($this->permissionOptionCollection)) {
			$this->permissionOptionCollection = $this->loadPermissionOptions();
		}

		$ls_identifier = AuthorizationService::sanitizeIdentifier($identifier);

		if ($this->permissionOptionCollection->has($ls_identifier)) {
			return $this->permissionOptionCollection->get($ls_identifier);
		}


		return null;
	}


	/**
	 * Creates a `PermissionOptionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function loadPermissionOptions(): PermissionOptionCollection {
		$lo_permissions = new PermissionOptionCollection($this->getScope());

		$lo_permissions->add('read', SimplePermissionOption::class);

		$lo_permissions->add('create', SimplePermissionOption::class);

		$lo_permissions->add('update', SimplePermissionOption::class);

		$lo_permissions->add('delete', SimplePermissionOption::class);

		if (ConfigOptionsProvider::getConfigOptionsFile($this->getScope()) || ConfigOptionsProvider::getConfigOptionsFile('GenericDatatables')) {
			$lo_permissions->add('configure', SimplePermissionOption::class);
		}


		return $lo_permissions;
	}
}
