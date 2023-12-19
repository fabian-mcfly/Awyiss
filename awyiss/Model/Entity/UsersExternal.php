<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Awyiss\Model\Entity;


/**
 * UsersExternal Entity
 *
 * @property int $id
 * @property string $provider_id
 * @property string $username
 * @property \Cake\I18n\FrozenTime $last_login
 * @property string $provider
 */
class UsersExternal extends Entity implements IdentityInterface {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'provider' => TRUE,
		'provider_id' => TRUE,
		'username' => TRUE,
		'last_login' => TRUE,
		'usergroups' => TRUE,
	];


	/**
	 * Retreives the unique identifier of this identity
	 *
	 * @see \Authentication\IdentityInterface::getIdentifier()
	 */
	public function getIdentifier (): ?int {
		return $this->id;
	}


	/**
	 * Retreive the data of this identity. Required by IdentityInterface
	 *
	 * @see \Authentication\IdentityInterface::getOriginalData()
	 */
	public function getOriginalData (): static {
		return $this;
	}
}
