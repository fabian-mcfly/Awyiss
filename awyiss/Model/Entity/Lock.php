<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity;
use Awyiss\Routing\Router;
use Cake\Utility\Text;


/**
 * Lock Entity
 *
 * @property int $id
 * @property string $scope
 * @property int $foreignKey
 * @property string $uniqueId
 * @property int $createdBy
 * @property \Cake\I18n\DateTime $createdOn
 */
class Lock extends Entity {
	use IdentityAwareTrait;


	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'foreign_key' => 'foreignKey',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'unique_id' => 'uniqueId',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'foreignKey' => true,
		'uniqueId' => true,
	];


	/**
	 * @return bool
	 */
	public function isOwnLock(): bool {
		$lo_identity = $this->getIdentity();
		$lo_session = Router::getRequest()->getSession();

		if ($lo_identity === null) {
			return false;
		}

		return $this->createdBy === $lo_identity->getIdentifier() &&
			$this->uniqueId === $lo_session->read('lockIdentifier');
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Attribute::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		return mb_strtolower(Text::slug($scope, ['replacement' => '_']));
	}
}
