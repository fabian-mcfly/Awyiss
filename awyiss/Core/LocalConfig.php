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
		$scope ??= Router::getRequest()->getParam('controller');

		$path ??= [];
		if ($path) {
			$path = is_array($path) ? $path : explode('.', $path);
		}

		array_unshift(
			$path,
			'Awyiss',
			$scope,
			Awyiss::getRealm()
		);


		return parent::read(static::stringify($path), $default);
	}


	/**
	 * Used to store a dynamic variable in Configure for a given scope.
	 * Defaults to the current controller.
	 *
	 * @param array|string $config
	 * @param mixed|null $value
	 * @param string|null $scope
	 * @return void
	 */
	public static function write(array|string $config, mixed $value = null, ?string $scope = null): void {
		$scope ??= Router::getRequest()->getParam('controller');

		if (!is_array($config)) {
			$config = [$config => $value];
		}

		$localConfig = [];
		foreach ($config as $itemKey => $itemValue) {
			$path = explode('.', $itemKey);

			array_unshift(
				$path,
				'Awyiss',
				$scope,
				Awyiss::getRealm()
			);

			$localConfig = Hash::merge($localConfig, [static::stringify($path) => $itemValue]);
		}

		parent::write($localConfig, $value);
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
