<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Utility\Inflector;
use ReflectionClass;
use RuntimeException;


/**
 * Reflects a single permission with a specific identifier for a for specific scope
 */
class Permission {
	use EventDispatcherTrait;


	protected mixed $access;
	protected ?AuthorizationService $authorizationService;
	protected string $identifier;
	protected string|PolicyInterface|AnonymousPolicy|NULL $policyClass = NULL;
	protected string $scope;
	protected mixed $settings;


	public const DEFAULT_PERMISSION = FALSE;


	/**
	 * @param string $as_scope
	 * @param string $as_identifier
	 * @param mixed|NULL $ax_access
	 * @param mixed $ax_settings
	 */
	public function __construct (string $as_scope, string $as_identifier, mixed $ax_access = NULL, mixed $ax_settings = []) {
		if (empty($as_scope)) {
			throw new RuntimeException(sprintf('Scope must not be empty in `%s`.', static::class));
		}

		if (empty($as_identifier)) {
			throw new RuntimeException(sprintf('Identifier must not be empty in `%s`.', static::class));
		}

		$this->access = $ax_access;
		$this->identifier = $as_identifier;
		$this->scope = Inflector::underscore($as_scope);
		$this->settings = $ax_settings;
	}


	/**
	 * @param array $aa_permission
	 *
	 * @return static
	 */
	public static function createFromArray (array $aa_permission): static {
		return new static($aa_permission['scope'] ?? '', $aa_permission['identifier'] ?? '', $aa_permission['access'] ?? NULL, $aa_permission['settings'] ?? NULL);
	}


	/**
	 * @param \Awyiss\Authorization\Permission\PermissionInterface $ao_permission
	 *
	 * @return static
	 */
	public static function createFromInterface (PermissionInterface $ao_permission): static {
		return new static($ao_permission->getScope(), $ao_permission->getIdentifier(), $ao_permission->getAccess(), $ao_permission->getSettings());
	}

	/**
	 * Return the access
	 *
	 * @return mixed
	 */
	public function getAccess (): mixed {
		return $this->access;
	}


	/**
	 * @param \Awyiss\Authorization\AuthorizationService $ao_authorizationService
	 *
	 * @return void
	 */
	public function setAuthorizationService (?AuthorizationService $ao_authorizationService): static {
		$this->authorizationService = $ao_authorizationService;

		return $this;
	}


	/**
	 * Return the identifier
	 *
	 * @return string
	 */
	public function getIdentifier (): string {
		return $this->identifier;
	}


	public function getPolicyClass () {
		if (!$this->policyClass) {
			$this->policyClass = $this->authorizationService->getPolicy($this->getScope());
		}

		if ( ! $this->policyClass) {
			$lo_event = $this->dispatchEvent('Authorization.requestPolicyClass', [
				'scope' => $this->getScope(),
			], $this);

			//Maybe the event handler has found a policy.
			//This is my Last Resort!
			$this->policyClass = $lo_event->getResult();
		}

		return $this->policyClass;
	}


	public function setPolicyClass (string|PolicyInterface|AnonymousPolicy|NULL $ax_policyClass = NULL): static {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
				throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, PolicyInterface::class));
			}
		}

		$this->policyClass = $ax_policyClass;

		return $this;
	}


	/**
	 * Return the scope
	 *
	 * @return string
	 */
	public function getScope (): string {
		return $this->scope;
	}


	/**
	 * Return the settings
	 *
	 * @return mixed
	 */
	public function getSettings (): mixed {
		return $this->settings;
	}


	public function isAccessible (array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		//string|PolicyInterface|AnonymousPolicy|NULL $lx_policyClass = NULL,
		$lx_policyClass = $this->getPolicyClass();

		if ( ! $lx_policyClass) {
			return static::DEFAULT_PERMISSION;
		}

		//Get the Permission from the policy class provided.
		if ($lx_policyClass instanceof AnonymousPolicy) {
			//If the $lx_policyClass is an instance of AnonymousPolicy, getPermission is a public, non-static method
			$lo_permissionOption = $lx_policyClass->getPermissionOption($this->getIdentifier());
		}
		else {
			//If the $lx_policyClass is a string or implements the PolicyInterface, getPermission is a static method
			$lo_permissionOption = $lx_policyClass::getPermissionOption($this->getIdentifier());
		}

		if ( ! $lo_permissionOption) {
			return static::DEFAULT_PERMISSION;
		}

		return $lo_permissionOption->isAccessible($this->getAccess(), $this->getSettings(), $aa_additionalData, $ao_permissionCollection);
	}


	public function isAccessible2 () {
		$ls_scope = Inflector::underscore($as_scope);

		$lx_policyClass = $ax_policyClass;
		//No policy class provided?
		if ( ! $lx_policyClass) {
			$lo_event = $this->dispatchEvent('Authorization.requestAuthorizationService', [], $this);

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
			$lo_event = $this->dispatchEvent('Authorization.requestPolicyClass', [
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

		if (is_string($ax_policyClass)) {
			$lo_reflection = new ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
				throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, PolicyInterface::class));
			}
		}
	}
}