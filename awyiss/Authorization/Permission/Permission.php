<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Policy\GenericPagePolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Cake\Event\EventDispatcherTrait;
use ReflectionClass;
use RuntimeException;


/**
 * Reflects a single permission with a specific identifier for a for specific scope
 */
class Permission {
	use EventDispatcherTrait;


	/**
	 * Default permission
	 */
	public const DEFAULT_PERMISSION = FALSE;


	/**
	 * @var mixed
	 */
	protected mixed $access;
	/**
	 * @var null|AuthorizationService
	 */
	protected ?AuthorizationService $authorizationService;
	/**
	 * @var string
	 */
	protected string $identifier;
	/**
	 * @var null|string|PolicyInterface|GenericPagePolicy
	 */
	protected string|PolicyInterface|GenericPagePolicy|null $policyClass = NULL;
	/**
	 * @var string
	 */
	protected string $scope;
	/**
	 * @var mixed|array
	 */
	protected mixed $settings;


	/**
	 * @param string $as_scope
	 * @param string $as_identifier
	 * @param mixed|NULL $ax_access
	 * @param mixed $ax_settings
	 */
	public function __construct(string $as_scope, string $as_identifier, mixed $ax_access = NULL, mixed $ax_settings = []) {
		if (empty($as_scope)) {
			throw new RuntimeException(sprintf('Scope must not be empty in `%s`.', static::class));
		}

		if (empty($as_identifier)) {
			throw new RuntimeException(sprintf('Identifier must not be empty in `%s`.', static::class));
		}

		$this->access = $ax_access;
		$this->identifier = AuthorizationService::sanitizeIdentifier($as_identifier);
		$this->scope = AuthorizationService::sanitizeScope($as_scope);
		$this->settings = $ax_settings;
	}


	/**
	 * Return the access
	 *
	 * @return mixed
	 */
	public function getAccess(): mixed {
		return $this->access;
	}


	/**
	 * @param null|AuthorizationService $ao_authorizationService
	 *
	 * @return Permission
	 */
	public function setAuthorizationService(?AuthorizationService $ao_authorizationService): static {
		$this->authorizationService = $ao_authorizationService;


		return $this;
	}


	/**
	 * Return the identifier
	 *
	 * @return string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * Returns the currently set policy class for this permission,
	 * or tries loading one from the authorization service.
	 * If still none's available, an event is fired, hoping it'll return one.
	 *
	 * @return NULL|GenericPagePolicy|PolicyInterface|string
	 * @throws \ReflectionException
	 */
	public function getPolicyClass(): mixed {
		if (!$this->policyClass) {
			$this->policyClass = $this->authorizationService->getPolicy($this->getScope());
		}

		if (!$this->policyClass) {
			$lo_event = $this->dispatchEvent('Authorization.requestPolicyClass', [
				'scope' => $this->getScope(),
			], $this);

			//Maybe the event handler has found a policy.
			//This is my Last Resort!
			$this->policyClass = $lo_event->getResult();
		}


		return $this->policyClass;
	}


	/**
	 * Sets the policy to be used by the permission
	 *
	 * @param null|string|PolicyInterface|GenericPagePolicy $ax_policyClass
	 *
	 * @return $this
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public function setPolicyClass(null|string|PolicyInterface|GenericPagePolicy $ax_policyClass = NULL): static {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new ReflectionClass($ax_policyClass);

			if (!$lo_reflection->implementsInterface(PolicyInterface::class)) {
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
	public function getScope(): string {
		return $this->scope;
	}


	/**
	 * Return the settings
	 *
	 * @return mixed
	 */
	public function getSettings(): mixed {
		return $this->settings;
	}


	/**
	 * Retreives the PermissionOption from the currently set policy class
	 * and checks the access
	 *
	 * @param array $aa_additionalData
	 * @param PermissionCollection $ao_permissionCollection
	 *
	 * @return null|bool
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function isAccessible(array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		//string|PolicyInterface|GenericPagePolicy|NULL $lx_policyClass = NULL,
		$lx_policyClass = $this->getPolicyClass();

		if (!$lx_policyClass) {
			return static::DEFAULT_PERMISSION;
		}

		//Get the Permission from the policy class provided.
		if ($lx_policyClass instanceof GenericPagePolicy) {
			//If the $lx_policyClass is an instance of GenericPagePolicy, getPermission is a public, non-static method
			$lo_permissionOption = $lx_policyClass->getPermissionOption($this->getIdentifier());
		}
		else {
			//If the $lx_policyClass is a string or implements the PolicyInterface, getPermission is a static method
			$lo_permissionOption = $lx_policyClass::getPermissionOption($this->getIdentifier());
		}


		if (!$lo_permissionOption) {
			return static::DEFAULT_PERMISSION;
		}


		return $lo_permissionOption->isAccessible($this->getAccess(), $this->getSettings(), $aa_additionalData, $ao_permissionCollection);
	}


	/**
	 * @param array $aa_permission
	 *
	 * @return static
	 */
	public static function createFromArray(array $aa_permission): static {
		return new static($aa_permission['scope'] ?? '', $aa_permission['identifier'] ?? '', $aa_permission['access'] ?? NULL, $aa_permission['settings'] ?? NULL);
	}


	/**
	 * @param PermissionInterface $ao_permission
	 *
	 * @return static
	 */
	public static function createFromObject(PermissionInterface $ao_permission): static {
		return new static($ao_permission->getScope(), $ao_permission->getIdentifier(), $ao_permission->getAccess(), $ao_permission->getSettings());
	}
}
