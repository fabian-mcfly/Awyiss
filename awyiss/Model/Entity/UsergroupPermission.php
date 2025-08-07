<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int|null $usergroupId
 * @property string|null $scope
 * @property string|null $identifier
 * @property PermissionAccess|null $access
 * @property array|null $settings
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 */
class UsergroupPermission extends Entity implements PermissionInterface {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'usergroup_id' => 'usergroupId',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'usergroupId' => true,
		'scope' => true,
		'identifier' => true,
		'access' => true,
		'settings' => true,
	];


	/**
	 * In the database, the scope exists as an underscored string
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		return Inflector::underscore($scope);
	}


	/**
	 * In the database, the identifier exists as an underscored string
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		return Inflector::underscore($identifier);
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getScope(): string {
		return $this->scope;
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getAccess(): PermissionAccess {
		return $this->access;
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getSettings(): ?array {
		return $this->settings;
	}
}
