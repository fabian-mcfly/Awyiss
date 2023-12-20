<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Model\Entity\UsergroupPermission;
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
	 * @var null|AuthorizationService
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
	 * @param null|AuthorizationService $ao_authorizationService
	 * @param array<UsergroupPermission|array{scope: string, identifier: string, access: mixed, settings: mixed}> $aa_permissions
	 */
	public function __construct(?AuthorizationService $ao_authorizationService, array $aa_permissions = []) {
		$this->authorizationService = $ao_authorizationService;

		foreach ($aa_permissions as $lx_permission) {
			if ($lx_permission instanceof Permission) {
				$this->add($lx_permission);
			}
			elseif ($lx_permission instanceof PermissionInterface) {
				//$this->add($lx_permission);
				$this->add(Permission::createFromObject($lx_permission));
			}
			elseif (is_array($lx_permission)) {
				//$this->add(...$lx_permission);
				$this->add(Permission::createFromArray(...$lx_permission));
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
	 * If `$ax_scope` is a string, `$as_identifier` needs to be provided
	 *
	 * @param Permission $ao_permission
	 *
	 * @return $this
	 *
	 */
	public function add(Permission $ao_permission): static {
		$ao_permission->setAuthorizationService($this->authorizationService);

		$this->permissions[ $ao_permission->getScope() ][ $ao_permission->getIdentifier() ][] = $ao_permission;


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
	 * Returns TRUE or FALSE, whether a scope (and optional identifier) exists in the collection of permission
	 *
	 * @noinspection PhpUnused
	 *
	 * @param string $as_scope
	 * @param null|string $as_identifier
	 *
	 * @return bool
	 */
	public function hasPermissions(string $as_scope, string $as_identifier = NULL): bool {
		return $this->getPermissions($as_scope, $as_identifier) !== NULL;
	}


	/**
	 * Returns the permission for the given scope (and optional identifier)
	 *
	 * @param string $as_scope
	 * @param null|string $as_identifier
	 *
	 * @return NULL|array<array<string, Permission[]>>|array<string, Permission[]>
	 */
	public function getPermissions(string $as_scope, string $as_identifier = NULL): ?array {
		$ls_scope = AuthorizationService::sanitizeScope($as_scope);

		if ($as_identifier) {
			$ls_identifier = AuthorizationService::sanitizeIdentifier($as_identifier);


			return $this->permissions[ $ls_scope ][ $ls_identifier ] ?? NULL;
		}


		return $this->permissions[ $ls_scope ] ?? NULL;
	}


	/**
	 * Checks if the provided identifiers are accessible by the provided identity for the provided scope
	 *
	 * A policy is
	 * - accessible if it returns TRUE
	 * - forbidden if it returns FALSE
	 * - indifferent if it returns NULL
	 *
	 * `$ax_identifier` captures all remaining arguments provided to `scopeIsAccessible`,
	 * which are then used to checked accesibility.
	 *
	 * - Providing a list of arguments, for example `scopeIsAccessible(..., 'read', 'create', 'update', 'delete')` means
	 * that every one of those identifiers must be accessible for this method to return TRUE.
	 *
	 * - Providing an array of arguments, for example `scopeIsAccessible(..., ['read', 'create', 'update', 'delete'])` means
	 * that at least one of those identifiers must be accessible for this method to return TRUE.
	 *
	 * It's possible to combine the two methods above.
	 *
	 * For example `scopeIsAccessible(..., ['read', 'create'], ['update', 'delete'])` will return TRUE when either `read` or `create`
	 * AND either `update` OR `delete` is accessible.
	 *
	 * @param string $as_scope
	 * @param array $aa_additionalData
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 *
	 * @throws \ReflectionException
	 */
	public function scopeIsAccessible(string $as_scope, array $aa_additionalData = [], string|array ...$ax_identifier): bool {
		/*
		 * Traverse the provided identifiers and remember the accessibility in $lx_policyClass,
		 * using the identity's currently assigned permissions.
		 *
		 */
		$la_accessible = [];
		foreach ($ax_identifier as $lx_identifier) {
			if (!is_array($lx_identifier)) {
				$lx_identifier = [$lx_identifier];
			}

			$la_accessible[] = $this->identifierIsAccessible($as_scope, $aa_additionalData, ...$lx_identifier);
		}

		//If TRUE is part of the result, and the result is only TRUE, and nothing but TRUE, access is granted.
		if (array_unique($la_accessible) === [TRUE]) {
			return TRUE;
		}


		//I am sorry Dave. I'm afraid I can't do that.
		return FALSE;
	}


	/**
	 * Return TRUE or FALSE depending on whether one of the provided identifiers is accessible
	 *
	 * @param string $as_scope
	 * @param array $aa_additionalData
	 * @param array|string[] $aa_identifier
	 *
	 * @return NULL|bool
	 *
	 * @throws \ReflectionException
	 */
	protected function identifierIsAccessible(string $as_scope, array $aa_additionalData = [], ...$aa_identifier): ?bool {
		$la_accessible = [];

		//Traverse the identifiers and check if it's accessible, given the collection of permissions for `$as_scope`
		foreach ($aa_identifier as $ls_identifier) {
			if (!is_string($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier is invalid. Expected `string`, `%s` given', gettype($ls_identifier)));
			}

			$la_permissions = $this->getPermissions($as_scope, $ls_identifier);
			if (!$la_permissions) {
				continue;
			}

			$la_accessible[] = $this->permissionsAreAccessible($la_permissions, $aa_additionalData);
		}

		//If TRUE is part of the result access is granted.
		if (in_array(TRUE, $la_accessible, TRUE)) {
			return TRUE;
		}


		//Otherwise the access depends on the default accessible. FALSE makes sense as a fallback.
		return Permission::DEFAULT_PERMISSION;
	}


	/**
	 * @param Permission[] $aa_permissions
	 * @param array $aa_additionalData
	 *
	 * @return null|bool
	 *
	 * @throws \ReflectionException
	 */
	protected function permissionsAreAccessible(array $aa_permissions, array $aa_additionalData = []): ?bool {
		$la_accessible = [];

		foreach ($aa_permissions as $lo_permission) {
			if (!($lo_permission instanceof Permission)) {
				throw new RuntimeException(sprintf('The permission is invalid. Expected instance of `%s`, `%s` given', Permission::class, gettype($lo_permission)));
			}

			$la_accessible[] = $lo_permission->isAccessible($aa_additionalData, $this);
		}

		if (in_array(FALSE, $la_accessible, TRUE)) {
			return FALSE;
		}
		elseif (in_array(TRUE, $la_accessible, TRUE)) {
			return TRUE;
		}


		return NULL;
	}
}
