<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Core\App;
use Cake\Core\ObjectRegistry;
use Exception;
use RuntimeException;


/**
 * Holds a collection of permissions for a single scope
 */
class PermissionOptionCollection extends ObjectRegistry {
	/**
	 * @var string
	 */
	protected string $scope;


	/**
	 * Constructor
	 *
	 * @param array $config Configuration
	 * @throws Exception
	 */
	public function __construct(string $scope, array $config = []) {
		$this->scope = AuthorizationService::sanitizeScope($scope);

		foreach ($config as $lx_key => $lx_value) {
			if (is_int($lx_key)) {
				$this->add($lx_value);
				continue;
			}

			$this->add($lx_key, $lx_value);
		}
	}


	/**
	 * Returns the scope the instance was created with
	 *
	 * @return string
	 */
	public function getScope(): string {
		return $this->scope;
	}


	/**
	 * Adds a permission to the collection.
	 * This is a convenient proxy method for `static::load` that returns `$this` instead of the permission instance
	 *
	 * If `$config` is a string, it will be used as the `className` key in the config array.
	 *
	 * @param string $identifier
	 * @param array|string $config
	 * @return \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 * @throws \Exception
	 * @see load()
	 */
	public function add(string $identifier, array|string $config = []): static {
		if (is_string($config)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$config = ['className' => $config];
		}

		$this->load($identifier, $config);


		return $this;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extended to make the config item 'className' mandatory.
	 *
	 * @param string $identifier The name/class of the object to load.
	 * @param array<string, mixed> $config Additional settings to use when loading the object.
	 * @return mixed
	 * @throws Exception
	 * @throws RuntimeException
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function load(string $identifier, array $config = []): PermissionOptionInterface {
		if (!isset($config['className'])) {
			throw new RuntimeException('Missing config key `className`');
		}

		$ls_identifier = AuthorizationService::sanitizeIdentifier($identifier);

		return parent::load($ls_identifier, ['identifier' => $ls_identifier] + $config);
	}


	/**
	 * Returns true if a collection is empty.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool {
		return empty($this->_loaded);
	}


	/**
	 * Creates a Permission instance.
	 *
	 * @param class-string<PermissionOptionInterface> $class Permission class.
	 * @param string $alias Permission alias.
	 * @param array $config Config array.
	 * @return PermissionOptionInterface
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _create($class, string $alias, array $config): PermissionOptionInterface {
		$lo_permission = new $class($config, $this);

		if (!($lo_permission instanceof PermissionOptionInterface)) {
			throw new RuntimeException(sprintf('Permission class `%s` must implement `%s`.', $class, PermissionOptionInterface::class));
		}


		return $lo_permission;
	}


	/**
	 * Resolves permission class name.
	 *
	 * @param string $class Class name to be resolved.
	 * @return string|null
	 * @psalm-return class-string|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _resolveClassName(string $class): ?string {
		$ls_className = App::className($class);


		return is_string($ls_className) ? $ls_className : null;
	}


	/**
	 * @param string $class Missing class.
	 * @param string|null $plugin Class plugin.
	 * @return void
	 * @throws Exception
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _throwMissingClassError(string $class, ?string $plugin): void {
		throw new Exception(sprintf('Permission class `%s` was not found.', $class));
	}
}
