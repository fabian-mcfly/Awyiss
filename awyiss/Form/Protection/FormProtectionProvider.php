<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
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
	 * @var array<string, class-string<\Awyiss\Form\Protection\FormProtectionInterface>>
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
				$ls_protectionName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);

				if (
					str_starts_with($ls_protectionName, '_') ||
					str_starts_with($ls_protectionName, 'Abstract')
				) {
					continue;
				}

				/** @var class-string<\Awyiss\Form\Protection\FormProtectionInterface> $ls_formProtectionClass */
				$ls_formProtectionClass = $ls_namespace . $ls_protectionName;

				if (!in_array(FormProtectionInterface::class, class_implements($ls_formProtectionClass))) {
					throw new RuntimeException(
						sprintf('The provided FormProtection class `%s` does not implement `%s`.', $ls_formProtectionClass, FormProtectionInterface::class)
					);
				}

				$ls_identifier = static::sanitizeIdentifier(substr($ls_protectionName, 0, -14));

				static::$classes[ $ls_identifier ] = $ls_formProtectionClass;
			}
		}
	}
}
