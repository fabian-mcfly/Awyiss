<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Awyiss\Authorization\PermissionOption\Setting\SettingCollection;


/**
 * Interface with method signatures for a Permission that offers additional settings.
 */
interface SettingPermissionOptionInterface {
	/**
	 * Returns the `SettingCollection` that was set for this Permission
	 *
	 * @return SettingCollection
	 * @noinspection PhpUnused
	 */
	public function getSettings(): SettingCollection;


	/**
	 * Sets the `SettingCollection` for this Permission
	 *
	 * @param SettingCollection $settings
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setSettings(SettingCollection $settings): static;
	/*
	protected SettingCollection $settingCollection;


	public function getSettings (): SettingCollection {
		if (!isset($this->settingCollection)) {
			$this->settingCollection = $this->defaultSettings();
		}

		return $this->settingCollection;
	}
	public function setSettings (SettingCollection $settings): static {
		$this->settingCollection = $settings;

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
