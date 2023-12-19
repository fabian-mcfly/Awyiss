<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Cake\Controller\Component;


class AccessHelper extends \Cake\View\Helper {
	protected $_defaultConfig = [
		'defaultAccessible' => FALSE,
		'identity' => NULL,
		'policiesType' => NULL,
		'scope' => NULL,
	];


	public function getScope (): string {
		$ls_scope = $this->getConfig('scope');

		if (!$ls_scope) {
			$ls_scope = \Cake\Utility\Inflector::underscore($this->getView()->getName());
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


	public function getIdentity (): IdentityPermissionsInterface {
		$lo_identity = $this->getConfig('identity');

		if (!$lo_identity) {
			$lo_identity = $this->_getIdentity();
			$this->setConfig('identity', $lo_identity);
		}

		return $lo_identity;
	}


	public function setIdentity (IdentityPermissionsInterface $ao_identity): self {
		$this->setConfig('identity', $ao_identity);

		return $this;
	}


	public function resetIdentity (): self {
		$this->setConfig('identity');

		return $this;
	}


	public function isAccessible (string|array ...$ax_identifier): bool {
		$ls_scope = $this->getScope();

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getView()->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($ls_scope, $this->getConfig('policiesType'));

		//No policy found means we cannot continue
		if (!$ls_policyClass) {
			return (bool) $this->getConfig('defaultAccessible', FALSE);
		}

		$lo_identity = $this->getIdentity();
		$la_accesses = [];
		foreach ($ax_identifier AS $lx_identifier) {
			$la_accesses[] = $this->_getAccess($ls_policyClass, $lx_identifier, $lo_identity->getAccess()->getScope($ls_scope));
		}

		if (in_array(FALSE, $la_accesses, TRUE) ||
			(!in_array(TRUE, $la_accesses, TRUE) && !$this->getConfig('defaultAccessible', FALSE))) {

			return FALSE;
		}

		return TRUE;
	}


	/**
	 * @var \Awyiss\Authorization\Policy\PolicyInterface|NULL $as_policyClass
	 */
	private function _getAccess (string $as_policyClass, string|array $ax_identifier, ?array $aa_access): mixed {
		if (is_string($ax_identifier)) {
			return $as_policyClass::getPermission($ax_identifier)?->isAccessible($aa_access);
		}

		$la_accesses = [];
		foreach ($ax_identifier AS $ls_identifier) {
			$la_accesses[] = $as_policyClass::getPermission($ls_identifier)?->isAccessible($aa_access);
		}

		if (in_array(TRUE, $la_accesses, TRUE)) {
			return TRUE;
		}

		return NULL;
	}


	private function _getIdentity (): IdentityPermissionsInterface {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getView()->getRequest()->getAttribute('authorization');
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