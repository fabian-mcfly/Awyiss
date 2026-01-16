<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Authorization\IdentityGroupPermissionInterface;
use Awyiss\Model\Entity;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Security;


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
class Customer extends Entity implements IdentityGroupPermissionInterface {
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
	 * @inheritDoc
	 */
	public function getGroups(): array {
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
	 * @inheritDoc
	 */
	public function unsetGroups(): static {
		$this->unset('customerGroups');

		return $this;
	}


	/**
	 * Returns the full name of the customer, if set, otherwise the email address,
	 * prefixed with 'inactive' if the customer is not active
	 *
	 * @return string
	 */
	protected function _getLabel(): string {
		$inactive = '';

		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$inactive = __d('users', 'inactive') . ' ';
		}

		if (!empty($this->firstname) && !empty($this->lastname)) {
			return $inactive . $this->firstname . ' ' . $this->lastname;
		}

		return $inactive . $this->username;
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

		$passwordHasher = new DefaultPasswordHasher();
		$passwordHasher->setConfig('hashOptions', [
			'cost' => 14,
		]);

		if (Configure::read('Security.prehashPassword', false) && Security::getSalt()) {
			$password = hash_hmac('sha256', $password, Security::getSalt());
		}

		// Automatically hash passwords when they are changed.
		return $passwordHasher->hash($password);
	}
}
