<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption\Setting;


use Awyiss\Core\App;
use Cake\Core\ObjectRegistry;
use RuntimeException;


/**
 * A collection of settings for permissions. WIP
 */
class SettingCollection extends ObjectRegistry {
	/**
	 * Map of loaded objects.
	 *
	 * @var array<SettingInterface>
	 */
	protected array $_loaded = [];
	/*public function render (\Cake\View\View $view, ?string $prePath = null): string {
		$ls_settings = '';

		foreach ($this->_loaded as $lo_setting) {
			$ls_settings .= $lo_setting->render($view, $prePath);
		}

		return $ls_settings;
	}*/


	/**
	 * Creates Setting instance.
	 *
	 * @param object|string $class Setting class.
	 * @param string $alias Setting alias.
	 * @param array $config Config array.
	 * @return SettingInterface
	 */
	protected function _create(object|string $class, string $alias, array $config): SettingInterface {
		$setting = new $class($config);
		if (!($setting instanceof SettingInterface)) {
			throw new RuntimeException(sprintf('Setting class `%s` must implement `%s`.', $class, SettingInterface::class));
		}


		return $setting;
	}


	/**
	 * Resolves permission class name.
	 *
	 * @param string $class Class name to be resolved.
	 * @return string|null
	 * @psalm-return class-string|null
	 */
	protected function _resolveClassName(string $class): ?string {
		return App::className($class);
	}


	/**
	 * @param string $class Missing class.
	 * @param string|null $plugin Class plugin.
	 * @return void
	 */
	protected function _throwMissingClassError(string $class, ?string $plugin): void {
		throw new RuntimeException(sprintf('Setting class `%s` was not found.', $class));
	}
}
