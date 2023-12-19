<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Core\App;
use Cake\Core\ObjectRegistry;
use RuntimeException;


class PermissionCollection extends ObjectRegistry {
	protected string $scope;


	/**
	 * Constructor
	 *
	 * @param array $aa_config Configuration
	 *
	 * @throws \Exception
	 */
	public function __construct (string $as_scope, array $aa_config = []) {
		$this->scope = $as_scope;

		foreach ($aa_config as $key => $value) {
			if (is_int($key)) {
				$this->load($value);
				continue;
			}
			$this->load($key, $value);
		}
	}


	public function getScope (): string {
		return $this->scope;
	}


	/**
	 * @throws \Exception
	 */
	public function add (string $as_identifier, array $aa_config = []): self {
		$this->load($as_identifier, $aa_config);

		return $this;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function load (string $as_identifier, array $aa_config = []): PermissionInterface {
		if ( ! isset($aa_config['className'])) {
			throw new RuntimeException('Missing config key `className`');
		}
		$la_config = $aa_config;
		if ( ! isset($la_config['identifier'])) {
			$la_config['identifier'] = $as_identifier;
		}

		$lb_loaded = isset($this->_loaded[ $as_identifier ]);
		if ($lb_loaded && ! empty($aa_config)) {
			$this->_checkDuplicate($as_identifier, $aa_config);
		}
		if ($lb_loaded) {
			return $this->_loaded[ $as_identifier ];
		}

		$ls_objectName = $ls_className = $aa_config['className'];
		if (is_string($ls_objectName)) {
			$ls_className = $this->_resolveClassName($ls_objectName);
			if ($ls_className === NULL) {
				[$ls_plugin, $ls_objectName] = pluginSplit($ls_objectName);
				$this->_throwMissingClassError($ls_objectName, $ls_plugin);
			}
		}

		$lo_instance = $this->_create($ls_className, $as_identifier, $la_config);
		$this->_loaded[ $as_identifier ] = $lo_instance;

		return $lo_instance;
	}


	/**
	 * Returns true if a collection is empty.
	 *
	 * @return bool
	 */
	public function isEmpty (): bool {
		return empty($this->_loaded);
	}


	/**
	 * Creates Permission instance.
	 *
	 * @param PermissionInterface $as_class Permission class.
	 * @param string $as_alias Permission alias.
	 * @param array $aa_config Config array.
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionInterface
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _create ($as_class, string $as_alias, array $aa_config): PermissionInterface {
		$lo_permission = new $as_class($aa_config, $this);
		if ( ! ($lo_permission instanceof PermissionInterface)) {
			throw new RuntimeException(sprintf('Permission class `%s` must implement `%s`.', $as_class, PermissionInterface::class));
		}

		return $lo_permission;
	}


	/**
	 * Resolves permission class name.
	 *
	 * @param string $as_class Class name to be resolved.
	 *
	 * @return string|null
	 * @psalm-return class-string|null
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _resolveClassName (string $as_class): ?string {
		$ls_className = App::className($as_class);

		return is_string($ls_className) ? $ls_className : NULL;
	}


	/**
	 * @param string $as_class Missing class.
	 * @param null|string $as_plugin Class plugin.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _throwMissingClassError (string $as_class, ?string $as_plugin): void {
		$message = sprintf('Permission class `%s` was not found.', $as_class);
		throw new RuntimeException($message);
	}
}
