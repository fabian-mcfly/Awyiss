<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Class FormProtectionProvider
 * Returns all found Form Protection classes in both the Awyiss and the custom namespace
 */
class FormProtectionProvider {
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;
	/**
	 * @var \class-string
	 */
	protected static array $classes = [];


	/**
	 * Trigger an exception during instantiation
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found Form Protection classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<\Awyiss\Form\Protection\FormProtectionInterface>>
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function getFormProtectionFiles(): array {
		if (!static::$foundAll) {
			static::findFormProtectionFile('*');
			static::$foundAll = true;
		}


		return static::$classes;
	}


	/**
	 * Returns the found FormProtection class for the provided identifier or null
	 *
	 * @param string $identifier
	 * @return class-string<\Awyiss\Form\Protection\FormProtectionInterface>|null
	 * @throws \ReflectionException
	 */
	public static function getFormProtectionFile(string $identifier): ?string {
		$ls_identifier = static::sanitizeIdentifier($identifier);

		if (!isset(static::$classes[ $ls_identifier ])) {
			static::findFormProtectionFile($ls_identifier);
		}

		return static::$classes[ $ls_identifier ] ?? null;
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $identifier
	 * @return string
	 */
	public static function sanitizeIdentifier(string $identifier): string {
		return Inflector::underscore(Text::slug($identifier, '_'));
	}


	/**
	 * Finds all FormProtection classes in both, the Awyiss and the custom namespace, for a given identifier.
	 * `$scope` can be "*" to return all files.
	 * If a FormProtection class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 * Abstract classes and those starting with "_" are ignored.
	 *
	 * @param string $identifier
	 * @return void
	 * @throws \ReflectionException
	 */
	protected static function findFormProtectionFile(string $identifier): void {
		$ls_className = $identifier;
		if ($ls_className !== '*') {
			$ls_identifier = static::sanitizeIdentifier($identifier);
			$ls_className = Inflector::camelize($ls_identifier);
		}

		$la_paths = [];

		if (defined('CUSTOM_NAMESPACE')) {
			$la_paths[ '\\' . CUSTOM_NAMESPACE . '\Form\Protection\\' ] = implode(DS, [ROOT, CUSTOM_DIR, 'Form', 'Protection', $ls_className . 'FormProtection.php']);
		}

		$la_paths['\Awyiss\Form\Protection\\'] = implode(DS, [ROOT, APP_DIR, 'Form', 'Protection', $ls_className . 'FormProtection.php']);

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_moduleName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (
					str_starts_with($ls_moduleName, '_') ||
					str_starts_with($ls_moduleName, 'Abstract')
				) {
					continue;
				}

				/** @var class-string<\Awyiss\Form\Protection\FormProtectionInterface> $ls_formProtectionClass */
				$ls_formProtectionClass = $ls_namespace . $ls_moduleName;

				$lo_reflection = new ReflectionClass($ls_formProtectionClass);

				if (!$lo_reflection->implementsInterface(FormProtectionInterface::class)) {
					throw new RuntimeException(
						sprintf('The provided FormProtection class `%s` does not implement `%s`.', $ls_formProtectionClass, FormProtectionInterface::class)
					);
				}

				$ls_identifier = static::sanitizeIdentifier($ls_formProtectionClass::getIdentifier());

				static::$classes[ $ls_identifier ] = $ls_formProtectionClass;
			}
		}
	}
}
