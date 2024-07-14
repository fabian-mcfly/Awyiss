<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Class ModulesProvider
 * Returns all found Module classes in both the Awyiss and the custom namespace
 */
class ModulesProvider {
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;
	/**
	 * @var array<string, class-string<\Awyiss\Module\ModuleInterface>>
	 */
	protected static array $modules = [];


	/**
	 * Trigger an exception during instantiation
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found Module classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<\Awyiss\Module\ModuleInterface>>
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function getModuleFiles(): array {
		if (!static::$foundAll) {
			static::findModuleFile('*');
			static::$foundAll = true;
		}


		return static::$modules;
	}


	/**
	 * Returns the found ConfigOptions class for the provided identifier or null
	 *
	 * @param string $identifier
	 * @return class-string<\Awyiss\Module\ModuleInterface>|null
	 * @throws \ReflectionException
	 */
	public static function getConfigOptionsFile(string $identifier): ?string {
		$ls_identifier = static::sanitizeIdentifier($identifier);

		if (!isset(static::$modules[ $ls_identifier ])) {
			static::findModuleFile($ls_identifier);
		}

		return static::$modules[ $ls_identifier ] ?? null;
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
	 * If a ConfigOptions class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 * For page roles, no ConfigOptionsInterface is returned but the \Awyiss\Model\Enum\PageRole case
	 *
	 * @param string $identifier
	 * @return void
	 * @throws \ReflectionException
	 */
	protected static function findModuleFile(string $identifier): void {
		$ls_className = $identifier;
		if ($ls_className !== '*') {
			$ls_identifier = static::sanitizeIdentifier($identifier);
			$ls_className = Inflector::camelize($ls_identifier);
		}

		$la_paths = [];

		if (defined('CUSTOM_NAMESPACE')) {
			$la_paths[ '\\' . CUSTOM_NAMESPACE . '\Module\\' ] = implode(DS, [ROOT, CUSTOM_DIR, 'Module', $ls_className . 'Module.php']);
		}

		$la_paths['\Awyiss\Module\\'] = implode(DS, [ROOT, APP_DIR, 'Module', $ls_className . 'Module.php']);

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_moduleName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (
					in_array($ls_moduleName, ['ModuleInterface', 'ModulesProvider']) ||
					str_starts_with($ls_moduleName, '_') ||
					str_starts_with($ls_moduleName, 'Abstract')
				) {
					continue;
				}

				$ls_moduleClass = $ls_namespace . $ls_moduleName;

				$lo_reflection = new ReflectionClass($ls_moduleClass);

				if (!$lo_reflection->implementsInterface(ModuleInterface::class)) {
					throw new RuntimeException(
						sprintf('The provided Module class `%s` does not implement `%s`.', $ls_moduleClass, ModuleInterface::class)
					);
				}

				/**
				 * @var \Awyiss\Module\ModuleInterface $ls_moduleClass
				 */
				$ls_identifier = static::sanitizeIdentifier($ls_moduleClass::getIdentifier());

				if (isset(static::$modules[ $ls_identifier ])) {
					continue;
				}

				static::$modules[ $ls_identifier ] = $ls_moduleClass;
			}
		}
	}
}
