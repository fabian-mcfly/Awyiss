<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Awyiss\Model\Entity\CustomerGroupAccessSetting;


/**
 * Proxy methods for CustomerGroupAccessSettingBehavior
 */
trait CustomerGroupAccessSettingBehaviorProxyTrait {
	/**
	 * Get the access setting for a specific entity.
	 *
	 * @param string|int|null $entityId
	 * @return \Awyiss\Model\Entity\CustomerGroupAccessSetting|null
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::getCustomerGroupAccessSettings()
	 */
	public function getCustomerGroupAccessSettings(int|string|null $entityId = null): ?CustomerGroupAccessSetting {
		return $this->getBehavior('CustomerGroupAccessSetting')->getCustomerGroupAccessSettings($entityId);
	}
}
