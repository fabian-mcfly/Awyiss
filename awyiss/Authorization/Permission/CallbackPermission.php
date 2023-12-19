<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\Permission\Setting\SettingCollection;
use RuntimeException;


class CallbackPermission extends SimplePermission implements SettingPermissionInterface {
	protected mixed $callback = NULL;
	protected ?SettingCollection $settingCollection = NULL;


	public function __construct (array $aa_config, ?PermissionCollection $ao_permissionCollection = NULL) {
		parent::__construct($aa_config, $ao_permissionCollection);

		if (isset($aa_config['callback'])) {
			$this->setCallback($aa_config['callback']);
		}
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getCallback (): mixed {
		return $this->callback;
	}


	public function setCallback (mixed $ax_callback): CallbackPermission {
		if (!is_callable($ax_callback)) {
			throw new RuntimeException('Config `callback` must be callable');
		}

		$this->callback = $ax_callback;

		return $this;
	}


	/**
	 * @throws \Exception
	 */
	public function getSettings (): SettingCollection {
		if ($this->settingCollection === NULL) {
			$this->settingCollection = $this->defaultSettings();
		}

		return $this->settingCollection;
	}


	public function setSettings (SettingCollection $ao_settings): CallbackPermission {
		$this->settingCollection = $ao_settings;

		return $this;
	}


	/**
	 * @throws \Exception
	 */
	protected function defaultSettings (): SettingCollection {
		$lo_settingCollection = new SettingCollection();

		$lo_settingCollection->load(\Awyiss\Authorization\Permission\Setting\SingleChoiceSetting::class, [
			'options' => [0, 1],
			'type' => \Awyiss\Authorization\Permission\Setting\SingleChoiceSetting::TYPE_SELECT,
		]);

		return $lo_settingCollection;
	}
}