<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
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
	public const DEFAULT_PERMISSION = false;


	/**
	 * @var mixed
	 */
	protected mixed $access;
	/**
	 * @var \Awyiss\Authorization\AuthorizationService|null
	 */
	protected ?AuthorizationService $authorizationService;
	/**
	 * @var string
	 */
	protected string $identifier;
	/**
	 * @var \Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AbstractGenericPolicy|string|null
	 */
	protected string|PolicyInterface|AbstractGenericPolicy|null $policyClass = null;
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
	 * @param mixed|null $ax_access
	 * @param mixed $ax_settings
	 */
	public function __construct(string $as_scope, string $as_identifier, mixed $ax_access = null, mixed $ax_settings = []) {
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
	 * @param AuthorizationService|null $ao_authorizationService
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
	 *
	 * @return \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface>|null
	 * @throws \ReflectionException
	 */
	public function getPolicyClass(): AbstractGenericPolicy|string|null {
		if (!$this->policyClass) {
			$this->policyClass = $this->authorizationService->getPolicy($this->getScope());
		}

		return $this->policyClass;
	}


	/**
	 * Sets the policy to be used by the permission
	 *
	 * @param \Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AbstractGenericPolicy|string|null $ax_policyClass
	 * @return $this
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public function setPolicyClass(string|PolicyInterface|AbstractGenericPolicy|null $ax_policyClass = null): static {
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
	 * @return bool|null
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function isAccessible(array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		$lx_policyClass = $this->getPolicyClass();

		if (!$lx_policyClass) {
			return static::DEFAULT_PERMISSION;
		}

		//Get the Permission from the policy class provided.
		if ($lx_policyClass instanceof AbstractGenericPolicy) {
			//If the $lx_policyClass is an instance of AbstractGenericPolicy, getPermission is a public, non-static method
			$lo_permissionOption = $lx_policyClass->getPermissionOption($this->getIdentifier());
		}
		else {
			//If the $lx_policyClass is a string or implements the PolicyInterface, getPermission is a static method
			/** @var \Awyiss\Authorization\Policy\PolicyInterface $lx_policyClass */
			$lo_permissionOption = $lx_policyClass::getPermissionOption($this->getIdentifier());
		}

		if (!$lo_permissionOption) {
			return static::DEFAULT_PERMISSION;
		}


		return $lo_permissionOption->isAccessible($this->getAccess(), $this->getSettings(), $aa_additionalData, $ao_permissionCollection);
	}


	/**
	 * @param array $aa_permission
	 * @return static
	 */
	public static function createFromArray(array $aa_permission): static {
		return new static($aa_permission['scope'] ?? '', $aa_permission['identifier'] ?? '', $aa_permission['access'] ?? null, $aa_permission['settings'] ?? null);
	}


	/**
	 * @param PermissionInterface $ao_permission
	 * @return static
	 */
	public static function createFromObject(PermissionInterface $ao_permission): static {
		return new static($ao_permission->getScope(), $ao_permission->getIdentifier(), $ao_permission->getAccess(), $ao_permission->getSettings());
	}
}
