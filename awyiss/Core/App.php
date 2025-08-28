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

		$ls_cacheName = $class . '::' . $type . '::' . $suffix;
		if (isset(static::$classNamesCache[ $ls_cacheName ])) {
			return static::$classNamesCache[ $ls_cacheName ];
		}

		[$ls_plugin, $ls_name] = pluginSplit($class);

		if ($ls_plugin) {
			$ls_base = str_replace('/', '\\', rtrim($ls_plugin, '\\'));
			$ls_fullname = '\\' . str_replace('/', '\\', $type . '\\' . $ls_name) . $suffix;

			if (static::_classExistsInBase($ls_fullname, $ls_base)) {
				static::$classNamesCache[ $ls_cacheName ] = $ls_base . $ls_fullname;

				/** @var class-string */
				return $ls_base . $ls_fullname;
			}

			return null;
		}


		// No Plugin? Let's check if the class exists in the CUSTOM_NAMESPACE
		$ls_fullname = '\\' . str_replace('/', '\\', $type . '\\' . $ls_name) . $suffix;
		if (defined('CUSTOM_NAMESPACE') && static::_classExistsInBase($ls_fullname, CUSTOM_NAMESPACE)) {
			static::$classNamesCache[ $ls_cacheName ] = '\\' . CUSTOM_NAMESPACE . $ls_fullname;

			/** @var class-string */
			return '\\' . CUSTOM_NAMESPACE . $ls_fullname;
		}


		// No class in the CUSTOM_NAMESPACE? It should be an Awyiss-class then.
		$ls_base = Configure::read('App.namespace');
		$ls_fullname = '\\' . str_replace('/', '\\', $type . '\\' . $ls_name) . $suffix;
		if (static::_classExistsInBase($ls_fullname, $ls_base)) {
			static::$classNamesCache[ $ls_cacheName ] = '\\' . $ls_base . $ls_fullname;

			/** @var class-string */
			return '\\' . $ls_base . $ls_fullname;
		}


		// Neither CUSTOM_NAMESPACE, nor Awyiss-class? CakePHP it is.
		if (static::_classExistsInBase($ls_fullname, 'Cake')) {
			static::$classNamesCache[ $ls_cacheName ] = 'Cake' . $ls_fullname;

			/** @var class-string */
			return 'Cake' . $ls_fullname;
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
	 * 	for it and an exception will be thrown if it does not implement the interface.
	 * @param string|null $subfolders Subfolders to check for the class that are not namespaces on their own, like console commands
	 * @param array $blocklistedClassNames Class names that should be ignored, like Abstract classes or interfaces
	 * @return array
	 */
	public static function classes(
		string $name,
		string $folder,
		string $suffix,
		?string $interface = null,
		?string $subfolders = null,
		array $blocklistedClassNames = []
	): array {
		$la_paths = [];
		$la_files = [];

		$ls_name = $name;
		if ($ls_name !== '*') {
			$ls_name = Inflector::camelize(Text::slug($name, '_'));
		}

		$ls_namespaceType = str_replace('/', '\\', $folder);
		$la_folders = explode('/', $folder);

		$la_subfolders = [];
		if ($subfolders) {
			$la_subfolders = explode('/', $subfolders);
			$la_folders = array_merge($la_folders, $la_subfolders);
		}

		if (defined('CUSTOM_NAMESPACE')) {
			$la_paths[ '\\' . CUSTOM_NAMESPACE . '\\' . $ls_namespaceType . '\\' ] = implode(DS, [ROOT, CUSTOM_DIR, ...$la_folders, $ls_name . $suffix . '.php']);
		}

		$la_paths[ '\Awyiss\\' . $ls_namespaceType . '\\' ] = implode(DS, [ROOT, APP_DIR, ...$la_folders, $ls_name . $suffix . '.php']);

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$li_offset = 0;
				if ($la_subfolders) {
					$li_offset = strlen($ls_filePath) - strrpos($ls_filePath, DS) + 1;
				}

				$ls_className = substr($ls_filePath, strrpos($ls_filePath, DS, -$li_offset) + 1, -4);
				$ls_className = str_replace(DS, '\\', $ls_className);

				if (
					str_starts_with($ls_className, '_') ||
					str_starts_with($ls_className, 'Abstract') ||
					in_array($ls_className, $blocklistedClassNames)
				) {
					continue;
				}

				$ls_fqClassName = $ls_namespace . $ls_className;

				if (
					$interface &&
					!in_array($interface, class_implements($ls_fqClassName))
				) {
					throw new RuntimeException(
						sprintf('The provided class `%s` does not implement `%s`.', $ls_fqClassName, $interface)
					);
				}

				$la_files[ $ls_className ] ??= $ls_fqClassName;
			}
		}

		return $la_files;
	}
}
