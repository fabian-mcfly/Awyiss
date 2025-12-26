<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * CustomerGroupAccessSetting Entity
 *
 * @property int $id
 * @property string $scope
 * @property int|null $foreignKey
 * @property string $accessType
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class CustomerGroupAccessSetting extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'foreign_key' => 'foreignKey',
		'access_type' => 'accessType',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'scope' => true,
		'foreignKey' => true,
		'accessType' => true,
	];
}
