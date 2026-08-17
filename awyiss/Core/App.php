<?php declare(strict_types=1);


namespace Awyiss\Core;


use Awyiss\Utility\Inflector;
use Cake\Core\App as BaseApp;
use Cake\Core\Configure;
use Cake\Utility\Text;
use RuntimeException;


/**
 * @inheritDoc
 */
class App extends BaseApp {
	/**
	 * @var array
	 */
	protected static array $classNamesCache = [];


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
	 * @return string|null
	 */
	public static function className(string $class, string $type = '', string $suffix = ''): ?string {
		if (str_contains($class, '\\')) {
			return class_exists($class) ? $class : null;
		}

		$cacheName = $class . '::' . $type . '::' . $suffix;
		if (isset(static::$classNamesCache[ $cacheName ])) {
			return static::$classNamesCache[ $cacheName ];
		}

		[$plugin, $name] = pluginSplit($class);

		if ($plugin) {
			$base = str_replace('/', '\\', rtrim($plugin, '\\'));
			$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

			if (static::_classExistsInBase($fullname, $base)) {
				static::$classNamesCache[ $cacheName ] = $base . $fullname;

				/** @var class-string */
				return $base . $fullname;
			}

			return null;
		}


		// No Plugin? Let's check if the class exists in the CUSTOM_NAMESPACE
		$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;
		if (defined('CUSTOM_NAMESPACE') && static::_classExistsInBase($fullname, CUSTOM_NAMESPACE)) {
			static::$classNamesCache[ $cacheName ] = '\\' . CUSTOM_NAMESPACE . $fullname;

			/** @var class-string */
			return '\\' . CUSTOM_NAMESPACE . $fullname;
		}


		// No class in the CUSTOM_NAMESPACE? It should be an Awyiss-class then.
		$base = Configure::read('App.namespace');
		$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;
		if (static::_classExistsInBase($fullname, $base)) {
			static::$classNamesCache[ $cacheName ] = '\\' . $base . $fullname;

			/** @var class-string */
			return '\\' . $base . $fullname;
		}


		// Neither CUSTOM_NAMESPACE, nor Awyiss-class? CakePHP it is.
		if (static::_classExistsInBase($fullname, 'Cake')) {
			static::$classNamesCache[ $cacheName ] = 'Cake' . $fullname;

			/** @var class-string */
			return 'Cake' . $fullname;
		}

		return null;
	}


	/**
	 * Get a list of all classes of a certain folder in the application
	 * and the custom namespace.
	 * This method checks if the class is defined
	 * - in the custom namespace or
	 * - in the application/plugin namespace
	 *
	 * @param string $name The name of the class or * to get all classes
	 * @param string $folder The folder of the class, e.g. 'Attribute/AttributeOptionsCollection', 'Event/Backend', 'Form'
	 * @param string $suffix The suffix of the class, e.g. 'AttributeOptionsCollection', 'Listener', 'FormOptions'
	 * @param string|null $interface The interface the class should implement. If set, the class will be checked
	 *    for it and an exception will be thrown if it does not implement the interface.
	 * @param string|null $subfolders Subfolders to check for the class that are not namespaces on their own, like console commands
	 * @param array $blocklistedClassNames Class names that should be ignored, like Abstract classes or interfaces
	 * @return array
	 */
	public static function classes(
		string $name,
		string $folder,
		string $suffix = '',
		?string $interface = null,
		?string $subfolders = null,
		array $blocklistedClassNames = []
	): array {
		$paths = [];
		$files = [];

		if ($name !== '*') {
			$name = Inflector::camelize(Text::slug($name, '_'));
		}

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
			'pattern' => implode(DS, [ROOT, APP_DIR, ...$folders, $name . $suffix . '.php']),
			'basePath' => implode(DS, [ROOT, APP_DIR, ...$baseFolders]),
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
