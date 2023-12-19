<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use RuntimeException;


/**
 * Helper class that provides methods related to the Authorization-logic in the views
 */
class AuthorizationHelper extends Helper {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'additionalData' => [],
		'identity' => NULL,
		'policiesRealm' => NULL,
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
	 * @return IdentityPermissionsInterface
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
	 * @param IdentityPermissionsInterface $ao_identity
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
	 * Sets the scope to check the authorization for
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
		$this->setConfig('scope', Inflector::pluralize(Inflector::underscore($as_scope)));

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
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 *
	 * @noinspection PhpUnused
	 */
	public function isAccessible (string|array ...$ax_identifier): bool {
		return $this->scopeIsAccessible($this->getScope(), NULL, ...$ax_identifier);
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @throws \Exception
	 *
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 */
	public function scopeIsAccessible (string $as_scope, ?array $aa_additionalData = NULL, string|array ...$ax_identifier): ?bool {
		//Get the currently assigned permissions from the identity object, resp. their permission collection
		$lo_identity = $this->getIdentity();
		$lo_permissionCollection = $lo_identity->getPermissionCollection();

		$la_additionalData = $aa_additionalData ?? $this->getConfig('additionalData');

		return $lo_permissionCollection->scopeIsAccessible($as_scope, $la_additionalData, ...$ax_identifier);
	}


	/**
	 * Renders an element containing all options for a specific permission.
	 *
	 * If no filename was provided, use the type and the preferred input of the permission.
	 * For example `element\authorization\permission\simple_radio`
	 *
	 * @param PermissionOptionInterface $ao_permission
	 * @param null|Entity               $ao_entity
	 * @param null|string               $as_fileName
	 * @param null|string               $as_subDir
	 *
	 * @return string
	 *
	 * @noinspection PhpUnused
	 */
	public function permissionOptions (PermissionOptionInterface $ao_permission, ?Entity $ao_entity = NULL, ?string $as_fileName = NULL, ?string $as_subDir = NULL): string {
		$ls_subDir = 'authorization' . DS . 'permission_option';
		if ( ! empty($as_subDir)) {
			$ls_subDir = trim($as_subDir, DS) . DS . $ls_subDir;
		}

		$ls_fileName = $as_fileName;
		if (empty($ls_fileName)) {
			$ls_fileName = $ao_permission->getType();
			$ls_fileName .= '_' . $ao_permission->getConfig('preferredInput')->value;
		}

		//This should never happen, but you never know.
		if ( ! $ao_permission->getConfig('identifier')) {
			throw new RuntimeException(sprintf('Permission `%s` requires an identifier to be representable.', $ao_permission::class));
		}

		$la_viewData = [
			'ao_permission' => $ao_permission,
			'ao_entity' => $ao_entity,
			'as_scope' => Inflector::underscore($ao_permission->getPermissionOptionCollection()->getScope()),
			'as_identifier' => Inflector::underscore($ao_permission->getConfig('identifier')),
		];

		return $this->getView()->element($ls_subDir . DS . $ls_fileName, $la_viewData);
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity (): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|User|UsersExternal $lo_identity */
		$lo_identity = $this->getView()->getRequest()->getAttribute('identity');
		if ( ! ($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}

		return $lo_identity;
	}
}
