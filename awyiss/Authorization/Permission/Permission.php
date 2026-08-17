<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Event\EventDispatcherTrait;
use RuntimeException;


/**
 * Reflects a single permission with a specific identifier for a for specific scope
 */
class Permission {
	use EventDispatcherTrait;


	/**
	 * Default permission
	 */
	public const ?bool DEFAULT_PERMISSION = false;


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
	 * @param string $scope
	 * @param string $identifier
	 * @param mixed|null $access
	 * @param mixed $settings
	 */
	public function __construct(string $scope, string $identifier, mixed $access = null, mixed $settings = []) {
		if (empty($scope)) {
			throw new RuntimeException(sprintf('Scope must not be empty in `%s`.', static::class));
		}

		if (empty($identifier)) {
			throw new RuntimeException(sprintf('Identifier must not be empty in `%s`.', static::class));
		}

		$this->access = $access;
		$this->identifier = AuthorizationService::sanitizeIdentifier($identifier);
		$this->scope = AuthorizationService::sanitizeScope($scope);
		$this->settings = $settings;
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
	 * @param AuthorizationService|null $authorizationService
	 * @return Permission
	 */
	public function setAuthorizationService(?AuthorizationService $authorizationService): static {
		$this->authorizationService = $authorizationService;


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
	 * @param \Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AbstractGenericPolicy|string|null $policyClass
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setPolicyClass(string|PolicyInterface|AbstractGenericPolicy|null $policyClass = null): static {
		if (is_string($policyClass)) {
			if (!in_array(PolicyInterface::class, class_implements($policyClass))) {
				throw new RuntimeException(
					sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $policyClass, PolicyInterface::class)
				);
			}
		}

		$this->policyClass = $policyClass;


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
	 * Retrieves the PermissionOption from the currently set policy class
	 * and checks the access
	 *
	 * @param array $additionalData
	 * @param PermissionCollection $permissionCollection
	 * @return bool|null
	 */
	public function isAccessible(array $additionalData, PermissionCollection $permissionCollection): ?bool {
		$policyClass = $this->getPolicyClass();

		if (!$policyClass) {
			return static::DEFAULT_PERMISSION;
		}

		//Get the Permission from the policy class provided.
		if ($policyClass instanceof AbstractGenericPolicy) {
			//If the $policyClass is an instance of AbstractGenericPolicy, getPermission is a public, non-static method
			$permissionOption = $policyClass->getPermissionOption($this->getIdentifier());
		}
		else {
			//If the $policyClass is a string or implements the PolicyInterface, getPermission is a static method
			/** @var \Awyiss\Authorization\Policy\PolicyInterface $policyClass */
			$permissionOption = $policyClass::getPermissionOption($this->getIdentifier());
		}

		if (!$permissionOption) {
			return static::DEFAULT_PERMISSION;
		}


		return $permissionOption->isAccessible($this->getAccess(), $this->getSettings(), $additionalData, $permissionCollection);
	}


	/**
	 * @param array $permission
	 * @return static
	 */
	public static function createFromArray(array $permission): static {
		return new static(
			$permission['scope'] ?? '',
			$permission['identifier'] ?? '',
			$permission['access'] ?? null,
			$permission['settings'] ?? null
		);
	}


	/**
	 * @param PermissionInterface $permission
	 * @return static
	 */
	public static function createFromObject(PermissionInterface $permission): static {
		return new static($permission->getScope(), $permission->getIdentifier(), $permission->getAccess(), $permission->getSettings());
	}
}
