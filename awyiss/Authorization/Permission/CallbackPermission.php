<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\Permission\Setting\SettingCollection;
use RuntimeException;


class CallbackPermission extends SimplePermission implements SettingPermissionInterface {
	private mixed $lx_callback = NULL;
	private ?SettingCollection $lo_settingCollection = NULL;


	public function __construct (array $aa_config, ?PermissionCollection $ao_permissionCollection = NULL) {
		parent::__construct($aa_config, $ao_permissionCollection);

		if (isset($aa_config['callback'])) {
			$this->setCallback($aa_config['callback']);
		}
	}


	public function getCallback (): mixed {
		return $this->lx_callback;
	}


	public function setCallback (mixed $ax_callback): CallbackPermission {
		if (!is_callable($ax_callback)) {
			throw new RuntimeException('Config `callback` must be callable');
		}

		$this->lx_callback = $ax_callback;

		return $this;
	}


	public function getSettings (): SettingCollection {
		if ($this->lo_settingCollection === NULL) {
			$this->lo_settingCollection = $this->_defaultSettings();
		}

		return $this->lo_settingCollection;
	}


	public function setSettings (SettingCollection $ao_settings): CallbackPermission {
		$this->lo_settingCollection = $ao_settings;

		return $this;
	}


	private function _defaultSettings (): SettingCollection {
		$lo_settingCollection = new SettingCollection();

		$lo_settingCollection->load(\Awyiss\Authorization\Permission\Setting\SingleChoiceSetting::class, [
			'options' => [0, 1],
			'type' => \Awyiss\Authorization\Permission\Setting\SingleChoiceSetting::TYPE_SELECT,
		]);

		return $lo_settingCollection;
	}
}