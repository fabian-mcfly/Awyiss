<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Provides access to all ConfigOptions classes in both the Awyiss and the custom namespace.
 */
class ConfigOptionsProvider {
	/**
	 * @var array<string, class-string<ConfigOptionsInterface>>
	 */
	protected static array $configOptions = [];
	/**
	 * @var array<string, ConfigOptionsInterface|GenericPagesConfigOptions>
	 */
	protected static array $loadedConfigOptions = [];
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;
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
	 * @return array<string, class-string<ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function getConfigOptionsFiles(bool $ab_returnLoaded = false): array {
		if (!static::$foundAll) {
			static::$configOptions = static::findConfigOptionsFile('*', $ab_returnLoaded);
			static::$foundAll = true;
		}

		if ($ab_returnLoaded) {
			if (!static::$loadedAll) {
				foreach (static::$configOptions as $ls_scope => $ls_configOptions) {
					static::$loadedConfigOptions[ $ls_scope ] = static::loadConfigOptions($ls_configOptions);
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
	 * @param string $as_scope
	 * @param bool $ab_returnLoaded
	 * @return ConfigOptionsInterface|string|null
	 * @throws \ReflectionException
	 */
	public static function getConfigOptionsFile(string $as_scope, bool $ab_returnLoaded = false): string|ConfigOptionsInterface|null {
		$ls_scope = static::sanitizeScope($as_scope);

		if (empty(static::$configOptions[ $ls_scope ])) {
			static::$configOptions += static::findConfigOptionsFile($ls_scope, $ab_returnLoaded);
		}

		if ($ab_returnLoaded) {
			return static::$loadedConfigOptions[ $ls_scope ] ?? null;
		}


		return static::$configOptions[ $ls_scope ] ?? null;
	}


	/**
	 * Returns an instance of a ConfigOptions class with the provided scope or null
	 *
	 * @param class-string<ConfigOptionsInterface>|string $as_configOptions
	 * @return ConfigOptionsInterface|null
	 * @throws \ReflectionException
	 */
	public static function loadConfigOptions(string $as_configOptions): ?ConfigOptionsInterface {
		if (str_contains($as_configOptions, '\\')) {
			if (class_exists($as_configOptions)) {
				$ls_scope = $as_configOptions::getScope();
				$ls_configurationClass = $as_configOptions;

				if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
					return static::$loadedConfigOptions[ $ls_scope ];
				}
			}
			else {
				return null;
			}
		}
		else {
			$ls_scope = static::sanitizeScope($as_configOptions);

			if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
				return static::$loadedConfigOptions[ $ls_scope ];
			}

			/** @var class-string<ConfigOptionsInterface>|null $ls_configurationClass */
			$ls_configurationClass = static::getConfigOptionsFile($ls_scope);

			if (!$ls_configurationClass) {
				static::$loadedConfigOptions[ $ls_scope ] = null;


				return null;
			}

			if (!str_contains($ls_configurationClass, '\\')) {
				$ls_configurationClass = GenericPagesConfigOptions::class;
			}
		}

		static::$loadedConfigOptions[ $ls_scope ] = new $ls_configurationClass();

		if ($ls_configurationClass::getScope() === 'GenericPages') {
			static::$loadedConfigOptions[ $ls_scope ]->setPageRole($ls_scope);
		}

		return static::$loadedConfigOptions[ $ls_scope ];
	}


	/**
	 * Loads a configuration class for the given scope and validates the provided value for the given identifier
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $as_scope
	 * @param string $as_realm
	 * @param string $as_identifier
	 * @param mixed $ax_value
	 * @param string|null $as_languageShortcode
	 * @return string|bool
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function validateConfigValue(string $as_scope, string $as_realm, string $as_identifier, mixed $ax_value, ?string $as_languageShortcode = null): bool|string {
		$lo_configuration = static::loadConfigOptions($as_scope);

		if (!$lo_configuration) {
			return false;
		}


		return $lo_configuration->validateConfigValue($as_realm, $as_identifier, $ax_value, $as_languageShortcode);
	}


	/**
	 * Loads a configuration class for the given scope and  cast the provided value to it's correct type for the given identifier
	 *
	 * @param string $as_scope
	 * @param string $as_realm
	 * @param string $ax_identifierPath
	 * @param mixed $ax_value
	 * @return mixed
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function typecastConfigValue(string $as_scope, string $as_realm, string $ax_identifierPath, mixed $ax_value, ?string $as_languageShortcode = null): mixed {
		$lo_configuration = static::loadConfigOptions($as_scope);

		if (!$lo_configuration) {
			return $ax_value;
		}


		return $lo_configuration->typecastConfigValue($as_realm, $ax_identifierPath, $ax_value, $as_languageShortcode);
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_scope
	 * @return string
	 */
	public static function sanitizeScope(string $as_scope): string {
		return Inflector::camelize(Inflector::pluralize(Text::slug($as_scope, '_')));
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_identifier
	 * @return string
	 */
	public static function sanitizeIdentifier(string $as_identifier): string {
		return Inflector::variable(Text::slug($as_identifier, '_'));
	}


	/**
	 * Finds all ConfigOptions classes in both the Awyiss and the custom namespace for a given identifier.
	 *
	 * `$as_scope` can be "*" to return all files.
	 *
	 * If a ConfigOptions class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 *
	 * @param string $as_scope
	 * @return array<string, class-string<ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 */
	protected static function findConfigOptionsFile(string $as_scope): array {
		$la_configurations = [];

		$ls_scope = $as_scope;
		if ($ls_scope !== '*') {
			$ls_scope = Inflector::camelize($ls_scope);
		}

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Configuration\ConfigOptions\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Configuration', 'ConfigOptions', $ls_scope . 'ConfigOptions.php',]),
			'\Awyiss\Configuration\ConfigOptions\\' => implode(DS, [ROOT, APP_DIR, 'Configuration', 'ConfigOptions', $ls_scope . 'ConfigOptions.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_configurationName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (str_starts_with($ls_configurationName, '_') || ($ls_scope === '*' && $ls_configurationName === 'GenericPagesConfigOptions')) {
					continue;
				}

				$ls_configurationClass = $ls_namespace . $ls_configurationName;

				$lo_reflection = new ReflectionClass($ls_configurationClass);

				if (!$lo_reflection->implementsInterface(ConfigOptionsInterface::class)) {
					throw new RuntimeException(
						sprintf('The provided Configuration class `%s` does not extend the `%s` class.', $ls_configurationClass, ConfigOptionsInterface::class)
					);
				}

				/**
				 * @var ConfigOptionsInterface $ls_configurationClass
				 */
				$ls_configScope = $ls_configurationClass::getScope();

				if (isset($la_configurations[ $ls_configScope ])) {
					continue;
				}

				$la_configurations[ $ls_configScope ] = $ls_configurationClass;
			}
		}

		if ($as_scope === '*') {
			/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
			$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
			/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
			foreach ($lo_pageRolesTable->find()->where(['identifier !=' => 'page'])->select('identifier') as $lo_pageRole) {
				$ls_configScope = static::sanitizeScope($lo_pageRole->identifier);

				if (isset($la_configurations[ $ls_configScope ])) {
					continue;
				}

				$la_configurations[ $ls_configScope ] = $ls_configScope;
			}
		}
		elseif (!isset($la_configurations[ $ls_scope ]) && !in_array(strtolower($ls_scope), ['page', 'pages'])) {
			$ls_singular = Inflector::singularize(Inflector::underscore($ls_scope));
			$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
			if (defined($ls_constant)) {
				$la_configurations[ $ls_scope ] = $ls_scope;
			}
		}

		return $la_configurations;
	}
}
