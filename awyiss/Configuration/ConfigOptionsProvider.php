<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions;
use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Awyiss\Core\App;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Provides access to all ConfigOptions classes in both the Awyiss and the custom namespace.
 */
class ConfigOptionsProvider {
	/**
	 * @var array<string, class-string<\Awyiss\Configuration\ConfigOptionsInterface>|\Awyiss\Model\Enum\PageRole>
	 */
	protected static array $configOptions = [];
	/**
	 * @var array<string, \Awyiss\Model\Entity\Datatable>
	 */
	protected static array $datatables;
	/**
	 * @var array<string, \Awyiss\Configuration\ConfigOptionsInterface|\Awyiss\Model\Enum\PageRole>
	 */
	protected static array $loadedConfigOptions = [];
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;
	/**
	 * @var bool
	 */
	protected static bool $loadedAll = false;


	/**
	 * Trigger an exception during instantiation
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found ConfigOptions classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<\Awyiss\Configuration\ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function getConfigOptionsFiles(bool $returnLoaded = false): array {
		if (!static::$foundAll) {
			static::findConfigOptionsFile('*');
			static::$foundAll = true;
		}

		if ($returnLoaded) {
			if (!static::$loadedAll) {
				foreach (static::$configOptions as $ls_scope => $ls_configOptions) {
					static::$loadedConfigOptions[ $ls_scope ] = static::loadConfigOptions($ls_scope);
				}

				static::$loadedAll = true;
			}


			return static::$loadedConfigOptions;
		}


		return static::$configOptions;
	}


	/**
	 * Returns the found ConfigOptions class for the provided scope or null
	 *
	 * @param string $scope
	 * @param bool $returnLoaded
	 * @return \Awyiss\Configuration\ConfigOptionsInterface|\Awyiss\Model\Enum\PageRoleEnumInterface|string|null
	 * @throws \ReflectionException
	 */
	public static function getConfigOptionsFile(string $scope, bool $returnLoaded = false): ConfigOptionsInterface|PageRoleEnumInterface|string|null {
		$ls_scope = static::sanitizeScope($scope);

		if (!isset(static::$configOptions[ $ls_scope ])) {
			static::findConfigOptionsFile($ls_scope);
		}

		if ($returnLoaded) {
			if (!isset(static::$loadedConfigOptions[ $ls_scope ])) {
				static::$loadedConfigOptions[ $ls_scope ] = static::loadConfigOptions($ls_scope);
			}

			return static::$loadedConfigOptions[ $ls_scope ] ?: null;
		}

		return static::$configOptions[ $ls_scope ] ?? null;
	}


	/**
	 * Returns an instance of a ConfigOptions class with the provided scope or null
	 *
	 * @param class-string<ConfigOptionsInterface>|string $configOptionScope
	 * @return ConfigOptionsInterface|null
	 * @throws \ReflectionException
	 */
	public static function loadConfigOptions(string $configOptionScope): ?ConfigOptionsInterface {
		if (str_contains($configOptionScope, '\\')) {
			if (class_exists($configOptionScope)) {
				$ls_scope = $configOptionScope::getScope();
				$lx_configurationClass = $configOptionScope;

				if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
					return static::$loadedConfigOptions[ $ls_scope ];
				}
			}
			else {
				return null;
			}
		}
		else {
			$ls_scope = static::sanitizeScope($configOptionScope);

			if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
				return static::$loadedConfigOptions[ $ls_scope ];
			}

			/** @var \Awyiss\Model\Enum\PageRoleEnumInterface|class-string<\Awyiss\Configuration\ConfigOptionsInterface>|null $ls_configurationClass */
			$lx_configurationClass = static::getConfigOptionsFile($ls_scope);

			if (!$lx_configurationClass) {
				static::$loadedConfigOptions[ $ls_scope ] = null;


				return null;
			}
		}

		if ($lx_configurationClass instanceof PageRoleEnumInterface) {
			static::$loadedConfigOptions[ $ls_scope ] = new GenericPagesConfigOptions($ls_scope);
		}
		elseif (is_string($lx_configurationClass)) {
			static::$loadedConfigOptions[ $ls_scope ] = new $lx_configurationClass();
		}
		else {
			static::$loadedConfigOptions[ $ls_scope ] = $lx_configurationClass;
		}


		return static::$loadedConfigOptions[ $ls_scope ];
	}


	/**
	 * Loads a configuration class for the given scope and validates the provided value for the given identifier
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $scope
	 * @param string $realm
	 * @param string $identifier
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return string|bool
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function validateConfigValue(string $scope, string $realm, string $identifier, mixed $value, ?string $languageShortcode = null): bool|string {
		$lo_configuration = static::loadConfigOptions($scope);

		if (!$lo_configuration) {
			return false;
		}


		return $lo_configuration->validateConfigValue($realm, $identifier, $value, $languageShortcode);
	}


	/**
	 * Loads a configuration class for the given scope and  cast the provided value to it's correct type for the given identifier
	 *
	 * @param string $scope
	 * @param string $realm
	 * @param string $identifierPath
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public static function typecastConfigValue(
		string $scope,
		string $realm,
		string $identifierPath,
		mixed $value,
		?string $languageShortcode = null,
	): mixed {
		$lo_configuration = static::loadConfigOptions($scope);

		if (!$lo_configuration) {
			return $value;
		}


		return $lo_configuration->typecastConfigValue(
			$realm,
			$identifierPath,
			$value,
			$languageShortcode,
		);
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a CamelCased string
	 *
	 * @param string $scope
	 * @return string
	 */
	public static function sanitizeScope(string $scope): string {
		$ls_scope = Text::slug($scope, '_');
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);


		return Inflector::camelize($ls_scope);
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $identifier
	 * @return string
	 */
	public static function sanitizeIdentifier(string $identifier): string {
		return Inflector::variable(Text::slug($identifier, '_'));
	}


	/**
	 * Finds all ConfigOptions classes in both, the Awyiss and the custom namespace, for a given identifier.
	 * `$scope` can be "*" to return all files.
	 *
	 * If a ConfigOptions class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 *
	 * For page roles, no ConfigOptionsInterface is returned but the \Awyiss\Model\Enum\PageRole case
	 *
	 * @param string $scope
	 * @return void
	 * @throws \ReflectionException
	 */
	protected static function findConfigOptionsFile(string $scope): void {
		$ls_scope = null;
		$ls_className = $scope;
		if ($ls_className !== '*') {
			$ls_scope = static::sanitizeScope($scope);
			$ls_className = Inflector::camelize($ls_scope);
		}

		$la_paths = [];

		if (defined('CUSTOM_NAMESPACE')) {
			$la_paths[ '\\' . CUSTOM_NAMESPACE . '\Configuration\ConfigOptions\\' ] = implode(
				DS,
				[ROOT, CUSTOM_DIR, 'Configuration', 'ConfigOptions', $ls_className . 'ConfigOptions.php',]
			);
		}

		$la_paths['\Awyiss\Configuration\ConfigOptions\\'] = implode(DS, [ROOT, APP_DIR, 'Configuration', 'ConfigOptions', $ls_className . 'ConfigOptions.php']);

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_configurationName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if ($ls_className === '*' && in_array($ls_configurationName, ['GenericDatatablesConfigOptions', 'GenericPagesConfigOptions'])) {
					continue;
				}

				$ls_configurationClass = $ls_namespace . $ls_configurationName;

				$lo_reflection = new ReflectionClass($ls_configurationClass);

				if (!$lo_reflection->implementsInterface(ConfigOptionsInterface::class)) {
					throw new RuntimeException(
						sprintf('The provided Configuration class `%s` does not implement `%s`.', $ls_configurationClass, ConfigOptionsInterface::class)
					);
				}

				/**
				 * @var ConfigOptionsInterface $ls_configurationClass
				 */
				$ls_configScope = static::sanitizeScope($ls_configurationClass::getScope());

				if (isset(static::$configOptions[ $ls_configScope ])) {
					continue;
				}

				static::$configOptions[ $ls_configScope ] = $ls_configurationClass;
			}
		}


		/** @var class-string<\Awyiss\Model\Enum\PageRole> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_configScope = static::sanitizeScope($le_pageRole->name);

			if (
				isset(static::$configOptions[ $ls_configScope ]) ||
				($ls_className !== '*' && $ls_configScope !== $ls_scope)
			) {
				continue;
			}

			static::$configOptions[ $ls_configScope ] = $le_pageRole;
		}


		if (!isset(static::$datatables)) {
			/*
			 * Get all datatables from the database because we want them to have a generic policy too
			 * Use a raw query to avoid the need for a model which would in return again try to load the config options
			 * due to the UserConfiguration.
			 */
			$lo_connection = ConnectionManager::get('default');
			$la_results = $lo_connection->selectQuery('*', 'datatables')->where(['deleted' => 0])->execute()->fetchAll('assoc');

			static::$datatables = collection($la_results)->indexBy(function (array $record) {
				return static::sanitizeScope($record['identifier']);
			})->map(function (array $record) {
				return new GenericDatatablesConfigOptions($record['identifier']);
			})->toArray();
		}

		if ($ls_scope) {
			if (isset(static::$datatables[ $ls_scope ])) {
				static::$configOptions[ $ls_scope ] = static::$datatables[ $ls_scope ];
			}
		}
		else {
			static::$configOptions += static::$datatables;
		}
	}
}
