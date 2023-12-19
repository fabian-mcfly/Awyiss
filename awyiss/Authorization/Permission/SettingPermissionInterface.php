<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\Permission\Setting\SettingCollection;


interface SettingPermissionInterface {
	public function getSettings (): SettingCollection;


	public function setSettings (SettingCollection $ao_settings): self;
}