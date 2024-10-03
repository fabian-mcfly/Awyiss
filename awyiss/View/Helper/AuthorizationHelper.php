<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
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
		'identity' => null,
		'policiesRealm' => null,
		'scope' => null,
	];


	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function getAdditionalData(): array {
		return $this->getConfig('additionalData');
	}


	/**
	 * @param array $data
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setAdditionalData(array $data): static {
		$this->setConfig('additionalData', $data, false);


		return $this;
	}


	/**
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function resetAdditionalData(): static {
		$this->setConfig('additionalData', [], false);


		return $this;
	}


	/**
	 * Returns the identity set in the config
	 *
	 * @return IdentityPermissionsInterface
	 */
	public function getIdentity(): IdentityPermissionsInterface {
		$lo_identity = $this->getConfig('identity');

		if (!$lo_identity) {
			$lo_identity = $this->_getIdentity();
			$this->setConfig('identity', $lo_identity);
		}


		return $lo_identity;
	}


	/**
	 * Save the given identity to the config
	 *
	 * @param IdentityPermissionsInterface $identity
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setIdentity(IdentityPermissionsInterface $identity): static {
		$this->setConfig('identity', $identity);


		return $this;
	}


	/**
	 * Resets the identity so that `getIdentity()` will use the default one provided by `_getIdentity()`
	 *
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function resetIdentity(): static {
		$this->setConfig('identity');


		return $this;
	}


	/**
	 * Sets the scope to check the authorization for
	 *
	 * @return string
	 */
	public function getScope(): string {
		$ls_scope = $this->getConfig('scope');

		if (!$ls_scope) {
			$ls_scope = Inflector::underscore($this->getView()->getName());
			$this->setConfig('scope', $ls_scope);
		}


		return $ls_scope;
	}


	/**
	 * Returns the currently set scope
	 *
	 * @param string $scope
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setScope(string $scope): static {
		$ls_scope = Inflector::underscore($scope);
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);

		$this->setConfig('scope', $ls_scope);


		return $this;
	}


	/**
	 * Resets the scope so that `getScope()` will use the name of the view's controller name.
	 *
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function resetScope(): static {
		$this->setConfig('scope');


		return $this;
	}


	/**
	 * For a list of given identifiers, return true or false whether they're accessible inside the current scope
	 * for the current identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $identifier is used.
	 *
	 * @param array|string ...$identifier
	 * @return bool
	 * @throws \Exception
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 * @noinspection PhpUnused
	 */
	public function isAccessible(string|array ...$identifier): bool {
		return $this->scopeIsAccessible($this->getScope(), $this->getConfig('additionalData'), ...$identifier);
	}


	/**
	 * For a list of given identifiers, return true or false whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $identifier is used.
	 *
	 * @throws \Exception
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 */
	public function scopeIsAccessible(string $scope, array $additionalData = [], string|array ...$identifier): ?bool {
		//Get the currently assigned permissions from the identity object, resp. their permission collection
		$lo_identity = $this->getIdentity();

		return $lo_identity->scopeIsAccessible($scope, $additionalData, ...$identifier);
	}


	/**
	 * Renders an element containing all options for a specific permission.
	 *
	 * If no filename was provided, use the type and the preferred input of the permission.
	 * For example `element\authorization\permission\simple_radio`
	 *
	 * @param PermissionOptionInterface $permission
	 * @param Entity|null $entity
	 * @param string|null $fileName
	 * @param string|null $subDir
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function permissionOptions(PermissionOptionInterface $permission, ?Entity $entity = null, ?string $fileName = null, ?string $subDir = null): string {
		$ls_subDir = 'authorization' . DS . 'permission_option';
		if (!empty($subDir)) {
			$ls_subDir = trim($subDir, DS) . DS . $ls_subDir;
		}

		$ls_fileName = $fileName;
		if (empty($ls_fileName)) {
			$ls_fileName = $permission->getType();
			$ls_fileName .= '_' . $permission->getConfig('preferredInput')->value;
		}

		//This should never happen, but you never know.
		if (!$permission->getConfig('identifier')) {
			throw new RuntimeException(sprintf('Permission `%s` requires an identifier to be representable.', $permission::class));
		}

		$la_viewData = [
			'permission' => $permission,
			'entity' => $entity,
			'scope' => Inflector::underscore($permission->getPermissionOptionCollection()->getScope()),
			'identifier' => Inflector::underscore($permission->getConfig('identifier')),
		];


		return $this->getView()->element($ls_subDir . DS . $ls_fileName, $la_viewData);
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $lo_identity */
		$lo_identity = $this->getView()->getRequest()->getAttribute('identity');
		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}
}
