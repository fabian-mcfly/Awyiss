<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
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
	 */
	public static function getModuleFiles(): array {
		if (!static::$foundAll) {
			static::findModuleFile('*');
			static::$foundAll = true;
		}


		return static::$modules;
	}


	/**
	 * Returns the found Module class for the provided identifier or null
	 *
	 * @param string $identifier
	 * @return class-string<\Awyiss\Module\ModuleInterface>|null
	 */
	public static function getModuleFile(string $identifier): ?string {
		$identifier = static::sanitizeIdentifier($identifier);

		if (!isset(static::$modules[ $identifier ])) {
			static::findModuleFile($identifier);
		}

		return static::$modules[ $identifier ] ?? null;
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
	 * Finds all Module classes in both, the Awyiss and the custom namespace, for a given identifier.
	 * `$scope` can be "*" to return all files.
	 * If a Module class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 * Abstract classes and those starting with "_" are ignored.
	 *
	 * @param string $identifier
	 * @return void
	 */
	protected static function findModuleFile(string $identifier): void {
		$modules = App::classes($identifier, 'Module', 'Module', ModuleInterface::class);

		/** @var class-string<\Awyiss\Module\ModuleInterface> $moduleClass */
		foreach ($modules as $moduleClass) {
			if (!$moduleClass::isAvailable()) {
				continue;
			}

			$identifier = static::extractIdentifierFromClassName($moduleClass);

			if (isset(static::$modules[ $identifier ])) {
				continue;
			}

			static::$modules[ $identifier ] = $moduleClass;
		}
	}


	/**
	 * @param string $identifier
	 * @param int $suffixLength
	 * @return string
	 */
	public static function extractIdentifierFromClassName(string $identifier, int $suffixLength = 6): string {
		$parts = explode('\\', trim($identifier, '\\'));
		$identifier = array_pop($parts);
		$identifier = substr($identifier, 0, -$suffixLength);

		return static::sanitizeIdentifier($identifier);
	}
}
