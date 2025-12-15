<?php declare(strict_types=1);


namespace Awyiss\Widget;


use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
use RuntimeException;


/**
 * Class WidgetsProvider
 * Returns all found Widget classes in both the Awyiss and the custom namespace
 */
class WidgetsProvider {
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;
	/**
	 * @var array<string, class-string<\Awyiss\Widget\WidgetInterface>>
	 */
	protected static array $widgets = [];


	/**
	 * Trigger an exception during instantiation
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found Widget classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<\Awyiss\Widget\WidgetInterface>>
	 */
	public static function getWidgetFiles(): array {
		if (!static::$foundAll) {
			static::findWidgetFile('*');
			static::$foundAll = true;
		}


		return static::$widgets;
	}


	/**
	 * Returns the found Widget class for the provided identifier or null
	 *
	 * @param string $identifier
	 * @return class-string<\Awyiss\Widget\WidgetInterface>|null
	 */
	public static function getWidgetFile(string $identifier): ?string {
		$identifier = static::sanitizeIdentifier($identifier);

		if (!isset(static::$widgets[ $identifier ])) {
			static::findWidgetFile($identifier);
		}

		return static::$widgets[ $identifier ] ?? null;
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
	 * Finds all Widget classes in both, the Awyiss and the custom namespace, for a given identifier.
	 * `$scope` can be "*" to return all files.
	 * If a Widget class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 * Abstract classes and those starting with "_" are ignored.
	 *
	 * @param string $identifier
	 * @return void
	 */
	protected static function findWidgetFile(string $identifier): void {
		$widgets = App::classes($identifier, 'Widget', 'Widget', WidgetInterface::class);

		/** @var class-string<\Awyiss\Widget\WidgetInterface> $widgetClass */
		foreach ($widgets as $widgetClass) {
			if (!$widgetClass::isAvailable()) {
				continue;
			}

			$identifier = static::extractIdentifierFromClassName($widgetClass);

			if (isset(static::$widgets[ $identifier ])) {
				continue;
			}

			static::$widgets[ $identifier ] = $widgetClass;
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
