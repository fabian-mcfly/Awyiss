<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Authorization\Permission\SimplePermission;
use Awyiss\Configuration\ConfigOptionsProvider;


/**
 * Instances of this class are used for pages/page roles that have no own policy class.
 *
 * It provides four `SimplePermission` for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
 *
 * It needs to provide non-static methods so itcan be used for multiple pages/page roles at the same time.
 *
 * @see \Awyiss\Authorization\Permission\SimplePermission
 */
class AnonymousPolicy {
	protected PermissionCollection $permissionCollection;
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
	 * Returns the complete `PermissionCollection`
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 * @throws \Exception
	 */
	public function getPermissions (): PermissionCollection {
		if (!isset($this->permissionCollection)) {
			$this->permissionCollection = static::loadPermissions();
		}

		return $this->permissionCollection;
	}


	/**
	 * Returns one `PermissionInterface` for the provided `$as_identifier`, otherwise NULL
	 *
	 * @param string $as_identifier
	 *
	 * @return NULL|\Awyiss\Authorization\Permission\PermissionInterface
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function getPermission (string $as_identifier): ?PermissionInterface {
		if (!isset($this->permissionCollection)) {
			$this->permissionCollection = $this->loadPermissions();
		}

		if ($this->permissionCollection->has($as_identifier)) {
			return $this->permissionCollection->get($as_identifier);
		}

		return NULL;
	}


	/**
	 * Creates a `PermissionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function loadPermissions (): PermissionCollection {
		$lo_permissions = new PermissionCollection($this->getScope());

		$lo_permissions->load('read', [
			'className' => SimplePermission::class,
		]);

		$lo_permissions->load('create', [
			'className' => SimplePermission::class,
		]);

		$lo_permissions->load('update', [
			'className' => SimplePermission::class,
		]);

		$lo_permissions->load('delete', [
			'className' => SimplePermission::class,
		]);

		if (ConfigOptionsProvider::getConfigurationFile($this->getScope())) {
			$lo_permissions->load('configure', [
				'className' => SimplePermission::class,
			]);
		}

		return $lo_permissions;
	}
}