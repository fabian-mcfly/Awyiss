<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Http\Exception\ForbiddenException;
use RuntimeException;


/**
 * This component provides and handles Authorization-specific logic.
 * It provides methods to set and retreive an identity nad the scope that will be used to check for access.
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
		$lo_new = clone $this;
		$lo_new->setConfig('additionalData', $data);


		return $lo_new;
	}


	/**
	 * Returns the identity set in the config
	 *
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface
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

		$lo_new = clone $this;
		$lo_new->setConfig('identity', $identity);


		return $lo_new;
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
		$ls_scope = $this->getConfig('scope');

		if (!$ls_scope) {
			$ls_scope = $this->defaultScope ?? Inflector::underscore($this->getController()->getName());

			$this->setConfig('scope', $ls_scope);
		}


		return $ls_scope;
	}


	/**
	 * Set the scope to be used for checking permissions.
	 *
	 * @param string $scope
	 * @return $this
	 */
	public function setScope(string $scope): static {
		$ls_scope = Inflector::underscore($scope);
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);

		$this->setConfig('scope', $ls_scope);


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
		$ls_defaultScope = $this->defaultScope;

		//Set defaultScope to null since we can't do this after cloning the component
		$this->defaultScope = null;

		//Clone the current instance and set the scope to the provided value
		$lo_new = clone $this;
		$lo_new->setScope($scope);

		//Reset the defaultScope for the current instance
		$this->defaultScope = $ls_defaultScope;


		return $lo_new;
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
	 * @throws ForbiddenException
	 * @throws \Exception
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 */
	public function ensure(string|array ...$identifier): void {
		$ls_scope = $this->getScope();

		$ls_isAccessible = $this->scopeIsAccessible($ls_scope, [], ...$identifier);
		if (!$ls_isAccessible) {
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
		//Get the currently assigned permissions from the identity object, resp. their permissions collection
		$lo_identity = $this->getIdentity();

		$la_additionalData = $additionalData ?: $this->getConfig('additionalData');


		return $lo_identity->scopeIsAccessible($scope, $la_additionalData, ...$identifier);
	}


	/**
	 * Retreive the IdentityInterface from the request.
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface $lo_identity */
		$lo_identity = $this->getController()->getRequest()->getAttribute('identity');
		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}
}
