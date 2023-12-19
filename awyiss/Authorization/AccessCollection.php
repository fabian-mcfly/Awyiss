<?php declare(strict_types=1);


namespace Awyiss\Authorization;

use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Model\Entity\UsergroupPermission;
use Cake\Event\EventDispatcherTrait;
use Cake\Utility\Inflector;
use Exception;
use ReflectionClass;
use RuntimeException;


/**
 * @todo Think about making $access not an array but a \Cake\Collection\Collection::class instance or extending \ArrayObject
 */
class AccessCollection {
	use EventDispatcherTrait;


	/**
	 * @var array<string, array<string, array{access: mixed, settings: mixed}>>
	 */
	protected array $access = [];
	protected bool $defaultAccessible = FALSE;
	protected string $type = 'backend';


	/**
	 * @param array<\Awyiss\Model\Entity\UsergroupPermission|array{scope: string, identifier: string, access: mixed, settings: mixed}> $aa_permissions
	 *
	 * @throws RuntimeException
	 */
	public function __construct (array $aa_permissions = []) {
		foreach ($aa_permissions as $lx_permission) {
			if ($lx_permission instanceof UsergroupPermission) {
				$this->add($lx_permission);
			}
			elseif (is_array($lx_permission)) {
				$this->add(...$lx_permission);
			}
			else {
				throw new RuntimeException(sprintf('Permission must be of type `array|%s` in `%s`. `%s` given', UsergroupPermission::class, static::class, gettype($lx_permission)));
			}
		}
	}


	/**
	 * Adds a new permission to the collection of accesses.
	 *
	 * If `$ax_scope` is a string, `$as_identifier` needs to be provided
	 *
	 * @param string|\Awyiss\Model\Entity\UsergroupPermission $ax_scope
	 * @param string|NULL $as_identifier
	 * @param mixed|NULL $ax_access
	 * @param mixed|NULL $ax_settings
	 *
	 * @return $this
	 *
	 * @throws RuntimeException
	 */
	public function add (string|UsergroupPermission $ax_scope, string $as_identifier = NULL, mixed $ax_access = NULL, mixed $ax_settings = NULL): static {
		if (is_string($ax_scope)) {
			if (empty($as_identifier)) {
				throw new RuntimeException(sprintf('Identifier must not be empty in `%s`. `%s` given', static::class, $as_identifier ?? 'NULL'));
			}

			$ls_scope = $ax_scope;
			$ls_identifier = $as_identifier;
			$lx_access = $ax_access;
			$lx_settings = $ax_settings;
		}
		else {
			$ls_scope = $ax_scope->scope;
			$ls_identifier = $ax_scope->identifier;
			$lx_access = $ax_scope->access;
			$lx_settings = $ax_scope->settings;
		}

		$ls_scope = Inflector::underscore($ls_scope);

		if ( ! isset($this->access[ $ls_scope ])) {
			$this->access[ $ls_scope ] = [];
		}

		if ( ! isset($this->access[ $ls_scope ][ $ls_identifier ])) {
			$this->access[ $ls_scope ][ $ls_identifier ] = [];
		}

		$this->access[ $ls_scope ][ $ls_identifier ][] = [
			'access' => $lx_access,
			'settings' => $lx_settings,
		];

		return $this;
	}


	/**
	 * Returns the whole collection of accesses
	 *
	 * @return array<string, array<string, array{access: mixed, settings: mixed}>>
	 */
	public function get (): array {
		return $this->access;
	}


	/**
	 * Returns TRUE or FALSE, whether a scope exists in the collection of accesses
	 *
	 * @noinspection PhpUnused
	 */
	public function hasAccessesForScope (string $as_scope): bool {
		return $this->getAccessesForScope($as_scope) === NULL;
	}


	/**
	 * Returns the accesses for the given scope
	 *
	 * @param string $as_scope
	 *
	 * @return NULL|array<string, array{access: mixed, settings: mixed}>
	 */
	public function getAccessesForScope (string $as_scope): ?array {
		$ls_scope = Inflector::underscore($as_scope);

		return $this->access[ $ls_scope ] ?? NULL;
	}


	/**
	 * Returns TRUE or FALSE, whether a scope and an $as_identifier exist in the collection of accesses
	 *
	 * @noinspection PhpUnused
	 */
	public function hasIdentifier (string $as_scope, string $as_identifier): bool {
		return $this->getIdentifier($as_scope, $as_identifier) === NULL;
	}


	/**
	 * Returns the accesses for the given scope and identifiers
	 *
	 * @param string $as_scope
	 * @param null|string $as_identifier
	 *
	 * @return NULL|array{access: mixed, settings: mixed}
	 */
	public function getIdentifier (string $as_scope, ?string $as_identifier): ?array {
		$ls_scope = Inflector::underscore($as_scope);

		return $this->access[ $ls_scope ][ $as_identifier ] ?? NULL;
	}


	/**
	 * Checks if the provided identifiers are accessible by the provided identity for the provided scope
	 * See \Awyiss\Authorization\AccessCollection::scopeIsAccessible() how $ax_identifier is used.
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
	 * @param string|\Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AnonymousPolicy|NULL $ax_policyClass
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @throws RuntimeException
	 *
	 * @noinspection PhpDocSignatureInspection
	 */
	public function scopeIsAccessible (string $as_scope, string|PolicyInterface|AnonymousPolicy|NULL $ax_policyClass = NULL, array $aa_additionalData = [], string|array ...$ax_identifier): bool {
		$ls_scope = Inflector::underscore($as_scope);

		$lx_policyClass = $ax_policyClass;
		//No policy class provided?
		if ( ! $lx_policyClass) {
			$lo_event = $this->dispatchEvent('Access.requestAuthorizationService', [], $this);

			/** @var ?\Awyiss\Authorization\AuthorizationService $lo_authorizationService */
			$lo_authorizationService = $lo_event->getResult();
			if ( ! $lo_authorizationService) {
				throw new RuntimeException(sprintf('Could not retreive `AuthorizationService` in `%s`.', static::class));
			}

			//Get a matching policy from the authorizationService that's set as a request attribute
			$lx_policyClass = $lo_authorizationService->getPolicy($ls_scope, $this->type);
		}

		//Still no policyClass found? Dispatch an event.
		if ( ! $lx_policyClass) {
			$lo_event = $this->dispatchEvent('Access.requestPolicyClass', [
				'scope' => $ls_scope,
			], $this);

			//Maybe the event handler has found a policy.
			//This is my Last Resort!
			$lx_policyClass = $lo_event->getResult();
		}

		//No policy found means we cannot continue
		if ( ! $lx_policyClass) {
			return $this->defaultAccessible;
		}

		/*
		 * Traverse the provided identifiers and remember the accessibility in $lx_policyClass,
		 * using the identity's currently assigned accesses.
		 */
		$la_accesses = [];
		foreach ($ax_identifier as $lx_identifier) {
			$la_accesses[] = $this->_isAccessible($lx_policyClass, $lx_identifier, $this->getAccessesForScope($ls_scope) ?? [], $aa_additionalData);
		}

		/*
		 * If TRUE is part of the result, and the result is only TRUE, and nothing but TRUE, access is granted.
		 */
		if (array_unique($la_accesses) === [TRUE]) {
			return TRUE;
		}

		//I am sorry Dave. I'm afraid I can't do that.
		return FALSE;
	}


	/**
	 * Return TRUE or FALSE depending on whether one of the provided identifiers is accessible
	 *
	 * @param string|\Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AnonymousPolicy $ax_policyClass
	 * @param string|array $ax_identifier
	 * @param array $aa_access
	 * @param array $aa_additionalData
	 *
	 * @return NULL|bool
	 *
	 * @throws \ReflectionException
	 */
	protected function _isAccessible (string|PolicyInterface|AnonymousPolicy $ax_policyClass, string|array $ax_identifier, array $aa_access, array $aa_additionalData = []): ?bool {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
				throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, PolicyInterface::class));
			}
		}

		$la_identifier = is_string($ax_identifier) ? [$ax_identifier] : $ax_identifier;

		$la_accesses = [];
		//Traverse the identifiers and check if it's accessible, given the collection of accesses in $aa_access
		foreach ($la_identifier as $ls_identifier) {
			if ( ! is_string($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier is invalid. Expected `string`, `%s` given', gettype($ls_identifier)));
			}

			//Get the Permission from the policy class provided.
			if (/*!is_string($ax_policyClass) && */$ax_policyClass instanceof AnonymousPolicy) {
				//If the $ax_policyClass is an instance of AnonymousPolicy, getPermission is a public, non-static method
				$lo_permission = $ax_policyClass->getPermission($ls_identifier);
			}
			else {
				//If the $ax_policyClass is a string or implements the PolicyInterface, getPermission is a static method
				$lo_permission = $ax_policyClass::getPermission($ls_identifier);
			}

			if (!$lo_permission) {
				$la_accesses[] = FALSE;
				continue;
			}

			$la_accesses[] = $lo_permission->isAccessible($aa_access, $aa_additionalData, $this);
		}

		//If TRUE is part of the result access is granted.
		if (in_array(TRUE, $la_accesses, TRUE)) {
			return TRUE;
		}

		//Otherwise the access depends on the defaultAccessible property. FALSE makes sense as a fallback.
		return $this->defaultAccessible;
	}
}