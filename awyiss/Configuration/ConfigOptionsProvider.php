<?php declare(strict_types=1);


namespace Awyiss\Configuration;


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
	 * @var array<string, ConfigOptionsInterface>
	 */
	protected static array $loadedConfigOptions = [];
	/**
	 * @var bool
	 */
	protected static bool $foundAll = FALSE;


	private function __construct () {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found ConfigOptions classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function getConfigOptionsFiles (bool $ab_returnLoaded = FALSE): array {
		if ( ! static::$foundAll) {
			static::$configOptions = static::findConfigOptionsFile('*', $ab_returnLoaded);
			static::$foundAll = TRUE;
		}

		if ($ab_returnLoaded) {
			return static::$loadedConfigOptions;
		}

		return static::$configOptions;
	}


	/**
	 * Returns the found ConfigOptions class for the provided scope or NULL
	 *
	 * @param string $as_scope
	 * @param bool $ab_returnLoaded
	 *
	 * @return NULL|string|ConfigOptionsInterface
	 * @throws \ReflectionException
	 */
	public static function getConfigOptionsFile (string $as_scope, bool $ab_returnLoaded = FALSE): string|ConfigOptionsInterface|NULL {
		$ls_scope = static::sanitizeScope($as_scope);

		if (empty(static::$configOptions[ $ls_scope ])) {
			static::$configOptions += static::findConfigOptionsFile($ls_scope, $ab_returnLoaded);
		}

		if ($ab_returnLoaded) {
			return static::$loadedConfigOptions[ $ls_scope ] ?? NULL;
		}

		return static::$configOptions[ $ls_scope ] ?? NULL;
	}


	/**
	 * Returns an instance of a ConfigOptions class with the provided scope or NULL
	 *
	 * @param string|class-string<ConfigOptionsInterface> $as_scope
	 *
	 * @return NULL|ConfigOptionsInterface
	 *
	 * @throws \ReflectionException
	 */
	public static function loadConfigOptions (string $as_scope): ?ConfigOptionsInterface {
		$ls_scope = static::sanitizeScope($as_scope);

		if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
			return static::$loadedConfigOptions[ $ls_scope ];
		}

		if (class_exists($as_scope)) {
			$ls_scope = $as_scope::getScope();
			$ls_configurationClass = $as_scope;

			if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
				return static::$loadedConfigOptions[ $ls_scope ];
			}
		}
		else {
			/** @var NULL|class-string<ConfigOptionsInterface> $ls_configurationClass */
			$ls_configurationClass = static::getConfigOptionsFile($ls_scope);
			if ( ! $ls_configurationClass) {
				static::$loadedConfigOptions[ $ls_scope ] = NULL;

				return NULL;
			}
		}

		static::$loadedConfigOptions[ $ls_scope ] = new $ls_configurationClass();

		return static::$loadedConfigOptions[ $ls_scope ];
	}


	/**
	 * Loads a configuration class for the given scope and validates the provided value for the given identifier
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string      $as_scope
	 * @param string      $as_realm
	 * @param string      $as_identifier
	 * @param mixed       $ax_value
	 * @param null|string $as_languageShortcode
	 *
	 * @return bool|string
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function validateConfigValue (string $as_scope, string $as_realm, string $as_identifier, mixed $ax_value, ?string $as_languageShortcode = NULL): bool|string {
		$lo_configuration = static::loadConfigOptions($as_scope);

		if ( ! $lo_configuration) {
			return FALSE;
		}

		return $lo_configuration->validateConfigValue($as_realm, $as_identifier, $ax_value, $as_languageShortcode);
	}


	/**
	 * Loads a configuration class for the given scope and  cast the provided value to it's correct type for the given identifier
	 *
	 * @param string $as_scope
	 * @param string $as_realm
	 * @param string $as_identifier
	 * @param mixed  $ax_value
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function typecastConfigValue (string $as_scope, string $as_realm, string $as_identifier, mixed $ax_value): mixed {
		$lo_configuration = static::loadConfigOptions($as_scope);

		if ( ! $lo_configuration) {
			return $ax_value;
		}

		return $lo_configuration->typecastConfigValue($as_realm, $as_identifier, $ax_value);
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
	 * @param bool   $ab_load
	 *
	 * @return array<string, class-string<ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 */
	protected static function findConfigOptionsFile (string $as_scope, bool $ab_load = FALSE): array {
		$la_configurations = [];

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Configuration\ConfigOptions\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Configuration', 'ConfigOptions', $as_scope . 'ConfigOptions.php',]),
			'\Awyiss\Configuration\ConfigOptions\\' => implode(DS, [ROOT, APP_DIR, 'Configuration', 'ConfigOptions', $as_scope . 'ConfigOptions.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_configurationName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (str_starts_with($ls_configurationName, '_')) {
					continue;
				}

				$ls_configurationClass = $ls_namespace . $ls_configurationName;

				$lo_reflection = new ReflectionClass($ls_configurationClass);

				if ( ! $lo_reflection->implementsInterface(ConfigOptionsInterface::class)) {
					throw new RuntimeException(sprintf('The provided Configuration class `%s` does not extend the `%s` class.', $ls_configurationClass, ConfigOptionsInterface::class));
				}

				/**
				 * @var ConfigOptionsInterface $ls_configurationClass
				 */
				$ls_scope = $ls_configurationClass::getScope();

				if (isset($la_configurations[ $ls_scope ])) {
					continue;
				}

				if ($ab_load) {
					static::loadConfigOptions($ls_configurationClass);
				}

				$la_configurations[ $ls_scope ] = $ls_configurationClass;
			}
		}

		return $la_configurations;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_scope
	 *
	 * @return string
	 */
	public static function sanitizeScope (string $as_scope): string {
		return Inflector::camelize(Inflector::pluralize(Text::slug($as_scope, '_')));
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_identifier
	 *
	 * @return string
	 */
	public static function sanitizeIdentifier (string $as_identifier): string {
		return Inflector::variable(Text::slug($as_identifier, '_'));
	}
}