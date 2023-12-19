<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use ReflectionClass;
use RuntimeException;


/**
 * Helper class that provides methods related to the Access-logic in the views
 */
class AccessHelper extends Helper {
	protected $_defaultConfig = [
		'additionalData' => [],
		'defaultAccessible' => FALSE,
		'identity' => NULL,
		'policyClass' => NULL,
		'policiesType' => NULL,
		'scope' => NULL,
	];


	/**
	 * @return array
	 *
	 * @noinspection PhpUnused
	 */
	public function getAdditionalData (): array {
		return $this->getConfig('additionalData');
	}


	/**
	 * @param array $aa_data
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function setAdditionalData (array $aa_data): static {
		$this->setConfig('additionalData', $aa_data, FALSE);

		return $this;
	}


	/**
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function resetAdditionalData (): static {
		$this->setConfig('additionalData', [], FALSE);

		return $this;
	}


	/**
	 * Returns the identity set in the config
	 *
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface
	 */
	public function getIdentity (): IdentityPermissionsInterface {
		$lo_identity = $this->getConfig('identity');

		if ( ! $lo_identity) {
			$lo_identity = $this->_getIdentity();
			$this->setConfig('identity', $lo_identity);
		}

		return $lo_identity;
	}


	/**
	 * Save the given identity to the config
	 *
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $ao_identity
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function setIdentity (IdentityPermissionsInterface $ao_identity): static {
		$this->setConfig('identity', $ao_identity);

		return $this;
	}


	/**
	 * Resets the identity so that `getIdentity()` will use the default one provided by `_getIdentity()`
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function resetIdentity (): static {
		$this->setConfig('identity');

		return $this;
	}


	/**
	 * Returns the name of the policy class set in the config
	 *
	 * @return NULL|string|\Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AnonymousPolicy
	 */
	public function getPolicyClass (): string|PolicyInterface|AnonymousPolicy|null {
		return $this->getConfig('policyClass');
	}


	/**
	 * Saves the given value as policyClass config item
	 * If $ax_policyClass is a string, it needs to be the name of a class that implements PolicyInterface
	 *
	 * @param string|\Awyiss\Authorization\Policy\AnonymousPolicy|\Awyiss\Authorization\Policy\PolicyInterface|NULL $ax_policyClass
	 *
	 * @return $this
	 *
	 * @throws \ReflectionException
	 *
	 * @see PolicyInterface::class
	 * @see AnonymousPolicy::class
	 *
	 * @noinspection PhpUnused
	 */
	public function setPolicyClass (string|AnonymousPolicy|PolicyInterface $ax_policyClass = NULL): static {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
				throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, PolicyInterface::class));
			}
		}

		$this->setConfig('policyClass', $ax_policyClass);

		return $this;
	}


	/**
	 * Sets the scope to check the access for
	 *
	 * @return string
	 */
	public function getScope (): string {
		$ls_scope = $this->getConfig('scope');

		if ( ! $ls_scope) {
			$ls_scope = Inflector::underscore($this->getView()->getName());
			$this->setConfig('scope', $ls_scope);
		}

		return $ls_scope;
	}


	/**
	 * Returns the currently set scope
	 *
	 * @param string $as_scope
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function setScope (string $as_scope): static {
		$this->setConfig('scope', $as_scope);

		return $this;
	}


	/**
	 * Resets the scope so that `getScope()` will use the name of the view's controller name.
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function resetScope (): static {
		$this->setConfig('scope');

		return $this;
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the current scope
	 * for the current identity.
	 *
	 * See \Awyiss\Authorization\AccessCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 * @see \Awyiss\Authorization\AccessCollection::scopeIsAccessible()
	 *
	 * @noinspection PhpUnused
	 */
	public function isAccessible (string|array ...$ax_identifier): bool {
		$ls_scope = $this->getScope();

		return $this->scopeIsAccessible($ls_scope, NULL, NULL, ...$ax_identifier);
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\AccessCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @throws \Exception
	 *
	 * @see \Awyiss\Authorization\AccessCollection::scopeIsAccessible()
	 */
	public function scopeIsAccessible (string $as_scope, ?IdentityPermissionsInterface $ao_identity = NULL, array $aa_additionalData = NULL, string|array ...$ax_identifier): ?bool {
		//Get the currently assigned accesses from the identity object, resp. their access collection
		$lo_identity = $ao_identity ?? $this->getIdentity();
		$lo_accessCollection = $lo_identity->getAccess();

		$la_additionalData = $aa_additionalData ?? $this->getConfig('additionalData');

		return $lo_accessCollection->scopeIsAccessible($as_scope, $this->getPolicyClass(), $la_additionalData, ...$ax_identifier);
	}


	/**
	 * Retreive the AuthorizationServiceInterface using getAuthorizationService.
	 * Then retreive the AuthenticationServiceInterface from the AuthorizationServiceInterface
	 * Then retreive the IdentityInterface from AuthenticationServiceInterface.
	 */
	protected function _getIdentity (): IdentityPermissionsInterface {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getView()->getRequest()->getAttribute('authorization');
		if ( ! $lo_authorizationService) {
			throw new RuntimeException(sprintf('Object `%s` does not use the authorization middleware.', static::class));
		}

		$lo_authenticationService = $lo_authorizationService->getAuthenticationService();
		if ( ! $lo_authenticationService) {
			throw new RuntimeException(sprintf('Object `%s` does not have an authentication service set.', get_class($lo_authorizationService)));
		}
		/** @var IdentityPermissionsInterface|User|UsersExternal $lo_identity */
		$lo_identity = $lo_authenticationService->getIdentity();
		if ( ! ($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}

		return $lo_identity;
	}
}