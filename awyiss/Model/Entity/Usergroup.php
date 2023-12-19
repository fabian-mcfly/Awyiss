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
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 * @property \Awyiss\Model\Entity\UsergroupPermission[] $usergroup_permissions
 * @property \Awyiss\Model\Entity\User[] $users
 */
class Usergroup extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'active' => TRUE,
		'usergroup_permissions' => TRUE,
		'users' => TRUE,
	];
}
