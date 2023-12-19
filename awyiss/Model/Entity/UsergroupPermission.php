<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


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
class UsergroupPermission extends Entity {
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
}
