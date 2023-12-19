<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;


class AnonymousPolicy {
	protected PermissionCollection $permissionCollection;
	protected string $scope;


	public function __construct (string $as_scope) {
		$this->scope = $as_scope;
	}


	public function getScope (): string {
		return $this->scope;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 */
	public function getPermissions (): PermissionCollection {
		if (!isset($this->permissionCollection)) {
			$this->permissionCollection = static::loadPermissions();
		}

		return $this->permissionCollection;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
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
	 * @throws \Exception
	 */
	protected function loadPermissions (): PermissionCollection {
		$lo_permissions = new PermissionCollection($this->getScope());

		$lo_permissions->load('create', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('update', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		$lo_permissions->load('delete', [
			'className' => \Awyiss\Authorization\Permission\SimplePermission::class,
		]);

		return $lo_permissions;
	}
}