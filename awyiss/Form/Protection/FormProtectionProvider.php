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
	 * @var array<string, class-string<\Awyiss\Form\Protection\FormProtectionInterface>>
	 */
	protected static array $classes = [];
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;


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
		$identifier = static::sanitizeIdentifier($identifier);

		if (!isset(static::$classes[ $identifier ])) {
			static::findFormProtectionFile($identifier);
		}

		return static::$classes[ $identifier ] ?? null;
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
		$classes = App::classes($identifier, 'Form/Protection', 'FormProtection', FormProtectionInterface::class);

		/** @var class-string<\Awyiss\Form\Protection\FormProtectionInterface> $className */
		foreach ($classes as $protectionName => $className) {
			$identifier = static::sanitizeIdentifier(substr($protectionName, 0, -14));

			static::$classes[ $identifier ] = $className;
		}
	}
}
