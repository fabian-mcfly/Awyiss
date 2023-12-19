<?php


namespace awyiss\Core;


use Cake\Core\Configure;


class App extends \Cake\Core\App {
	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function className (string $as_class, string $as_type = '', string $as_suffix = ''): ?string {
		if (str_contains($as_class, '\\')) {
			return class_exists($as_class) ? $as_class : null;
		}

		[$ls_plugin, $ls_name] = pluginSplit($as_class);

		if ($ls_plugin) {
			$ls_base = str_replace('/', '\\', rtrim($ls_plugin, '\\'));
			$ls_fullname = '\\' . str_replace('/', '\\', $as_type . '\\' . $ls_name) . $as_suffix;

			if (static::_classExistsInBase($ls_fullname, $ls_base)) {
				/** @var class-string */
				return $ls_base . $ls_fullname;
			}

			return null;
		}


		//No Plugin? Let's check if the class exists in the CUSTOM_NAMESPACE
		$ls_base = str_replace('/', '\\', rtrim(CUSTOM_NAMESPACE, '\\'));
		$ls_fullname = '\\' . str_replace('/', '\\', $as_type . '\\' . $ls_name) . $as_suffix;

		if (static::_classExistsInBase($ls_fullname, $ls_base)) {
			/** @var class-string */
			return $ls_base . $ls_fullname;
		}


		//No class in the CUSTOM_NAMESPACE? It should be an Awyiss-class then.
		$ls_base = str_replace('/', '\\', rtrim(Configure::read('App.namespace'), '\\'));
		$ls_fullname = '\\' . str_replace('/', '\\', $as_type . '\\' . $ls_name) . $as_suffix;

		if (static::_classExistsInBase($ls_fullname, $ls_base)) {
			/** @var class-string */
			return $ls_base . $ls_fullname;
		}


		//Neither CUSTOM_NAMESPACE, nor Awyiss-class? CakePHP it is.
		if (static::_classExistsInBase($ls_fullname, 'Cake')) {
			/** @var class-string */
			return 'Cake' . $ls_fullname;
		}

		return null;
	}
}