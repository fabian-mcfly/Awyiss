<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Awyiss\Model\Entity;


/**
 * UsersExternal Entity
 *
 * @property int $id
 * @property string $providerId
 * @property string $username
 * @property \Cake\I18n\DateTime $lastLogin
 * @property string $provider
 */
class UsersExternal extends Entity implements IdentityInterface {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'provider' => true,
		'providerId' => true,
		'username' => true,
		'usergroups' => true,
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
	public function getIdentifier(): ?int {
		return $this->id;
	}


	/**
	 * Retreive the data of this identity. Required by IdentityInterface
	 *
	 * @see IdentityInterface::getOriginalData
	 */
	public function getOriginalData(): static {
		return $this;
	}
}
