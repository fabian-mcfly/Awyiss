<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;


/**
 * Usergroup Entity
 *
 * @property int $id
 * @property string $title
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
 * @property UsergroupPermission[] $usergroupPermissions
 * @property User[] $users
 */
class Usergroup extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => TRUE,
		'active' => TRUE,
		'usergroupPermissions' => FALSE,
		'users' => FALSE,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'usergroup_permissions' => 'usergroupPermissions',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];
}
