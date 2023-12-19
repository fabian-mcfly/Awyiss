<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * UsergroupsUser Entity
 *
 * @property int       $id
 * @property int       $usergroupId
 * @property int       $userId
 * @property Usergroup $usergroup
 * @property User      $user
 */
class UsergroupsUser extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'usergroupId' => TRUE,
		'userId' => TRUE,
		'usergroup' => TRUE,
		'user' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'usergroup_id' => 'usergroupId',
		'user_id' => 'userId',
	];
}
