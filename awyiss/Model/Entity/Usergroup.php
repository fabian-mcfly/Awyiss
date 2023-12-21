<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Usergroup Entity
 *
 * @property int $id
 * @property string $title
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property UsergroupPermission[] $usergroupPermissions
 * @property User[] $users
 */
class Usergroup extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'active' => true,
		'usergroupPermissions' => false,
		'users' => false,
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
