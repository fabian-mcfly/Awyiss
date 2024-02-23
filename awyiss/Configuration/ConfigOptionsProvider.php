<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions;
use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Enum\PageRoleEnumInterface;
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
	public static function getConfigOptionsFiles(bool $ab_returnLoaded = false): array {
		if (!static::$foundAll) {
			static::findConfigOptionsFile('*');
			static::$foundAll = true;
		}

		if ($ab_returnLoaded) {
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
	 * @param string $as_scope
	 * @param bool $ab_returnLoaded
	 * @return \Awyiss\Configuration\ConfigOptionsInterface|\Awyiss\Model\Enum\PageRoleEnumInterface|string|null
	 * @throws \ReflectionException
	 */
	public static function getConfigOptionsFile(string $as_scope, bool $ab_returnLoaded = false): ConfigOptionsInterface|PageRoleEnumInterface|string|null {
		$ls_scope = static::sanitizeScope($as_scope);

		if (!isset(static::$configOptions[ $ls_scope ])) {
			static::findConfigOptionsFile($ls_scope);
		}

		if ($ab_returnLoaded) {
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
	 * @param class-string<ConfigOptionsInterface>|string $as_configOptionScope
	 * @return ConfigOptionsInterface|null
	 * @throws \ReflectionException
	 */
	public static function loadConfigOptions(string $as_configOptionScope): ?ConfigOptionsInterface {
		if (str_contains($as_configOptionScope, '\\')) {
			if (class_exists($as_configOptionScope)) {
				$ls_scope = $as_configOptionScope::getScope();
				$lx_configurationClass = $as_configOptionScope;

				if (array_key_exists($ls_scope, static::$loadedConfigOptions)) {
					return static::$loadedConfigOptions[ $ls_scope ];
				}
			}
			else {
				return null;
			}
		}
		else {
			$ls_scope = static::sanitizeScope($as_configOptionScope);

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
	 * @param string|null $as_languageShortcode
	 * @return mixed
	 * @throws \ReflectionException
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
	 * Returns a CamelCased string
	 *
	 * @param string $as_scope
	 * @return string
	 */
	public static function sanitizeScope(string $as_scope): string {
		$ls_scope = Text::slug($as_scope, '_');
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);


		return Inflector::camelize($ls_scope);
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
	 * Finds all ConfigOptions classes in both, the Awyiss and the custom namespace, for a given identifier.
	 * `$as_scope` can be "*" to return all files.
	 *
	 * If a ConfigOptions class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 *
	 * For page roles, no ConfigOptionsInterface is returned but the \Awyiss\Model\Enum\PageRole case
	 *
	 * @param string $as_scope
	 * @return void
	 * @throws \ReflectionException
	 */
	protected static function findConfigOptionsFile(string $as_scope): void {
		$ls_scope = null;
		$ls_className = $as_scope;
		if ($ls_className !== '*') {
			$ls_scope = static::sanitizeScope($as_scope);
			$ls_className = Inflector::camelize($ls_scope);
		}

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Configuration\ConfigOptions\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Configuration', 'ConfigOptions', $ls_className . 'ConfigOptions.php',]),
			'\Awyiss\Configuration\ConfigOptions\\' => implode(DS, [ROOT, APP_DIR, 'Configuration', 'ConfigOptions', $ls_className . 'ConfigOptions.php']),
		];

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
						sprintf('The provided Configuration class `%s` does not extend the `%s` class.', $ls_configurationClass, ConfigOptionsInterface::class)
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
			//Get all datatables from the database because we want them to have a generic policy too
			/** @var \Awyiss\Model\Table\DatatablesTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get('Datatables');
			static::$datatables = $lo_table->findAllAndCache()->reject(function (Datatable $ao_datatable) use ($ls_className, $ls_scope) {
				if ($ls_className !== '*' && static::sanitizeScope($ao_datatable->identifier) !== $ls_scope) {
					return true;
				}


				return $ao_datatable->active === false;
			})->indexBy(function (Datatable $ao_datatable) {
				return static::sanitizeScope($ao_datatable->identifier);
			})->map(function (Datatable $ao_datatable) {
				return new GenericDatatablesConfigOptions($ao_datatable->identifier);
			})->toArray();

			static::$configOptions += static::$datatables;
		}
	}
}
