<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;


/**
 * UsersExternal Entity
 *
 * @property int $id
 * @property string $providerId
 * @property string $username
 * @property FrozenTime $lastLogin
 * @property string $provider
 */
class UsersExternal extends Entity implements IdentityInterface {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'provider' => TRUE,
		'providerId' => TRUE,
		'username' => TRUE,
		'usergroups' => TRUE,
	];

	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'provider_id' => 'providerId',
		'last_login' => 'lastLogin',
	];


	/**
	 * Retreives the unique identifier of this identity
	 *
	 * @see IdentityInterface::getIdentifier
	 */
	public function getIdentifier (): ?int {
		return $this->id;
	}


	/**
	 * Retreive the data of this identity. Required by IdentityInterface
	 *
	 * @see IdentityInterface::getOriginalData
	 */
	public function getOriginalData (): static {
		return $this;
	}
}
