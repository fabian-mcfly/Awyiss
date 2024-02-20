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
	 * @param array|string|null $ax_path
	 * @param mixed|null $ax_default
	 * @param string|null $as_scope
	 * @return mixed
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function read(string|array|null $ax_path = null, mixed $ax_default = null, ?string $as_scope = null): mixed {
		$ls_scope = $as_scope ?? Router::getRequest()->getParam('controller');

		if ($ax_path) {
			$la_path = is_array($ax_path) ? $ax_path : explode('.', $ax_path);
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


		return parent::read(static::stringify($la_path), $ax_default);
	}


	/**
	 * Used to store a dynamic variable in Configure for a given scope.
	 * Defaults to the current controller.
	 *
	 * @param array|string $ax_config
	 * @param mixed|null $ax_value
	 * @param string|null $as_scope
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function write(array|string $ax_config, mixed $ax_value = null, ?string $as_scope = null): void {
		$ls_scope = $as_scope ?? Router::getRequest()->getParam('controller');

		$la_config = $ax_config;
		if (!is_array($ax_config)) {
			$la_config = [$ax_config => $ax_value];
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

		parent::write($la_localConfig, $ax_value);
	}


	/**
	 * CakePHP's Configure::read wants a string... Why?
	 *
	 * @param array $aa_path
	 * @return string
	 */
	public static function stringify(array $aa_path): string {
		return implode('.', $aa_path);
	}
}
