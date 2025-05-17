<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use Awyiss\Core\App;
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
		$ls_classes = App::classes($identifier, 'Form/Protection', 'FormProtection', FormProtectionInterface::class);

		/** @var class-string<\Awyiss\Form\Protection\FormProtectionInterface> $ls_className */
		foreach ($ls_classes as $ls_protectionName => $ls_className) {
			$ls_identifier = static::sanitizeIdentifier(substr($ls_protectionName, 0, -14));

			static::$classes[ $ls_identifier ] = $ls_className;
		}
	}
}
