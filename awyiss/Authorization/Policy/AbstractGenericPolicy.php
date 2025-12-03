<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Configuration\ConfigOptionsProvider;
use Exception;


/**
 * Classes that extend this one need to define
 * - `protected static PermissionOptionCollection $permissionOptionCollection;`
 * - `protected static string $scope;`
 */
abstract class AbstractGenericPolicy {
	/**
	 * @var \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 */
	protected PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
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
	 */
	public function getPermissionOption(string $identifier): ?PermissionOptionInterface {
		if (!isset($this->permissionOptionCollection)) {
			try {
				$this->permissionOptionCollection = $this->loadPermissionOptions();
			}
			catch (Exception) {
				return null;
			}
		}

		$identifier = AuthorizationService::sanitizeIdentifier($identifier);

		if ($this->permissionOptionCollection->has($identifier)) {
			return $this->permissionOptionCollection->get($identifier);
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
		$permissions = new PermissionOptionCollection($this->getScope());

		$permissions->add('read', SimplePermissionOption::class);

		$permissions->add('create', SimplePermissionOption::class);

		$permissions->add('update', SimplePermissionOption::class);

		$permissions->add('delete', SimplePermissionOption::class);

		if (ConfigOptionsProvider::getConfigOptionsFile($this->getScope()) || ConfigOptionsProvider::getConfigOptionsFile('GenericDatatables')) {
			$permissions->add('configure', SimplePermissionOption::class);
		}


		return $permissions;
	}
}
