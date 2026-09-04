<?php declare(strict_types=1);


namespace Awyiss\Core;


use Awyiss\Utility\Inflector;
use Cake\Cache\Cache;
use Cake\Core\App as BaseApp;
use Cake\Core\Configure;
use Cake\Utility\Text;
use RuntimeException;


/**
 * @inheritDoc
 */
class App extends BaseApp {
	/**
	 * Return the class name namespaced.
	 *
	 * This method checks if the class is defined
	 * - in the custom namespace or
	 * - in the application/plugin namespace or
	 * - in the CakePHP core namespace
	 *
	 * Cached version of `findClassName()`.
	 *
	 * @param string $class
	 * @param string $type
	 * @param string $suffix
	 * @return string|null The fully qualified class name, or null if the class does not exist.
	 */
	public static function className(string $class, string $type = '', string $suffix = ''): ?string {
		if (str_contains($class, '\\')) {
			return class_exists($class) ? $class : null;
		}

		// If the CUSTOM_NAMESPACE is not defined, caching would result in a cache miss when the CUSTOM_NAMESPACE is defined later.
		// Therefore, we skip caching in this case.
		if (!defined('CUSTOM_NAMESPACE')) {
			return static::findClassName($class, $type, $suffix);
		}

		$cacheName = hash('xxh3', $class . '::' . $type . '::' . $suffix);

		return Cache::remember(
			$cacheName,
			fn() => static::findClassName($class, $type, $suffix) ?? false,
			'classes'
		) ?: null;
	}


	/**
	 * Return the class name namespaced.
	 *
	 * This method checks if the class is defined
	 * - in the custom namespace or
	 * - in the application/plugin namespace or
	 * - in the CakePHP core namespace
	 *
	 * @param string $class
	 * @param string $type
	 * @param string $suffix
	 * @return string|null The fully qualified class name, or null if the class does not exist.
	 */
	public static function findClassName(string $class, string $type = '', string $suffix = ''): ?string {
		[$plugin, $name] = pluginSplit($class);

		if ($plugin) {
			$base = str_replace('/', '\\', rtrim($plugin, '\\'));
			$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

			if (static::_classExistsInBase($fullname, $base)) {
				return $base . $fullname;
			}

			return null;
		}


		// No Plugin? Let's check if the class exists in the CUSTOM_NAMESPACE
		$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;
		if (defined('CUSTOM_NAMESPACE') && static::_classExistsInBase($fullname, CUSTOM_NAMESPACE)) {
			return '\\' . CUSTOM_NAMESPACE . $fullname;
		}


		// No class in the CUSTOM_NAMESPACE? It should be an Awyiss-class then.
		$base = Configure::read('App.namespace');
		$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;
		if (static::_classExistsInBase($fullname, $base)) {
			/** @var class-string */
			return '\\' . $base . $fullname;
		}


		// Neither CUSTOM_NAMESPACE, nor Awyiss-class? CakePHP it is.
		if (static::_classExistsInBase($fullname, 'Cake')) {
			return 'Cake' . $fullname;
		}

		return null;
	}


	/**
	 * Get a list of all classes of a certain folder in the application and the custom namespace.
	 * This method checks if the class is defined
	 * - in the custom namespace or
	 * - in the application/plugin namespace
	 *
	 * Cached version of `findClasses()`.
	 *
	 * @param string $name The name of the class or * to get all classes
	 * @param string $folder The folder of the class, e.g. 'Attribute/AttributeOptionsCollection', 'Event/Backend', 'Form'
	 * @param string $suffix The suffix of the class, e.g. 'AttributeOptionsCollection', 'Listener', 'FormOptions'
	 * @param string|null $interface The interface the class should implement. If set, the class will be checked
	 *  for it and an exception will be thrown if it does not implement the interface.
	 * @param string|null $subfolders Subfolders to check for the class that are not namespaces on their own, like console commands
	 * @param array $blocklistedClassNames Class names that should be ignored, like Abstract classes or interfaces
	 * @return array<string, class-string> An array of class names with the key being the class name without namespace
	 *  and the value being the fully qualified class name.
	 */
	public static function classes(
		string $name,
		string $folder,
		string $suffix = '',
		?string $interface = null,
		?string $subfolders = null,
		array $blocklistedClassNames = []
	): array {
		if ($name !== '*') {
			$name = Inflector::camelize(Text::slug($name, '_'));
		}

		// If the CUSTOM_NAMESPACE is not defined, caching would result in a cache miss when the CUSTOM_NAMESPACE is defined later.
		// Therefore, we skip caching in this case.
		if (!defined('CUSTOM_NAMESPACE')) {
			return static::findClasses($name, $folder, $suffix, $interface, $subfolders, $blocklistedClassNames);
		}

		$cacheName = hash(
			'xxh3',
			$name . '::' . $folder . '::' . ($suffix ?: '-') . '::' . ($interface ?? '-')
				. '::' . ($subfolders ?: '-') . '::' . json_encode($blocklistedClassNames)
		);

		return Cache::remember(
			$cacheName,
			fn() => static::findClasses($name, $folder, $suffix, $interface, $subfolders, $blocklistedClassNames),
			'classes'
		);
	}


	/**
	 * Finds all classes in the given folder and returns them as an array.
	 *
	 * @param string $name The name of the class or * to get all classes
	 * @param string $folder The folder of the class, e.g. 'Attribute/AttributeOptionsCollection', 'Event/Backend', 'Form'
	 * @param string $suffix The suffix of the class, e.g. 'AttributeOptionsCollection', 'Listener', 'FormOptions'
	 * @param string|null $interface The interface the class should implement. If set, the class will be checked
	 *  for it and an exception will be thrown if it does not implement the interface.
	 * @param string|null $subfolders Subfolders to check for the class that are not namespaces on their own, like console commands
	 * @param array $blocklistedClassNames Class names that should be ignored, like Abstract classes or interfaces
	 * @return array<string, class-string> An array of class names with the key being the class name without namespace
	 *  and the value being the fully qualified class name.
	 */
	public static function findClasses(
		string $name,
		string $folder,
		string $suffix = '',
		?string $interface = null,
		?string $subfolders = null,
		array $blocklistedClassNames = []
	): array {
		$paths = [];
		$files = [];

		$namespaceType = str_replace('/', '\\', $folder);
		$baseFolders = explode('/', $folder);
		$folders = $baseFolders;

		if ($subfolders) {
			if ($subfolders === '*') {
				// Add {/*,} to the last element of $folders
				$folders[ count($folders) - 1 ] .= '{/*,}';
			}
			else {
				$subfolderNames = explode('/', $subfolders);
				$folders = array_merge($folders, $subfolderNames);
			}
		}


		if (defined('CUSTOM_NAMESPACE')) {
			$paths[ '\\' . CUSTOM_NAMESPACE . '\\' . $namespaceType . '\\' ] = [
				'pattern' => implode(DS, [ROOT, CUSTOM_DIR, ...$folders, $name . $suffix . '.php']),
				'basePath' => implode(DS, [ROOT, CUSTOM_DIR, ...$baseFolders]),
			];
		}

		$paths[ '\\Awyiss\\' . $namespaceType . '\\' ] = [
			'pattern' => implode(DS, [APP, ...$folders, $name . $suffix . '.php']),
			'basePath' => implode(DS, [APP, ...$baseFolders]),
		];

		foreach ($paths as $namespace => $pathConfig) {
			foreach (glob($pathConfig['pattern'], GLOB_BRACE) as $filePath) {
				$className = substr($filePath, strlen($pathConfig['basePath']) + 1, -4);
				$className = str_replace(DS, '\\', $className);

				if (
					str_starts_with($className, '_')
					|| str_starts_with($className, 'Abstract')
					|| in_array($className, $blocklistedClassNames)
				) {
					continue;
				}

				$fqClassName = $namespace . $className;

				if (
					$interface
					&& !in_array($interface, class_implements($fqClassName) ?: [])
				) {
					if ($name === '*') {
						continue;
					}

					throw new RuntimeException(
						sprintf('The provided class `%s` does not implement `%s`.', $fqClassName, $interface)
					);
				}

				$files[ $className ] ??= $fqClassName;
			}
		}

		return $files;
	}
}
