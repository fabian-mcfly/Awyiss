<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Configuration\ConfigOptionsProvider;


/**
 * Instances of this class are used for pages/page roles that have no own policy class.
 *
 * It provides four `SimplePermission` for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
 *
 * It needs to provide non-static methods so itcan be used for multiple pages/page roles at the same time.
 *
 * @see \Awyiss\Authorization\PermissionOption\SimplePermissionOption
 */
class AnonymousPolicy {
	protected PermissionOptionCollection $permissionOptionCollection;
	protected string $scope;


	/**
	 * Sets the scope for this policy only when creating an instance
	 *
	 * @param string $as_scope
	 */
	public function __construct (string $as_scope) {
		$this->scope = $as_scope;
	}


	/**
	 * Returns the scope for the policy
	 *
	 * @return string
	 */
	public function getScope (): string {
		return $this->scope;
	}


	/**
	 * Returns the complete `PermissionOptionCollection`
	 *
	 * @return \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 * @throws \Exception
	 */
	public function getPermissionOptions (): PermissionOptionCollection {
		if (!isset($this->permissionOptionCollection)) {
			$this->permissionOptionCollection = static::loadPermissionOptions();
		}

		return $this->permissionOptionCollection;
	}


	/**
	 * Returns one `PermissionInterface` for the provided `$as_identifier`, otherwise NULL
	 *
	 * @param string $as_identifier
	 *
	 * @return NULL|\Awyiss\Authorization\PermissionOption\PermissionOptionInterface
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function getPermissionOption (string $as_identifier): ?PermissionOptionInterface {
		if (!isset($this->permissionOptionCollection)) {
			$this->permissionOptionCollection = $this->loadPermissionOptions();
		}

		if ($this->permissionOptionCollection->has($as_identifier)) {
			return $this->permissionOptionCollection->get($as_identifier);
		}

		return NULL;
	}


	/**
	 * Creates a `PermissionOptionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * @return \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function loadPermissionOptions (): PermissionOptionCollection {
		$lo_permissions = new PermissionOptionCollection($this->getScope());

		$lo_permissions->load('read', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissions->load('create', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissions->load('update', [
			'className' => SimplePermissionOption::class,
		]);

		$lo_permissions->load('delete', [
			'className' => SimplePermissionOption::class,
		]);

		if (ConfigOptionsProvider::getConfigurationFile($this->getScope())) {
			$lo_permissions->load('configure', [
				'className' => SimplePermissionOption::class,
			]);
		}

		return $lo_permissions;
	}
}