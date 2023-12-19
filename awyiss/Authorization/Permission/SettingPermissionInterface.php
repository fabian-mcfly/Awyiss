<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\Permission\Setting\SettingCollection;


/**
 * Interface with method signatures for a Permission that offers additional settings.
 */
interface SettingPermissionInterface {
	/**
	 * Returns the `SettingCollection` that was set for this Permission
	 *
	 * @return \Awyiss\Authorization\Permission\Setting\SettingCollection
	 * @noinspection PhpUnused
	 */
	public function getSettings (): SettingCollection;


	/**
	 * Sets the `SettingCollection` for this Permission
	 *
	 * @param \Awyiss\Authorization\Permission\Setting\SettingCollection $ao_settings
	 *
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setSettings (SettingCollection $ao_settings): static;


	/*
	protected SettingCollection $settingCollection;


	public function getSettings (): SettingCollection {
		if (!isset($this->settingCollection)) {
			$this->settingCollection = $this->defaultSettings();
		}

		return $this->settingCollection;
	}
	public function setSettings (SettingCollection $ao_settings): static {
		$this->settingCollection = $ao_settings;

		return $this;
	}
	protected function defaultSettings (): SettingCollection {
		$lo_settingCollection = new SettingCollection();

		$lo_settingCollection->load(\Awyiss\Authorization\Permission\Setting\SingleChoiceSetting::class, [
			'options' => [0, 1],
			'type' => \Awyiss\Authorization\Permission\Setting\SingleChoiceSetting::TYPE_SELECT,
		]);

		return $lo_settingCollection;
	}*/
}