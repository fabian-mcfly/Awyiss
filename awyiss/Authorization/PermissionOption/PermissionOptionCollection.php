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
	 * @param array $aa_config Configuration
	 *
	 * @throws Exception
	 */
	public function __construct(string $as_scope, array $aa_config = []) {
		$this->scope = AuthorizationService::sanitizeScope($as_scope);

		foreach ($aa_config as $lx_key => $lx_value) {
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
	 *
	 * @see load()
	 */
	public function add(string $as_identifier, array $aa_config = []): static {
		$this->load($as_identifier, $aa_config);


		return $this;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extended to make the config item 'className' mandatory.
	 *
	 * @param string $as_identifier The name/class of the object to load.
	 * @param array<string, mixed> $aa_config Additional settings to use when loading the object.
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 * @throws RuntimeException
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function load(string $as_identifier, array $aa_config = []): PermissionOptionInterface {
		if (!isset($aa_config['className'])) {
			throw new RuntimeException('Missing config key `className`');
		}

		$ls_identifier = AuthorizationService::sanitizeIdentifier($as_identifier);
		if ($ls_identifier !== $as_identifier) {
			throw new RuntimeException(sprintf('The provided identifier should be written camelBacked (`%s`). `%s` given.', $ls_identifier, $as_identifier));
		}

		$la_config = $aa_config;
		//if ( ! isset($la_config['identifier'])) {
		//	$la_config['identifier'] = $as_identifier;
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
	 * @param class-string<PermissionOptionInterface> $as_class Permission class.
	 * @param string $as_alias Permission alias.
	 * @param array $aa_config Config array.
	 *
	 * @return PermissionOptionInterface
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _create($as_class, string $as_alias, array $aa_config): PermissionOptionInterface {
		$lo_permission = new $as_class($aa_config, $this);

		if (!($lo_permission instanceof PermissionOptionInterface)) {
			throw new RuntimeException(sprintf('Permission class `%s` must implement `%s`.', $as_class, PermissionOptionInterface::class));
		}


		return $lo_permission;
	}


	/**
	 * Resolves permission class name.
	 *
	 * @param string $as_class Class name to be resolved.
	 *
	 * @return string|NULL
	 * @psalm-return class-string|NULL
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _resolveClassName(string $as_class): ?string {
		$ls_className = App::className($as_class);


		return is_string($ls_className) ? $ls_className : NULL;
	}


	/**
	 * @param string $as_class Missing class.
	 * @param NULL|string $as_plugin Class plugin.
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _throwMissingClassError(string $as_class, ?string $as_plugin): void {
		throw new Exception(sprintf('Permission class `%s` was not found.', $as_class));
	}
}
