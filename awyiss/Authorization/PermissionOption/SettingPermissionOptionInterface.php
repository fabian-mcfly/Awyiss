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
}
