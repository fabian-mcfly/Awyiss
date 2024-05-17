<?php declare(strict_types=1);


namespace Awyiss\Core;


use Cake\Core\App as BaseApp;
use Cake\Core\Configure;


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
	 * @param string $class
	 * @param string $type
	 * @param string $suffix
	 * @return string|null
	 */
	public static function className(string $class, string $type = '', string $suffix = ''): ?string {
		if (str_contains($class, '\\')) {
			return class_exists($class) ? $class : null;
		}

		[$ls_plugin, $ls_name] = pluginSplit($class);

		if ($ls_plugin) {
			$ls_base = str_replace('/', '\\', rtrim($ls_plugin, '\\'));
			$ls_fullname = '\\' . str_replace('/', '\\', $type . '\\' . $ls_name) . $suffix;

			if (static::_classExistsInBase($ls_fullname, $ls_base)) {
				/** @var class-string */
				return $ls_base . $ls_fullname;
			}


			return null;
		}


		//No Plugin? Let's check if the class exists in the CUSTOM_NAMESPACE
		$ls_fullname = '\\' . str_replace('/', '\\', $type . '\\' . $ls_name) . $suffix;
		if (defined('CUSTOM_NAMESPACE') && static::_classExistsInBase($ls_fullname, CUSTOM_NAMESPACE)) {
			/** @var class-string */
			return '\\' . CUSTOM_NAMESPACE . $ls_fullname;
		}


		//No class in the CUSTOM_NAMESPACE? It should be an Awyiss-class then.
		$ls_base = Configure::read('App.namespace');
		$ls_fullname = '\\' . str_replace('/', '\\', $type . '\\' . $ls_name) . $suffix;
		if (static::_classExistsInBase($ls_fullname, $ls_base)) {
			/** @var class-string */
			return '\\' . $ls_base . $ls_fullname;
		}


		//Neither CUSTOM_NAMESPACE, nor Awyiss-class? CakePHP it is.
		if (static::_classExistsInBase($ls_fullname, 'Cake')) {
			/** @var class-string */
			return 'Cake' . $ls_fullname;
		}


		return null;
	}
}
