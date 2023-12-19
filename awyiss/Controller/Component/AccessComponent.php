<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Cake\Controller\Component;


/**
 * @method \Awyiss\Controller\AppController getController()
 */
class AccessComponent extends Component {
	protected $_defaultConfig = [
		'defaultAccessible' => FALSE,
		'identity' => NULL,
		'policyClass' => NULL,
		'policiesType' => NULL,
		'scope' => NULL,
	];


	public function getScope (): string {
		$ls_scope = $this->getConfig('scope');

		if ( ! $ls_scope) {
			$ls_scope = \Cake\Utility\Inflector::underscore($this->getController()->getName());
			$this->setConfig('scope', $ls_scope);
		}

		return $ls_scope;
	}


	public function setScope (string $as_scope): self {
		$this->setConfig('scope', $as_scope);

		return $this;
	}


	public function resetScope (): self {
		$this->setConfig('scope');

		return $this;
	}


	public function getPolicyClass (): string|AnonymousPolicy|NULL {
		return $this->getConfig('policyClass');
	}


	/**
	 * @throws \ReflectionException
	 */
	public function setPolicyClass (string|AnonymousPolicy|NULL $ax_policyClass): self {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new \ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(AnonymousPolicy::class)) {
				throw new \RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, AnonymousPolicy::class));
			}
		}


		$this->setConfig('policyClass', $ax_policyClass);

		return $this;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function forScope (string $as_scope): self {
		$this->resetScope();

		$lo_new = clone $this;
		$lo_new->setConfig('scope', $as_scope);

		return $lo_new;
	}


	public function getIdentity (): IdentityPermissionsInterface {
		$lo_identity = $this->getConfig('identity');

		if ( ! $lo_identity) {
			$lo_identity = $this->_getIdentity();
			$this->setConfig('identity', $lo_identity);
		}

		return $lo_identity;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function setIdentity (IdentityPermissionsInterface $ao_identity): self {
		$this->setConfig('identity', $ao_identity);

		return $this;
	}


	public function resetIdentity (): self {
		$this->setConfig('identity');

		return $this;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function withIdentity (IdentityPermissionsInterface $ao_identity): self {
		$this->resetIdentity();

		$lo_new = clone $this;
		$lo_new->setConfig('identity', $ao_identity);

		return $lo_new;
	}


	/**
	 * @throws \Exception
	 */
	public function ensureOne (string ...$ax_identifier): void {
		$this->ensure($ax_identifier);
	}


	/**
	 * @noinspection PhpUnused
	 *
	 * @throws \Exception
	 */
	public function ensureAll (string ...$ax_identifier): void {
		$this->ensure(...$ax_identifier);
	}


	/**
	 * @throws \Exception
	 */
	public function ensure (string|array ...$ax_identifier): void {
		$ls_scope = $this->getScope();
		//$lo_identity = $this->getIdentity();

		$ls_isAccessible = $this->scopeIsAccessible($ls_scope, NULL, ...$ax_identifier);
		if ( ! $ls_isAccessible) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
	}


	/**
	 * @noinspection PhpUnused
	 *
	 * @throws \Exception
	 */
	public function isAccessible (string|array ...$ax_identifier): bool {
		$ls_scope = $this->getScope();
		//$lo_identity = $this->getIdentity();

		return $this->scopeIsAccessible($ls_scope, NULL, ...$ax_identifier);
	}


	/**
	 * @noinspection DuplicatedCode
	 *
	 * @throws \Exception
	 */
	public function scopeIsAccessible (string $as_scope, ?IdentityPermissionsInterface $ao_identity = NULL, string|array ...$ax_identifier): bool {
		$ls_scope = \Cake\Utility\Inflector::underscore($as_scope);

		$lx_policyClass = $this->getPolicyClass();
		if (!$lx_policyClass) {
			/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
			$lo_authorizationService = $this->getController()->getRequest()->getAttribute('authorization');
			$lx_policyClass = $lo_authorizationService->getPolicy($ls_scope, $this->getConfig('policiesType'));

			if (!$lx_policyClass) {
				//Still no policyClass found? Dispatch an event.
				$this->getController()->dispatchEvent('Component.requestPolicyClass', [
					'authorizationService' => $lo_authorizationService,
					'scope' => $ls_scope,
				], $this);

				//Maybe the event handler has set a class.
				//This is my Last Resort!
				$lx_policyClass = $this->getPolicyClass();
			}
		}

		//No policy found means we cannot continue
		if ( ! $lx_policyClass) {
			return (bool) $this->getConfig('defaultAccessible', FALSE);
		}


		$lo_identity = $ao_identity ?? $this->getIdentity();
		$la_accesses = [];
		foreach ($ax_identifier as $lx_identifier) {
			$la_accesses[] = $this->getAccess($lx_policyClass, $lx_identifier, $lo_identity->getAccess()->getScope($ls_scope));
		}

		if (in_array(FALSE, $la_accesses, TRUE) || ( ! in_array(TRUE, $la_accesses, TRUE) && ! $this->getConfig('defaultAccessible', FALSE))) {
			return FALSE;
		}


		return TRUE;
	}


	/**
	 *
	 * @param string|AnonymousPolicy $ax_policyClass
	 * @param string|array $ax_identifier
	 * @param null|array $aa_access
	 *
	 * @return null|bool
	 *
	 * @throws \Exception
	 *
	 * @noinspection DuplicatedCode
	 */
	protected function getAccess (string|AnonymousPolicy $ax_policyClass, string|array $ax_identifier, ?array $aa_access): ?bool {
		/** @var PolicyInterface|AnonymousPolicy $ax_policyClass */
		if (is_string($ax_identifier)) {
			$lo_permission = is_string($ax_policyClass) ? $ax_policyClass::getPermission($ax_identifier) : $ax_policyClass->getPermission($ax_identifier);
			return $lo_permission?->isAccessible($aa_access) ?? $this->getConfig('defaultAccessible', FALSE);
		}

		$la_accesses = [];
		foreach ($ax_identifier as $ls_identifier) {
			$lo_permission = is_string($ax_policyClass) ? $ax_policyClass::getPermission($ls_identifier) : $ax_policyClass->getPermission($ls_identifier);
			$la_accesses[] = $lo_permission?->isAccessible($aa_access);
		}

		if (in_array(TRUE, $la_accesses, TRUE)) {
			return TRUE;
		}

		return $this->getConfig('defaultAccessible', FALSE);
	}


	/**
	 * @noinspection DuplicatedCode
	 */
	protected function _getIdentity (): IdentityPermissionsInterface {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getController()->getRequest()->getAttribute('authorization');
		if ( ! $lo_authorizationService) {
			throw new \RuntimeException(sprintf('Object `%s` does not use the authorization middleware.', static::class));
		}

		$lo_authenticationService = $lo_authorizationService->getAuthenticationService();
		if ( ! $lo_authenticationService) {
			throw new \RuntimeException(sprintf('Object `%s` does not have an authentication service set.', get_class($lo_authorizationService)));
		}
		/** @var IdentityPermissionsInterface|User|UsersExternal $lo_identity */
		$lo_identity = $lo_authenticationService->getIdentity();
		if ( ! ($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new \RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}

		return $lo_identity;
	}
}