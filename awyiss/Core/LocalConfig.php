<?php declare(strict_types=1);


namespace Awyiss\Core;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Cake\Core\Configure;


/**
 * Provides an easier access to config values for a controller/model/view in the current realm
 * Prefixes every path with `Awyiss.<CurrentController>.<CurrentRealm>`
 */
class LocalConfig extends Configure {
	/**
	 * Used to read information stored in Configure. It's not
	 * possible to store `null` values in Configure.
	 * Usage:
	 * ```
	 * Configure::read('Name'); will return all values for Name
	 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
	 * ```
	 *
	 * @param string|null $var Variable to obtain. Use '.' to access array elements.
	 * @param mixed $default The return value when the configure does not exist
	 * @return mixed Value stored in configure, or null.
	 * @link https://book.cakephp.org/5/en/development/configuration.html#reading-configuration-data
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public static function read(string|array|null $ax_path = null, mixed $ax_default = null): mixed {
		if ($ax_path) {
			$la_path = is_array($ax_path) ? $ax_path : explode('.', $ax_path);
		}
		else {
			$la_path = [];
		}

		$ls_controller = Router::getRequest()->getParam('controller');

		array_unshift(
			$la_path,
			'Awyiss',
			$ls_controller,
			Awyiss::getRealm()
		);


		return parent::read(static::stringify($la_path), $ax_default);
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
