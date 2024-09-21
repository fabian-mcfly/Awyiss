<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Cake\Event\EventDispatcherTrait;
use RuntimeException;


/**
 * A collection that can hold multiple permissions for different scopes.
 * Allows retreiving permissions for a specific scope or for a specific scope and a specific identifier.
 *
 * This also offers the scopeIsAccessible()-method, used in AuthorizationComponent, AuthorizationBehavior and AuthorizationHelper
 */
class PermissionCollection {
	use EventDispatcherTrait;


	/**
	 * @var AuthorizationService|null
	 */
	protected ?AuthorizationService $authorizationService;
	/**
	 * @var array<string, array<string, Permission[]>>
	 */
	protected array $permissions = [];
	/**
	 * @var string
	 */
	protected string $realm = 'Backend';


	/**
	 * @param AuthorizationService|null $authorizationService
	 * @param array<\Awyiss\Model\Entity\UsergroupPermission|array{scope: string, identifier: string, access: mixed, settings: mixed}> $permissions
	 */
	public function __construct(?AuthorizationService $authorizationService, array $permissions = []) {
		$this->authorizationService = $authorizationService;

		foreach ($permissions as $lx_permission) {
			if ($lx_permission instanceof Permission) {
				$this->add($lx_permission);
			}
			elseif ($lx_permission instanceof PermissionInterface) {
				//$this->add($lx_permission);
				$this->add(Permission::createFromObject($lx_permission));
			}
			elseif (is_array($lx_permission)) {
				//$this->add(...$lx_permission);
				$this->add(Permission::createFromArray($lx_permission));
			}
			else {
				throw new RuntimeException(
					sprintf(
						'Permission must be of type `array|%s` in `%s`. `%s` given',
						PermissionInterface::class,
						static::class,
						gettype($lx_permission)
					)
				);
			}
		}
	}


	/**
	 * Adds a new permission to the collection of permission.
	 *
	 * If `$scope` is a string, `$identifier` needs to be provided
	 *
	 * @param Permission $permission
	 * @return $this
	 */
	public function add(Permission $permission): static {
		$permission->setAuthorizationService($this->authorizationService);

		$ls_scope = AuthorizationService::sanitizeScope($permission->getScope());
		$ls_identifier = AuthorizationService::sanitizeIdentifier($permission->getIdentifier());

		$this->permissions[ $ls_scope ][ $ls_identifier ][] = $permission;

		return $this;
	}


	/**
	 * Returns the whole collection of permission
	 *
	 * @return array<string, array<string, Permission[]>>
	 */
	public function get(): array {
		return $this->permissions;
	}


	/**
	 * Returns true or false, whether a scope (and optional identifier) exists in the collection of permission
	 *
	 * @noinspection PhpUnused
	 * @param string $scope
	 * @param string|null $identifier
	 * @return bool
	 */
	public function hasPermissions(string $scope, ?string $identifier = null): bool {
		return $this->getPermissions($scope, $identifier) !== null;
	}


	/**
	 * Returns the permission for the given scope (and optional identifier)
	 *
	 * @param string $scope
	 * @param string|null $identifier
	 * @return array<array<string, Permission[]>>|array<string, Permission[]>|null
	 */
	public function getPermissions(string $scope, ?string $identifier = null): ?array {
		$ls_scope = AuthorizationService::sanitizeScope($scope);

		if ($identifier) {
			$ls_identifier = AuthorizationService::sanitizeIdentifier($identifier);

			return $this->permissions[ $ls_scope ][ $ls_identifier ] ?? null;
		}

		return $this->permissions[ $ls_scope ] ?? null;
	}


	/**
	 * Checks if the provided identifiers are accessible by the provided identity for the provided scope
	 *
	 * A policy is
	 * - accessible if it returns true
	 * - forbidden if it returns false
	 * - indifferent if it returns null
	 *
	 * `$identifier` captures all remaining arguments provided to `scopeIsAccessible`,
	 * which are then used to checked accesibility.
	 *
	 * - Providing a list of arguments, for example `scopeIsAccessible(..., 'read', 'create', 'update', 'delete')` means
	 * that every one of those identifiers must be accessible for this method to return true.
	 *
	 * - Providing an array of arguments, for example `scopeIsAccessible(..., ['read', 'create', 'update', 'delete'])` means
	 * that at least one of those identifiers must be accessible for this method to return true.
	 *
	 * It's possible to combine the two methods above.
	 *
	 * For example `scopeIsAccessible(..., ['read', 'create'], ['update', 'delete'])` will return true when either `read` or `create`
	 * AND either `update` OR `delete` is accessible.
	 *
	 * @param string $scope
	 * @param array $additionalData
	 * @param array|string ...$identifier
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function scopeIsAccessible(string $scope, array $additionalData = [], string|array ...$identifier): bool {
		/*
		 * Traverse the provided identifiers and remember the accessibility in $lx_policyClass,
		 * using the identity's currently assigned permissions.
		 *
		 */
		$la_accessible = [];
		foreach ($identifier as $lx_identifier) {
			if (!is_array($lx_identifier)) {
				$lx_identifier = [$lx_identifier];
			}

			$la_accessible[] = $this->identifierIsAccessible($scope, $additionalData, ...$lx_identifier);
		}

		//If true is part of the result, and the result is only true, and nothing but true, access is granted.
		if (array_unique($la_accessible) === [true]) {
			return true;
		}


		//I am sorry Dave. I'm afraid I can't do that.
		return false;
	}


	/**
	 * Return true or false depending on whether one of the provided identifiers is accessible
	 *
	 * @param string $scope
	 * @param array $additionalData
	 * @param array|array<string> $identifier
	 * @return bool|null
	 * @throws \ReflectionException
	 */
	protected function identifierIsAccessible(string $scope, array $additionalData = [], string|array ...$identifier): ?bool {
		$la_accessible = [];

		//Traverse the identifiers and check if it's accessible, given the collection of permissions for `$scope`
		foreach ($identifier as $ls_identifier) {
			if (!is_string($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier is invalid. Expected `string`, `%s` given', gettype($ls_identifier)));
			}

			$la_permissions = $this->getPermissions($scope, $ls_identifier);

			if (!$la_permissions) {
				continue;
			}

			$la_accessible[] = $this->permissionsAreAccessible($la_permissions, $additionalData);
		}

		//If true is part of the result access is granted.
		if (in_array(true, $la_accessible, true)) {
			return true;
		}


		//Otherwise the access depends on the default accessible. false makes sense as a fallback.
		return Permission::DEFAULT_PERMISSION;
	}


	/**
	 * @param array<Permission> $permissions
	 * @param array $additionalData
	 * @return bool|null
	 * @throws \ReflectionException
	 */
	protected function permissionsAreAccessible(array $permissions, array $additionalData = []): ?bool {
		$la_accessible = [];

		foreach ($permissions as $lo_permission) {
			if (!($lo_permission instanceof Permission)) {
				throw new RuntimeException(sprintf('The permission is invalid. Expected instance of `%s`, `%s` given', Permission::class, gettype($lo_permission)));
			}

			$la_accessible[] = $lo_permission->isAccessible($additionalData, $this);
		}

		if (in_array(false, $la_accessible, true)) {
			return false;
		}
		elseif (in_array(true, $la_accessible, true)) {
			return true;
		}


		return null;
	}
}
