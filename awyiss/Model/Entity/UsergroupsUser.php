<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * UsergroupsUser Entity
 *
 * @property int $id
 * @property int $usergroup_id
 * @property int $user_id
 *
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 * @property \Awyiss\Model\Entity\User $user
 */
class UsergroupsUser extends \Awyiss\Model\Entity {
	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'usergroup_id' => TRUE,
		'user_id' => TRUE,
		'usergroup' => TRUE,
		'user' => TRUE,
	];
}
