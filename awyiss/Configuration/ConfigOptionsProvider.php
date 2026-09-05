<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\config\GenericPagesConfigOptions;
use Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions;
use Awyiss\Core\App;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Text;
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
	 * @var bool
	 */
	protected static bool $foundAll = false;
	/**
	 * @var bool
	 */
	protected static bool $loadedAll = false;
	/**
	 * @var array<string, \Awyiss\Configuration\ConfigOptionsInterface|\Awyiss\Model\Enum\PageRole>
	 */
	protected static array $loadedConfigOptions = [];


	/**
	 * Trigger an exception during instantiation
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found ConfigOptions classes in both the Awyiss and the custom namespace
	 *
	 * @param bool $returnLoaded If true, returns instances of the ConfigOptions classes instead of class names
	 * @return array<string, class-string<\Awyiss\Configuration\ConfigOptionsInterface>>
	 * @noinspection PhpUnused
	 */
	public static function getConfigOptionsFiles(bool $returnLoaded = false): array {
		if (!static::$foundAll) {
			static::findConfigOptionsFile('*');
			static::$foundAll = true;
		}

		if ($returnLoaded) {
			if (!static::$loadedAll) {
				foreach (static::$configOptions as $scope => $configOptions) {
					static::$loadedConfigOptions[ $scope ] = static::loadConfigOptions($scope);
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
	 */
	public static function getConfigOptionsFile(
		string $scope,
		bool $returnLoaded = false
	): ConfigOptionsInterface|PageRoleEnumInterface|string|null {
		$scope = static::sanitizeScope($scope);

		if (!isset(static::$configOptions[ $scope ])) {
			static::findConfigOptionsFile($scope);
		}

		if ($returnLoaded) {
			if (!isset(static::$loadedConfigOptions[ $scope ])) {
				static::$loadedConfigOptions[ $scope ] = static::loadConfigOptions($scope);
			}

			return static::$loadedConfigOptions[ $scope ] ?: null;
		}

		return static::$configOptions[ $scope ] ?? null;
	}


	/**
	 * Returns an instance of a ConfigOptions class with the provided scope or null
	 *
	 * @param class-string<ConfigOptionsInterface>|string $configOptionScope
	 * @return ConfigOptionsInterface|null
	 */
	public static function loadConfigOptions(string $configOptionScope): ?ConfigOptionsInterface {
		if (str_contains($configOptionScope, '\\')) {
			if (class_exists($configOptionScope)) {
				$scope = static::extractScopeFromClassName($configOptionScope);
				$configurationClass = $configOptionScope;

				if (array_key_exists($scope, static::$loadedConfigOptions)) {
					return static::$loadedConfigOptions[ $scope ];
				}
			}
			else {
				return null;
			}
		}
		else {
			$scope = static::sanitizeScope($configOptionScope);

			if (array_key_exists($scope, static::$loadedConfigOptions)) {
				return static::$loadedConfigOptions[ $scope ];
			}

			/** @var \Awyiss\Model\Enum\PageRoleEnumInterface|class-string<\Awyiss\Configuration\ConfigOptionsInterface>|null $configurationClass */
			$configurationClass = static::getConfigOptionsFile($scope);

			if (!$configurationClass) {
				static::$loadedConfigOptions[ $scope ] = null;


				return null;
			}
		}

		if ($configurationClass instanceof PageRoleEnumInterface) {
			static::$loadedConfigOptions[ $scope ] = new GenericPagesConfigOptions($scope);
		}
		elseif (is_string($configurationClass)) {
			static::$loadedConfigOptions[ $scope ] = new $configurationClass();
		}
		else {
			static::$loadedConfigOptions[ $scope ] = $configurationClass;
		}


		return static::$loadedConfigOptions[ $scope ];
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
	 * @noinspection PhpUnused
	 */
	public static function validateConfigValue(
		string $scope,
		string $realm,
		string $identifier,
		mixed $value,
		?string $languageShortcode = null
	): bool|string {
		$configuration = static::loadConfigOptions($scope);

		if (!$configuration) {
			return false;
		}


		return $configuration->validateConfigValue($realm, $identifier, $value, $languageShortcode);
	}


	/**
	 * Loads a configuration class for the given scope and cast the provided value to it's correct type for the given identifier
	 *
	 * @param string $scope
	 * @param string $realm
	 * @param string $identifierPath
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return mixed
	 */
	public static function typecastConfigValue(
		string $scope,
		string $realm,
		string $identifierPath,
		mixed $value,
		?string $languageShortcode = null,
	): mixed {
		$configuration = static::loadConfigOptions($scope);

		if (!$configuration) {
			return $value;
		}


		return $configuration->typecastConfigValue(
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
		$scope = Text::slug($scope, '_');
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);


		return Inflector::camelize($scope);
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
	 */
	protected static function findConfigOptionsFile(string $scope): void {
		$classes = App::classes(
			$scope,
			'Configuration/ConfigOptions',
			'ConfigOptions',
			ConfigOptionsInterface::class,
			null,
			['GenericDatatablesConfigOptions', 'GenericPagesConfigOptions']
		);

		/** @var class-string<\Awyiss\Configuration\ConfigOptionsInterface> $className */
		foreach ($classes as $className) {
			$configScope = static::extractScopeFromClassName($className);

			static::$configOptions[ $configScope ] ??= $className;
		}

		$cleanedScope = null;
		$className = $scope;
		if ($className !== '*') {
			$cleanedScope = static::sanitizeScope($scope);
			$className = Inflector::camelize($cleanedScope);
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRole> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$configScope = static::sanitizeScope($pageRole->name);

			if (
				//Skip if the config scope is already set
				isset(static::$configOptions[ $configScope ])
				|| (
					// or if the config scope is not the same as the provided scope
					$className !== '*'
					&& $configScope !== $cleanedScope
				)
			) {
				continue;
			}

			static::$configOptions[ $configScope ] = $pageRole;
		}


		if (!isset(static::$datatables)) {
			/**
			 * Get all datatables from the database because we want them to have a generic policy too.
			 *
			 * Use a raw query to avoid the need for a model which would in return again try to
			 * load the config options due to the UserConfiguration.
			 */
			$connection = ConnectionManager::get('default');
			$results = $connection
				->selectQuery('*', 'datatables')
				->where(['deleted' => 0])
				->execute()
				->fetchAll('assoc')
			;

			static::$datatables = collection($results)
				->indexBy(function (array $record) {
					return static::sanitizeScope($record['identifier']);
				})
				->map(function (array $record) {
					return new GenericDatatablesConfigOptions($record['identifier']);
				})
				->toArray()
			;
		}

		if ($cleanedScope) {
			if (
				!isset(static::$configOptions[ $cleanedScope ]) && isset(static::$datatables[ $cleanedScope ])
			) {
				static::$configOptions[ $cleanedScope ] = static::$datatables[ $cleanedScope ];
			}
		}
		else {
			static::$configOptions += static::$datatables;
		}
	}


	/**
	 * @param string $scope
	 * @param int $suffixLength
	 * @return string
	 */
	public static function extractScopeFromClassName(string $scope, int $suffixLength = 13): string {
		$parts = explode('\\', trim($scope, '\\'));
		$scope = array_pop($parts);
		$scope = substr($scope, 0, -$suffixLength);

		return static::sanitizeScope($scope);
	}
}
