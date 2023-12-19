<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission\Setting;


use Awyiss\Core\App;
use Cake\Core\ObjectRegistry;
use RuntimeException;


/**
 *
 */
class SettingCollection extends ObjectRegistry {
	/**
	 * Map of loaded objects.
	 *
	 * @var SettingInterface[]
	 */
	protected $_loaded = [];


	/*public function render (\Cake\View\View $ao_view, ?string $as_prePath = NULL): string {
		$ls_settings = '';

		foreach ($this->_loaded as $lo_setting) {
			$ls_settings .= $lo_setting->render($ao_view, $as_prePath);
		}

		return $ls_settings;
	}*/


	/**
	 * Creates Setting instance.
	 *
	 * @param string $as_class Setting class.
	 * @param string $as_alias Setting alias.
	 * @param array $aa_config Config array.
	 *
	 * @return \Awyiss\Authorization\Permission\Setting\SettingInterface
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _create ($as_class, string $as_alias, array $aa_config): SettingInterface {
		$lo_setting = new $as_class($aa_config);
		if ( ! ($lo_setting instanceof SettingInterface)) {
			throw new RuntimeException(sprintf('Setting class `%s` must implement `%s`.', $as_class, SettingInterface::class));
		}

		return $lo_setting;
	}


	/**
	 * Resolves permission class name.
	 *
	 * @param string $as_class Class name to be resolved.
	 *
	 * @return string|NULL
	 * @psalm-return class-string|NULL
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _resolveClassName (string $as_class): ?string {
		$ls_className = App::className($as_class);

		return is_string($ls_className) ? $ls_className : NULL;
	}


	/**
	 * @param string $as_class Missing class.
	 * @param NULL|string $as_plugin Class plugin.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _throwMissingClassError (string $as_class, ?string $as_plugin): void {
		throw new RuntimeException(sprintf('Setting class `%s` was not found.', $as_class));
	}
}
