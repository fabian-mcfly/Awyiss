<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Utility\Text;


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
	protected array $_accessible = [ // phpcs:ignore
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


	/**
	 * Make sure the scope is always CamelCase and free of special characters
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Attribute::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		return Inflector::camelize(Text::slug($scope, ['replacement' => '_']));
	}
}
