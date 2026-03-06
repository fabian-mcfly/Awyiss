<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
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
	protected array $_accessible = [ // phpcs:ignore
		'scope' => true,
		'foreignKey' => true,
		'uniqueId' => true,
	];


	/**
	 * @return bool
	 */
	public function isOwnLock(): bool {
		$identity = $this->getIdentity();
		$session = Router::getRequest()->getSession();

		if ($identity === null) {
			return false;
		}

		$sessionBased = Configure::read('Awyiss.System.Backend.lock.sessionBased', true);

		if ($sessionBased) {
			return $this->createdBy === $identity->getIdentifier() &&
			   $this->uniqueId === $session->read('Backend.lockIdentifier');
		}

		return $this->createdBy === $identity->getIdentifier();
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

		return Inflector::camelize(Text::slug($scope, ['replacement' => '_']));
	}
}
