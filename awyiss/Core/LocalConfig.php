<?php declare(strict_types=1);


namespace Awyiss\Core;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Utility\Hash;


/**
 * Provides an easier access to config values for a controller/model/view in the current realm
 * Prefixes every path with `Awyiss.<CurrentController>.<CurrentRealm>`
 */
class LocalConfig extends Configure {
	/**
	 * Used to read information stored in Configure for a given scope.
	 * Defaults to the current controller.
	 *
	 * @param array|string|null $path
	 * @param mixed|null $default
	 * @param string|null $scope
	 * @return mixed
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function read(string|array|null $path = null, mixed $default = null, ?string $scope = null): mixed {
		$ls_scope = $scope ?? Router::getRequest()->getParam('controller');

		if ($path) {
			$la_path = is_array($path) ? $path : explode('.', $path);
		}
		else {
			$la_path = [];
		}

		array_unshift(
			$la_path,
			'Awyiss',
			$ls_scope,
			Awyiss::getRealm()
		);


		return parent::read(static::stringify($la_path), $default);
	}


	/**
	 * Used to store a dynamic variable in Configure for a given scope.
	 * Defaults to the current controller.
	 *
	 * @param array|string $config
	 * @param mixed|null $value
	 * @param string|null $scope
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function write(array|string $config, mixed $value = null, ?string $scope = null): void {
		$ls_scope = $scope ?? Router::getRequest()->getParam('controller');

		$la_config = $config;
		if (!is_array($config)) {
			$la_config = [$config => $value];
		}

		$la_localConfig = [];
		foreach ($la_config as $ls_key => $lx_value) {
			$la_path = explode('.', $ls_key);

			array_unshift(
				$la_path,
				'Awyiss',
				$ls_scope,
				Awyiss::getRealm()
			);

			$la_localConfig = Hash::merge($la_localConfig, [static::stringify($la_path) => $lx_value]);
		}

		parent::write($la_localConfig, $value);
	}


	/**
	 * CakePHP's Configure::read wants a string... Why?
	 *
	 * @param array $path
	 * @return string
	 */
	public static function stringify(array $path): string {
		return implode('.', $path);
	}
}
