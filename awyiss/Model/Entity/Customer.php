<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;


/**
 * Customer Entity
 *
 * @property int $id
 * @property string|null $email
 * @property string|null $password
 * @property \Cake\I18n\DateTime|null $lastLogin
 * @property int $failedAttempts
 * @property string|null $firstname
 * @property string|null $lastname
 * @property bool $verified
 * @property \Cake\I18n\DateTime|null $verifiedOn
 * @property string|null $verificationCode
 * @property string|null $passwordResetCode
 * @property \Cake\I18n\DateTime|null $passwordResetOn
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\CustomerGroup[] $customerGroups
 */
class Customer extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'last_login' => 'lastLogin',
		'failed_attempts' => 'failedAttempts',
		'verified' => 'verified',
		'verified_on' => 'verifiedOn',
		'verification_code' => 'verificationCode',
		'password_reset_code' => 'passwordResetCode',
		'customer_groups' => 'customerGroups',
		'password_reset_on' => 'passwordResetOn',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'email' => true,
		'password' => true,
		'firstname' => true,
		'lastname' => true,
		'lastLogin' => true,
		'failedAttempts' => true,
		'verified' => true,
		'verifiedOn' => true,
		'verificationCode' => true,
		'passwordResetCode' => true,
		'passwordResetOn' => true,
		'active' => true,
		'customerGroups' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $_hidden = [ // phpcs:ignore
		'password',
	];


	/**
	 * Returns an array of CustomerGroup-entities
	 *
	 * @return array<\Awyiss\Model\Entity\CustomerGroup>
	 */
	public function getCustomerGroups(): array {
		if (isset($this->customerGroups)) {
			return $this->customerGroups;
		}

		/** @var \Awyiss\Model\Table\CustomerGroupsTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());

		$table->loadInto($this, [
			'CustomerGroups' => [
				'finder' => 'active', //Only find active groups.
			],
		]);

		return $this->customerGroups;
	}


	/**
	 * If the provided password is not an empty string, hash it.
	 * Otherwise, set it to null
	 *
	 * @param string|null $password
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Customer::$password
	 */
	protected function _setPassword(?string $password): ?string {
		if (empty($password)) {
			return null;
		}

		// Automatically hash passwords when they are changed.
		return new DefaultPasswordHasher()->hash($password);
	}
}
