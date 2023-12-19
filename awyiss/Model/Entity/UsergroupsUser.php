<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * UsergroupsUser Entity
 *
 * @property int $id
 * @property int $usergroup_id
 * @property int $user_id
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 * @property \Awyiss\Model\Entity\User $user
 */
class UsergroupsUser extends \Awyiss\Model\Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'usergroup_id' => TRUE,
		'user_id' => TRUE,
		'usergroup' => TRUE,
		'user' => TRUE,
	];
}
