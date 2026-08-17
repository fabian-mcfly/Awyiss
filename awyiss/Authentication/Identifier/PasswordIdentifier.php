<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier;


use ArrayAccess;
use Authentication\Identifier\PasswordIdentifier as BasePasswordIdentifier;
use Cake\Core\Configure;
use Cake\Utility\Security;


/**
 * Password Identifier
 * Re-implemented the `_checkPassword` method to use prehash the password
 * if the Security configuration 'prehashPassword' is enabled.
 */
class PasswordIdentifier extends BasePasswordIdentifier {
	/**
	 * Re-implemented to support pre-hashing of passwords.
	 *
	 * @inheritDoc
	 */
	protected function _checkPassword(ArrayAccess|array|null $identity, ?string $password): bool {
		$passwordField = $this->getConfig('fields.' . self::CREDENTIAL_PASSWORD);

		if ($identity === null) {
			$identity = [
				$passwordField => '',
			];
		}

		$hasher = $this->getPasswordHasher();
		$hashedPassword = $identity[ $passwordField ];

		$password = (string)$password;
		if (Configure::read('Security.prehashPassword', false) && Security::getSalt()) {
			$password = hash_hmac('sha256', $password, Security::getSalt());
		}

		if (
			$hashedPassword === null
			|| !$hasher->check($password, $hashedPassword)
		) {
			return false;
		}

		$this->_needsPasswordRehash = $hasher->needsRehash($hashedPassword);

		return true;
	}
}
