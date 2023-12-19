<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Cake\Utility\Inflector;
use ReflectionClass;
use RuntimeException;


/**
 * Provide access to all ConfigOptions classes in both the Awyiss and the custom namespace.
 */
class ConfigOptionsProvider {
	/**
	 * @var array<string, class-string<\Awyiss\Configuration\ConfigOptionsInterface>>
	 */
	protected static array $configurations = [];
	/**
	 * @var array<string, \Awyiss\Configuration\ConfigOptionsInterface>
	 */
	protected static array $loadedConfigurations = [];


	private function __construct () {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found ConfigOptions classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<\Awyiss\Configuration\ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function getConfigurationFiles (): array {
		if (empty(static::$configurations)) {
			static::$configurations = static::findConfiguration('*');
		}

		return static::$configurations;
	}


	/**
	 * Returns the found ConfigOptions class for the provided name or NULL
	 *
	 * @param string $as_name
	 *
	 * @return NULL|class-string<\Awyiss\Configuration\ConfigOptionsInterface>
	 * @throws \ReflectionException
	 */
	public static function getConfigurationFile (string $as_name): ?string {
		$ls_name = Inflector::underscore($as_name);

		if (empty(static::$configurations[ $ls_name ])) {
			static::$configurations += static::findConfiguration($ls_name);
		}

		return static::$configurations[ $ls_name ] ?? NULL;
	}


	/**
	 * Returns an instance of a ConfigOptions class with the provided name or NULL
	 *
	 * @param string $as_name
	 *
	 * @return NULL|\Awyiss\Configuration\ConfigOptionsInterface
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function loadConfiguration (string $as_name): ?ConfigOptionsInterface {
		$ls_name = Inflector::underscore($as_name);

		if (array_key_exists($ls_name, static::$loadedConfigurations)) {
			return static::$loadedConfigurations[ $ls_name ];
		}

		/** @var NULL|class-string<\Awyiss\Configuration\ConfigOptionsInterface> $ls_configurationClass */
		$ls_configurationClass = static::getConfigurationFile($ls_name);
		if ( ! $ls_configurationClass) {
			static::$loadedConfigurations[ $ls_name ] = NULL;

			return NULL;
		}

		static::$loadedConfigurations[ $ls_name ] = new $ls_configurationClass();

		return static::$loadedConfigurations[ $ls_name ];
	}


	/**
	 * Loads a configuration class and validates the provided value for the given configOptionName
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $as_scopeName
	 * @param string $as_configOptionName
	 * @param mixed $ax_value
	 * @param null|string $as_languageShortcode
	 *
	 * @return bool|string
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function validateConfigValue (string $as_scopeName, string $as_configOptionName, mixed $ax_value, ?string $as_languageShortcode = NULL): bool|string {
		$lo_configuration = static::loadConfiguration($as_scopeName);

		if ( ! $lo_configuration) {
			return FALSE;
		}

		return $lo_configuration->validateConfigValue($as_configOptionName, $ax_value, $as_languageShortcode);
	}


	/**
	 * Loads a configuration class and cast the provided value to it's correct type for the given configOptionName
	 *
	 * @param string $as_scopeName
	 * @param string $as_configOptionName
	 * @param mixed $ax_value
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function typecastConfigValue (string $as_scopeName, string $as_configOptionName, mixed $ax_value): mixed {
		$lo_configuration = static::loadConfiguration($as_scopeName);

		if ( ! $lo_configuration) {
			return $ax_value;
		}

		return $lo_configuration->typecastConfigValue($as_configOptionName, $ax_value);
	}


	/**
	 * Finds all ConfigOptions classes in both the Awyiss and the custom namespace for a given name.
	 *
	 * `$as_name` can be "*" to return all files.
	 *
	 * If a ConfigOptions class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 *
	 * @param string $as_name
	 *
	 * @return array<string, class-string<\Awyiss\Configuration\ConfigOptionsInterface>>
	 * @throws \ReflectionException
	 */
	protected static function findConfiguration (string $as_name): array {
		$la_configurations = [];
		$ls_name = Inflector::camelize($as_name);

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Configuration\ConfigOptions\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Configuration', 'ConfigOptions', $ls_name . 'ConfigOptions.php',]),
			'\Awyiss\Configuration\ConfigOptions\\' => implode(DS, [ROOT, APP_DIR, 'Configuration', 'ConfigOptions', $ls_name . 'ConfigOptions.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_configurationName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				$ls_configurationClass = $ls_namespace . $ls_configurationName;

				$lo_reflection = new ReflectionClass($ls_configurationClass);

				if ( ! $lo_reflection->implementsInterface(ConfigOptionsInterface::class)) {
					throw new RuntimeException(sprintf('The provided Configuration class `%s` does not extend the `%s` class.', $ls_configurationClass, ConfigOptionsInterface::class));
				}

				/**
				 * @var ConfigOptionsInterface $ls_configurationClass
				 */
				$ls_scope = Inflector::underscore($ls_configurationClass::getScope());

				if (isset($la_configurations[ $ls_scope ])) {
					continue;
				}

				$la_configurations[ $ls_scope ] = $ls_configurationClass;
			}
		}

		return $la_configurations;
	}
}