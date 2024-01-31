<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * UsergroupsUser Entity
 *
 * @property int $id
 * @property int|null $usergroupId
 * @property int|null $userId
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 * @property \Awyiss\Model\Entity\User $user
 */
class UsergroupsUser extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'usergroupId' => true,
		'userId' => true,
		'usergroup' => true,
		'user' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'usergroup_id' => 'usergroupId',
		'user_id' => 'userId',
	];
}
