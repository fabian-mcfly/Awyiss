<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;


/**
 * UsersExternal Entity
 *
 * @property int $id
 * @property string $provider_id
 * @property string $username
 * @property \Cake\I18n\FrozenTime $last_login
 * @property string $provider
 */
class UsersExternal extends \Awyiss\Model\Entity implements IdentityInterface {
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
		'provider' => TRUE,
		'provider_id' => TRUE,
		'username' => TRUE,
		'last_login' => TRUE,
		'usergroups' => TRUE,
	];


	/**
	 * Authentication\IdentityInterface method
	 */
	public function getIdentifier (): ?int {
		return $this->id;
	}


	/**
	 * Authentication\IdentityInterface method
	 */
	public function getOriginalData (): self {
		return $this;
	}
}
