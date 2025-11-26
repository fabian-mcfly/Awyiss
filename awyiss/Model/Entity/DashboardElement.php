<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * DashboardElement Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $title
 * @property array|null $access
 * @property array|null $settings
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class DashboardElement extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'title' => true,
		'access' => true,
		'settings' => true,
		'systemOrder' => true,
		'active' => true,
	];


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function _setAccess(mixed $value): mixed {
		if (empty($value)) {
			return null;
		}

		return is_string($value) ? json_decode($value) : $value;
	}
}
