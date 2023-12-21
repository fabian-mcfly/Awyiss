<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int $usergroupId
 * @property string $scope
 * @property string $identifier
 * @property PermissionAccess $access
 * @property array|null $settings
 * @property Usergroup $usergroup
 */
class UsergroupPermission extends Entity implements PermissionInterface {
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
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'usergroup_id' => 'usergroupId',
	];


	/**
	 * In the database, the scope exists as an underscored string
	 *
	 * @param string $as_scope
	 * @return string
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$scope
	 */
	public function _setScope(string $as_scope): string {
		return Inflector::underscore($as_scope);
	}


	/**
	 * In the database, the identifier exists as an underscored string
	 *
	 * @param string $as_identifier
	 * @return string
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$identifier
	 */
	public function _setIdentifier(string $as_identifier): string {
		return Inflector::underscore($as_identifier);
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
