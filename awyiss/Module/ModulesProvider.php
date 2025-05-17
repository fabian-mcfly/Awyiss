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
	 * Returns the found Module class for the provided identifier or null
	 *
	 * @param string $identifier
	 * @return class-string<\Awyiss\Module\ModuleInterface>|null
	 */
	public static function getModuleFile(string $identifier): ?string {
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
		$la_modules = App::classes($identifier, 'Module', 'Module', ModuleInterface::class);

		/** @var class-string<\Awyiss\Module\ModuleInterface> $ls_moduleClass */
		foreach ($la_modules as $ls_moduleClass) {
			if (!$ls_moduleClass::isAvailable()) {
				continue;
			}

			$ls_identifier = static::sanitizeIdentifier($ls_moduleClass::getIdentifier());

			if (isset(static::$modules[ $ls_identifier ])) {
				continue;
			}

			static::$modules[ $ls_identifier ] = $ls_moduleClass;
		}
	}
}
