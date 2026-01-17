<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Awyiss;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\View\Helper;
use InvalidArgumentException;
use RuntimeException;


/**
 * Helper class that provides methods related to the Authorization-logic in the views
 */
class AuthorizationHelper extends Helper {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'additionalData' => [],
		'identity' => null,
		'policiesRealm' => null,
		'scope' => null,
	];


	/**
	 * @return array
	 */
	public function getAdditionalData(): array {
		return $this->getConfig('additionalData');
	}


	/**
	 * @param array $data
	 * @return $this
	 */
	public function setAdditionalData(array $data): static {
		$this->setConfig('additionalData', $data, false);


		return $this;
	}


	/**
	 * @return $this
	 */
	public function resetAdditionalData(): static {
		$this->setConfig('additionalData', [], false);


		return $this;
	}


	/**
	 * Returns the identity set in the config
	 *
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface
	 */
	public function getIdentity(): IdentityPermissionsInterface {
		$identity = $this->getConfig('identity');

		if (!$identity) {
			$identity = $this->_getIdentity();
			$this->setConfig('identity', $identity);
		}


		return $identity;
	}


	/**
	 * Save the given identity to the config
	 *
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $identity
	 * @return $this
	 */
	public function setIdentity(IdentityPermissionsInterface $identity): static {
		$this->setConfig('identity', $identity);


		return $this;
	}


	/**
	 * Resets the identity so that `getIdentity()` will use the default one provided by `_getIdentity()`
	 *
	 * @return $this
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
		$scope = $this->getConfig('scope');

		if (!$scope) {
			$scope = Inflector::underscore($this->getView()->getName());
			$this->setConfig('scope', $scope);
		}


		return $scope;
	}


	/**
	 * Returns the currently set scope
	 *
	 * @param string $scope
	 * @return $this
	 */
	public function setScope(string $scope): static {
		$scope = Inflector::underscore($scope);
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);

		$this->setConfig('scope', $scope);


		return $this;
	}


	/**
	 * Resets the scope so that `getScope()` will use the name of the view's controller name.
	 *
	 * @return $this
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
		$identity = $this->getIdentity();

		$additionalData = $additionalData ?: $this->getConfig('additionalData');

		return $identity->scopeIsAccessible($scope, $additionalData, ...$identifier);
	}


	/**
	 * @param array{scope: string, identifier: string|array<string>, additionalData?: array} ...$actions
	 * @return bool
	 * @throws \Exception
	 */
	public function anyIsAccessible(array ...$actions): bool {
		foreach ($actions as $action) {
			if (
				!is_array($action) ||
				!isset($action['scope']) ||
				!isset($action['identifier'])
			) {
				throw new InvalidArgumentException('Invalid action provided. Must be an array with keys `scope` and `identifier`.');
			}

			if (!is_array($action['identifier'])) {
				$action['identifier'] = [$action['identifier']];
			}

			if (!is_array($action['additionalData'] ?? null)) {
				$action['additionalData'] = isset($action['additionalData']) ? [$action['additionalData']] : [];
			}

			if ($this->scopeIsAccessible($action['scope'], $action['additionalData'], ...$action['identifier'])) {
				return true;
			}
		}

		return false;
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
	 */
	public function permissionOptions(PermissionOptionInterface $permission, ?Entity $entity = null, ?string $fileName = null, ?string $subDir = null): string {
		$elementPath = 'authorization' . DS . 'permission_option';
		if (!empty($subDir)) {
			$elementPath = trim($subDir, DS) . DS . $elementPath;
		}

		if (empty($fileName)) {
			$fileName = $permission->getType();
			$fileName .= '_' . ($permission->getConfig('preferredInput')?->value ?? 'radio');
		}

		//This should never happen, but you never know.
		if (!$permission->getConfig('identifier')) {
			throw new RuntimeException(sprintf('Permission `%s` requires an identifier to be representable.', $permission::class));
		}

		$viewData = [
			'permission' => $permission,
			'entity' => $entity,
			'scope' => Inflector::underscore($permission->getPermissionOptionCollection()->getScope()),
			'identifier' => Inflector::underscore($permission->getConfig('identifier')),
			'AuthorizationHelper' => $this,
		];


		return $this->getView()->element($elementPath . DS . $fileName, $viewData);
	}


	/**
	 * Retrieve the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $identity */
		$identity = $this->getView()->getRequest()->getAttribute(Awyiss::getRealm() . 'Identity');

		if (!$identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($identity), IdentityPermissionsInterface::class));
		}


		return $identity;
	}
}
