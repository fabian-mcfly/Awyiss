<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * CustomerGroupAssignment Entity
 *
 * @property int $id
 * @property int $customerGroupId
 * @property string $scope
 * @property int|null $foreignKey
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\CustomerGroup $customerGroup
 */
class CustomerGroupAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'customer_group_id' => 'customerGroupId',
		'foreign_key' => 'foreignKey',
		'customer_group' => 'customerGroup',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'customerGroupId' => true,
		'scope' => true,
		'foreignKey' => true,
	];
}
