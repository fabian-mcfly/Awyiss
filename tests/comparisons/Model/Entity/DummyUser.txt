<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * DummyUser Entity
 *
 * @property int $id
 * @property string $username
 * @property string|null $password
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $email
 * @property \Cake\I18n\DateTime|null $lastLogin
 * @property int $failedAttempts
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class DummyUser extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'last_login' => 'lastLogin',
		'failed_attempts' => 'failedAttempts',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'username' => true,
		'password' => true,
		'firstname' => true,
		'lastname' => true,
		'email' => true,
		'lastLogin' => true,
		'failedAttempts' => true,
		'active' => true,
		'mediaAssignments' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $_hidden = [
		'password',
	];
}
