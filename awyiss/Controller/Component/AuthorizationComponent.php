<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Http\Exception\ForbiddenException;
use RuntimeException;


/**
 * This component provides and handles Authorization-specific logic.
 * It provides methods to set and retrieve an identity nad the scope that will be used to check for access.
 *
 * - `ensureOne(string ...$identifier)`
 *   Called to ensure one of the provided identifiers is accessible
 *
 * - `ensureAll(string ...$identifier)`
 *   Called to ensure all the provided identifiers is accessible
 *
 * - `ensure(string|array ...$identifier)`
 *   Called to ensure one of the provided identifiers is accessible. Allows providing not only strings as identifiers
 *   but arrays as well.
 *
 * - `isAccessible(string|array ...$identifier)`
 *   Returns true or false, depending on the accessibility of the provided identifier(s).
 *
 * - `scopeIsAccessible(string $scope, ?IdentityPermissionsInterface $identity = null, ?array $additionalData = null, string|array ...$identifier)`
 *   Returns true or false, depending on the accessibility of the provided identifier(s) for the provided scope and identity
 *
 * @method \Awyiss\Controller\AppController getController()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class AuthorizationComponent extends Component {
	/**
	 * @inheritDoc
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'additionalData' => [],
		'identity' => null,
		'scope' => null,
	];
	/**
	 * The default scope to be used by `getScope()`.
	 *
	 * @var string|null
	 */
	protected ?string $defaultScope = null;


	/**
	 * Is called when the component is initialized
	 *
	 * @param array<string, mixed> $config The configuration settings provided to this component.
	 */
	public function initialize(array $config): void {
		if (!empty($config['scope'])) {
			$this->defaultScope = $config['scope'];
		}
	}


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
	 * Returns a new instance of the AuthorizationComponent with the data in `$data` set.
	 *
	 * With this clone it's possible to check access with a different settings than the currently set ones,
	 * without the need to reset the settings after the check.
	 *
	 * @param array $data
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function withAdditionalData(array $data): static {
		$new = clone $this;
		$new->setConfig('additionalData', $data);


		return $new;
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
	 * @noinspection PhpUnused
	 */
	public function setIdentity(IdentityPermissionsInterface $identity): static {
		$this->setConfig('identity', $identity);


		return $this;
	}


	/**
	 * The withIdentity method returns a cloned instance of the component.
	 *
	 * With this clone it's possible to check access for a different identity than the default one provided by _getIdentity(),
	 * without the need to reset the identity after checking the access for a different one.
	 *
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $identity
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function withIdentity(IdentityPermissionsInterface $identity): static {
		$this->resetIdentity();

		$new = clone $this;
		$new->setConfig('identity', $identity);


		return $new;
	}


	/**
	 * Reset the currently set identity, so `getIdentity()` will return the default one provided by `_getIdentity()`
	 *
	 * @return $this
	 * @see _getIdentity()
	 */
	public function resetIdentity(): static {
		$this->setConfig('identity');


		return $this;
	}


	/**
	 * Returns the currently set scope for checking permissions.
	 *
	 * If empty, the scope the component was loaded with will be used.
	 *
	 * If still empty, the name of the controller that loaded the component will be used.
	 *
	 * @return string
	 */
	public function getScope(): string {
		$scope = $this->getConfig('scope');

		if (!$scope) {
			$scope = $this->defaultScope ?? Inflector::underscore($this->getController()->getName());

			$this->setConfig('scope', $scope);
		}


		return $scope;
	}


	/**
	 * Set the scope to be used for checking permissions.
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
	 * The forScope method returns a cloned instance of the component.
	 *
	 * With this clone it's possible to check access for a different scope than the controller's,
	 * without the need to reset the scope after checking the access for a different one.
	 *
	 * @param string $scope
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function forScope(string $scope): static {
		$this->resetScope();

		//Remember the defaultScope
		$defaultScope = $this->defaultScope;

		//Set defaultScope to null since we can't do this after cloning the component
		$this->defaultScope = null;

		//Clone the current instance and set the scope to the provided value
		$new = clone $this;
		$new->setScope($scope);

		//Reset the defaultScope for the current instance
		$this->defaultScope = $defaultScope;


		return $new;
	}


	/**
	 * Reset the scope to null so getScope will use default.
	 *
	 * @return $this
	 */
	public function resetScope(): static {
		$this->setConfig('scope');


		return $this;
	}


	/**
	 * For a list of given identifiers, ensure that at least one of them is accessible inside the current scope
	 * for the current identity.
	 *
	 * No access results in an exception.
	 *
	 * @param string ...$identifier
	 * @return void
	 * @throws ForbiddenException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function ensureOne(string ...$identifier): void {
		$this->ensure($identifier);
	}


	/**
	 * For a list of given identifiers, ensure that all of them are accessible inside the current scope
	 * for the current identity.
	 *
	 * No access results in an exception.
	 *
	 * @param string ...$identifier
	 * @return void
	 * @throws ForbiddenException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function ensureAll(string ...$identifier): void {
		$this->ensure(...$identifier);
	}


	/**
	 * For a list of given identifiers, ensure that all of them are accessible inside the current scope
	 * for the current identity.
	 *
	 * No access results in an exception.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $identifier is used.
	 *
	 * @param array|string ...$identifier
	 * @return void
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 * @throws \Cake\Http\Exception\ForbiddenException|\Exception
	 */
	public function ensure(string|array ...$identifier): void {
		$scope = $this->getScope();

		$isAccessible = $this->scopeIsAccessible($scope, [], ...$identifier);
		if (!$isAccessible) {
			throw new ForbiddenException();
		}
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
		return $this->scopeIsAccessible($this->getScope(), [], ...$identifier);
	}


	/**
	 * Checks if the provided identifiers are accessible by the provided identity for the provided scope.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $identifier is used.
	 *
	 * @param string $scope
	 * @param array $additionalData
	 * @param array|string ...$identifier
	 * @return bool
	 * @throws \Exception
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 */
	public function scopeIsAccessible(string $scope, array $additionalData = [], string|array ...$identifier): bool {
		// Get the currently assigned permissions from the identity object, resp. their permissions collection
		$identity = $this->getIdentity();

		$additionalData = $additionalData ?: $this->getConfig('additionalData');


		return $identity->scopeIsAccessible($scope, $additionalData, ...$identifier);
	}


	/**
	 * Retrieve the IdentityInterface from the request.
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface $identity */
		$identity = $this->getController()->getRequest()->getAttribute('identity');
		if (!($identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($identity), IdentityPermissionsInterface::class));
		}


		return $identity;
	}
}
