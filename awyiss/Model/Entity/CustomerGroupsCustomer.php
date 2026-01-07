<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * CustomerGroupsCustomer Entity
 *
 * @property int $id
 * @property int $customerGroupId
 * @property int $customerId
 */
class CustomerGroupsCustomer extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'customer_group_id' => 'customerGroupId',
		'customer_id' => 'customerId',
		'customer_group' => 'customerGroup',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'customerGroupId' => true,
		'customerId' => true,
		'customerGroup' => true,
		'customer' => true,
	];
}
