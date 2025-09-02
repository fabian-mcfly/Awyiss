<?php declare(strict_types=1);


namespace Awyiss\Authentication\Authenticator;


use Authentication\Authenticator\AuthenticatorCollection as BaseAuthenticatorCollection;
use Authentication\Authenticator\AuthenticatorInterface;
use Awyiss\Authentication\Identifier\IdentifierCollection;


/**
 * @inheritDoc
 */
class AuthenticatorCollection extends BaseAuthenticatorCollection {
	/**
	 * Reimplemented 1:1 to use `\Awyiss\Authentication\Identifier\IdentifierCollection` instead of
	 * `\Authentication\Identifier\IdentifierCollection`
	 *
	 * @inheritDoc
	 */
	protected function _create(object|string $class, string $alias, array $config): AuthenticatorInterface {
		if (is_string($class)) {
			if (!empty($config['identifier'])) {
				$this->_identifiers = new IdentifierCollection((array)$config['identifier']);
			}

			return new $class($this->_identifiers, $config);
		}

		return $class;
	}
}
