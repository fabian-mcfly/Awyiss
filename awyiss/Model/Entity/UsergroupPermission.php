<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Model\Entity;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int $usergroup_id
 * @property string $scope
 * @property string $identifier
 * @property int $access
 * @property array|NULL $settings
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 */
class UsergroupPermission extends Entity implements PermissionInterface {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'usergroup_id' => TRUE,
		'scope' => TRUE,
		'identifier' => TRUE,
		'access' => TRUE,
		'settings' => TRUE,
	];

	/**
	 * @inheritDoc
	 */
	public function getScope (): string {
		return $this->scope;
	}

	/**
	 * @inheritDoc
	 */
	public function getIdentifier (): string {
		return $this->identifier;
	}

	/**
	 * @inheritDoc
	 */
	public function getAccess (): int {
		return $this->access;
	}

	/**
	 * @inheritDoc
	 */
	public function getSettings (): ?array {
		return $this->settings;
	}
}
