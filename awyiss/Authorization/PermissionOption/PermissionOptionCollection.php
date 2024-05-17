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
				$this->load($lx_value);
				continue;
			}
			$this->load($lx_key, $lx_value);
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
	 *
	 * This is a convenient proxy method for `static::load` that returns `$this` instead of the permission instance
	 *
	 * @throws Exception
	 * @see load()
	 */
	public function add(string $identifier, array $config = []): static {
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
		if ($ls_identifier !== $identifier) {
			throw new RuntimeException(sprintf('The provided identifier should be written camelBacked (`%s`). `%s` given.', $ls_identifier, $identifier));
		}

		$la_config = $config;
		//if ( ! isset($la_config['identifier'])) {
		//	$la_config['identifier'] = $identifier;
		//}
		$la_config['identifier'] = $ls_identifier;


		return parent::load($ls_identifier, $la_config);
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
